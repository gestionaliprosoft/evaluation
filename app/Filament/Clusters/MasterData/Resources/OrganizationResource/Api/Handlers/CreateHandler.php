<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Handlers;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Requests\CreateOrganizationRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Rupadana\ApiService\Http\Handlers;

#[Group(name: 'Organizations')]
class CreateHandler extends Handlers
{
    public static ?string $uri = '/';

    public static ?string $resource = OrganizationResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Create Organization
     *
     * @return JsonResponse
     */
    public function handler(CreateOrganizationRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, 'Successfully Create Resource');
    }
}
