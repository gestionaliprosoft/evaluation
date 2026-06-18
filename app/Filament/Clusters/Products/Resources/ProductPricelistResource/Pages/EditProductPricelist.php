<?php

namespace App\Filament\Clusters\Products\Resources\ProductPricelistResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPricelistResource;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\EditRecord;

class EditProductPricelist extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = ProductPricelistResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
