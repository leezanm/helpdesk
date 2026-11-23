<?php

namespace Tests\Feature\Pergerakan;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\FeatureTestCase;

/**
 * Kebenaran modul Pergerakan: kewujudan, label `desc`, dan pemetaan peranan.
 *
 * Ujian ini juga menjaga perangkap pemasangan: migration kebenaran berjalan
 * SEBELUM PermissionSeeder pada pangkalan data baharu, jadi kalau seeder itu
 * guna create() dan bukan firstOrCreate, setUp() FeatureTestCase akan gagal
 * dengan PermissionAlreadyExists — setiap ujian dalam kelas ini akan merah.
 */
class MovementPermissionTest extends FeatureTestCase
{
    private const PERMISSIONS = [
        'pergerakan.view',
        'pergerakan.create',
        'pergerakan.manageAny',
        'pergerakan.manageLocked',
        'pergerakan.manageRoster',
    ];

    public function test_kebenaran_pergerakan_wujud_sekali_dengan_label(): void
    {
        foreach (self::PERMISSIONS as $name) {
            $matches = Permission::where('name', $name)->where('guard_name', 'web')->get();

            $this->assertCount(1, $matches, "Kebenaran {$name} patut wujud tepat sekali.");
            $this->assertNotEmpty($matches->first()->desc, "Kebenaran {$name} patut ada label desc.");
        }
    }

    public function test_peranan_teknikal_boleh_lihat_dan_catat_pergerakan_sendiri(): void
    {
        foreach (['jtk', 'ptm', 'pptm'] as $name) {
            $role = Role::findByName($name);

            $this->assertTrue($role->hasPermissionTo('pergerakan.view'), "{$name} patut boleh lihat.");
            $this->assertTrue($role->hasPermissionTo('pergerakan.create'), "{$name} patut boleh catat.");
            $this->assertFalse($role->hasPermissionTo('pergerakan.manageAny'), "{$name} tidak patut catat untuk orang lain.");
            $this->assertFalse($role->hasPermissionTo('pergerakan.manageLocked'));
            $this->assertFalse($role->hasPermissionTo('pergerakan.manageRoster'));
        }
    }

    /**
     * Keputusan pengguna: pengurusan boleh mengisi untuk semua pegawai, tetapi
     * hanya helpdesk.admin boleh mengubah entri yang sudah terkunci. Sebab itu
     * manageAny dan manageLocked ialah dua kebenaran berasingan.
     */
    public function test_pengurusan_boleh_catat_untuk_semua_tetapi_bukan_entri_terkunci(): void
    {
        $role = Role::findByName('pengurusan');

        $this->assertTrue($role->hasPermissionTo('pergerakan.view'));
        $this->assertTrue($role->hasPermissionTo('pergerakan.create'));
        $this->assertTrue($role->hasPermissionTo('pergerakan.manageAny'));
        $this->assertFalse($role->hasPermissionTo('pergerakan.manageLocked'));
        $this->assertFalse($role->hasPermissionTo('pergerakan.manageRoster'));
    }

    public function test_helpdesk_admin_memegang_semua_kebenaran_pergerakan(): void
    {
        $role = Role::findByName('helpdesk.admin');

        foreach (self::PERMISSIONS as $name) {
            $this->assertTrue($role->hasPermissionTo($name), "helpdesk.admin patut ada {$name}.");
        }
    }

    public function test_superadmin_memegang_semua_kebenaran_pergerakan(): void
    {
        $role = Role::findByName('superadmin');

        foreach (self::PERMISSIONS as $name) {
            $this->assertTrue($role->hasPermissionTo($name), "superadmin patut ada {$name}.");
        }
    }

    public function test_pengguna_biasa_tiada_akses_modul_pergerakan(): void
    {
        $role = Role::findByName('pengguna');

        foreach (self::PERMISSIONS as $name) {
            $this->assertFalse($role->hasPermissionTo($name), "pengguna tidak patut ada {$name}.");
        }
    }
}
