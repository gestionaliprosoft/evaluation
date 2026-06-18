<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Password\PasswordAccount;
use App\Traits\RelationManagers\PasswordAccountRelationManager;
use Illuminate\Database\Eloquent\Model;

class PasswordAccountsRelationManager extends AbstractRelationManager
{
    use PasswordAccountRelationManager;

    protected static string $relationship = 'passwordAccounts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->passwordAccounts->isNotEmpty() && auth()->user()->can('viewAny', PasswordAccount::class);
    }
}
