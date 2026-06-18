<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Handlers;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Requests\UpdateOrganizationRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Rupadana\ApiService\Http\Handlers;

#[Group(name: 'Organizations')]
class UpdateHandler extends Handlers
{
    public static ?string $uri = '/{id}';

    public static ?string $resource = OrganizationResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Update Organization
     *
     * @return JsonResponse
     */
    public function handler(UpdateOrganizationRequest $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (! $model) {
            return static::sendNotFoundResponse();
        }

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, 'Successfully Update Resource');
    }
}
