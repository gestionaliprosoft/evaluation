<?php

namespace App\Filament\Resources\PicklistResource\Pages;

use App\Filament\Resources\PicklistResource;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPicklist extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = PicklistResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
