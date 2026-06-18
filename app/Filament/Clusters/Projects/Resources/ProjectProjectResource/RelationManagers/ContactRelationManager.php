<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class ContactRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\ContactRelationManager;

    protected static string $relationship = 'contacts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Contact::class);
    }
}
