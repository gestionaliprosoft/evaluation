<?php

namespace App\Traits\RelationManagers;

use App\Libs\UserService;
use App\Models\ModuleMember;
use App\Models\User;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

trait MemberRelationManager
{
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->members->count();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label(__('resources.UserResources'))
                ->options(function () {
                    $user = $this->getOwnerRecord()->user_id ? User::find($this->getOwnerRecord()->user_id) : null;

                    return UserService::getAllowedUsers($user);
                })
                ->afterStateHydrated(function (RelationManager $livewire, Set $set) {
                    $record = $livewire->getOwnerRecord();
                    $members = $record->members->map(function (ModuleMember $record) {
                        return $record->user_id;
                    });

                    $set('user_id', $members->toArray());
                })
                ->multiple()
                ->required()
                ->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('user.avatar_url')
                    ->label('')
                    ->circular(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Name')),
                Tables\Columns\TextColumn::make('user.surname')
                    ->label(__('Surname')),
                Tables\Columns\TextColumn::make('user.email')
                    ->label(__('Email')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('user.Member Since'))
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('user.Add Member'))
                    ->visible(fn (RelationManager $livewire) => auth()->user()->can('update', $livewire->ownerRecord))
                    ->modalHeading(__('user.Add Member'))
                    ->modalSubmitAction(fn (StaticAction $action) => $action->label(__('Add')))
                    ->createAnother(false)
                    ->before(function (CreateAction $action, RelationManager $livewire, $data) {
                        foreach ($data['user_id'] as $member) {
                            if (! $livewire->ownerRecord->members->contains('user_id', $member)) {
                                $memberable = new ModuleMember;
                                $memberable->memberable_id = $livewire->ownerRecord->id;
                                $memberable->memberable_type = $livewire->ownerRecord::class;
                                $memberable->user_id = $member;

                                $livewire->ownerRecord->members()->save($memberable);
                            }
                        }

                        $action->cancel();
                        $action->halt();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('detachMember')
                    ->requiresConfirmation()
                    ->label(__('Detach'))
                    ->visible(fn (RelationManager $livewire) => auth()->user()->can('update', $livewire->ownerRecord))
                    ->action(function (Model $record) {
                        $record->delete();

                        Notification::make()
                            ->title(__('user.Member Has Been Detached'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('manageMember', $ownerRecord);
    }
}
