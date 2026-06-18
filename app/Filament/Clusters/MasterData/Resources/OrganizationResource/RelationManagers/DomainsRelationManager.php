<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Domain\DomainDomain;
use App\Traits\RelationManagers\DomainRelationManager;
use Illuminate\Database\Eloquent\Model;

class DomainsRelationManager extends AbstractRelationManager
{
    use DomainRelationManager;

    protected static string $relationship = 'domains';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', DomainDomain::class);
    }
}
