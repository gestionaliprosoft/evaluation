<?php

namespace App\Filament\Clusters\MasterData\Resources\OrganizationResource\Api\Transformers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Organization $resource
 */
class OrganizationTransformer extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
