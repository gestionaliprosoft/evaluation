<?php

namespace App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListUserTicketPackageActivities extends ListActivities
{
    protected static string $resource = UserTicketPackageResource::class;
}
