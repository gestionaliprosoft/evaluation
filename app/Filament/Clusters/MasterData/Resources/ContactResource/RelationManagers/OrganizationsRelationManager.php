<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Models\Organization;
use App\Traits\RelationManagers\OrganizationRelationManager;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrganizationsRelationManager extends AbstractRelationManager
{
    use OrganizationRelationManager;

    protected static string $relationship = 'organizations';

    public function table(Table $table): Table
    {
        return $table
            ->columns(OrganizationResource::getColumnsComponents())
            ->filters(OrganizationResource::getFiltersComponents())
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label(__('contact.Attach Organization'))
                    ->visible(function (RelationManager $livewire) {
                        return auth()->user()->can('update', $livewire->ownerRecord);
                    })
                    ->modalHeading(__('contact.Attach Organization'))
                    ->recordSelect(fn () => Forms\Components\Select::make('recordId')
                        ->label(__('resources.OrganizationResource'))
                        ->options(Organization::getOptionsForSelect())
                        ->searchable(['name'])
                        ->multiple()
                        ->preload(),
                    )
                    ->recordSelectSearchColumns(['name'])
                    ->preloadRecordSelect(),
                Tables\Actions\CreateAction::make()
                    ->visible(auth()->user()->can('create', Organization::class))
                    ->label(__('contact.Add New Organization'))
                    ->modalHeading(__('contact.Add New Organization'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'team_id' => $livewire->ownerRecord->team_id,
                        'user_id' => $livewire->ownerRecord->user_id,
                    ])
                    ->modalWidth(MaxWidth::Full)
                    ->createAnother(false),
            ])
            ->actions([
                self::viewAction(),
                self::editAction(),
                Tables\Actions\DetachAction::make(),
                static::completeFormAction(OrganizationResource::class),
            ])
            ->bulkActions(OrganizationResource::getBulkActionsComponents());
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Organization::class);
    }
}
