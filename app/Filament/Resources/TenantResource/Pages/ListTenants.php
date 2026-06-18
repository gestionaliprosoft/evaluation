<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = TenantResource::class;
}
