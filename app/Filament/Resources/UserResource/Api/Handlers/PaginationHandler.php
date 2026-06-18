<?php

namespace App\Filament\Resources\UserResource\Api\Handlers;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Api\Requests\PaginationUserRequest;
use App\Filament\Resources\UserResource\Api\Transformers\UserTransformer;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;

#[Group(name: 'Users')]
class PaginationHandler extends Handlers
{
    public static ?string $uri = '/';

    public static ?string $resource = UserResource::class;

    /**
     * List of User
     *
     * @return AnonymousResourceCollection
     */
    public function handler(PaginationUserRequest $request)
    {
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for($query)
            ->allowedFields($this->getAllowedFields() ?? [])
            ->allowedSorts($this->getAllowedSorts() ?? [])
            ->allowedFilters($this->getAllowedFilters() ?? [])
            ->allowedIncludes($this->getAllowedIncludes() ?? [])
            ->paginate(request()->query('per_page'))
            ->appends(request()->query());

        return UserTransformer::collection($query);
    }
}
