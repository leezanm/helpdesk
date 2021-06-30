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
        $role->syncPermissions(['aduan.create' ,'aduan.all', 'aduan.updateTask']);

        $role = Role::create(['name' => 'pptm', 'desc' => 'Pembantu Pegawai Teknologi Maklumat']);
        $role->syncPermissions(['aduan.create', 'aduan.all', 'aduan.updateTask']);

        $role = Role::create(['name' => 'ptm', 'desc' => 'Pegawai Teknologi Maklumat']);
        $role->syncPermissions(['aduan.create', 'aduan.all', 'aduan.updateTask']);

        $role = Role::create(['name' => 'pengguna', 'desc' => 'Warga KPLB']);
        $role->syncPermissions(['aduan.create']);

        $role = Role::create(['name' => 'helpdesk.admin', 'desc' => 'Admin KPLB Helpdesk']);
        $role->syncPermissions(['manage.category', 'manage.user', 'aduan.all', 'aduan.create', 'aduan.assignTask', 'view.report', 'dashboard.viewGraph']);

        $role = Role::create(['name' => 'superadmin', 'desc' => 'Jurutadbir Sistem']);
        $role->syncPermissions(Permission::pluck('name'));
    }
}
