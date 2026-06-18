<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class TimeRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\TimeRelationManager;

    protected static string $relationship = 'times';
}
