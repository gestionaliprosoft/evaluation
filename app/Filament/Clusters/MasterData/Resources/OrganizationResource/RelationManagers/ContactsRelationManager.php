<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Contact;
use App\Traits\RelationManagers\ContactRelationManager;
use Illuminate\Database\Eloquent\Model;

class ContactsRelationManager extends AbstractRelationManager
{
    use ContactRelationManager;

    protected static string $relationship = 'contacts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Contact::class);
    }
}
