<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailTemplate extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = EmailTemplateResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
