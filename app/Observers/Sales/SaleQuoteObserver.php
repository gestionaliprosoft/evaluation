<?php

namespace App\Observers\Sales;

use App\Models\Sale\SaleQuote;
use App\Services\AttachmentService;

class SaleQuoteObserver
{
    public function creating(SaleQuote $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the SaleQuote "created" event.
     */
    public function created(SaleQuote $saleQuote): void
    {
        //
    }

    /**
     * Handle the SaleQuote "updated" event.
     */
    public function updated(SaleQuote $saleQuote): void
    {
        //
    }

    /**
     * Soft delete event.
     * This method is always triggered, even during force delete.
     */
    public function deleting(SaleQuote $record): void
    {
        //
    }

    /**
     * Handle the SaleQuote "deleted" event.
     */
    public function deleted(SaleQuote $record): void
    {
        AttachmentService::deleteAttachments($record);
    }

    /**
     * Handle the SaleQuote "restored" event.
     */
    public function restored(SaleQuote $record): void
    {
        AttachmentService::restoreAttachments($record);
    }

    /**
     * Force delete event.
     * Triggered only when forceDelete() is executed.
     */
    public function forceDeleting(SaleQuote $record): void
    {
        //
    }

    /**
     * Handle the SaleQuote "force deleted" event.
     */
    public function forceDeleted(SaleQuote $record): void
    {
        // 1. Wipe physical files and attachment records from DB
        AttachmentService::forceDeleteAttachments($record);
    }
}
