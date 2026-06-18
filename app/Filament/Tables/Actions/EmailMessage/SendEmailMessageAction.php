<?php

namespace App\Filament\Tables\Actions\EmailMessage;

use App\Models\Email\EmailTemplate;
use App\Services\EmailMessageService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class SendEmailMessageAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sendNotification';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('email-message.send_email'))
            ->icon('heroicon-o-envelope')
            ->color('success')
            ->visible(fn ($record) => $record && filled($record->getRecipientEmail()))

            ->form([
                Placeholder::make('recipient_preview')
                    ->label(__('email-message.recipient'))
                    ->content(fn ($record) => "{$record->getRecipientName()} <{$record->getRecipientEmail()}>"),

                // LOAD TEMPLATES FROM DATABASE
                Select::make('template_id')
                    ->label(__('email-message.use_a_model'))
                    ->options(function () {
                        // Only load templates belonging to the current user's team
                        return EmailTemplate::where('team_id', auth()->user()->team_id)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder(__('email-message.send_a_free_email'))
                    ->live()

                    // PARSE DATABASE VALUES INTO THE FORM
                    ->afterStateUpdated(function ($state, callable $set, $record) {
                        if (blank($state) || ! $record) {
                            return;
                        }

                        // Retrieve the template from DB
                        $template = EmailTemplate::find($state);
                        if (! $template) {
                            return;
                        }

                        // Dynamic Resource routing for the {{url}} placeholder
                        $resource = Filament::getCurrentPanel()
                            ->getModelResource($record::class);

                        $recordUrl = '#';
                        if ($resource) {
                            $recordUrl = $resource::getUrl('edit', ['record' => $record]);
                        }

                        // Placeholder mapping
                        $placeholders = [
                            '{{name}}' => $record->getRecipientName(),
                            '{{email}}' => $record->getRecipientEmail(),
                            '{{url}}' => $recordUrl,
                        ];

                        // Parse placeholders inside both Subject and Message columns
                        $parsedSubject = str_replace(array_keys($placeholders), array_values($placeholders), $template->subject);
                        $parsedContent = str_replace(array_keys($placeholders), array_values($placeholders), $template->message);

                        $set('subject', trim($parsedSubject));
                        $set('message', trim($parsedContent));
                    }),

                TextInput::make('subject')
                    ->label(__('email-message.subject'))
                    ->required()
                    ->maxLength(255),

                RichEditor::make('message')
                    ->label(__('email-message.message'))
                    ->required(),
            ])

            ->action(function ($record, array $data, EmailMessageService $emailMessageService): void {
                $email = $record->getRecipientEmail();
                $name = $record->getRecipientName();

                try {
                    // CALL CENTRALIZED SERVICE
                    $emailMessageService->sendAndLog(
                        emailable: $record,
                        recipientEmail: $email,
                        recipientName: $name,
                        subject: $data['subject'],
                        messageBody: $data['message'],
                        templateId: $data['template_id'],
                    );

                    Notification::make()
                        ->title(__('email-message.sent_with_success'))
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title(__('email-message.error_while_sending'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}
