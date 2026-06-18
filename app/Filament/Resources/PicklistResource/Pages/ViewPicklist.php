<?php

namespace App\Filament\Resources\PicklistResource\Pages;

use App\Filament\Resources\PicklistResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewPicklist extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = PicklistResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
