<?php

namespace App\Filament\Clusters\Products\Resources\ProductPricelistResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPricelistResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListProductPricelists extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = ProductPricelistResource::class;
}
