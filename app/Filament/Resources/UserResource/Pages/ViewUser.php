<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = UserResource::class;

    protected function getJollyField()
    {
        return $this->record->name.' '.$this->record->surname;
    }
}
