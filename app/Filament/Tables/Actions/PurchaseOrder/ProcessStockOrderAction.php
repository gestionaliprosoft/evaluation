<?php

namespace App\Filament\Tables\Actions\PurchaseOrder;

use App\Models\Purchase\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class ProcessStockOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'processStockOrderAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('purchase-order.Load into Warehouse'))
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('success')

            // SECURITY: Evaluate action visibility strictly through the Laravel Policy && set to isFinalStep
            // In a table context, the specific row $record is automatically injected into the closure
            ->visible(fn (PurchaseOrder $record) => auth()->user()->can('processStockIn', $record) && $record->isFinalStep())

            // CONFIRMATION: Trigger a confirmation modal to avoid accidental clicks
            ->requiresConfirmation()
            ->modalHeading(__('purchase-order.Confirm Load into Warehouse'))
            ->modalDescription(__('purchase-order.Generate Stock Movements Warning'))
            ->modalSubmitActionLabel(__('purchase-order.Yes, load goods into warehouse'))

            // EXECUTION: Process business logic safely inside a database transaction
            // Both the row $record and your custom PurchaseOrderService are automatically resolved via dependency injection
            ->action(function (PurchaseOrder $record, PurchaseOrderService $stockService): void {
                $order = $record;

                try {
                    // Wrap execution in a database transaction to ensure stock data integrity
                    DB::transaction(function () use ($order, $stockService) {

                        // Execute your ERP stock service workflow
                        $stockService->createStockEntryFromPurchaseOrder($order);

                        // Update the order flag/status so it fails subsequent policy checks
                        $order->setArchived(true);
                    });

                    // Send a temporary success toast notification
                    Notification::make()
                        ->title(__('purchase-order.Warehouse load completed'))
                        ->body(__('purchase-order.Product stock updated successfully'))
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    // Keep the error notification visible if the database rollback triggers
                    Notification::make()
                        ->title(__('purchase-order.Error during load'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}
