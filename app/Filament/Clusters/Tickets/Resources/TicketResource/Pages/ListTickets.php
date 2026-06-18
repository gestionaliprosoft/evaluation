<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = TicketResource::class;
}
