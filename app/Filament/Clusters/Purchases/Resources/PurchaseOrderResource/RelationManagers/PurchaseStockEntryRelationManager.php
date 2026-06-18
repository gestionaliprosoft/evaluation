<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Clusters\Purchases\Resources\PurchaseStockEntryResource;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class PurchaseStockEntryRelationManager extends AbstractRelationManager
{
    protected static string $relationship = 'stockEntries';

    public function form(Form $form): Form
    {
        return $form->schema(PurchaseStockEntryResource::getFormsComponents());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(PurchaseStockEntryResource::getColumnsComponents())
            ->filters(PurchaseStockEntryResource::getFiltersComponents())
            ->actions(array_merge(PurchaseStockEntryResource::getActionsComponents(), [
                static::completeFormAction(PurchaseStockEntryResource::class),
            ]))
            ->bulkActions(PurchaseStockEntryResource::getBulkActionsComponents());
    }

    public static function completeFormAction($resourceClass): Action
    {
        return Action::make('Complete Form')
            ->visible(function ($record) {
                if ($record->purchaseOrder->status->is_final_step || $record->purchaseOrder->status->archived) {
                    return false;
                }

                return PurchaseStockEntryResource::canEdit($record);
            })
            ->label(__(''))
            ->tooltip(__('Complete Form'))
            ->icon(config('module-icon.icon-complete-form'))
            ->url(function ($record) use ($resourceClass) {
                if ($record instanceof ModuleContact) {
                    return $resourceClass::getUrl('edit', ['record' => $record->contact]);
                } else {
                    return $resourceClass::getUrl('edit', ['record' => $record]);
                }
            });
    }
}
