<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\Pages;

use App\Filament\Clusters\MasterData\Resources\ContactResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = ContactResource::class;
}
