<?php

namespace App\Policies;

use App\Models\Sale\SaleQuote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaleQuotePolicy
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
            return $user->can('view_any_sale::quote');
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('view_sale::quote');
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
            return $user->can('create_sale::quote');
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('update_sale::quote');
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('delete_sale::quote');
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
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('delete_any_sale::quote');
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('force_delete_sale::quote');
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
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('force_delete_any_sale::quote');
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('restore_sale::quote');
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
            return $user->can('restore_any_sale::quote');
        }
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('replicate_sale::quote');
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
            return $user->can('reorder_sale::quote');
        }
    }

    /**
     * Determine whether the user can download.
     */
    public function download(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('download_sale::quote');
        }
    }

    /**
     * Determine whether the user can generate pdf.
     */
    public function generatePdf(User $user, SaleQuote $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('generate_pdf_sale::quote');
        }
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMember(User $user, ?SaleQuote $record = null): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('manage_member_sale::quote');
        }
    }
}
