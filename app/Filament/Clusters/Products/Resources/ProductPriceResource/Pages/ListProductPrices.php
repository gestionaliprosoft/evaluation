<?php

namespace App\Filament\Clusters\Products\Resources\ProductPriceResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPriceResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListProductPrices extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = ProductPriceResource::class;
}
