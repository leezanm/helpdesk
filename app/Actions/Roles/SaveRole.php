<?php

namespace App\Actions\Roles;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cipta atau kemaskini peranan (label dan kebenaran).
 *
 * Kawalan kenaikan taraf (dikuatkuasakan di sini kerana tindakan Livewire
 * boleh dipanggil terus):
 *  - peranan `superadmin` tidak boleh diubah kebenarannya oleh sesiapa, dan
 *    labelnya hanya oleh pemegang `superadmin`;
 *  - selain superadmin, pengguna hanya boleh memberi atau menarik kebenaran
 *    yang dia sendiri pegang. Kebenaran lain pada peranan itu kekal seperti
 *    sedia ada.
 * `name` tidak berubah selepas peranan dicipta.
 */
class SaveRole
{
    /**
     * @param  array{name?:string,desc:string,permissions:array<int,string>}  $data
     */
    public function handle(User $actor, ?Role $role, array $data): Role
    {
        abort_unless($actor->can('manage.role'), 403);

        $isSuperadmin = $actor->hasRole('superadmin');

        if ($role?->name === 'superadmin' && ! $isSuperadmin) {
            abort(403, 'Peranan Admin Sistem tidak boleh diubah.');
        }

        return DB::transaction(function () use ($actor, $role, $data, $isSuperadmin) {
            if ($role === null) {
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'web',
                    'desc' => $data['desc'],
                ]);
                $current = [];
            } else {
                $role->desc = $data['desc'];
                $role->save();
                $current = $role->permissions->pluck('name')->all();
            }

            // Kebenaran superadmin sentiasa lengkap.
            if ($role->name === 'superadmin') {
                return $role;
            }

            $requested = Permission::whereIn('name', $data['permissions'])->pluck('name')->all();

            if (! $isSuperadmin) {
                $own = $actor->getAllPermissions()->pluck('name')->all();
                $requested = array_values(array_unique(array_merge(
                    array_intersect($requested, $own),
                    array_diff($current, $own),
                )));
            }

            $role->syncPermissions($requested);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role;
        });
    }
}
