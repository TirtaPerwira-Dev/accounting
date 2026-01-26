<?php

namespace App\Policies;

use App\Models\User;
use App\Models\JurnalRekeningAir;
use Illuminate\Auth\Access\HandlesAuthorization;

class JurnalRekeningAirPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('view_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('update_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('delete_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('force_delete_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('restore_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('replicate_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can confirm.
     */
    public function confirm(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('confirm_jurnal::rekening::air');
    }

    /**
     * Determine whether the user can unconfirm.
     */
    public function unconfirm(User $user, JurnalRekeningAir $jurnalRekeningAir): bool
    {
        return $user->can('unconfirm_jurnal::rekening::air');
    }
}
