<?php

namespace App\Filament\Clusters\Products\Resources\ProductPricelistResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPricelistResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewProductPricelist extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = ProductPricelistResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
