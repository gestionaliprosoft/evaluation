<?php

namespace App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class AttachmentRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\AttachmentRelationManager;

    protected static string $relationship = 'attachments';
}
