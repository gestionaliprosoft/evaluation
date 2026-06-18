<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class EmailMessageRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\EmailMessageRelationManager;

    protected static string $relationship = 'emails';
}
