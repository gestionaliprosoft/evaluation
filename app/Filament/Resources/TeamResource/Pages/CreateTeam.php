<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = TeamResource::class;

    protected static bool $canCreateAnother = false;
}
