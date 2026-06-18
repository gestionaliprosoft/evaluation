<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Traits\RelationManagers\UserRelationManager;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                Tables\Actions\AssociateAction::make()
                    ->label(__('tenant.Associate User'))
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        return User::query()->where('tenant_id', null);
                    })
                    ->recordSelectSearchColumns(['name', 'surname'])
                    ->modalHeading(__('tenant.Associate User'))
                    ->modalSubheading(__('tenant.Chosen User will be Associated with this Tenant'))
                    ->preloadRecordSelect()
                    ->recordTitle(function ($record) {
                        return $record->name.' '.$record->surname.' (Team: '.$record->team->name.')';
                    })
                    ->recordTitleAttribute('surname'),
                Tables\Actions\CreateAction::make()
                    ->visible(auth()->user()->can('create', User::class))
                    ->label(__('tenant.Add User'))
                    ->modalHeading(__('tenant.Add User'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'team_id' => '',
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
