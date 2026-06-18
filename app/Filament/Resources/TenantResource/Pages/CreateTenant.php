<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Services\TenantService;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = TenantResource::class;

    protected static bool $canCreateAnother = false;

    protected function afterCreate(): void
    {
        if ($this->data['create_new_database']) {
            $tenantService = app(TenantService::class);

            $tenantService->createNewDatabase($this->record);
        }
    }
}
