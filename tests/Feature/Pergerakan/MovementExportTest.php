<?php

namespace Tests\Feature\Pergerakan;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use App\Livewire\Pergerakan\MovementSchedule;
use App\Models\Ref\MovementType;
use App\Models\User;
use App\Support\MovementCsvExporter;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Cetak dan eksport CSV — Req 7.
 */
class MovementExportTest extends FeatureTestCase
{
    private MovementUnit $unit;
    private MovementType $type;
    private MonitoredOfficer $officer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-14 10:00:00');

        $this->unit = MovementUnit::factory()->create([
            'name' => 'Unit Operasi dan Bantuan Teknikal',
            'short_name' => 'UOBT, BPM',
        ]);
        $this->type = MovementType::factory()->create(['name' => 'Mesyuarat']);
        $this->officer = MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'En. Syarul Emy bin Abu Samah',
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actingAsViewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pergerakan.view');
        $this->actingAs($user);

        return $user;
    }

    private function params(array $overrides = []): array
    {
        return array_merge([
            'unit' => $this->unit->id,
            'year' => 2026,
            'month' => 11,
        ], $overrides);
    }

    // ------------------------------------------------------------------ cetak

    public function test_cetak_memerlukan_kebenaran_view(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('pergerakan.print', $this->params()))
            ->assertForbidden();
    }

    public function test_cetak_memaparkan_tajuk_unit_bulan_dan_grid(): void
    {
        $this->actingAsViewer();

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $this->officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Perbincangan Sistem Helpdesk 2.0',
            'location' => 'Bilik Mesyuarat Digitalis, Aras 11',
            'start_time' => '15:00',
            'end_time' => '17:00',
        ]);
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        $this->get(route('pergerakan.print', $this->params()))
            ->assertOk()
            ->assertSee('Jadual Pergerakan Unit Operasi dan Bantuan Teknikal')
            ->assertSee('November 2026')
            ->assertSee('En. Syarul Emy bin Abu Samah')
            ->assertSee('Perbincangan Sistem Helpdesk 2.0')
            ->assertSee('Bilik Mesyuarat Digitalis, Aras 11')
            ->assertSee('15:00')
            ->assertSee('Deepavali')
            ->assertSee('MINGGU PERTAMA')
            ->assertSee('KHAMIS')
            // Halaman cetak berdiri sendiri — tiada sidebar aplikasi.
            ->assertDontSee('app-sidebar__nav');
    }

    public function test_cetak_tanpa_parameter_menggunakan_bulan_semasa_dan_unit_pertama(): void
    {
        $this->actingAsViewer();

        $this->get(route('pergerakan.print'))
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('Unit Operasi dan Bantuan Teknikal');
    }

    public function test_cetak_menolak_bulan_tidak_sah(): void
    {
        $this->actingAsViewer();

        $this->get(route('pergerakan.print', $this->params(['month' => 13])))
            ->assertSessionHasErrors('month');

        $this->get(route('pergerakan.print', $this->params(['unit' => 99999])))
            ->assertSessionHasErrors('unit');
    }

    public function test_cetak_memberitahu_apabila_roster_kosong(): void
    {
        $this->actingAsViewer();
        $kosong = MovementUnit::factory()->create(['name' => 'Unit Tanpa Pegawai']);

        $this->get(route('pergerakan.print', $this->params(['unit' => $kosong->id])))
            ->assertOk()
            ->assertSee('Belum ada pegawai dalam senarai pemantauan');
    }

    // -------------------------------------------------------------------- csv

    /**
     * Eksport ialah tindakan komponen, bukan laluan — sama seperti eksport
     * Laporan_Keseluruhan. Baris fail dibina daripada MovementCalendar yang
     * sama seperti grid, jadi fail dan skrin tidak boleh terpesong.
     */
    private function csv(array $set = []): string
    {
        $component = Livewire::test(MovementSchedule::class)
            ->set('unitId', $set['unitId'] ?? $this->unit->id)
            ->set('year', $set['year'] ?? 2026)
            ->set('month', $set['month'] ?? 11)
            ->call('exportCsv');

        return base64_decode($component->effects['download']['content']);
    }

    /** @return array<int,array<int,string>> */
    private function rows(string $csv): array
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    public function test_csv_hanya_untuk_pemegang_kebenaran_view(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(MovementSchedule::class)->assertForbidden();
    }

    public function test_csv_mengandungi_tajuk_lajur_dan_entri_bulan_itu(): void
    {
        $this->actingAsViewer();

        Movement::factory()->onDate('2026-11-20')->create([
            'monitored_officer_id' => $this->officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Perbincangan Sistem Helpdesk 2.0',
            'start_time' => '15:00',
            'end_time' => '17:00',
            'location' => 'Bilik Mesyuarat Digitalis, Aras 11',
            'remarks' => 'Bawa dokumen',
        ]);
        Movement::factory()->onDate('2026-12-02')->create([
            'monitored_officer_id' => $this->officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Mesyuarat Disember',
        ]);

        $csv = $this->csv();
        $rows = $this->rows($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'BOM diperlukan supaya Excel membaca UTF-8.');
        $this->assertSame(MovementCsvExporter::HEADERS, $rows[0]);
        $this->assertCount(2, $rows, 'Satu baris tajuk + satu entri dalam bulan itu.');
        $this->assertSame([
            'En. Syarul Emy bin Abu Samah',
            'Mesyuarat',
            'Perbincangan Sistem Helpdesk 2.0',
            '20/11/2026',
            '20/11/2026',
            '15:00',
            '17:00',
            'Dalam pejabat',
            'Bilik Mesyuarat Digitalis, Aras 11',
            'Bawa dokumen',
        ], $rows[1]);
        $this->assertStringNotContainsString('Mesyuarat Disember', $csv);
    }

    public function test_entri_sepanjang_hari_meninggalkan_lajur_masa_kosong(): void
    {
        $this->actingAsViewer();

        $cuti = MovementType::factory()->leave()->create(['name' => 'Cuti Rehat']);

        Movement::factory()->onDate('2026-11-10')->create([
            'monitored_officer_id' => $this->officer->id,
            'movement_type_id' => $cuti->id,
            'title' => 'Cuti Rehat',
            'start_time' => null,
            'end_time' => null,
            'location' => null,
            'remarks' => null,
        ]);

        $row = $this->rows($this->csv())[1];

        // Masa kosong, lokasi aktiviti kosong (cuti), tempat dan catatan kosong.
        $this->assertSame(['', '', '', '', ''], [$row[5], $row[6], $row[7], $row[8], $row[9]]);
    }

    /** Satu baris satu entri: aktiviti berbilang hari tidak dipecahkan. */
    public function test_csv_menanda_aktiviti_luar_pejabat(): void
    {
        $this->actingAsViewer();

        Movement::factory()->onDate('2026-11-10')->outside()->create([
            'monitored_officer_id' => $this->officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Mesyuarat di Putrajaya',
        ]);

        $this->assertSame('Luar pejabat', $this->rows($this->csv())[1][7]);
    }

    public function test_csv_menyimpan_entri_berbilang_hari_sebagai_satu_baris(): void
    {
        $this->actingAsViewer();

        Movement::factory()->spanning('2026-11-23', '2026-11-25')->create([
            'monitored_officer_id' => $this->officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Kursus Jangka Pendek',
        ]);

        $rows = $this->rows($this->csv());

        $this->assertCount(2, $rows);
        $this->assertSame('23/11/2026', $rows[1][3]);
        $this->assertSame('25/11/2026', $rows[1][4]);
    }

    public function test_csv_tidak_mengandungi_entri_unit_lain(): void
    {
        $this->actingAsViewer();

        $lain = MonitoredOfficer::factory()->create([
            'movement_unit_id' => MovementUnit::factory()->create(['name' => 'Unit Zon Selatan'])->id,
            'display_name' => 'En. Unit Lain',
        ]);
        Movement::factory()->onDate('2026-11-10')->create([
            'monitored_officer_id' => $lain->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Entri Unit Lain',
        ]);

        $this->assertStringNotContainsString('Entri Unit Lain', $this->csv());
    }

    public function test_csv_mengecualikan_entri_yang_dihapus(): void
    {
        $this->actingAsViewer();

        $movement = Movement::factory()->onDate('2026-11-10')->create([
            'monitored_officer_id' => $this->officer->id,
            'movement_type_id' => $this->type->id,
            'title' => 'Entri Dihapus',
        ]);
        $movement->delete();

        $this->assertStringNotContainsString('Entri Dihapus', $this->csv());
    }

    public function test_nama_fail_csv_mengandungi_unit_dan_bulan(): void
    {
        $this->actingAsViewer();

        $component = Livewire::test(MovementSchedule::class)->call('exportCsv');

        $this->assertSame(
            'pergerakan-uobt-bpm-2026-11.csv',
            $component->effects['download']['name']
        );
    }

    public function test_csv_hanya_tajuk_lajur_apabila_tiada_entri(): void
    {
        $this->actingAsViewer();

        $this->assertCount(1, $this->rows($this->csv(['month' => 7])));
    }
}
