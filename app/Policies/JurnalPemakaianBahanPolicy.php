<?php

namespace App\Policies;

use App\Models\User;
use App\Models\JurnalPemakaianBahan;
use Illuminate\Auth\Access\HandlesAuthorization;

class JurnalPemakaianBahanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('view_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('update_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('delete_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('force_delete_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('restore_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('replicate_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can confirm.
     */
    public function confirm(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('confirm_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can unconfirm.
     */
    public function unconfirm(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('unconfirm_jurnal::pemakaian::bahan');
    }

    /**
     * Determine whether the user can post to ledger.
     */
    public function postToLedger(User $user, JurnalPemakaianBahan $jurnalPemakaianBahan): bool
    {
        return $user->can('post_jurnal::pemakaian::bahan');
    }
}
