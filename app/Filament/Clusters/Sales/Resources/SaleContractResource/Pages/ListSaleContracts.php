<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\Pages;

use App\Filament\Clusters\Sales\Resources\SaleContractResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListSaleContracts extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = SaleContractResource::class;
}
