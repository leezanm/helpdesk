<?php

namespace Tests\Feature\Pergerakan;

use App\Livewire\Pergerakan\MovementForm;
use App\Livewire\Pergerakan\MyMovements;
use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Borang rekod pergerakan — Req 2, dan penguatkuasaan kunci pada peringkat
 * tindakan (Req 2A.5).
 */
class MovementFormTest extends FeatureTestCase
{
    private MovementUnit $unit;
    private MovementType $mesyuarat;
    private MovementType $cuti;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-14 10:00:00');

        $this->unit = MovementUnit::factory()->create();
        $this->mesyuarat = MovementType::factory()->create(['name' => 'Mesyuarat']);
        $this->cuti = MovementType::factory()->leave()->create(['name' => 'Cuti Rehat']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param array<int,string> $permissions */
    private function actingAsOfficer(array $permissions = ['pergerakan.view', 'pergerakan.create']): array
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        $officer = MonitoredOfficer::factory()->forUser($user)->create([
            'movement_unit_id' => $this->unit->id,
        ]);

        return [$user, $officer];
    }

    public function test_halaman_pergerakan_saya_memerlukan_kebenaran_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pergerakan.view');
        $this->actingAs($user)->get(route('pergerakan.my'))->assertForbidden();

        [$officer] = $this->actingAsOfficer();
        $this->get(route('pergerakan.my'))->assertOk()->assertSee('Pergerakan Saya');
    }

    public function test_pegawai_merekod_pergerakan_sendiri(): void
    {
        [$user, $officer] = $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->assertSet('showModal', true)
            // Pegawai sendiri dipilih secara lalai.
            ->assertSet('monitoredOfficerId', $officer->id)
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Perbincangan Sistem Helpdesk 2.0')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->set('startTime', '15:00')
            ->set('endTime', '17:00')
            ->set('location', 'Bilik Mesyuarat Digitalis, Aras 11')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertDispatched('movement-saved');

        $movement = Movement::sole();

        $this->assertSame($officer->id, $movement->monitored_officer_id);
        $this->assertSame('Perbincangan Sistem Helpdesk 2.0', $movement->title);
        $this->assertSame('2026-11-20', $movement->start_date->toDateString());
        $this->assertSame('15:00', substr($movement->start_time, 0, 5));
        $this->assertSame($user->id, $movement->created_by);
        $this->assertNull($movement->updated_by);
    }

    /**
     * Tekan "+" pada baris pegawai lain: pegawai biasa tetap mendapat namanya
     * sendiri, kerana dia memang tidak boleh menyimpan untuk orang lain.
     */
    public function test_pegawai_biasa_mendapat_namanya_sendiri_walaupun_klik_baris_orang_lain(): void
    {
        [, $officer] = $this->actingAsOfficer();
        $lain = MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'En. Pegawai Lain',
        ]);

        Livewire::test(MovementForm::class)
            ->call('openCreate', '2026-11-25', $lain->id)
            ->assertSet('monitoredOfficerId', $officer->id)
            ->assertSet('startDate', '2026-11-25');
    }

    /** Urus setia pula memang mahu baris yang diklik itu terisi. */
    public function test_pemegang_manage_any_mendapat_pegawai_baris_yang_diklik(): void
    {
        $this->actingAsOfficer(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $lain = MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'En. Pegawai Lain',
        ]);

        Livewire::test(MovementForm::class)
            ->call('openCreate', '2026-11-25', $lain->id)
            ->assertSet('monitoredOfficerId', $lain->id);
    }

    public function test_pengguna_tanpa_baris_roster_tiada_pegawai_lalai(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['pergerakan.view', 'pergerakan.create']);
        $this->actingAs($user);

        $lain = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);

        Livewire::test(MovementForm::class)
            ->call('openCreate', '2026-11-25', $lain->id)
            ->assertSet('monitoredOfficerId', null);
    }

    /** Lokasi aktiviti: dalam atau luar pejabat, disimpan pada entri. */
    public function test_lokasi_dalam_atau_luar_pejabat_disimpan(): void
    {
        [, $officer] = $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Mesyuarat di Putrajaya')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->set('isOutside', true)
            ->set('location', 'Putrajaya')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Movement::sole()->is_outside);
    }

    public function test_lokasi_lalai_adalah_dalam_pejabat(): void
    {
        $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->assertSet('isOutside', false)
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Perbincangan dalaman')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(Movement::sole()->is_outside);
    }

    /** Cuti tiada lokasi bertugas: penanda luar pejabat tidak boleh melekat padanya. */
    public function test_jenis_cuti_tidak_boleh_ditanda_luar_pejabat(): void
    {
        $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('isOutside', true)
            ->set('movementTypeId', $this->cuti->id)
            // Medan lokasi hilang dan penandanya dikosongkan apabila cuti dipilih.
            ->assertSet('isOutside', false)
            ->set('title', 'Cuti Rehat')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(Movement::sole()->is_outside);
    }

    public function test_lokasi_dikekalkan_semasa_kemaskini(): void
    {
        [, $officer] = $this->actingAsOfficer();
        $movement = Movement::factory()->onDate('2026-11-20')->outside()->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
        ]);

        Livewire::test(MovementForm::class)
            ->call('openEdit', $movement->id)
            ->assertSet('isOutside', true)
            ->set('isOutside', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($movement->fresh()->is_outside);
    }

    public function test_entri_berbilang_hari_disimpan_sebagai_satu_rekod(): void
    {
        [, $officer] = $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'MEXCLUB KKDW Ke-62')
            ->set('startDate', '2026-11-18')
            ->set('endDate', '2026-11-19')
            ->call('save')
            ->assertHasNoErrors();

        $movement = Movement::sole();

        $this->assertTrue($movement->isMultiDay());
        $this->assertSame('2026-11-19', $movement->end_date->toDateString());
    }

    public function test_medan_wajib_disemak(): void
    {
        $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('title', '')
            ->set('movementTypeId', null)
            ->call('save')
            ->assertHasErrors([
                'movementTypeId' => 'required',
                'title' => 'required',
            ]);

        $this->assertSame(0, Movement::count());
    }

    public function test_tarikh_tamat_lebih_awal_ditolak(): void
    {
        $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Mesyuarat')
            ->set('startDate', '2026-11-20')
            // Ditetapkan terus supaya ia tidak diselaraskan oleh updated().
            ->set('endDate', '2026-11-19')
            ->call('save')
            ->assertHasErrors(['endDate' => 'after_or_equal']);

        $this->assertSame(0, Movement::count());
    }

    public function test_masa_tamat_mesti_selepas_masa_mula_pada_hari_yang_sama(): void
    {
        $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Mesyuarat')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->set('startTime', '15:00')
            ->set('endTime', '14:00')
            ->call('save')
            ->assertHasErrors(['endTime' => 'after']);
    }

    /** Entri berbilang hari boleh tamat pada waktu yang lebih awal daripada waktu mula. */
    public function test_masa_tidak_dibandingkan_untuk_entri_berbilang_hari(): void
    {
        $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Kursus tiga hari')
            ->set('startDate', '2026-11-23')
            ->set('endDate', '2026-11-25')
            ->set('startTime', '09:00')
            ->set('endTime', '08:30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Movement::count());
    }

    public function test_beberapa_entri_pada_hari_yang_sama_dibenarkan(): void
    {
        [, $officer] = $this->actingAsOfficer();

        foreach (['Mesyuarat JK KKSK', 'Perbincangan Sistem Helpdesk 2.0'] as $title) {
            Livewire::test(MovementForm::class)
                ->call('openCreate')
                ->set('movementTypeId', $this->mesyuarat->id)
                ->set('title', $title)
                ->set('startDate', '2026-11-20')
                ->set('endDate', '2026-11-20')
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertSame(2, Movement::where('monitored_officer_id', $officer->id)->count());
    }

    /** Amaran, bukan halangan: hantaran kedua menyimpan (Req 2.7). */
    public function test_pertindihan_cuti_memberi_amaran_dan_disimpan_pada_hantaran_kedua(): void
    {
        [, $officer] = $this->actingAsOfficer();

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->cuti->id,
            'title' => 'Cuti Rehat',
        ]);

        $component = Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Mesyuarat bertindih cuti')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', true)
            ->assertSet('confirmedOverlap', true)
            ->assertSee('Cuti Rehat');

        $this->assertSame(1, Movement::count());

        $component->call('save')->assertSet('showModal', false);

        $this->assertSame(2, Movement::count());
    }

    public function test_entri_tanpa_pertindihan_cuti_disimpan_terus(): void
    {
        [, $officer] = $this->actingAsOfficer();

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
        ]);

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Mesyuarat kedua')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->call('save')
            ->assertSet('showModal', false);

        $this->assertSame(2, Movement::count());
    }

    public function test_pegawai_tanpa_manage_any_tidak_boleh_merekod_untuk_pegawai_lain(): void
    {
        $this->actingAsOfficer();
        $lain = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('monitoredOfficerId', $lain->id)
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Entri untuk orang lain')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Movement::count());
    }

    public function test_pemegang_manage_any_boleh_merekod_untuk_pegawai_lain(): void
    {
        $this->actingAsOfficer(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $lain = MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'En. Pegawai Lain',
        ]);

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('monitoredOfficerId', $lain->id)
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Mesyuarat diuruskan urus setia')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($lain->id, Movement::sole()->monitored_officer_id);
    }

    public function test_pegawai_roster_tidak_aktif_tidak_ditawarkan_dan_ditolak(): void
    {
        $this->actingAsOfficer(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $tidakAktif = MonitoredOfficer::factory()->inactive()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'En. Sudah Bertukar',
        ]);

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->assertDontSee('En. Sudah Bertukar')
            ->set('monitoredOfficerId', $tidakAktif->id)
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Entri untuk pegawai tidak aktif')
            ->set('startDate', '2026-11-20')
            ->set('endDate', '2026-11-20')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Movement::count());
    }

    public function test_kemaskini_entri_sendiri_merekod_pengguna_yang_mengubah(): void
    {
        [$user, $officer] = $this->actingAsOfficer();
        $movement = Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
            'title' => 'Tajuk Lama',
        ]);

        Livewire::test(MovementForm::class)
            ->call('openEdit', $movement->id)
            ->assertSet('title', 'Tajuk Lama')
            ->set('title', 'Tajuk Baharu')
            ->call('save')
            ->assertHasNoErrors();

        $movement->refresh();
        $this->assertSame('Tajuk Baharu', $movement->title);
        $this->assertSame($user->id, $movement->updated_by);
    }

    /** Kunci dikuatkuasakan pada peringkat server, bukan hanya dengan menyembunyikan butang. */
    public function test_entri_terkunci_tidak_boleh_dibuka_untuk_edit_oleh_pemiliknya(): void
    {
        [, $officer] = $this->actingAsOfficer();
        $movement = Movement::factory()->onDate('2026-11-10')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
        ]);

        Livewire::test(MovementForm::class)
            ->call('openEdit', $movement->id)
            ->assertForbidden();
    }

    public function test_entri_terkunci_boleh_diedit_oleh_pemegang_manage_locked(): void
    {
        $this->actingAsOfficer([
            'pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny', 'pergerakan.manageLocked',
        ]);
        $lain = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);
        $movement = Movement::factory()->onDate('2026-11-10')->create([
            'monitored_officer_id' => $lain->id,
            'movement_type_id' => $this->mesyuarat->id,
        ]);

        Livewire::test(MovementForm::class)
            ->call('openEdit', $movement->id)
            ->set('title', 'Dibetulkan oleh pentadbir')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Dibetulkan oleh pentadbir', $movement->fresh()->title);
    }

    /** Tarikh lepas boleh dicatat untuk melengkapkan rekod tertinggal (D13). */
    public function test_entri_bertarikh_lepas_boleh_dicipta_tetapi_terus_terkunci(): void
    {
        [, $officer] = $this->actingAsOfficer();

        Livewire::test(MovementForm::class)
            ->call('openCreate')
            ->set('movementTypeId', $this->mesyuarat->id)
            ->set('title', 'Mesyuarat minggu lepas')
            ->set('startDate', '2026-11-10')
            ->set('endDate', '2026-11-10')
            ->call('save')
            ->assertHasNoErrors();

        $movement = Movement::sole();

        $this->assertTrue($movement->isLocked());
        Livewire::test(MovementForm::class)->call('openEdit', $movement->id)->assertForbidden();
    }

    public function test_hapus_entri_sendiri_yang_belum_sampai(): void
    {
        [, $officer] = $this->actingAsOfficer();
        $movement = Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
        ]);

        Livewire::test(MyMovements::class)->call('delete', $movement->id);

        $this->assertSoftDeleted('movements', ['id' => $movement->id]);
    }

    public function test_hapus_entri_terkunci_ditolak(): void
    {
        [, $officer] = $this->actingAsOfficer();
        $movement = Movement::factory()->onDate('2026-11-10')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
        ]);

        Livewire::test(MyMovements::class)
            ->call('delete', $movement->id)
            ->assertForbidden();

        $this->assertDatabaseHas('movements', ['id' => $movement->id, 'deleted_at' => null]);
    }

    public function test_senarai_pergerakan_saya_memaparkan_bulan_dipilih_sahaja(): void
    {
        [, $officer] = $this->actingAsOfficer();

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
            'title' => 'Mesyuarat November',
        ]);
        Movement::factory()->onDate('2026-12-02')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
            'title' => 'Mesyuarat Disember',
        ]);

        Livewire::test(MyMovements::class)
            ->assertSee('Mesyuarat November')
            ->assertDontSee('Mesyuarat Disember')
            ->call('nextMonth')
            ->assertSee('Mesyuarat Disember')
            ->assertDontSee('Mesyuarat November');
    }

    public function test_senarai_pergerakan_saya_tidak_memaparkan_entri_pegawai_lain(): void
    {
        $this->actingAsOfficer();
        $lain = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $lain->id,
            'movement_type_id' => $this->mesyuarat->id,
            'title' => 'Entri Pegawai Lain',
        ]);

        Livewire::test(MyMovements::class)->assertDontSee('Entri Pegawai Lain');
    }

    public function test_pengguna_yang_tidak_dipantau_dimaklumkan(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['pergerakan.view', 'pergerakan.create']);
        $this->actingAs($user);

        Livewire::test(MyMovements::class)
            ->assertSee('Nama anda tiada dalam senarai pemantauan');
    }
}
