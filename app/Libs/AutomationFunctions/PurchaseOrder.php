<?php

namespace App\Libs\AutomationFunctions;

use App\Models\Purchase\PurchaseOrderStatus;
use App\Models\User;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class PurchaseOrder
{
    /**
     * Send notifications if order is_final_step status && Automation is enabled
     *
     * @return null
     */
    public function sendNotifications(Model $record)
    {
        // Check if the order status was explicitly changed
        if ($record->wasChanged('order_status_id') && (int) $record->order_status_id === (int) PurchaseOrderStatus::getIsFinalStepId()) {
            // 1. Fetch all users belonging to that specific team
            $supervisors = User::where('team_id', $record->team_id)
                ->get()
                // 2. Filter the collection using the Laravel Policy system
                ->filter(function (User $user) use ($record) {
                    // This evaluates the 'processStockIn' method inside PurchaseOrderPolicy
                    return $user->can('processStockIn', $record);
                });

            // Send the notification (Email + Bell) only to the supervisors of this specific team
            foreach ($supervisors as $supervisor) {
                try {
                    $supervisor->notify(new OrderCompletedNotification($record));
                } catch (TransportExceptionInterface $e) {
                    // Catch invalid email addresses or SMTP connection failures
                    Log::error("Failed to send order email to User ID {$supervisor->id}. Invalid email or SMTP error: ".$e->getMessage());
                } catch (Throwable $e) {
                    // Catch any other unexpected errors so the application workflow doesn't crash
                    Log::error("Generic error during order notification for User ID {$supervisor->id}: ".$e->getMessage());
                }
            }
        }
    }
}
