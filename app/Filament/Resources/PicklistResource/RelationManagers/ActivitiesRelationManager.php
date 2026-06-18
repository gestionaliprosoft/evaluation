<?php

namespace App\Filament\Resources\PicklistResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Traits\RelationManagers\ActivityRelationManager;

class ActivitiesRelationManager extends AbstractRelationManager
{
    use ActivityRelationManager;

    protected static string $relationship = 'activities';
}
