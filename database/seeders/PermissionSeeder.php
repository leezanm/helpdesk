<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Permission::create(['name' => 'aduan.create', 'desc' => 'Cipta aduan']);
        Permission::create(['name' => 'aduan.all', 'desc' => 'Lihat Semua aduan']);
        Permission::create(['name' => 'aduan.adminTask', 'desc' => 'Tindakan admin']);
        Permission::create(['name' => 'aduan.updateTask', 'desc' => 'Kemaskini status tugasan']);

        Permission::create(['name' => 'view.report', 'desc' => 'Jana dan lihat laporan']);

        Permission::create(['name' => 'manage.division', 'desc' => 'Selenggara bahagian']);
        Permission::create(['name' => 'manage.category', 'desc' => 'Selenggara kategori aduan']);
        Permission::create(['name' => 'manage.role', 'desc' => 'Pengurusan peranan']);
        Permission::create(['name' => 'manage.user', 'desc' => 'Pengurusan pengguna']);
        Permission::create(['name' => 'manage.admin', 'desc' => 'Pengurusan admin']);

        Permission::create(['name' => 'manage.audit-trail', 'desc' => 'Lihat audit trail']);
        Permission::create(['name' => 'manage.backup', 'desc' => 'Backup database']);
        Permission::create(['name' => 'dashboard.viewGraph', 'desc' => 'Lihat Statistik Aduan']);
    }
}
