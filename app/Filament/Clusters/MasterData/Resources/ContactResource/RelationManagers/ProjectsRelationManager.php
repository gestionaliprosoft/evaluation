<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Project\ProjectProject;
use App\Traits\RelationManagers\ProjectRelationManager;
use Illuminate\Database\Eloquent\Model;

class ProjectsRelationManager extends AbstractRelationManager
{
    use ProjectRelationManager;

    protected static string $relationship = 'projects';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', ProjectProject::class);
    }
}
