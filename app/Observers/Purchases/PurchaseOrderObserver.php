<?php

namespace App\Observers\Purchases;

use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseStockEntry;
use App\Services\AttachmentService;
use Filament\Notifications\Notification;

class PurchaseOrderObserver
{
    public function creating(PurchaseOrder $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the PurchaseOrder "created" event.
     */
    public function created(PurchaseOrder $record): void
    {
        $record->processAutomation('created');
    }

    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $record): void
    {
        $record->processAutomation('updated');
    }

    /**
     * Soft delete event.
     * This method is always triggered, even during force delete.
     */
    public function deleting(PurchaseOrder $record)
    {
        // Check if there are any Stock Entries linked to this purchase order
        $hasStockEntries = PurchaseStockEntry::where('purchase_order_id', $record->id)->exists();

        if ($hasStockEntries) {
            // 1. Send a danger notification to the Filament UI
            Notification::make()
                ->title(__('purchase-order.cannot_delete_has_stock_entries_title'))
                ->body(__('purchase-order.cannot_delete_has_stock_entries_body', ['number' => $record->number_seq]))
                ->danger()
                ->persistent()
                ->send();

            // 2. CRITICAL: Return false to halt the Eloquent deleting process
            return false;
        }
    }

    /**
     * Handle the PurchaseOrder "deleted" event.
     */
    public function deleted(PurchaseOrder $record): void
    {
        $record->processAutomation('deleted');
        AttachmentService::deleteAttachments($record);
    }

    /**
     * Handle the PurchaseOrder "restored" event.
     */
    public function restored(PurchaseOrder $record): void
    {
        AttachmentService::restoreAttachments($record);
    }

    /**
     * Force delete event.
     * Triggered only when forceDelete() is executed.
     */
    public function forceDeleting(PurchaseOrder $record): void
    {
        //
    }

    /**
     * Handle the PurchaseOrder "force deleted" event.
     */
    public function forceDeleted(PurchaseOrder $record): void
    {
        // 1. Wipe physical files and attachment records from DB
        AttachmentService::forceDeleteAttachments($record);
    }
}
