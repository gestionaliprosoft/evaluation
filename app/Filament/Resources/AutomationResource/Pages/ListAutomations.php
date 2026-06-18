<?php

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListAutomations extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = AutomationResource::class;
}
