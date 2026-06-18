<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketPackageResource;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicketPackage extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = TicketPackageResource::class;

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
