<?php

namespace App\Observers;

use App\Models\User;
use App\Services\EmailMessageService;

class UserObserver
{
    /**
     * Handle the User "retrieved" event.
     */
    public function retrieved(User $user)
    {
        //
    }

    public function creating(User $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(User $record): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $record): void
    {
        EmailMessageService::deleteEmails($record);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $record): void
    {
        EmailMessageService::restoreEmails($record);
    }

    /**
     * Handle "force deleting" event.
     */
    public function forceDeleting(User $record): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $record): void
    {
        EmailMessageService::forceDeleteEmails($record);
    }
}
