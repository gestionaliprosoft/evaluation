<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Pages;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListOrganizations extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = OrganizationResource::class;
}
