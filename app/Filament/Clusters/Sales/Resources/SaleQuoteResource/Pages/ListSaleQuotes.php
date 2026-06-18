<?php

namespace App\Filament\Clusters\Sales\Resources\SaleQuoteResource\Pages;

use App\Filament\Clusters\Sales\Resources\SaleQuoteResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListSaleQuotes extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = SaleQuoteResource::class;
}
