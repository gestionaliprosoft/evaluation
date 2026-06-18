<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Email\EmailMessage;
use App\Models\Email\EmailTemplate;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailMessageService
{
    /**
     * Sends an email wrapped in the standard layout and logs it to the database.
     *
     * @throws \Exception
     */
    public function sendAndLog(
        Model $emailable,
        string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $messageBody,
        ?int $userId = null,
        ?int $templateId = null,
    ): void {
        // get attachment (logo)
        if ($templateId) {
            $logo = public_path('vendor/invoices/sample-logo.png'); // fallback logo

            $template = EmailTemplate::find($templateId);

            if ($template) {
                $filename = Attachment::where('attachable_id', $template->getKey())
                    ->where('attachable_type', $template::class)
                    ->first();

                // if attachment exists
                if ($filename) {
                    $modelName = Str::afterLast($template::class, '\\');
                    $customPath = storage_path("team-{$filename->team_id}/{$modelName}/{$filename->filename}");

                    // override fallback $logo
                    if (file_exists($customPath)) {
                        $logo = $customPath;
                    }
                }
            }
        }

        // Send the email directly using the layout view.
        // This approach automatically injects the special $message variable into the Blade view,
        // which is strictly required for native CID inline image embedding.
        Mail::send('emails.notifications.layout', [
            'logo' => $logo ?? null,        // Absolute file path on the server
            'mailContent' => $messageBody, // The dynamic email body text/HTML
            'record' => $emailable,   // The underlying model record related to the email
        ], function ($message) use ($recipientEmail, $recipientName, $subject) {
            $message->to($recipientEmail, $recipientName)
                ->subject($subject);
        });

        // Log the transaction in the database
        EmailMessage::create([
            'emailable_type' => $emailable::class,
            'emailable_id' => $emailable->getKey(),
            'user_id' => $userId ?? auth()->id(),
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'message' => $messageBody,
            'email_template_id' => $templateId,
        ]);
    }

    /**
     * Soft-delete all email messages linked to the given model.
     */
    public static function deleteEmails(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'deleteEmails:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $deletedAny = false;

            DB::transaction(function () use ($record, &$deletedAny) {
                // Process in chunks to avoid loading all email messages into memory
                $record->emails()
                    ->select(['id'])
                    ->chunkById(200, function ($emails) use (&$deletedAny) {
                        foreach ($emails as $email) {
                            try {
                                // Soft-delete to trigger model events and SoftDeletes behavior
                                $email->delete();
                                $deletedAny = true;
                            } catch (\Throwable $e) {
                                Log::error('Failed to soft-delete email', [
                                    'email_id' => $email->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            if ($deletedAny) {
                Notification::make()
                    ->success()
                    ->title(__('email-message.soft_deleted.title'))
                    ->body(__('email-message.soft_deleted.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('deleteEmails failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('email-message.failed.title'))
                ->body(__('email-message.failed.body'))
                ->send();

            throw $e;
        } finally {
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Restore all soft-deleted email messages linked to the given model.
     */
    public static function restoreEmails(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'restoreEmails:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $restoredAny = false;

            DB::transaction(function () use ($record, &$restoredAny) {
                // Process in chunks to avoid loading all email messages into memory
                $record->emails()
                    ->onlyTrashed()
                    ->select(['id'])
                    ->chunkById(200, function ($emails) use (&$restoredAny) {
                        foreach ($emails as $email) {
                            try {
                                // Restore to trigger model events and SoftDeletes behavior
                                $email->restore();
                                $restoredAny = true;
                            } catch (\Throwable $e) {
                                Log::error('Failed to restore email', [
                                    'email_id' => $email->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            if ($restoredAny) {
                Notification::make()
                    ->success()
                    ->title(__('email-message.restored.title'))
                    ->body(__('email-message.restored.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('restoreEmails failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('email-message.failed.title'))
                ->body(__('email-message.failed.body'))
                ->send();

            throw $e;
        } finally {
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Permanently delete all email messages linked to the given model.
     */
    public static function forceDeleteEmails(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'forceDeleteEmails:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $forceDeletedAny = false;

            DB::transaction(function () use ($record, &$forceDeletedAny) {
                // Process in chunks to avoid loading all email messages into memory
                $record->emails()
                    ->withTrashed()
                    ->select(['id'])
                    ->chunkById(200, function ($emails) use (&$forceDeletedAny) {
                        foreach ($emails as $email) {
                            try {
                                // Force-delete to trigger model events
                                $email->forceDelete();
                                $forceDeletedAny = true;
                            } catch (\Throwable $e) {
                                Log::error('Failed to force-delete email', [
                                    'email_id' => $email->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            if ($forceDeletedAny) {
                Notification::make()
                    ->success()
                    ->title(__('email-message.force_deleted.title'))
                    ->body(__('email-message.force_deleted.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('forceDeleteEmails failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('email-message.failed.title'))
                ->body(__('email-message.failed.body'))
                ->send();

            throw $e;
        } finally {
            app()->forgetInstance($guardKey);
        }
    }
}
