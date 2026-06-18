<?php

namespace App\Filament\Clusters\Products\Resources\ProductPriceResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductPriceResource;
use App\Libs\UserService;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\EditRecord;

class EditProductPrice extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = ProductPriceResource::class;

    protected function getJollyField()
    {
        return $this->record->product->name.' '.UserService::getCurrencyPrefix().' '.$this->record->price;
    }
}
