<?php

namespace App\Filament\Resources\UserResource\Api\Handlers;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Api\Requests\CreateUserRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Rupadana\ApiService\Http\Handlers;

#[Group(name: 'Users')]
class CreateHandler extends Handlers
{
    public static ?string $uri = '/';

    public static ?string $resource = UserResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Create User
     *
     * @return JsonResponse
     */
    public function handler(CreateUserRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, 'Successfully Create Resource');
    }
}
