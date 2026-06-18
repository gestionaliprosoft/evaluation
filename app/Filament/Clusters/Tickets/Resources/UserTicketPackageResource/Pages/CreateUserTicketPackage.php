<?php

namespace App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource\Pages;

use App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;

class CreateUserTicketPackage extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = UserTicketPackageResource::class;
}
