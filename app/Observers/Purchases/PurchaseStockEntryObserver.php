<?php

namespace App\Observers\Purchases;

use App\Models\Purchase\PurchaseStockEntry;
use App\Services\AttachmentService;

class PurchaseStockEntryObserver
{
    public function creating(PurchaseStockEntry $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the PurchaseStockEntry "created" event.
     */
    public function created(PurchaseStockEntry $record): void
    {
        //
    }

    /**
     * Handle the PurchaseStockEntry "updated" event.
     */
    public function updated(PurchaseStockEntry $record): void
    {
        //
    }

    /**
     * Soft delete event.
     * This method is always triggered, even during force delete.
     */
    public function deleting(PurchaseStockEntry $record): void
    {
        //
    }

    /**
     * Handle the PurchaseStockEntry "deleted" event.
     */
    public function deleted(PurchaseStockEntry $record): void
    {
        AttachmentService::deleteAttachments($record);
    }

    /**
     * Handle the PurchaseStockEntry "restored" event.
     */
    public function restored(PurchaseStockEntry $record): void
    {
        AttachmentService::restoreAttachments($record);
    }

    /**
     * Force delete event.
     * Triggered only when forceDelete() is executed.
     */
    public function forceDeleting(PurchaseStockEntry $record): void
    {
        //
    }

    /**
     * Handle the PurchaseStockEntry "force deleted" event.
     */
    public function forceDeleted(PurchaseStockEntry $record): void
    {
        // 1. Wipe physical files and attachment records from DB
        AttachmentService::forceDeleteAttachments($record);
    }
}
