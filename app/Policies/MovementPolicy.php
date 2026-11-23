<?php

namespace App\Policies;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Siapa boleh mengubah entri pergerakan.
 *
 * Peraturan tempoh (D12): pegawai boleh edit/hapus entri MILIKNYA SENDIRI
 * hanya selagi tarikh entri belum sampai — paling lewat sehari sebelum tarikh
 * mula. Sebaik tarikh itu sampai, entri terkunci dan hanya pemegang
 * `pergerakan.manageLocked` (helpdesk.admin) boleh mengubahnya.
 *
 * Nota: superadmin sudah diberi segalanya melalui hook Gate::before dalam
 * AuthServiceProvider, jadi ia tidak diulang di sini.
 */
class MovementPolicy
{
    use HandlesAuthorization;

    /**
     * Cipta entri untuk seorang pegawai roster.
     *
     * Tarikh lepas dibenarkan supaya rekod tertinggal boleh dilengkapkan (D13);
     * entri itu kemudian terus terkunci untuk edit/hapus.
     */
    public function createFor(User $user, MonitoredOfficer $officer): bool
    {
        if (! $officer->is_active) {
            return false;
        }

        return $officer->belongsToUser($user)
            ? $user->can('pergerakan.create')
            : $user->can('pergerakan.manageAny');
    }

    public function update(User $user, Movement $movement): bool
    {
        if ($movement->isLocked()) {
            return $user->can('pergerakan.manageLocked');
        }

        return $movement->officer->belongsToUser($user)
            ? $user->can('pergerakan.create')
            : $user->can('pergerakan.manageAny');
    }

    /** Syarat hapus sama dengan syarat edit. */
    public function delete(User $user, Movement $movement): bool
    {
        return $this->update($user, $movement);
    }
}
