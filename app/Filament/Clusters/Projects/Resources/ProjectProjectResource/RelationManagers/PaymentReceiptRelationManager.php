<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Accounting\PaymentReceipt;
use Illuminate\Database\Eloquent\Model;

class PaymentReceiptRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\PaymentReceiptRelationManager;

    protected static string $relationship = 'paymentReceipts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', PaymentReceipt::class);
    }
}
