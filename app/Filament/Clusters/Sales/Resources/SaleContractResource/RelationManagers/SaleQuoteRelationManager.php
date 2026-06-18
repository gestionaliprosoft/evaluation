<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Clusters\Sales\Resources\SaleQuoteResource;
use Filament\Forms\Form;
use Filament\Tables\Table;

class SaleQuoteRelationManager extends AbstractRelationManager
{
    protected static string $relationship = 'quote';

    public function form(Form $form): Form
    {
        return $form->schema(SaleQuoteResource::getFormsComponents());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(SaleQuoteResource::getColumnsComponents())
            ->filters(SaleQuoteResource::getFiltersComponents())
            ->actions(array_merge(SaleQuoteResource::getActionsComponents(), [
                static::completeFormAction(SaleQuoteResource::class),
            ]))
            ->bulkActions(SaleQuoteResource::getBulkActionsComponents());
    }
}
