<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Pages;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = OrganizationResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
