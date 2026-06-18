<?php

namespace App\Filament\Clusters\Products\Resources\ProductPriceResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPriceResource;
use App\Libs\UserService;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewProductPrice extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = ProductPriceResource::class;

    protected function getJollyField()
    {
        return $this->record->product->name.' '.UserService::getCurrencyPrefix().' '.$this->record->price;
    }
}
