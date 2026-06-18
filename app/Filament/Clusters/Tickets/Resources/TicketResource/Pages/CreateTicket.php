<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTicket extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = TicketResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = Str::uuid();

        return $data;
    }
}
