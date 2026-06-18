<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActivityScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->user()->isMainTenantSuperUser()) {
            $builder->whereNotNull('causer_id');
        } else {
            $builder->where('causer_id', auth()->user()->getKey());
        }
    }
}
