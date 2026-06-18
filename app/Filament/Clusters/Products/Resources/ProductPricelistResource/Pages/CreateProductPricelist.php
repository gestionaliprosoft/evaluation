<?php

namespace App\Filament\Clusters\Products\Resources\ProductPricelistResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPricelistResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;

class CreateProductPricelist extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = ProductPricelistResource::class;
}
