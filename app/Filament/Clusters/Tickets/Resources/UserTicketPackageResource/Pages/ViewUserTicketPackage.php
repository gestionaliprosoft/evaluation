<?php

namespace App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewUserTicketPackage extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = UserTicketPackageResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
