<?php

namespace App\Observers\Sales;

use App\Models\Sale\SaleContract;
use App\Services\AttachmentService;
use App\Services\PaymentService;

class SaleContractObserver
{
    public function creating(SaleContract $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the SaleContract "created" event.
     */
    public function created(SaleContract $record): void
    {
        $record->processAutomation('created');

        PaymentService::createPayment($record);
    }

    /**
     * Handle the SaleContract "updated" event.
     */
    public function updated(SaleContract $record): void
    {
        $record->processAutomation('updated');

        PaymentService::updatePayment($record);
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(SaleContract $record): void
    {
        //
    }

    /**
     * Handle the SaleContract "deleted" event.
     */
    public function deleted(SaleContract $record): void
    {
        $record->processAutomation('deleted');

        PaymentService::deletePayment($record);
        AttachmentService::deleteAttachments($record);
    }

    /**
     * Handle the SaleContract "restored" event.
     */
    public function restored(SaleContract $record): void
    {
        PaymentService::restorePayment($record);
        AttachmentService::restoreAttachments($record);
    }

    /**
     * Handle "force deleting" event.
     */
    public function forceDeleting(SaleContract $record): void
    {
        //
    }

    /**
     * Handle the SaleContract "force deleted" event.
     */
    public function forceDeleted(SaleContract $record): void
    {
        // 1. Wipe physical files and attachment records from DB
        AttachmentService::forceDeleteAttachments($record);

        // 2. Clean up any orphaned soft-deleted receipts from DB
        PaymentService::forceDeletePayment($record);
    }
}
