<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Model;

class OpportunityRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\OpportunityRelationManager;

    protected static string $relationship = 'opportunity';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return null;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->opportunity !== null && auth()->user()->can('viewAny', Opportunity::class);
    }
}
