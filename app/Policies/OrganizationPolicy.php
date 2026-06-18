<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
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
            return $user->can('view_any_organization');
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('view_organization');
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('create_organization');
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('update_organization');
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('delete_organization');
        }
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('delete_any_organization');
        }
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Organization $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('force_delete_organization');
        }
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('force_delete_any_organization');
        }
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Organization $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('restore_organization');
        }
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('restore_any_organization');
        }
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Organization $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('replicate_organization');
        }
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('reorder_organization');
        }
    }

    /**
     * Determine whether the user can download.
     */
    public function download(User $user, Organization $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('download_organization');
        }
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMember(User $user, ?Organization $record = null): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('manage_member_organization');
        }
    }

    /**
     * Determine whether the user can send emails.
     */
    public function sendEmail(User $user, ?Organization $record = null): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('send_email_organization');
        }
    }
}
