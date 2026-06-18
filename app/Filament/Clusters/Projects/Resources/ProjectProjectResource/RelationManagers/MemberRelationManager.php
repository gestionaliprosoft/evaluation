<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class MemberRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\MemberRelationManager;

    protected static string $relationship = 'members';
}
