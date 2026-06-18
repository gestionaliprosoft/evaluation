<?php

namespace App\Observers;

use App\Models\Contact;
use App\Services\AddressService;
use App\Services\AttachmentService;
use App\Services\EmailMessageService;

class ContactObserver
{
    public function creating(Contact $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $record): void
    {
        $record->processAutomation('created');
    }

    /**
     * Handle the Contact "updated" event.
     */
    public function updated(Contact $record): void
    {
        $record->processAutomation('updated');
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(Contact $record): void
    {
        //
    }

    /**
     * Handle the Contact "deleted" event.
     */
    public function deleted(Contact $record): void
    {
        $record->processAutomation('deleted');
        AttachmentService::deleteAttachments($record);
        AddressService::deleteAddresses($record);
        EmailMessageService::deleteEmails($record);
    }

    /**
     * Handle the Contact "restored" event.
     */
    public function restored(Contact $record): void
    {
        AttachmentService::restoreAttachments($record);
        AddressService::restoreAddresses($record);
        EmailMessageService::restoreEmails($record);
    }

    /**
     * Handle "force deleting" event.
     */
    public function forceDeleting(Contact $record): void
    {
        //
    }

    /**
     * Handle the Contact "force deleted" event.
     */
    public function forceDeleted(Contact $record): void
    {
        // Wipe physical files and attachment records from DB
        AttachmentService::forceDeleteAttachments($record);

        // Wipe polymorphic address records permanently from DB
        AddressService::forceDeleteAddresses($record);

        EmailMessageService::forceDeleteEmails($record);
    }
}
