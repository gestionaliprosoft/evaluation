<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = ProductResource::class;
}
