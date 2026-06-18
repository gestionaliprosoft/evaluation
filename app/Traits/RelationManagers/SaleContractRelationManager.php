<?php

namespace App\Traits\RelationManagers;

use App\Filament\Clusters\Sales\Resources\SaleContractResource;
use App\Filament\Tables\Actions\SaleContract\GenerateProjectAction;
use App\Filament\Tables\Actions\SaleContract\RenewContractAction;
use App\Libs\FormService;
use App\Libs\GenerateService;
use App\Models\Sale\SaleContract;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

trait SaleContractRelationManager
{
    protected Form $form;

    public function form(Form $form): Form
    {
        $formComponents = $form->schema(SaleContractResource::getFormsComponents());
        $this->form = $formComponents;

        return $formComponents;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(SaleContractResource::getColumnsComponents())
            ->filters(SaleContractResource::getFiltersComponents())
            ->actions([
                static::viewAction(),
                EditAction::make()
                    ->label('')
                    ->tooltip(__('Edit'))
                    ->modalWidth(MaxWidth::Full)
                    ->fillForm(function (SaleContract $record): array {
                        $record->fromRelationManager = true;

                        return $record->toArray();
                    })
                    ->after(function ($data, RelationManager $livewire, Model $record) {
                        FormService::addAttachmentsToRelationManager(
                            $this->form->getRawState(),
                            $livewire->ownerRecord,
                            $record,
                            SaleContract::class,
                            $data['description'],
                        );
                    }),
                ActionGroup::make([
                    GenerateProjectAction::make('generate_project'),
                    GenerateService::generateCommercialPdf('SaleContract', 'sale'),
                    RenewContractAction::make('renew_contract'),
                    DeleteAction::make()
                        ->label(__('Delete')),
                ]),
                static::completeFormAction(SaleContractResource::class),
            ])
            ->bulkActions(SaleContractResource::getBulkActionsComponents());
    }
}
