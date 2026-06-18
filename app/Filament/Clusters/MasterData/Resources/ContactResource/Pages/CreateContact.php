<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\Pages;

use App\Filament\Clusters\MasterData\Resources\ContactResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = ContactResource::class;

    protected static bool $canCreateAnother = false;
}
