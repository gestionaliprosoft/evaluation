<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Pages;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Models\OrganizationDetail;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganization extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = OrganizationResource::class;

    protected static bool $canCreateAnother = false;

    protected function afterCreate()
    {
        self::handleAttachments();

        $data = [
            'organization_id' => $this->record->getKey(),
            'payment_method_id' => $this->data['payment_method_id'] ?? null,
        ];

        OrganizationDetail::create($data);
    }
}
