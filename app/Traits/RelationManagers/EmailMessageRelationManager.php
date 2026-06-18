<?php

namespace App\Traits\RelationManagers;

use App\Filament\Tables\Actions\EmailMessage\ReSendEmailMessageAction;
use App\Models\Email\EmailMessage;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

trait EmailMessageRelationManager
{
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->emails->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('#Id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_id')
                    ->formatStateUsing(fn (EmailMessage $record) => $record->sender->name.' '.$record->sender->surname)
                    ->description(fn (EmailMessage $record) => $record->sender->email)
                    ->label(__('email-message.sent_by'))
                    ->wrap()
                    ->placeholder('System') // Fallback if user was deleted
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('recipient_name')
                    ->label(__('email-message.recipient'))
                    ->wrap()
                    ->description(fn ($record) => $record->recipient_email)
                    ->searchable(['recipient_name', 'recipient_email']),

                Tables\Columns\TextColumn::make('subject')
                    ->description(fn (EmailMessage $record) => __('email-message.use_a_model').': '.$record->emailTemplate->name)
                    ->wrap()
                    ->label(__('email-message.subject'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->html()
                    ->label(__('email-message.message')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('email-message.sent_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ReSendEmailMessageAction::make('resend_email'),
                ]),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('sendEmail', $ownerRecord);
    }
}
