<?php

namespace App\Filament\Tables\Actions\Contact;

use App\Models\Contact;
use App\Models\Organization;
use App\Services\ContactService;
use Filament\Tables\Actions\Action;

class ConvertToOrganizationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'convertToOrganizationAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visible(auth()->user()->can('create', Organization::class))
            ->label(__('contact.Convert to Organization'))
            ->requiresConfirmation()
            ->form(fn (ContactService $contactService) => $contactService->getConvertForm())
            ->action(function (Contact $record, array $data, ContactService $contactService) {
                $contactService->getConvertFormAction($record, $data);
            });
    }
}
