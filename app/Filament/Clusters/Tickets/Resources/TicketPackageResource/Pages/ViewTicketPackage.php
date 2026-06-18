<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketPackageResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewTicketPackage extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = TicketPackageResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
