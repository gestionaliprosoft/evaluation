<?php

namespace App\Filament\Clusters\Products\Resources\ProductPriceResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPriceResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;

class CreateProductPrice extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = ProductPriceResource::class;
}
