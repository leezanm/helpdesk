<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Filing an aduan on behalf of someone (wakil) is restricted to users of the
 * SAME bahagian. The exception: IT support staff serve the whole ministry, so
 * juruteknik (jtk), Pegawai Teknologi Maklumat (ptm), Penolong Pegawai
 * Teknologi Maklumat (pptm) and helpdesk officers (helpdesk.admin) may
 * represent a user from any bahagian.
 *
 * That exception used to ride on aduan.adminTask, which only helpdesk.admin and
 * superadmin hold — leaving jtk/ptm/pptm unable to do it. It now has its own
 * permission, aduan.representAny, so the right to represent across bahagian can
 * be granted without granting admin actions on an aduan.
 *
 * Seeders are not idempotent (PermissionSeeder uses create()), so existing
 * databases get the permission and the grants here.
 */
return new class extends Migration
{
    private const PERMISSION = 'aduan.representAny';

    /** Roles that receive the permission; superadmin holds every permission. */
    private const ROLES = ['jtk', 'ptm', 'pptm', 'helpdesk.admin', 'superadmin'];

    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => self::PERMISSION, 'guard_name' => 'web'],
            ['desc' => 'Wakil aduan pengguna bahagian lain'],
        );

        foreach (self::ROLES as $name) {
            // Only touch roles this database actually has.
            $role = Role::where('name', $name)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
