<?php

namespace App\Filament\Tables\Actions\PaymentReceipt;

use App\Libs\PaymentService;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;

class AddLinkedTransactionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'addLLinkedTransactionAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('payment-receipt.New Linked Transaction'))
            ->visible(function ($record): bool {
                $originClass = $record?->paymentable_type ?? null;
                $class = $originClass ? (new $originClass)->getResourceClass() : null;

                return $originClass && $class && auth()->user()->can('create', $record);
            })
            ->form(function ($livewire): array {
                // Check if the current Livewire page has a parent Resource
                if (method_exists($livewire, 'getResource')) {
                    $resource = $livewire::getResource();

                    // Call the static method on the hosting Resource class
                    if (method_exists($resource, 'getFormsComponents')) {
                        return $resource::getFormsComponents();
                    }
                }

                return []; // Fallback empty schema
            })
            ->fillForm(function ($record) {
                return [
                    'team_id' => $record->team_id,
                    'user_id' => $record->user_id,
                    'date' => now(),
                    'description' => $record->description,
                    'debit' => $record->total ?? 0,
                    'credit' => $record->total ?? 0,
                    'payment_method_id' => $record?->payment_method_id,
                    'contact_id' => $record?->contact_id,
                    'organization_id' => $record?->organization_id,
                    'attachments' => [],
                ];
            })
            ->modalWidth(MaxWidth::Full)
            ->action(function (array $data, $record) {
                $data['paymentable_id'] = $record->paymentable_id;
                $data['paymentable_type'] = $record->paymentable_type;

                return PaymentService::createPaymentFromPayment($data);
            })
            ->after(function () {
                Notification::make()
                    ->title(__('payment-receipt.Transaction added'))
                    ->success()
                    ->send();
            });
    }
}
