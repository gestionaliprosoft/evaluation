<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource;
use App\Filament\Tables\HeaderActions\CloseAction;
use App\Libs\GenerateService;
use App\Services\PurchaseOrderService;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewPurchaseOrder extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getJollyField()
    {
        return ' Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            static::editAction()
                ->label(__('Edit')),
            GenerateService::generateCommercialPdf('PurchaseOrder', 'purchase', true),
            Action::make('processPhysicalStockIn')
                ->label(__('purchase-order.Load into Warehouse'))
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('success')

                // SECURITY: Evaluate action visibility strictly through the Laravel Policy && set to isFinalStep
                ->visible(fn () => auth()->user()->can('processStockIn', $this->getRecord()) && $this->getRecord()->isFinalStep())

                // CONFIRMATION: Trigger a confirmation modal to avoid accidental clicks
                ->requiresConfirmation()
                ->modalHeading(__('purchase-order.Confirm Load into Warehouse'))
                ->modalDescription(__('purchase-order.Generate Stock Movements Warning'))
                ->modalSubmitActionLabel(__('purchase-order.Yes, load goods into warehouse'))

                // EXECUTION: Process business logic safely inside a database transaction
                ->action(function (PurchaseOrderService $stockService): void {
                    $order = $this->getRecord();

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

                        // Soft-refresh the form fields to hide the action button immediately
                        $this->refreshFormData([
                            'order_status_id',
                        ]);

                    } catch (\Exception $e) {
                        // Keep the error notification visible if the database rollback triggers
                        Notification::make()
                            ->title(__('purchase-order.Error during load'))
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            CloseAction::make('close'),
        ];
    }
}
