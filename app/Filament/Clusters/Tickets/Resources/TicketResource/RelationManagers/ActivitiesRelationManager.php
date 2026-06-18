<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Traits\RelationManagers\ActivityRelationManager;

class ActivitiesRelationManager extends AbstractRelationManager
{
    use ActivityRelationManager;

    protected static string $relationship = 'activities';
}
