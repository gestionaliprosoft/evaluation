<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = TeamResource::class;
}
