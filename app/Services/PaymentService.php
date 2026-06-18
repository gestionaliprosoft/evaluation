<?php

namespace App\Services;

use App\Models\Accounting\PaymentReceipt;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Summary of createPayment
     *
     * @param  mixed  $record
     * @return void
     */
    public static function createPayment($record)
    {
        DB::transaction(function () use ($record) {

            $modelLabel = getLabelFromModelClass($record::class);
            $formattedDate = Carbon::parse($record->date)->toFormattedDateString();
            $displayName = $record->organization?->name ?? $record->contact?->name ?? '';

            $number = method_exists($record, 'getPaymentNumber') ? $record->getPaymentNumber() : $record->number;
            $total = method_exists($record, 'getPaymentTotal') ? $record->getPaymentTotal() : $record->total;

            $description = "{$displayName}, {$modelLabel}, ".__('Nr. ')."{$number}, ".__('Date: ').$formattedDate;

            $record->paymentReceipts()->create([
                'uuid' => Str::uuid(),
                'team_id' => $record->team_id,
                'user_id' => $record->user_id,
                'date' => $record->date,
                'description' => $description,
                'debit' => $total ?? 0,
                'credit' => 0,
                'payment_method_id' => $record->payment_method_id,
                'contact_id' => $record->contact_id,
                'organization_id' => $record->organization_id,
            ]);
        });
    }

    /**
     * Summary of updatePayment
     *
     * @param  mixed  $record
     * @return void
     */
    public static function updatePayment($record)
    {
        DB::transaction(function () use ($record) {

            $modelLabel = getLabelFromModelClass($record::class);
            $formattedDate = Carbon::parse($record->date)->toFormattedDateString();
            $displayName = $record->organization?->name ?? $record->contact?->name ?? '';

            $number = method_exists($record, 'getPaymentNumber') ? $record->getPaymentNumber() : $record->number;
            $total = method_exists($record, 'getPaymentTotal') ? $record->getPaymentTotal() : $record->total;

            $description = "{$displayName}, {$modelLabel}, ".__('Nr. ')."{$number}, ".__('Data: ').$formattedDate;

            $record->paymentReceipts()
                ->where('is_generated', true)
                ->update([
                    'team_id' => $record->team_id,
                    'user_id' => $record->user_id,
                    'date' => $record->date,
                    'description' => $description,
                    'debit' => $total ?? 0,
                    'credit' => 0,
                    'payment_method_id' => $record->payment_method_id,
                    'contact_id' => $record->contact_id,
                    'organization_id' => $record->organization_id,
                ]);
        });
    }

    /**
     * Soft-delete payment receipts for any model that has paymentReceipts relation.
     *
     * Comments in English inside the method.
     */
    public static function deletePayment(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'deletePayment:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $deletedAny = false;

            // Use a transaction to keep DB operations consistent
            DB::transaction(function () use ($record, &$deletedAny) {
                // Process receipts in chunks to avoid OOM and keep operations bounded
                $record->paymentReceipts()
                    ->select(['id']) // only select what we need
                    ->chunkById(500, function ($receipts) use (&$deletedAny) {
                        foreach ($receipts as $receipt) {
                            try {
                                // Call delete() to trigger SoftDeletes and model events
                                $receipt->delete();
                                $deletedAny = true;
                            } catch (\Throwable $e) {
                                // Log per-item failure and continue with other receipts
                                Log::error('Failed to soft-delete payment receipt', [
                                    'receipt_id' => $receipt->id,
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
                    ->title(__('messages.payments_receipts.soft_deleted.title'))
                    ->body(__('messages.payments_receipts.soft_deleted.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            // Log unexpected errors and notify failure
            Log::error('deletePayment failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('messages.payments_receipts.failed.title'))
                ->body(__('messages.payments_receipts.failed.body'))
                ->send();

            throw $e;
        } finally {
            // Clean up guard
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Restore soft-deleted payment receipts for any model that has paymentReceipts relation.
     *
     * Comments in English inside the method.
     */
    public static function restorePayment(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'restorePayment:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $restoredAny = false;

            // Use a transaction to keep DB operations consistent
            DB::transaction(function () use ($record, &$restoredAny) {
                // Process trashed receipts in chunks to avoid OOM
                $record->paymentReceipts()
                    ->onlyTrashed()
                    ->select(['id'])
                    ->chunkById(500, function ($receipts) use (&$restoredAny) {
                        foreach ($receipts as $receipt) {
                            try {
                                // Restore each receipt (will trigger model events)
                                $receipt->restore();
                                $restoredAny = true;
                            } catch (\Throwable $e) {
                                // Log per-item failure and continue with other receipts
                                Log::error('Failed to restore payment receipt', [
                                    'receipt_id' => $receipt->id,
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
                    ->title(__('messages.payments_receipts.restored.title')) // reuse or create payments.restored if preferred
                    ->body(__('messages.payments_receipts.restored.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            // Log unexpected errors and notify failure
            Log::error('restorePayment failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('messages.payments_receipts.failed.title'))
                ->body(__('messages.payments_receipts.failed.body'))
                ->send();

            throw $e;
        } finally {
            // Clean up guard
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Permanently delete automatically generated receipts from the database.
     *
     * Comments in English inside the method.
     */
    public static function forceDeletePayment(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'forceDeletePayment:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $deletedAny = false;

            // Use chunking to avoid loading all receipts into memory
            $query = $record->paymentReceipts()->withTrashed()->select('id');

            $query->chunkById(500, function ($receipts) use (&$deletedAny) {
                $ids = $receipts->pluck('id')->all();
                if (! empty($ids)) {
                    // Force delete the batch of receipts
                    PaymentReceipt::whereIn('id', $ids)->forceDelete();
                    $deletedAny = true;
                }
            });

            // Filament notification: success or no-op
            if ($deletedAny) {
                // Filament notification: success
                Notification::make()
                    ->success()
                    ->title(__('messages.payments_receipts.deleted.title'))
                    ->body(__('messages.payments_receipts.deleted.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            // Log and rethrow so the caller can decide how to handle failures
            Log::error('forceDeletePayment failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            // Filament notification: failure
            Notification::make()
                ->danger()
                ->title(__('messages.payments_receipts.failed.title'))
                ->body(__('messages.payments_receipts.failed.body'))
                ->send();

            throw $e;
        } finally {
            // Clean up guard
            app()->forgetInstance($guardKey);
        }
    }
}
