<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\Pages;

use App\Filament\Clusters\MasterData\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Organization;
use App\Services\ContactService;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Tables\Actions\EmailMessage\SendEmailMessageHeaderAction;

class EditContact extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = ContactResource::class;

    protected function getJollyField()
    {
        return $this->record->first_name.' '.$this->record->last_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('convert')
                ->visible(auth()->user()->can('create', Organization::class))
                ->label(__('contact.Convert to Organization'))
                ->requiresConfirmation()
                ->form(fn (ContactService $contactService) => $contactService->getConvertForm())
                ->action(function (Contact $record, array $data, ContactService $contactService) {
                    $contactService->getConvertFormAction($record, $data);
                }),
            SendEmailMessageHeaderAction::make('send_email_message'),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
