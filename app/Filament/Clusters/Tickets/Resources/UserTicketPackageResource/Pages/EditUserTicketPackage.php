<?php

namespace App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserTicketPackage extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = UserTicketPackageResource::class;

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
