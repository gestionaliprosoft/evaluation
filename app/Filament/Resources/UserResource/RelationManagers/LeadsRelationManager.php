<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Lead;
use App\Traits\RelationManagers\LeadRelationManager;
use Illuminate\Database\Eloquent\Model;

class LeadsRelationManager extends AbstractRelationManager
{
    use LeadRelationManager;

    protected static string $relationship = 'leads';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Lead::class);
    }
}
