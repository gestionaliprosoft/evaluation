<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\Pages;

use App\Filament\Clusters\Sales\Resources\SaleContractResource;
use App\Filament\Tables\HeaderActions\CloseAction;
use App\Libs\GenerateService;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewSaleContract extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = SaleContractResource::class;

    protected function getJollyField()
    {
        return ' Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            static::editAction(),
            GenerateService::generateCommercialPdf('SaleContract', 'sale', true),
            CloseAction::make('close'),
        ];
    }
}
