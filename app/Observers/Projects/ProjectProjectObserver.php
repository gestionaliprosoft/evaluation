<?php

namespace App\Observers\Projects;

use App\Models\Project\ProjectProject;
use App\Services\AttachmentService;
use App\Services\PaymentService;

class ProjectProjectObserver
{
    public function creating(ProjectProject $record): void
    {
        if (auth()->check() && ! isset($record->team_id)) {
            $record->team()->associate(auth()->user()->team);
        }
    }

    /**
     * Handle the ProjectProject "created" event.
     */
    public function created(ProjectProject $record): void
    {
        $record->processAutomation('created');
    }

    /**
     * Handle the ProjectProject "updated" event.
     */
    public function updated(ProjectProject $record): void
    {
        $record->processAutomation('updated');
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(ProjectProject $record): void
    {
        //
    }

    /**
     * Handle the ProjectProject "deleted" event.
     */
    public function deleted(ProjectProject $record): void
    {
        $record->processAutomation('deleted');
        PaymentService::deletePayment($record);
        AttachmentService::deleteAttachments($record);
    }

    /**
     * Handle the ProjectProject "restored" event.
     */
    public function restored(ProjectProject $record): void
    {
        PaymentService::restorePayment($record);
        AttachmentService::restoreAttachments($record);
    }

    /**
     * Handle "force deleting" event.
     */
    public function forceDeleting(ProjectProject $record): void
    {
        //
    }

    /**
     * Handle the ProjectProject "force deleted" event.
     */
    public function forceDeleted(ProjectProject $record): void
    {
        // 1. Wipe physical files and attachment records from DB
        AttachmentService::forceDeleteAttachments($record);

        // 2. Clean up any orphaned soft-deleted receipts from DB
        PaymentService::forceDeletePayment($record);

    }
}
