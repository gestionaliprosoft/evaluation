<?php

namespace App\Notifications;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public Model $order) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('purchase-stock-entry.purchase_order_complete_warehouse_load_requested', ['id' => $this->order->id]))
            ->greeting(__('purchase-stock-entry.hello_user', ['name' => $notifiable->name]))
            ->line(__('purchase-stock-entry.purchase_order_verified_and_complete', ['id' => $this->order->id]))
            ->line(__('purchase-stock-entry.approval_required_to_load_warehouse'))
            ->action(__('purchase-stock-entry.view_order'), PurchaseOrderResource::getUrl('view', ['record' => $this->order]))
            ->line(__('purchase-stock-entry.thank_you'));
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('purchase-stock-entry.order_verified_and_ready_for_load', ['id' => $this->order->id]))
            ->body(__('purchase-stock-entry.click_here_to_load_stock'))
            ->success()
            ->icon('heroicon-o-cube')
            ->actions([
                Action::make('view')
                    ->label(__('purchase-stock-entry.open_order'))
                    ->url(PurchaseOrderResource::getUrl('view', ['record' => $this->order])),
            ])
            ->getDatabaseMessage();
    }
}
