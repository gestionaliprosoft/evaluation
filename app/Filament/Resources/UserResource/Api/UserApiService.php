<?php

namespace App\Filament\Resources\UserResource\Api;

use App\Filament\Resources\UserResource;
use Rupadana\ApiService\ApiService;

class UserApiService extends ApiService
{
    protected static ?string $resource = UserResource::class;

    protected static ?string $groupRouteName = 'users';

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
