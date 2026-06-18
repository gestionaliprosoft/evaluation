<?php

namespace App\Filament\Tables\Actions\ProjectProject;

use App\Libs\PaymentService;
use App\Models\Accounting\PaymentReceipt;
use App\Models\Project\ProjectProject;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class AttachTransactionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'attachTransactionAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation()
            ->label(__('project-project.Attach To Transaction'))
            ->form([
                Select::make('transaction_id')
                    ->label(__('resources.PaymentReceiptResource'))
                    ->options(PaymentService::getAllowedNoAssociatedPayments())
                    ->searchable()
                    ->required(),
            ])
            ->visible(fn (ProjectProject $record) => auth()->user()->can('update', $record))
            ->action(function (array $data, ProjectProject $record) {
                $payment = PaymentReceipt::whereId($data['transaction_id'])->first();

                $payment->paymentable_type = 'App\\Models\\Project\\ProjectProject';
                $payment->paymentable_id = $record->getKey();
                $payment->update();

                Notification::make()
                    ->title(__('project-project.Project Has Been Associated'))
                    ->success()
                    ->send();
            });
    }
}
