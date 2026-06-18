<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Sale\SaleQuote;
use App\Traits\RelationManagers\QuoteRelationManager;
use Illuminate\Database\Eloquent\Model;

class QuotesRelationManager extends AbstractRelationManager
{
    use QuoteRelationManager;

    protected static string $relationship = 'quotes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', SaleQuote::class);
    }
}
