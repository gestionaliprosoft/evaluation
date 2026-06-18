<?php

namespace App\Policies;

use App\Models\Purchase\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
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
            return $user->can('view_any_purchase::order');
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('view_purchase::order');
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
            return $user->can('create_purchase::order');
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('update_purchase::order');
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('delete_purchase::order');
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
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('delete_any_purchase::order');
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('force_delete_purchase::order');
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
            return ($user->tenant->id == (int) config('demo.demo_default_tenant_id')) ? false : $user->can('force_delete_any_purchase::order');
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('restore_purchase::order');
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
            return $user->can('restore_any_purchase::order');
        }
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('replicate_purchase::order');
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
            return $user->can('reorder_purchase::order');
        }
    }

    /**
     * Determine whether the user can download.
     */
    public function download(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('download_purchase::order');
        }
    }

    /**
     * Determine whether the user can generate pdf.
     */
    public function generatePdf(User $user, PurchaseOrder $record): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('generate_pdf_purchase::order');
        }
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMember(User $user, ?PurchaseOrder $record = null): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('manage_member_purchase::order');
        }
    }

    /**
     * Determine whether the user can process stock in.
     */
    public function processStockIn(User $user, ?PurchaseOrder $record = null): bool
    {
        if ($user->isMainTenantSuperUser()) {
            return true;
        } else {
            return $user->can('process_stock_in_purchase::order');
        }
    }
}
