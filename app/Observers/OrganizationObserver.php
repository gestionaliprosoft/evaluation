<?php

namespace App\Observers;

use App\Models\Organization;
use App\Services\AddressService;
use App\Services\AttachmentService;
use App\Services\EmailMessageService;

class OrganizationObserver
{
    public function creating(Organization $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $record): void
    {
        $record->processAutomation('created');
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $record): void
    {
        $record->processAutomation('updated');
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(Organization $record): void
    {
        //
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $record): void
    {
        $record->processAutomation('deleted');
        AttachmentService::deleteAttachments($record);
        AddressService::deleteAddresses($record);
        EmailMessageService::deleteEmails($record);
    }

    /**
     * Handle the Organization "restored" event.
     */
    public function restored(Organization $record): void
    {
        AttachmentService::restoreAttachments($record);
        AddressService::restoreAddresses($record);
        EmailMessageService::restoreEmails($record);
    }

    /**
     * Handle "force deleting" event.
     */
    public function forceDeleting(Organization $record): void
    {
        //
    }

    /**
     * Handle the Organization "force deleted" event.
     */
    public function forceDeleted(Organization $record): void
    {
        // Wipe physical files and attachment records from DB
        AttachmentService::forceDeleteAttachments($record);

        // Wipe polymorphic address records permanently from DB
        AddressService::forceDeleteAddresses($record);

        EmailMessageService::forceDeleteEmails($record);
    }
}
