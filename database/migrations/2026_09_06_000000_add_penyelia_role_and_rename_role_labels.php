<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Struktur peranan baharu operasi helpdesk:
 *
 *  - superadmin      -> label "Admin Sistem"   (developer yang menjaga sistem)
 *  - penyelia (baru) -> "Penyelia"             (mengawasi keseluruhan operasi helpdesk)
 *  - helpdesk.admin  -> label "Admin Helpdesk" (menerima & mengagihkan aduan)
 *  - pengguna        -> label "Warga KKDW"     (dahulu "Warga KPLB")
 *
 * Penyelia memegang semua kebenaran helpdesk.admin, ditambah manage.user dan
 * manage.role. Admin Helpdesk tidak lagi memegang manage.user: hanya Penyelia
 * dan Admin Sistem boleh mengurus pengguna.
 *
 * Seeder tidak boleh dijalankan semula pada pangkalan data sedia ada, jadi
 * perubahan dibuat di sini. Migrasi ini juga berjalan pada pemasangan baharu
 * SEBELUM RoleSeeder, ketika peranan belum wujud; RoleSeeder yang menyusul.
 * Label hanya ditukar jika masih bernilai lama supaya suntingan manual
 * tidak ditimpa.
 */
return new class extends Migration
{
    private const RELABEL = [
        'superadmin' => ['Superadmin', 'Admin Sistem'],
        'helpdesk.admin' => ['Admin Sistem', 'Admin Helpdesk'],
        'pengguna' => ['Warga KPLB', 'Warga KKDW'],
    ];

    public function up(): void
    {
        foreach (self::RELABEL as $name => [$from, $to]) {
            Role::where('name', $name)->where('guard_name', 'web')->where('desc', $from)->update(['desc' => $to]);
        }

        $helpdesk = Role::where('name', 'helpdesk.admin')->where('guard_name', 'web')->first();

        if ($helpdesk) {
            $penyelia = Role::firstOrCreate(
                ['name' => 'penyelia', 'guard_name' => 'web'],
                ['desc' => 'Penyelia'],
            );
            $penyelia->syncPermissions(
                $helpdesk->permissions->pluck('name')->merge(['manage.user', 'manage.role'])->unique()->all()
            );

            $helpdesk->revokePermissionTo('manage.user');
        }

        $this->updateNotificationRoles(fn (array $roles) => in_array('helpdesk.admin', $roles, true) && ! in_array('penyelia', $roles, true)
            ? [...$roles, 'penyelia']
            : $roles);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::RELABEL as $name => [$from, $to]) {
            Role::where('name', $name)->where('guard_name', 'web')->where('desc', $to)->update(['desc' => $from]);
        }

        Role::where('name', 'helpdesk.admin')->where('guard_name', 'web')->first()?->givePermissionTo('manage.user');

        $this->updateNotificationRoles(fn (array $roles) => array_values(array_diff($roles, ['penyelia'])));

        // Pengguna yang memegang penyelia kehilangan peranan itu melalui cascade.
        Role::where('name', 'penyelia')->where('guard_name', 'web')->first()?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function updateNotificationRoles(callable $change): void
    {
        foreach (DB::table('notification_settings')->select('id', 'roles')->get() as $row) {
            $roles = json_decode((string) $row->roles, true);

            if (! is_array($roles)) {
                continue;
            }

            $new = array_values($change($roles));

            if ($new !== $roles) {
                DB::table('notification_settings')->where('id', $row->id)
                    ->update(['roles' => json_encode($new), 'updated_at' => now()]);
            }
        }
    }
};
