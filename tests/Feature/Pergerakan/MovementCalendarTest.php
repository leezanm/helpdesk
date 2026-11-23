<?php

namespace Tests\Feature\Pergerakan;

use App\Livewire\Pergerakan\MovementSchedule;
use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use App\Models\User;
use App\Support\MovementCalendar;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Jadual pergerakan bulanan — Req 3.
 *
 * November 2026 digunakan sebagai bulan rujukan kerana itulah bulan dalam
 * jadual Google Sheet yang modul ini ganti: 1 Nov jatuh pada Ahad dan bulan itu
 * ada 30 hari.
 */
class MovementCalendarTest extends FeatureTestCase
{
    private MovementUnit $unit;
    private MovementType $type;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-14 10:00:00');

        $this->unit = MovementUnit::factory()->create([
            'name' => 'Unit Operasi dan Bantuan Teknikal',
            'short_name' => 'UOBT, BPM',
        ]);
        $this->type = MovementType::factory()->create(['name' => 'Mesyuarat', 'class' => 'bg-primary']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function officer(string $name, int $order = 0, ?User $user = null): MonitoredOfficer
    {
        $factory = MonitoredOfficer::factory();

        if ($user !== null) {
            $factory = $factory->forUser($user);
        }

        return $factory->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => $name,
            'sort_order' => $order,
        ]);
    }

    private function calendar(int $year = 2026, int $month = 11): MovementCalendar
    {
        return new MovementCalendar($this->unit->fresh(), $year, $month);
    }

    private function actingAsViewer(array $permissions = ['pergerakan.view']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }

    // ------------------------------------------------------------ hari & minggu

    public function test_setiap_hari_dalam_bulan_dipaparkan_dengan_nama_hari_bahasa_melayu(): void
    {
        $days = $this->calendar()->days();

        $this->assertCount(30, $days);
        $this->assertSame('2026-11-01', $days[0]['key']);
        $this->assertSame('AHAD', $days[0]['name']);
        $this->assertSame('ISNIN', $days[1]['name']);
        $this->assertSame('SABTU', $days[6]['name']);
        $this->assertSame(30, $days[29]['day']);
    }

    public function test_februari_tahun_lompat_ada_29_hari(): void
    {
        $this->assertCount(29, $this->calendar(2028, 2)->days());
    }

    public function test_hujung_minggu_dan_hari_ini_ditandakan(): void
    {
        $days = collect($this->calendar()->days())->keyBy('key');

        $this->assertTrue($days['2026-11-01']['is_weekend'], 'Ahad adalah hujung minggu.');
        $this->assertTrue($days['2026-11-07']['is_weekend'], 'Sabtu adalah hujung minggu.');
        $this->assertFalse($days['2026-11-05']['is_weekend']);
        $this->assertTrue($days['2026-11-14']['is_today']);
        $this->assertFalse($days['2026-11-15']['is_today']);
    }

    public function test_hari_dikumpulkan_mengikut_minggu_bermula_ahad(): void
    {
        $weeks = $this->calendar()->weeks();

        $this->assertSame('MINGGU PERTAMA', $weeks[0]['label']);
        $this->assertSame('MINGGU KEDUA', $weeks[1]['label']);
        // 1 Nov 2026 jatuh pada Ahad, jadi minggu pertama penuh tujuh hari.
        $this->assertCount(7, $weeks[0]['days']);
        $this->assertSame('2026-11-08', $weeks[1]['days'][0]['key']);

        // Jumlah hari dalam semua minggu mesti sama dengan jumlah hari bulan.
        $this->assertSame(30, collect($weeks)->sum(fn ($week) => count($week['days'])));
    }

    public function test_minggu_pertama_tidak_penuh_apabila_bulan_tidak_mula_pada_ahad(): void
    {
        // 1 Disember 2026 jatuh pada Selasa: minggu pertama hanya Sel-Sab.
        $weeks = $this->calendar(2026, 12)->weeks();

        $this->assertCount(5, $weeks[0]['days']);
        $this->assertSame('2026-12-01', $weeks[0]['days'][0]['key']);
        $this->assertSame('2026-12-06', $weeks[1]['days'][0]['key']);
    }

    // ----------------------------------------------------------------- cuti umum

    public function test_cuti_umum_ditandakan_pada_hari_berkenaan(): void
    {
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        $days = collect($this->calendar()->days())->keyBy('key');

        $this->assertSame('Deepavali', $days['2026-11-08']['holiday']);
        $this->assertNull($days['2026-11-09']['holiday']);
    }

    public function test_cuti_umum_berbilang_hari_ditandakan_pada_setiap_hari(): void
    {
        MovementHoliday::factory()->create([
            'name' => 'Cuti Perayaan',
            'start_date' => '2026-11-28',
            // Melampaui hujung bulan: hanya hari dalam bulan ditandakan.
            'end_date' => '2026-12-02',
        ]);

        $days = collect($this->calendar()->days())->keyBy('key');

        $this->assertSame('Cuti Perayaan', $days['2026-11-28']['holiday']);
        $this->assertSame('Cuti Perayaan', $days['2026-11-30']['holiday']);
        $this->assertNull($days['2026-11-27']['holiday']);
    }

    public function test_cuti_umum_unit_lain_tidak_ditandakan(): void
    {
        MovementHoliday::factory()->onDate('2026-11-10')->create([
            'name' => 'Cuti Unit Lain',
            'movement_unit_id' => MovementUnit::factory()->create()->id,
        ]);

        $days = collect($this->calendar()->days())->keyBy('key');

        $this->assertNull($days['2026-11-10']['holiday']);
    }

    // --------------------------------------------------------------------- baris

    public function test_baris_mengikut_susunan_paparan_dan_hanya_pegawai_aktif(): void
    {
        $this->officer('En. Ketiga', 3);
        $this->officer('En. Pertama', 1);
        $this->officer('Pn. Kedua', 2);
        MonitoredOfficer::factory()->inactive()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'En. Tidak Aktif',
            'sort_order' => 0,
        ]);

        $rows = $this->calendar()->rows();

        $this->assertSame(
            ['En. Pertama', 'Pn. Kedua', 'En. Ketiga'],
            array_map(fn ($row) => $row['officer']->display_name, $rows)
        );
    }

    public function test_setiap_baris_mempunyai_sel_untuk_setiap_hari(): void
    {
        $this->officer('En. Pegawai');

        $rows = $this->calendar()->rows();

        $this->assertCount(30, $rows[0]['cells']);
        $this->assertSame([], $rows[0]['cells']['2026-11-03']);
    }

    public function test_entri_sehari_muncul_dalam_sel_hari_itu(): void
    {
        $officer = $this->officer('En. Pegawai');
        $movement = Movement::factory()->onDate('2026-11-05')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Training MYGOVUC',
        ]);

        $cells = $this->calendar()->rows()[0]['cells'];

        $this->assertSame([$movement->id], array_column($cells['2026-11-05'], 'id'));
        $this->assertSame([], $cells['2026-11-04']);
        $this->assertSame([], $cells['2026-11-06']);
    }

    /** Satu rekod, tetapi muncul pada setiap hari dalam julatnya (Req 3.6). */
    public function test_entri_berbilang_hari_muncul_pada_setiap_hari_dalam_julat(): void
    {
        $officer = $this->officer('En. Pegawai');
        $movement = Movement::factory()->spanning('2026-11-18', '2026-11-19')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'MEXCLUB KKDW Ke-62',
        ]);

        $cells = $this->calendar()->rows()[0]['cells'];

        $this->assertSame([$movement->id], array_column($cells['2026-11-18'], 'id'));
        $this->assertSame([$movement->id], array_column($cells['2026-11-19'], 'id'));
        $this->assertSame([], $cells['2026-11-17']);
        $this->assertSame([], $cells['2026-11-20']);
        $this->assertSame(1, Movement::count(), 'Aktiviti berbilang hari kekal satu rekod.');
    }

    public function test_entri_yang_melampaui_sempadan_bulan_dipangkas(): void
    {
        $officer = $this->officer('En. Pegawai');
        $movement = Movement::factory()->spanning('2026-10-29', '2026-11-03')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Kursus merentas bulan',
        ]);

        $cells = $this->calendar()->rows()[0]['cells'];

        $this->assertSame([$movement->id], array_column($cells['2026-11-01'], 'id'));
        $this->assertSame([$movement->id], array_column($cells['2026-11-03'], 'id'));
        $this->assertSame([], $cells['2026-11-04']);
        $this->assertCount(30, $cells, 'Tiada kunci tarikh di luar bulan.');
    }

    public function test_beberapa_entri_dalam_satu_sel(): void
    {
        $officer = $this->officer('En. Pegawai');

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Mesyuarat JK KKSK',
            'start_time' => '09:00',
        ]);
        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Perbincangan Sistem Helpdesk 2.0',
            'start_time' => '15:00',
        ]);

        $cell = $this->calendar()->rows()[0]['cells']['2026-11-20'];

        $this->assertCount(2, $cell);
        // Disusun mengikut masa mula.
        $this->assertSame(
            ['Mesyuarat JK KKSK', 'Perbincangan Sistem Helpdesk 2.0'],
            array_column($cell, 'title')
        );
    }

    public function test_entri_unit_lain_tidak_dipaparkan(): void
    {
        $this->officer('En. Pegawai Unit Ini');

        $lain = MonitoredOfficer::factory()->create([
            'movement_unit_id' => MovementUnit::factory()->create()->id,
        ]);
        Movement::factory()->onDate('2026-11-05')->create([
            'monitored_officer_id' => $lain->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Entri Unit Lain',
        ]);

        $rows = $this->calendar()->rows();

        $this->assertCount(1, $rows);
        $this->assertSame([], $rows[0]['cells']['2026-11-05']);
    }

    public function test_entri_yang_dihapus_tidak_dipaparkan(): void
    {
        $officer = $this->officer('En. Pegawai');
        $movement = Movement::factory()->onDate('2026-11-05')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
        ]);

        $movement->delete();

        $this->assertSame([], $this->calendar()->rows()[0]['cells']['2026-11-05']);
    }

    // -------------------------------------------------------------------- agenda

    public function test_agenda_hanya_mengandungi_hari_yang_ada_entri_atau_cuti(): void
    {
        $officer = $this->officer('En. Pegawai');
        Movement::factory()->onDate('2026-11-05')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Training MYGOVUC',
        ]);
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        $agenda = $this->calendar()->agenda();

        $this->assertSame(['2026-11-05', '2026-11-08'], array_map(fn ($d) => $d['day']['key'], $agenda));
        $this->assertSame('Training MYGOVUC', $agenda[0]['entries'][0]['movement']->title);
        $this->assertSame('En. Pegawai', $agenda[0]['entries'][0]['officer']->display_name);
        $this->assertSame([], $agenda[1]['entries']);
    }

    // ----------------------------------------------------------------- komponen

    public function test_halaman_jadual_memerlukan_kebenaran_view(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('pergerakan.calendar'))->assertForbidden();

        $this->actingAsViewer();
        $this->get(route('pergerakan.calendar'))->assertOk()->assertSee('Jadual Pergerakan');
    }

    public function test_jadual_memaparkan_nama_pegawai_dan_entri_bulan_semasa(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Syarul Emy bin Abu Samah');
        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Perbincangan Sistem Helpdesk 2.0',
        ]);

        Livewire::test(MovementSchedule::class)
            ->assertOk()
            ->assertSet('year', 2026)
            ->assertSet('month', 11)
            ->assertSee('En. Syarul Emy bin Abu Samah')
            ->assertSee('Perbincangan Sistem Helpdesk 2.0')
            ->assertSee('ISNIN');
    }

    public function test_tukar_bulan_memuat_entri_bulan_itu(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Mesyuarat November',
        ]);
        Movement::factory()->onDate('2026-12-02')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Mesyuarat Disember',
        ]);

        Livewire::test(MovementSchedule::class)
            ->assertSee('Mesyuarat November')
            ->assertDontSee('Mesyuarat Disember')
            ->call('nextMonth')
            ->assertSet('month', 12)
            ->assertSee('Mesyuarat Disember')
            ->assertDontSee('Mesyuarat November')
            ->call('previousMonth')
            ->assertSet('month', 11)
            ->call('previousMonth')
            ->assertSet('month', 10)
            ->call('goToToday')
            ->assertSet('month', 11)
            ->assertSet('year', 2026);
    }

    public function test_tukar_bulan_melintasi_tahun(): void
    {
        $this->actingAsViewer();

        Livewire::test(MovementSchedule::class)
            ->set('year', 2026)
            ->set('month', 12)
            ->call('nextMonth')
            ->assertSet('year', 2027)
            ->assertSet('month', 1)
            ->call('previousMonth')
            ->assertSet('year', 2026)
            ->assertSet('month', 12);
    }

    public function test_butiran_entri_dipaparkan_apabila_diklik(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');
        $movement = Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Mesyuarat Penyelarasan',
            'location' => 'Bilik Mesyuarat Seroja, Aras 29',
            'remarks' => 'Bawa dokumen cadangan',
        ]);

        Livewire::test(MovementSchedule::class)
            ->call('showDetails', $movement->id)
            ->assertSet('detailId', $movement->id)
            ->assertSee('Butiran Pergerakan')
            ->assertSee('Bilik Mesyuarat Seroja, Aras 29')
            ->assertSee('Bawa dokumen cadangan')
            ->call('closeDetails')
            ->assertSet('detailId', null);
    }

    public function test_tambah_entri_dari_sel_membawa_tarikh_dan_pegawai(): void
    {
        $this->actingAsViewer(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $officer = $this->officer('En. Pegawai');

        Livewire::test(MovementSchedule::class)
            ->call('addEntry', '2026-11-25', $officer->id)
            ->assertDispatched('movement-create', date: '2026-11-25', officerId: $officer->id);
    }

    public function test_hapus_dari_jadual_mengikut_peraturan_kunci(): void
    {
        $user = $this->actingAsViewer(['pergerakan.view', 'pergerakan.create']);
        $officer = $this->officer('En. Pegawai', 1, $user);

        $akanDatang = Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
        ]);
        $terkunci = Movement::factory()->onDate('2026-11-10')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->type->id,
        ]);

        Livewire::test(MovementSchedule::class)->call('delete', $terkunci->id)->assertForbidden();
        $this->assertDatabaseHas('movements', ['id' => $terkunci->id, 'deleted_at' => null]);

        Livewire::test(MovementSchedule::class)->call('delete', $akanDatang->id);
        $this->assertSoftDeleted('movements', ['id' => $akanDatang->id]);
    }

    public function test_jadual_memberitahu_apabila_roster_kosong(): void
    {
        $this->actingAsViewer();

        Livewire::test(MovementSchedule::class)
            ->assertSee('Belum ada pegawai dalam senarai pemantauan');
    }
}
