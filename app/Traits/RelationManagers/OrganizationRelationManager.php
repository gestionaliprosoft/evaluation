<?php

namespace App\Traits\RelationManagers;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

trait OrganizationRelationManager
{
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->organizations->count();
    }

    public function form(Form $form): Form
    {
        return $form->schema(OrganizationResource::getFormsComponents());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(OrganizationResource::getColumnsComponents())
            ->filters(OrganizationResource::getFiltersComponents())
            ->actions(array_merge(OrganizationResource::getActionsComponents(), [
                static::completeFormAction(OrganizationResource::class),
            ]))
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('user.Add Organization'))
                    ->modalHeading(__('user.Add Organization'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'team_id' => $livewire->ownerRecord->team_id,
                        'user_id' => $livewire->ownerRecord->id,
                    ])
                    ->modalWidth(MaxWidth::Full)
                    ->createAnother(false),
            ])
            ->bulkActions(OrganizationResource::getBulkActionsComponents());
    }
}
