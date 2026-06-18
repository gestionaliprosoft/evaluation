<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class EmailMessageRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\EmailMessageRelationManager;

    protected static string $relationship = 'emails';
}
