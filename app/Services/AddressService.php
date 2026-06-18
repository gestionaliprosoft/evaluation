<?php

namespace App\Services;

use App\Models\Address;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressService
{
    /**
     * Clone all addresses from one record to another.
     *
     * @param  mixed  $sourceRecord  The model that already owns the addresses
     * @param  mixed  $targetRecord  The model that will receive the cloned addresses
     */
    public function cloneAddressesFrom($sourceRecord, $targetRecord): void
    {
        // Retrieve the fillable fields from the Address model
        $fillable = (new Address)->getFillable();

        // Loop through each address of the source record
        foreach ($sourceRecord->addresses as $address) {

            // Extract only the allowed (fillable) fields from the address
            $data = array_intersect_key($address->toArray(), array_flip($fillable));

            // Create a new address on the target record using the filtered data
            $targetRecord->addresses()->create($data);
        }
    }

    /**
     * Soft-delete all addresses linked to the given model.
     */
    public static function deleteAddresses(Model $record): void
    {
        // Guard key to avoid re-entrancy in the same process
        $guardKey = 'deleteAddresses:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $deletedAny = false;

            DB::transaction(function () use ($record, &$deletedAny) {
                // Process in chunks to avoid loading all addresses into memory
                $record->addresses()
                    ->select(['id'])
                    ->chunkById(200, function ($addresses) use (&$deletedAny) {
                        foreach ($addresses as $address) {
                            try {
                                // Soft-delete to trigger model events and SoftDeletes behavior
                                $address->delete();
                                $deletedAny = true;
                            } catch (\Throwable $e) {
                                Log::error('Failed to soft-delete address', [
                                    'address_id' => $address->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            if ($deletedAny) {
                Notification::make()
                    ->success()
                    ->title(__('messages.addresses.soft_deleted.title'))
                    ->body(__('messages.addresses.soft_deleted.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('deleteAddresses failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('messages.addresses.failed.title'))
                ->body(__('messages.addresses.failed.body'))
                ->send();

            throw $e;
        } finally {
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Restore all soft-deleted addresses linked to the given model.
     */
    public static function restoreAddresses(Model $record): void
    {
        $guardKey = 'restoreAddresses:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $restoredAny = false;

            DB::transaction(function () use ($record, &$restoredAny) {
                // Restore trashed addresses in chunks to avoid OOM
                $record->addresses()
                    ->onlyTrashed()
                    ->select(['id'])
                    ->chunkById(200, function ($addresses) use (&$restoredAny) {
                        foreach ($addresses as $address) {
                            try {
                                $address->restore();
                                $restoredAny = true;
                            } catch (\Throwable $e) {
                                Log::error('Failed to restore address', [
                                    'address_id' => $address->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            if ($restoredAny) {
                Notification::make()
                    ->success()
                    ->title(__('messages.addresses.restored.title'))
                    ->body(__('messages.addresses.restored.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('restoreAddresses failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('messages.addresses.failed.title'))
                ->body(__('messages.addresses.failed.body'))
                ->send();

            throw $e;
        } finally {
            app()->forgetInstance($guardKey);
        }
    }

    /**
     * Permanently wipe all addresses linked to the given model from the database.
     */
    public static function forceDeleteAddresses(Model $record): void
    {
        $guardKey = 'forceDeleteAddresses:'.get_class($record).':'.$record->getKey();
        if (app()->has($guardKey)) {
            return;
        }
        app()->instance($guardKey, true);

        try {
            $deletedAny = false;

            DB::transaction(function () use ($record, &$deletedAny) {
                // Iterate in chunks and hard-delete each chunk via a mass query for efficiency
                $record->addresses()
                    ->withTrashed()
                    ->select(['id'])
                    ->chunkById(200, function ($addresses) use (&$deletedAny) {
                        $ids = $addresses->pluck('id')->all();
                        if (! empty($ids)) {
                            try {
                                Address::whereIn('id', $ids)->forceDelete();
                                $deletedAny = true;
                            } catch (\Throwable $e) {
                                Log::error('Failed to force-delete address chunk', [
                                    'address_ids' => $ids,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            });

            if ($deletedAny) {
                Notification::make()
                    ->success()
                    ->title(__('messages.addresses.force_deleted.title'))
                    ->body(__('messages.addresses.force_deleted.body'))
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('forceDeleteAddresses failed', [
                'record_class' => get_class($record),
                'record_id' => $record->getKey(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('messages.addresses.failed.title'))
                ->body(__('messages.addresses.failed.body'))
                ->send();

            throw $e;
        } finally {
            app()->forgetInstance($guardKey);
        }
    }
}
