<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Handlers;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Transformers\OrganizationTransformer;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;

#[Group(name: 'Organizations')]
class DetailHandler extends Handlers
{
    public static ?string $uri = '/{id}';

    public static ?string $resource = OrganizationResource::class;

    /**
     * Show Organization
     *
     * @return OrganizationTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');

        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (! $query) {
            return static::sendNotFoundResponse();
        }

        return new OrganizationTransformer($query);
    }
}
