<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Pages;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Models\OrganizationDetail;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Tables\Actions\EmailMessage\SendEmailMessageHeaderAction;
use Filament\Actions;

class EditOrganization extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = OrganizationResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            SendEmailMessageHeaderAction::make('send_email_message'),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function afterSave()
    {
        self::handleAttachments();

        $data = [
            'organization_id' => $this->data['id'],
            'payment_method_id' => $this->data['payment_method_id'] ?? null,
        ];

        OrganizationDetail::updateOrCreate(
            ['organization_id' => $this->data['id']],
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
