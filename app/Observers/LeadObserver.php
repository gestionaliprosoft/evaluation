<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\AddressService;
use App\Services\EmailMessageService;

class LeadObserver
{
    public function creating(Lead $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $record): void
    {
        $record->processAutomation('created');
    }

    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $record): void
    {
        $record->processAutomation('updated');
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(Lead $record): void
    {
        //
    }

    /**
     * Handle the Lead "deleted" event.
     */
    public function deleted(Lead $record): void
    {
        $record->processAutomation('deleted');
        AddressService::deleteAddresses($record);
        EmailMessageService::deleteEmails($record);
    }

    /**
     * Handle the Lead "restored" event.
     */
    public function restored(Lead $record): void
    {
        AddressService::restoreAddresses($record);
        EmailMessageService::restoreEmails($record);
    }

    /**
     * Handle "force deleting" event.
     */
    public function forceDeleting(Lead $record): void
    {
        //
    }

    /**
     * Handle the Lead "force deleted" event.
     */
    public function forceDeleted(Lead $record): void
    {
        // Wipe polymorphic address records permanently from DB
        AddressService::forceDeleteAddresses($record);
        EmailMessageService::forceDeleteEmails($record);
    }
}
