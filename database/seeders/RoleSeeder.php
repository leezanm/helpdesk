<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $role = Role::create(['name' => 'jtk', 'desc' => 'Juruteknik']);

        $role = Role::create(['name' => 'pptm', 'desc' => 'Pembantu Pegawai Teknologi Maklumat']);

        $role = Role::create(['name' => 'ptm', 'desc' => 'Pegawai Teknologi Maklumat']);

        $role = Role::create(['name' => 'pengguna', 'desc' => 'Warga KPLB']);

        $role = Role::create(['name' => 'helpdesk', 'desc' => 'Admin Sistem']);
        $role->syncPermissions(['manage.category', 'manage.user']);

        $role = Role::create(['name' => 'superadmin', 'desc' => 'Pentadbir Sitem Helpdesk']);
        $role->syncPermissions(Permission::pluck('name'));
    }
}
