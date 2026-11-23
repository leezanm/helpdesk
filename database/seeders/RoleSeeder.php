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
        // IT support staff (juruteknik, PTM, PPTM) and helpdesk officers serve
        // the whole ministry, so they hold aduan.representAny — the exception
        // that lets them file an aduan on behalf of a user from any bahagian.
        $role = Role::firstOrCreate(['name' => 'jtk'],['desc' => 'Juruteknik']);
        $role->syncPermissions(['aduan.create' ,'aduan.all', 'aduan.updateTask', 'aduan.representAny', 'pergerakan.view', 'pergerakan.create']);

        $role = Role::firstOrCreate(['name' => 'pptm'],['desc' => 'Penolong Pegawai Teknologi Maklumat']);
        $role->syncPermissions(['aduan.create', 'aduan.all', 'aduan.updateTask', 'aduan.representAny', 'pergerakan.view', 'pergerakan.create']);

        $role = Role::firstOrCreate(['name' => 'ptm'],['desc' => 'Pegawai Teknologi Maklumat']);
        $role->syncPermissions(['aduan.create', 'aduan.all', 'aduan.updateTask', 'aduan.representAny', 'pergerakan.view', 'pergerakan.create']);

        $role = Role::firstOrCreate(['name' => 'pengguna'],['desc' => 'Warga KKDW']);
        $role->syncPermissions(['aduan.create']);

        $role = Role::firstOrCreate(['name' => 'helpdesk.admin'], ['desc' => 'Admin Helpdesk']);
        $role->syncPermissions(['manage.category', 'aduan.all', 'aduan.create', 'aduan.adminTask', 'aduan.representAny', 'view.report', 'dashboard.viewGraph', 'pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny', 'pergerakan.manageLocked', 'pergerakan.manageRoster']);

        // Penyelia: mengawasi operasi helpdesk. Semua kebenaran Admin Helpdesk,
        // ditambah pengurusan pengguna dan peranan. Admin Helpdesk sendiri tidak
        // mengurus pengguna.
        $role = Role::firstOrCreate(['name' => 'penyelia'], ['desc' => 'Penyelia']);
        $role->syncPermissions(['manage.category', 'manage.user', 'manage.role', 'aduan.all', 'aduan.create', 'aduan.adminTask', 'aduan.representAny', 'view.report', 'dashboard.viewGraph', 'pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny', 'pergerakan.manageLocked', 'pergerakan.manageRoster']);

        $role = Role::firstOrCreate(['name' => 'superadmin'], ['desc' => 'Admin Sistem']);
        $role->syncPermissions(Permission::pluck('name'));

        $role = Role::firstOrCreate(['name' => 'pengurusan'], ['desc' => 'Pengurusan']);
        $role->syncPermissions(['view.report', 'dashboard.viewGraph', 'pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
    }
}
