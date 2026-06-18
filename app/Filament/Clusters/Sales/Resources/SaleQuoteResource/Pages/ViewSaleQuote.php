<?php

namespace App\Filament\Clusters\Sales\Resources\SaleQuoteResource\Pages;

use App\Filament\Clusters\Sales\Resources\SaleQuoteResource;
use App\Filament\Tables\HeaderActions\CloseAction;
use App\Libs\GenerateService;
use App\Models\Sale\SaleQuote;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewSaleQuote extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = SaleQuoteResource::class;

    protected function getJollyField()
    {
        return ' Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            static::editAction()
                ->visible(fn (SaleQuote $record): bool => ! $record->contract)
                ->label(__('Edit')),
            GenerateService::generateCommercialPdf('SaleQuote', 'sale', true),
            CloseAction::make('close'),
        ];
    }
}
