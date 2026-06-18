<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Product\ProductPrice;
use App\Traits\RelationManagers\ProductPriceRelationManager;
use Illuminate\Database\Eloquent\Model;

class PricesRelationManager extends AbstractRelationManager
{
    use ProductPriceRelationManager;

    protected static string $relationship = 'productPrices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', ProductPrice::class);
    }
}
