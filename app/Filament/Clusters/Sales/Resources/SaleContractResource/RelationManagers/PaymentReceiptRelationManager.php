<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class PaymentReceiptRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\PaymentReceiptRelationManager;

    protected static string $relationship = 'paymentReceipts';
}
