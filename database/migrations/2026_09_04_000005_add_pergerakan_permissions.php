<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Kebenaran untuk modul Pergerakan Pegawai.
 *
 * Seeder tidak idempoten (PermissionSeeder guna create()), jadi pangkalan data
 * sedia ada mendapat kebenaran dan pemberian peranan di sini. Migration ini
 * juga berjalan pada pemasangan baharu SEBELUM seeder — sebab itu
 * PermissionSeeder menggunakan firstOrCreate untuk kebenaran ini, dan sebab itu
 * pemberian peranan di bawah menggunakan null-safe (peranan belum wujud lagi
 * pada pemasangan baharu; RoleSeeder yang akan mengaitkannya).
 *
 * `pergerakan.manageAny` (isi/ubah untuk pegawai lain) sengaja DIPISAHKAN
 * daripada `pergerakan.manageLocked` (ubah entri yang tarikhnya sudah sampai):
 * pengurusan boleh mengisi untuk pegawai lain, tetapi hanya pentadbir helpdesk
 * boleh mengubah rekod yang sudah berlaku.
 */
return new class extends Migration
{
    /** @var array<string, array{desc:string, roles:array<int,string>}> */
    private const PERMISSIONS = [
        'pergerakan.view' => [
            'desc' => 'Lihat jadual pergerakan',
            'roles' => ['jtk', 'ptm', 'pptm', 'pengurusan', 'helpdesk.admin', 'superadmin'],
        ],
        'pergerakan.create' => [
            'desc' => 'Catat pergerakan sendiri',
            'roles' => ['jtk', 'ptm', 'pptm', 'pengurusan', 'helpdesk.admin', 'superadmin'],
        ],
        'pergerakan.manageAny' => [
            'desc' => 'Catat pergerakan semua pegawai',
            'roles' => ['pengurusan', 'helpdesk.admin', 'superadmin'],
        ],
        'pergerakan.manageLocked' => [
            'desc' => 'Kemaskini pergerakan yang sudah terkunci',
            'roles' => ['helpdesk.admin', 'superadmin'],
        ],
        'pergerakan.manageRoster' => [
            'desc' => 'Selenggara pegawai dipantau & jenis pergerakan',
            'roles' => ['helpdesk.admin', 'superadmin'],
        ],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name => $spec) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['desc' => $spec['desc']],
            );

            foreach ($spec['roles'] as $roleName) {
                // Hanya peranan yang memang ada dalam pangkalan data ini.
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                $role?->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::PERMISSIONS))
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
