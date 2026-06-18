<?php

namespace App\Filament\Resources\TeamResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Traits\RelationManagers\UserRelationManager;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends AbstractRelationManager
{
    use UserRelationManager;

    protected static string $relationship = 'users';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(UserResource::getColumnsComponents())
            ->filters(UserResource::getFiltersComponents())
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(auth()->user()->can('create', User::class))
                    ->label(__('team.Add User'))
                    ->modalHeading(__('team.Add User'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'team_id' => $livewire->ownerRecord->id,
                    ])
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->createAnother(false),
            ])
            ->actions(array_merge(UserResource::getActionsComponents(), [
                static::completeFormAction(UserResource::class),
            ]))
            ->bulkActions(UserResource::getBulkActionsComponents());
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', User::class);
    }
}
