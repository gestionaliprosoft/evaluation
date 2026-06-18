<?php

namespace App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource\Pages;

use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource;
use App\Libs\PaymentService;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;

class EditPaymentReceipt extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = PaymentReceiptResource::class;

    protected function getJollyField()
    {
        return ' Nr. '.$this->record->uuid;
    }

    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('Add Linked Transaction')
                ->label(__('payment-receipt.New Linked Transaction'))
                ->visible(function ($record): bool {
                    $originClass = $record?->paymentable_type ?? null;
                    $class = $originClass ? (new $originClass)->getResourceClass() : null;

                    return $originClass && $class && auth()->user()->can('create', $record);
                })
                ->form(self::$resource::getFormsComponents())
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
                }),

            Actions\DeleteAction::make()
                ->label(__('Delete'))
                ->after(fn () => redirect(self::getUrl(['index']))),
        ];
    }
}
