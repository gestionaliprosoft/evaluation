<?php

namespace App\Filament\Clusters\Sales\Resources\SaleQuoteResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class SaleContractRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\SaleContractRelationManager;

    protected static string $relationship = 'contract';

    protected string $ticketableType = 'App\\Models\\Sale\\SaleContract';
}
