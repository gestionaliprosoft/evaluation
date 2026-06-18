<?php

namespace App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListUserTicketPackages extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = UserTicketPackageResource::class;
}
