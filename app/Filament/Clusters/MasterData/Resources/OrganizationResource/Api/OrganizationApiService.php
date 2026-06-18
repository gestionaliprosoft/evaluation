<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use Rupadana\ApiService\ApiService;

class OrganizationApiService extends ApiService
{
    protected static ?string $resource = OrganizationResource::class;

    protected static ?string $groupRouteName = 'organizations';

    public static function handlers(): array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class,
        ];

    }
}
