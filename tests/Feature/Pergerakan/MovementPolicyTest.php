<?php

namespace Tests\Feature\Pergerakan;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementUnit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\FeatureTestCase;

/**
 * Tempoh dibenarkan untuk edit dan hapus — Req 2A / D12.
 *
 * Entri terbuka selagi tarikh mula masih di hadapan (paling lewat sehari
 * sebelum). Sebaik tarikh sampai ia terkunci, dan hanya pemegang
 * `pergerakan.manageLocked` boleh mengubahnya.
 */
class MovementPolicyTest extends FeatureTestCase
{
    private MovementUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-14 10:00:00');
        $this->unit = MovementUnit::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param array<int,string> $permissions */
    private function user(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function movementFor(User $user, string $startDate, ?string $endDate = null): Movement
    {
        $officer = MonitoredOfficer::factory()->forUser($user)->create([
            'movement_unit_id' => $this->unit->id,
        ]);

        return Movement::factory()->spanning($startDate, $endDate ?? $startDate)->create([
            'monitored_officer_id' => $officer->id,
        ]);
    }

    private function movementForSomeoneElse(string $startDate): Movement
    {
        $officer = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);

        return Movement::factory()->onDate($startDate)->create([
            'monitored_officer_id' => $officer->id,
        ]);
    }

    public function test_pegawai_boleh_ubah_entri_sendiri_yang_belum_sampai(): void
    {
        $user = $this->user(['pergerakan.view', 'pergerakan.create']);
        $movement = $this->movementFor($user, '2026-11-15');

        $this->assertTrue($user->can('update', $movement));
        $this->assertTrue($user->can('delete', $movement));
    }

    public function test_entri_sendiri_pada_hari_ini_sudah_terkunci(): void
    {
        $user = $this->user(['pergerakan.view', 'pergerakan.create']);
        $movement = $this->movementFor($user, '2026-11-14');

        $this->assertFalse($user->can('update', $movement));
        $this->assertFalse($user->can('delete', $movement));
    }

    public function test_entri_sendiri_yang_sudah_lepas_terkunci(): void
    {
        $user = $this->user(['pergerakan.view', 'pergerakan.create']);
        $movement = $this->movementFor($user, '2026-11-01');

        $this->assertFalse($user->can('update', $movement));
    }

    /** Entri berbilang hari yang sudah bermula tetapi belum tamat: terkunci (Req 2A.6). */
    public function test_entri_berbilang_hari_yang_sudah_bermula_terkunci(): void
    {
        $user = $this->user(['pergerakan.view', 'pergerakan.create']);
        $movement = $this->movementFor($user, '2026-11-13', '2026-11-20');

        $this->assertFalse($user->can('update', $movement));
    }

    public function test_pegawai_tidak_boleh_ubah_entri_pegawai_lain(): void
    {
        $user = $this->user(['pergerakan.view', 'pergerakan.create']);
        $movement = $this->movementForSomeoneElse('2026-11-20');

        $this->assertFalse($user->can('update', $movement));
        $this->assertFalse($user->can('delete', $movement));
    }

    public function test_pemegang_manage_any_boleh_ubah_entri_pegawai_lain_yang_belum_sampai(): void
    {
        $urusSetia = $this->user(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $movement = $this->movementForSomeoneElse('2026-11-20');

        $this->assertTrue($urusSetia->can('update', $movement));
        $this->assertTrue($urusSetia->can('delete', $movement));
    }

    /** Keputusan pengguna: pengurusan boleh mengisi, tetapi bukan mengubah rekod yang sudah berlaku. */
    public function test_pemegang_manage_any_tanpa_manage_locked_tidak_boleh_ubah_entri_terkunci(): void
    {
        $urusSetia = $this->user(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $movement = $this->movementForSomeoneElse('2026-11-10');

        $this->assertFalse($urusSetia->can('update', $movement));
        $this->assertFalse($urusSetia->can('delete', $movement));
    }

    public function test_pemegang_manage_locked_boleh_ubah_entri_terkunci(): void
    {
        $admin = $this->user(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny', 'pergerakan.manageLocked']);
        $lepas = $this->movementForSomeoneElse('2026-11-10');
        $hariIni = $this->movementForSomeoneElse('2026-11-14');

        $this->assertTrue($admin->can('update', $lepas));
        $this->assertTrue($admin->can('delete', $lepas));
        $this->assertTrue($admin->can('update', $hariIni));
    }

    public function test_superadmin_boleh_ubah_semua_entri(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->assertTrue($superadmin->can('update', $this->movementForSomeoneElse('2026-01-01')));
    }

    public function test_pengguna_tanpa_kebenaran_pergerakan_tidak_boleh_ubah_apa_apa(): void
    {
        $user = User::factory()->create();   // hanya role 'pengguna'
        $movement = $this->movementForSomeoneElse('2026-12-01');

        $this->assertFalse($user->can('update', $movement));
        $this->assertFalse($user->can('delete', $movement));
    }

    public function test_cipta_untuk_pegawai_roster_tidak_aktif_ditolak(): void
    {
        $user = $this->user(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $aktif = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);
        $tidakAktif = MonitoredOfficer::factory()->inactive()->create(['movement_unit_id' => $this->unit->id]);

        $this->assertTrue($user->can('createFor', [Movement::class, $aktif]));
        $this->assertFalse($user->can('createFor', [Movement::class, $tidakAktif]));
    }
}
