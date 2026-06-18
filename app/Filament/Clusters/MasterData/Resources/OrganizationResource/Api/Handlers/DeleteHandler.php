<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Handlers;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;

#[Group(name: 'Organizations')]
class DeleteHandler extends Handlers
{
    public static ?string $uri = '/{id}';

    public static ?string $resource = OrganizationResource::class;

    public static function getMethod()
    {
        return Handlers::DELETE;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Delete Organization
     *
     * @return JsonResponse
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (! $model) {
            return static::sendNotFoundResponse();
        }

        $model->delete();

        return static::sendSuccessResponse($model, 'Successfully Delete Resource');
    }
}
