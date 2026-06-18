<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketPackageResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketPackage extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = TicketPackageResource::class;
}
