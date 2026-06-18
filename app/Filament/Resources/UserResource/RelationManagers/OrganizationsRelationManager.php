<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Organization;
use App\Traits\RelationManagers\OrganizationRelationManager;
use Illuminate\Database\Eloquent\Model;

class OrganizationsRelationManager extends AbstractRelationManager
{
    use OrganizationRelationManager;

    protected static string $relationship = 'organizations';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Organization::class);
    }
}
