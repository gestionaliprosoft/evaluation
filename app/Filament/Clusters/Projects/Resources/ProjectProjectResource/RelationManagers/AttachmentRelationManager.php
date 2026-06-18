<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;

class AttachmentRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\AttachmentRelationManager;

    protected static string $relationship = 'attachments';
}
