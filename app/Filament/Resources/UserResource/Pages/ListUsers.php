<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = UserResource::class;
}
