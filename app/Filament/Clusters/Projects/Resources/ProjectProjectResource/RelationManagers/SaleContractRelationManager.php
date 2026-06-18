<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Sale\SaleContract;
use Illuminate\Database\Eloquent\Model;

class SaleContractRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\SaleContractRelationManager;

    protected static string $relationship = 'contract';

    protected string $ticketableType = SaleContract::class;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', SaleContract::class);
    }
}
