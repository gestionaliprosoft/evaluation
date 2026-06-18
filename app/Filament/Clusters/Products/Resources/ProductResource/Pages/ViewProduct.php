<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = ProductResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
