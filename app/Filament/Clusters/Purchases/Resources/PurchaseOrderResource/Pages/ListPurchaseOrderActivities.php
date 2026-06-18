<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListPurchaseOrderActivities extends ListActivities
{
    protected static string $resource = PurchaseOrderResource::class;
}
