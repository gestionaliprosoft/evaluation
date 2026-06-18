<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketPackageResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListTicketPackages extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = TicketPackageResource::class;
}
