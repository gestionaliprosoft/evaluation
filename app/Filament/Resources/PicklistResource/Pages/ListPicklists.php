<?php

namespace App\Filament\Resources\PicklistResource\Pages;

use App\Filament\Imports\PicklistImporter;
use App\Filament\Resources\PicklistResource;
use App\Traits\BaseListSettings;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListPicklists extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = PicklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            static::createAction(),
            ImportAction::make()
                ->importer(PicklistImporter::class)
                ->visible(auth()->user()->isMainTenantSuperUser()),
        ];
    }
}
