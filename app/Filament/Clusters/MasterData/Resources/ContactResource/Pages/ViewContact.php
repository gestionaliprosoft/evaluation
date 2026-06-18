<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\Pages;

use App\Filament\Clusters\MasterData\Resources\ContactResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewContact extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = ContactResource::class;

    protected function getJollyField()
    {
        return $this->record->first_name.' '.$this->record->last_name;
    }
}
