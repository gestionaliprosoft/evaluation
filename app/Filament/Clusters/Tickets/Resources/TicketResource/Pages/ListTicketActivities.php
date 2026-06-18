<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListTicketActivities extends ListActivities
{
    protected static string $resource = TicketResource::class;
}
