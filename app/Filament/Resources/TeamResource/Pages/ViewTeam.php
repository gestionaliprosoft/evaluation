<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewTeam extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = TeamResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
