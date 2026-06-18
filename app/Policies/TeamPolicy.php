<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return auth()->user()->hasRole(['super_admin'])
                ? $user->can('view_any_team')
                : false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return auth()->user()->hasRole(['super_admin'])
                ? $user->can('view_team')
                : false;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return auth()->user()->hasRole(['super_admin']) && $user->tenant->id !== (int) config('demo.demo_default_tenant_id')
                ? $user->can('update_team')
                : false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $record): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Team $record): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Team $record): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Team $record): bool
    {
        return $user->isMainTenantSuperUser();
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->isMainTenantSuperUser();
    }
}
