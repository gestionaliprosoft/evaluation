<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = PurchaseOrderResource::class;
}
