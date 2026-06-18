<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = EmailTemplateResource::class;
}
