<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class MemberRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\MemberRelationManager;

    protected static string $relationship = 'members';
}
