<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Traits\RelationManagers\ActivityRelationManager;

class ActivitiesRelationManager extends AbstractRelationManager
{
    use ActivityRelationManager;

    protected static string $relationship = 'activities';
}
