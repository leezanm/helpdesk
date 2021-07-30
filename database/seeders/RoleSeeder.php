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
        $role = Role::firstOrCreate(['name' => 'jtk'],['desc' => 'Juruteknik']);
        $role->syncPermissions(['aduan.create' ,'aduan.all', 'aduan.updateTask']);

        $role = Role::firstOrCreate(['name' => 'pptm'],['desc' => 'Penolong Pegawai Teknologi Maklumat']);
        $role->syncPermissions(['aduan.create', 'aduan.all', 'aduan.updateTask']);

        $role = Role::firstOrCreate(['name' => 'ptm'],['desc' => 'Pegawai Teknologi Maklumat']);
        $role->syncPermissions(['aduan.create', 'aduan.all', 'aduan.updateTask']);

        $role = Role::firstOrCreate(['name' => 'pengguna'],['desc' => 'Warga KPLB']);
        $role->syncPermissions(['aduan.create']);

        $role = Role::firstOrCreate(['name' => 'helpdesk.admin'], ['desc' => 'Admin Sistem']);
        $role->syncPermissions(['manage.category', 'manage.user', 'aduan.all', 'aduan.create', 'aduan.adminTask', 'view.report', 'dashboard.viewGraph']);

        $role = Role::firstOrCreate(['name' => 'superadmin'], ['desc' => 'Superadmin']);
        $role->syncPermissions(Permission::pluck('name'));

        $role = Role::firstOrCreate(['name' => 'pengurusan'], ['desc' => 'Pengurusan']);
        $role->syncPermissions(['view.report', 'dashboard.viewGraph']);
    }
}
