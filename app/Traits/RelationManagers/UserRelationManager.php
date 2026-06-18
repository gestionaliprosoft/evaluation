<?php

namespace App\Traits\RelationManagers;

use App\Filament\Resources\UserResource;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;

trait UserRelationManager
{
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->users->count();
    }

    public function form(Form $form): Form
    {
        return $form->schema(UserResource::getFormsComponents());
    }
}
