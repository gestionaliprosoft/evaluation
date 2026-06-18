<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource;
use App\Models\Contact;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactsRelationManager extends AbstractRelationManager
{
    protected static string $relationship = 'contacts';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->contacts->count();
    }

    public function form(Form $form): Form
    {
        return $form->schema(ContactResource::getFormsComponents());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(ContactResource::getColumnsComponents())
            ->filters(ContactResource::getFiltersComponents())
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('user.Add Contact'))
                    ->visible(auth()->user()->can('create', User::class))
                    ->modalHeading(__('user.Add Contact'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'team_id' => $livewire->ownerRecord->team_id,
                        'user_id' => $livewire->ownerRecord->id,
                    ])
                    ->modalWidth(MaxWidth::Full)
                    ->createAnother(false),
            ])
            ->actions(array_merge(ContactResource::getActionsComponents(), [
                static::completeFormAction(ContactResource::class),
            ]))
            ->bulkActions(ContactResource::getBulkActionsComponents());
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Contact::class);
    }
}
