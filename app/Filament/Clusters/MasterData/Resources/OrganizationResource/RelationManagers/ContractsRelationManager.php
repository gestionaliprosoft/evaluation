<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Sale\SaleContract;
use App\Traits\RelationManagers\ContractRelationManager;
use Illuminate\Database\Eloquent\Model;

class ContractsRelationManager extends AbstractRelationManager
{
    use ContractRelationManager;

    protected static string $relationship = 'contracts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', SaleContract::class);
    }
}
