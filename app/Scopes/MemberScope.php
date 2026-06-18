<?php

namespace App\Scopes;

use App\Models\ModuleMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log; // Added for the log

class MemberScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Standard users see owned or memberable records.
     * Super admins see all records.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = auth()->user();

        // Bypass check for Super Admins
        if ($user->hasRole(['super_admin'])) {
            return;
        }

        // Check if the model has the 'user_id' field and if the trait flag is active
        $hasUserId = in_array('user_id', $model->getFillable());
        $hasMembers = method_exists($model, 'hasMembersRelation') && $model::hasMembersRelation();

        // If the model has neither ownership columns nor members, skip filtering to prevent empty results
        if (! $hasUserId && ! $hasMembers) {
            return;
        }

        // Encapsulate queries to isolate OR operations safely
        $builder->where(function (Builder $query) use ($user, $model, $hasUserId, $hasMembers) {

            // Resolve the pivot table name dynamically from the ModuleMember model instance
            $pivotTable = (new ModuleMember)->getTable();

            if ($hasUserId && $hasMembers) {
                // Model has both. Show records if user is the owner OR a member.
                $query->where($model->qualifyColumn('user_id'), $user->getKey())
                    ->orWhereHas('members', function (Builder $memberQuery) use ($user, $pivotTable) {
                        return $memberQuery->where("{$pivotTable}.user_id", $user->getKey());
                    });
            } elseif ($hasUserId) {
                // Model only has user_id. Show only owned records.
                return $query->where($model->qualifyColumn('user_id'), $user->getKey());
            } elseif ($hasMembers) {
                // Model only has members. Show only records where user is a member.
                return $query->whereHas('members', function (Builder $memberQuery) use ($user, $pivotTable) {
                    $memberQuery->where("{$pivotTable}.user_id", $user->getKey());
                });
            }
        });
    }
}
