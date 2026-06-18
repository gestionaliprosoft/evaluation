<?php

namespace App\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TeamScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (in_array('team_id', $model->getFillable()) && auth()->check()) {
            // apply scope

            // get user tenant #id
            $tenantId = auth()->user()->tenant_id;

            // get user team #id
            $teamIds = User::where('tenant_id', $tenantId)->pluck('team_id')->unique();

            if (auth()->user()->hasRole(['super_admin'])) {
                $builder->whereIn('team_id', $teamIds)->orWhereNull('team_id');
            } else {
                $builder->whereBelongsTo(auth()->user()->team);
            }
        }
    }
}
