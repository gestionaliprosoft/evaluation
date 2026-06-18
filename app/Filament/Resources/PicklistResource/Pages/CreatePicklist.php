<?php

namespace App\Filament\Resources\PicklistResource\Pages;

use App\Filament\Resources\PicklistResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;

class CreatePicklist extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = PicklistResource::class;
}
