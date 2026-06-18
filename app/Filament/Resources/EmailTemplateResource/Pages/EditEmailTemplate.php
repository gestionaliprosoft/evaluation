<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = EmailTemplateResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
