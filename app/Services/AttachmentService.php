<?php

namespace App\Services;

use App\Libs\FileService;
use App\Models\Attachment;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Soft-delete attachments for any model using InteractsWithAttachments.
     *
     * Comments in English inside the method.
     */
    public static function deleteAttachments(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'deleteAttachments:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $deletedAny = false;

            // Use a transaction to keep DB operations consistent
            DB::transaction(function () use ($record, &$deletedAny) {
                // Process attachments in chunks to avoid OOM and to keep operations bounded
                $record->attachments()
                    ->select(['id']) // only select what we need
                    ->chunkById(200, function ($attachments) use (&$deletedAny) {
                        foreach ($attachments as $attachment) {
                            try {
                                // Call delete() on the model to trigger model events and SoftDeletes behavior
                                $attachment->delete();
                                $deletedAny = true;
                            } catch (\Throwable $e) {
                                // Log per-item failure and continue with other attachments
                                Log::error('Failed to soft-delete attachment', [
                                    'attachment_id' => $attachment->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            // Notify user via Filament depending on outcome
            if ($deletedAny) {
                Notification::make()
                    ->success()
                    ->title(__('messages.attachments.soft_deleted.title'))
                    ->body(__('messages.attachments.soft_deleted.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            // Log unexpected errors and notify failure
            Log::error('deleteAttachments failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('messages.attachments.failed.title'))
                ->body(__('messages.attachments.failed.body'))
                ->send();

            throw $e;
        } finally {
            // Clean up guard
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Restore soft-deleted attachments for any model using InteractsWithAttachments.
     *
     * Comments in English inside the method.
     */
    public static function restoreAttachments(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'restoreAttachments:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $restoredAny = false;

            // Use a transaction to keep DB operations consistent
            DB::transaction(function () use ($record, &$restoredAny) {
                // Process trashed attachments in chunks to avoid OOM
                $record->attachments()
                    ->onlyTrashed()
                    ->select(['id'])
                    ->chunkById(200, function ($attachments) use (&$restoredAny) {
                        foreach ($attachments as $attachment) {
                            try {
                                // Restore each attachment (will trigger model events)
                                $attachment->restore();
                                $restoredAny = true;
                            } catch (\Throwable $e) {
                                // Log per-item failure and continue with other attachments
                                Log::error('Failed to restore attachment', [
                                    'attachment_id' => $attachment->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            // Notify user via Filament depending on outcome
            if ($restoredAny) {
                Notification::make()
                    ->success()
                    ->title(__('messages.attachments.restored.title'))
                    ->body(__('messages.attachments.restored.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            // Log unexpected errors and notify failure
            Log::error('restoreAttachments failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('messages.attachments.failed.title'))
                ->body(__('messages.attachments.failed.body'))
                ->send();

            throw $e;
        } finally {
            // Clean up guard
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Permanently delete attachment records and their physical files from storage.
     *
     * Comments in English inside the method.
     */
    public static function forceDeleteAttachments(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'forceDeleteAttachments:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            // 1) Collect attachment ids and file paths in batches to avoid loading everything in memory
            $attachmentIds = [];
            $filesToDelete = [];

            $query = $record->attachments()->withTrashed()->select(['id', 'filename', 'team_id']);

            $query->chunkById(200, function ($attachments) use (&$attachmentIds, &$filesToDelete, $record) {
                $folder = Str::afterLast($record::class, '\\');

                foreach ($attachments as $attachment) {
                    $attachmentIds[] = $attachment->id;

                    // Build the exact storage path according to your team folder pattern
                    if (! empty($attachment->filename)) {
                        $filesToDelete[] = 'team-'.$record->team_id.'/'.$folder.'/'.$attachment->filename;
                    }
                }
            });

            // 2) Delete DB records inside a transaction (atomic DB operation)
            DB::transaction(function () use ($attachmentIds) {
                if (! empty($attachmentIds)) {
                    Attachment::whereIn('id', $attachmentIds)->forceDelete();
                }
            });

            // 3) After DB commit, delete physical files synchronously (no job)
            //    If a file deletion fails, log and continue (idempotent behavior)
            if (! empty($filesToDelete)) {
                try {
                    FileService::deletePrivateFiles($filesToDelete);

                    // Filament notification: success
                    Notification::make()
                        ->success()
                        ->title(__('messages.attachments.deleted.title'))
                        ->body(__('messages.attachments.deleted.body'))
                        ->send();

                } catch (\Throwable $e) {
                    // Log the failure but do not rethrow to avoid breaking the caller flow
                    Log::error('Failed to delete attachment files after DB commit', [
                        'record_class' => get_class($record),
                        'record_id' => $record->getKey(),
                        'error' => $e->getMessage(),
                    ]);

                    // Filament notification: failure
                    Notification::make()
                        ->danger()
                        ->title(__('messages.attachments.files_failed.title'))
                        ->body(__('messages.attachments.files_failed.body'))
                        ->send();

                }
            } else {
                // No files to delete but DB operation succeeded -> notify success
                Notification::make()
                    ->success()
                    ->title(__('messages.attachments.deleted.title'))
                    ->body(__('messages.attachments.deleted.no_files.body'))
                    ->send();

            }
        } catch (\Throwable $e) {
            // Log unexpected errors and rethrow so upstream can handle if needed
            Log::error('forceDeleteAttachments failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            // Filament notification: failure
            Notification::make()
                ->danger()
                ->title(__('messages.attachments.failed.title'))
                ->body(__('messages.attachments.failed.body'))
                ->send();

            throw $e;
        } finally {
            // Clean up guard
            app()->forgetInstance($guardKey);
        }
    }
}
