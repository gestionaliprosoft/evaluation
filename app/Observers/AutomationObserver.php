<?php

namespace App\Observers;

use App\Models\Automation;

class AutomationObserver
{
    public function creating(Automation $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the Automation "created" event.
     */
    public function created(Automation $automation): void
    {
        //
    }

    /**
     * Handle the Automation "updated" event.
     */
    public function updated(Automation $automation): void
    {
        //
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(Automation $record): void
    {
        //
    }

    /**
     * Handle the Automation "deleted" event.
     */
    public function deleted(Automation $automation): void
    {
        //
    }

    /**
     * Handle the Automation "restored" event.
     */
    public function restored(Automation $automation): void
    {
        //
    }

    /**
     * Handle "force deleting" event.
     */
    public function forceDeleting(Automation $record): void
    {
        //
    }

    /**
     * Handle the Automation "force deleted" event.
     */
    public function forceDeleted(Automation $automation): void
    {
        //
    }
}
