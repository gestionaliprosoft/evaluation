<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Handlers;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Requests\PaginationOrganizationRequest;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Transformers\OrganizationTransformer;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;

#[Group(name: 'Organizations')]
class PaginationHandler extends Handlers
{
    public static ?string $uri = '/';

    public static ?string $resource = OrganizationResource::class;

    /**
     * List of Organization
     *
     * @return AnonymousResourceCollection
     */
    public function handler(PaginationOrganizationRequest $request)
    {
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for($query)
            ->allowedFields($this->getAllowedFields() ?? [])
            ->allowedSorts($this->getAllowedSorts() ?? [])
            ->allowedFilters($this->getAllowedFilters() ?? [])
            ->allowedIncludes($this->getAllowedIncludes() ?? [])
            ->paginate(request()->query('per_page'))
            ->appends(request()->query());

        return OrganizationTransformer::collection($query);
    }
}
