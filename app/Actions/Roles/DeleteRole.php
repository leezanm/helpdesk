<?php

namespace App\Actions\Roles;

use App\Models\User;
use App\Support\SystemRoles;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Padam peranan tambahan. Peranan sistem tidak boleh dipadam, dan peranan yang
 * masih dipegang pengguna mesti dikosongkan dahulu.
 */
class DeleteRole
{
    public function handle(User $actor, Role $role): void
    {
        abort_unless($actor->can('manage.role'), 403);

        if (SystemRoles::is($role->name)) {
            throw ValidationException::withMessages(['role' => 'Peranan sistem tidak boleh dipadam.']);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'Peranan masih dipegang oleh pengguna. Tukar peranan mereka dahulu.']);
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
