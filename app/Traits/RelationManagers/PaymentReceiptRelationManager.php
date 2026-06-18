<?php

namespace App\Traits\RelationManagers;

use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource;
use App\Libs\FormService;
use App\Models\Accounting\PaymentReceipt;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait PaymentReceiptRelationManager
{
    protected Form $form;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->paymentReceipts->count();
    }

    public function form(Form $form): Form
    {
        $formComponents = $form->schema(PaymentReceiptResource::getFormsComponents());
        $this->form = $formComponents;

        return $formComponents;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns(PaymentReceiptResource::getColumnsComponents())
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(auth()->user()->can('create', PaymentReceipt::class))
                    ->label(__('sale-contract.Add Payment Receipt'))
                    ->modalHeading(__('sale-contract.Add Payment Receipt'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'date' => now(),
                        'contact_id' => $livewire->ownerRecord->contact_id,
                        'organization_id' => $livewire->ownerRecord->organization_id,
                        'team_id' => $livewire->ownerRecord->team_id,
                        'user_id' => auth()->user()->getKey(),
                        'payment_method_id' => $livewire->ownerRecord->organization?->details->first()?->payment_method_id,
                        'paymentable_type' => $livewire->ownerRecord::class,
                    ])
                    ->modalWidth(MaxWidth::Full)
                    ->createAnother(false)
                    ->mutateFormDataUsing(function ($data, RelationManager $livewire) {
                        $data['uuid'] = Str::uuid();
                        $data['paymentable_id'] = $livewire->ownerRecord->id;

                        return $data;
                    })
                    ->after(function ($data, RelationManager $livewire, Model $record) {
                        FormService::addAttachmentsToRelationManager(
                            $this->form->getRawState(),
                            $livewire->ownerRecord,
                            $record,
                            PaymentReceipt::class,
                            $data['description'],
                        );
                    }),
            ])
            ->actions([
                PaymentReceiptResource::viewAction(),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip(__('Edit'))
                    ->modalWidth(MaxWidth::Full)
                    ->after(function ($data, RelationManager $livewire, Model $record) {
                        FormService::addAttachmentsToRelationManager(
                            $this->form->getRawState(),
                            $livewire->ownerRecord,
                            $record,
                            PaymentReceipt::class,
                            $data['description'],
                        );
                    }),
                ActionGroup::make([
                    Tables\Actions\DeleteAction::make()
                        ->label(__('Delete')),
                ]),
                static::completeFormAction(PaymentReceiptResource::class),
            ]);
    }
}
