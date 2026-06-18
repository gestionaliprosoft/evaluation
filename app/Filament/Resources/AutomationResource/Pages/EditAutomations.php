<?php

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\EditRecord;

class EditAutomations extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = AutomationResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
