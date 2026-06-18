<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketPackageResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListTicketPackageActivities extends ListActivities
{
    protected static string $resource = TicketPackageResource::class;
}
