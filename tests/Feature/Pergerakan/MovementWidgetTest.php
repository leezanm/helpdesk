<?php

namespace Tests\Feature\Pergerakan;

use App\Livewire\Pergerakan\MovementSchedule;
use App\Livewire\Pergerakan\TodayWidget;
use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Widget dashboard "Pergerakan Hari Ini" (task 8) dan fokus automatik jadual
 * bulanan ke tarikh hari ini.
 */
class MovementWidgetTest extends FeatureTestCase
{
    private MovementUnit $unit;
    private MovementType $mesyuarat;
    private MovementType $cuti;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-14 10:00:00');

        $this->unit = MovementUnit::factory()->create([
            'name' => 'Unit Operasi dan Bantuan Teknikal',
            'short_name' => 'UOBT, BPM',
        ]);
        $this->mesyuarat = MovementType::factory()->create(['name' => 'Mesyuarat']);
        $this->cuti = MovementType::factory()->leave()->create(['name' => 'Cuti Rehat']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actingAsManagement(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['pergerakan.view', 'pergerakan.create', 'pergerakan.manageAny']);
        $this->actingAs($user);

        return $user;
    }

    private function officer(string $name, int $order = 0): MonitoredOfficer
    {
        return MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => $name,
            'sort_order' => $order,
        ]);
    }

    private function movement(MonitoredOfficer $officer, string $date, string $title, array $attributes = []): Movement
    {
        return Movement::factory()->onDate($date)->create(array_merge([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
            'title' => $title,
        ], $attributes));
    }

    // ------------------------------------------------------------------ widget

    public function test_widget_memerlukan_kebenaran_view(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TodayWidget::class)->assertForbidden();
    }

    /**
     * Empat kategori. Hanya pegawai tanpa rekod pada tarikh semasa yang
     * disenaraikan sebagai tersedia; tiga yang lain hanya nombor.
     */
    public function test_empat_kategori_ketersediaan(): void
    {
        $this->actingAsManagement();

        $this->officer('En. Tersedia Satu', 1);
        $this->officer('En. Tersedia Dua', 2);
        $dalam = $this->officer('En. Mesyuarat Dalam', 3);
        $luar = $this->officer('En. Bertugas Luar', 4);
        $cuti = $this->officer('Pn. Bercuti', 5);

        // Mesyuarat dalam pejabat: ada di bangunan tetapi terikat.
        $this->movement($dalam, '2026-11-14', 'Perbincangan Sistem Helpdesk 2.0', [
            'is_outside' => false,
            'location' => 'Bilik Mesyuarat Digitalis, Aras 11',
        ]);
        $this->movement($luar, '2026-11-14', 'Mesyuarat di Putrajaya', [
            'is_outside' => true,
            'location' => 'Putrajaya',
        ]);
        $this->movement($cuti, '2026-11-14', 'Cuti Rehat', ['movement_type_id' => $this->cuti->id]);

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 2)
            ->assertViewHas('busyInsideCount', 1)
            ->assertViewHas('outsideCount', 1)
            ->assertViewHas('onLeaveCount', 1)
            ->assertSee('Ketersediaan Pegawai')
            ->assertSee('Tersedia')
            ->assertSee('Tidak Tersedia')
            ->assertSee('Luar Pejabat')
            ->assertSee('Bercuti')
            // Hanya nama yang tersedia disenaraikan.
            ->assertSee('En. Tersedia Satu')
            ->assertSee('En. Tersedia Dua')
            ->assertDontSee('En. Mesyuarat Dalam')
            ->assertDontSee('En. Bertugas Luar')
            ->assertDontSee('Pn. Bercuti');
    }

    /** Cuti mengatasi segalanya walaupun ada aktiviti lain pada hari sama. */
    public function test_cuti_mengatasi_aktiviti_lain(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('Pn. Bercuti', 1);

        $this->movement($officer, '2026-11-14', 'Cuti Rehat', [
            'movement_type_id' => $this->cuti->id, 'start_time' => null, 'end_time' => null,
        ]);
        $this->movement($officer, '2026-11-14', 'Mesyuarat ganti', [
            'is_outside' => true, 'start_time' => null, 'end_time' => null,
        ]);

        Livewire::test(TodayWidget::class)
            ->assertViewHas('onLeaveCount', 1)
            ->assertViewHas('outsideCount', 0)
            ->assertViewHas('busyInsideCount', 0)
            ->assertViewHas('availableCount', 0);
    }

    /**
     * Mesyuarat separuh hari hanya menduduki pegawai dalam tetingkap masanya.
     * Jam ujian ditetapkan pada 10:00 pagi, sebelum mesyuarat 15:00-17:00.
     */
    public function test_pegawai_tersedia_sebelum_mesyuarat_bermula(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Pegawai', 1);

        $this->movement($officer, '2026-11-14', 'Perbincangan petang', [
            'start_time' => '15:00',
            'end_time' => '17:00',
        ]);

        Carbon::setTestNow('2026-11-14 10:00:00');

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 1)
            ->assertViewHas('busyInsideCount', 0)
            ->assertSee('En. Pegawai')
            // Tingkap terbuka sehingga program bermula.
            ->assertSee('hingga 15:00');
    }

    public function test_pegawai_tidak_tersedia_semasa_mesyuarat_berjalan(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Pegawai', 1);

        $this->movement($officer, '2026-11-14', 'Perbincangan petang', [
            'start_time' => '15:00',
            'end_time' => '17:00',
        ]);

        Carbon::setTestNow('2026-11-14 16:00:00');

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 0)
            ->assertViewHas('busyInsideCount', 1)
            ->assertDontSee('En. Pegawai');
    }

    public function test_pegawai_tersedia_semula_selepas_mesyuarat_tamat(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Pegawai', 1);

        $this->movement($officer, '2026-11-14', 'Perbincangan petang', [
            'start_time' => '15:00',
            'end_time' => '17:00',
        ]);

        Carbon::setTestNow('2026-11-14 17:30:00');

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 1)
            ->assertViewHas('busyInsideCount', 0)
            // Tiada komitmen lagi hari ini.
            ->assertDontSee('hingga 15:00')
            ->assertSee('Tersedia');
    }

    /** Tiada masa bermaksud sepanjang hari. */
    public function test_entri_tanpa_masa_menduduki_sepanjang_hari(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Pegawai', 1);

        $this->movement($officer, '2026-11-14', 'Bengkel sehari', [
            'start_time' => null,
            'end_time' => null,
        ]);

        foreach (['2026-11-14 08:30:00', '2026-11-14 14:00:00', '2026-11-14 18:00:00'] as $moment) {
            Carbon::setTestNow($moment);

            Livewire::test(TodayWidget::class)
                ->assertViewHas('availableCount', 0)
                ->assertViewHas('busyInsideCount', 1);
        }
    }

    /** Masa tamat kosong: menduduki pegawai sehingga akhir hari. */
    public function test_entri_tanpa_masa_tamat_menduduki_sehingga_akhir_hari(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Pegawai', 1);

        $this->movement($officer, '2026-11-14', 'Bengkel bermula 14:00', [
            'start_time' => '14:00',
            'end_time' => null,
        ]);

        Carbon::setTestNow('2026-11-14 10:00:00');
        Livewire::test(TodayWidget::class)->assertViewHas('availableCount', 1);

        Carbon::setTestNow('2026-11-14 19:00:00');
        Livewire::test(TodayWidget::class)->assertViewHas('busyInsideCount', 1);
    }

    /** Cuti separuh hari yang direkod dengan masa tidak menutup sepanjang hari. */
    public function test_cuti_separuh_hari_mengikut_masanya(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('Pn. Pegawai', 1);

        $this->movement($officer, '2026-11-14', 'Cuti Rehat separuh hari', [
            'movement_type_id' => $this->cuti->id,
            'start_time' => '08:00',
            'end_time' => '13:00',
        ]);

        Carbon::setTestNow('2026-11-14 09:00:00');
        Livewire::test(TodayWidget::class)
            ->assertViewHas('onLeaveCount', 1)
            ->assertViewHas('availableCount', 0);

        Carbon::setTestNow('2026-11-14 15:00:00');
        Livewire::test(TodayWidget::class)
            ->assertViewHas('onLeaveCount', 0)
            ->assertViewHas('availableCount', 1);
    }

    /** Luar pejabat mengatasi aktiviti dalam pejabat yang serentak. */
    public function test_tugas_luar_mengatasi_aktiviti_dalam_pejabat(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Pegawai', 1);

        // Kedua-duanya tanpa masa, jadi kedua-duanya aktif sepanjang hari.
        $this->movement($officer, '2026-11-14', 'Perbincangan', [
            'is_outside' => false, 'start_time' => null, 'end_time' => null,
        ]);
        $this->movement($officer, '2026-11-14', 'Mesyuarat luar', [
            'is_outside' => true, 'start_time' => null, 'end_time' => null,
        ]);

        Livewire::test(TodayWidget::class)
            ->assertViewHas('outsideCount', 1)
            ->assertViewHas('busyInsideCount', 0);
    }

    public function test_indikator_hijau_dan_label_tersedia(): void
    {
        $this->actingAsManagement();
        $this->officer('En. Di Pejabat', 1);

        Livewire::test(TodayWidget::class)
            ->assertSeeHtml('availability-dot')
            ->assertSeeHtml('availability-item__status')
            ->assertSee('Tersedia');
    }

    public function test_butang_lihat_jadual_bulanan_dipaparkan(): void
    {
        $this->actingAsManagement();
        $this->officer('En. Di Pejabat', 1);

        Livewire::test(TodayWidget::class)
            ->assertSee('Lihat Jadual Bulanan')
            ->assertSeeHtml(route('pergerakan.calendar'));
    }

    /** Status mengikut tarikh semasa: rekod esok atau semalam tidak mengubahnya. */
    public function test_rekod_hari_lain_tidak_mengubah_status_hari_ini(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Pegawai', 1);

        $this->movement($officer, '2026-11-13', 'Mesyuarat semalam', ['is_outside' => true]);
        $this->movement($officer, '2026-11-15', 'Mesyuarat esok', ['is_outside' => true]);

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 1)
            ->assertViewHas('outsideCount', 0)
            ->assertSee('En. Pegawai');
    }

    public function test_entri_berbilang_hari_yang_merangkumi_hari_ini_dikira(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Berkursus', 1);

        Movement::factory()->spanning('2026-11-13', '2026-11-16')->outside()->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->mesyuarat->id,
            'title' => 'Kursus empat hari',
        ]);

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 0)
            ->assertViewHas('outsideCount', 1)
            ->assertDontSee('En. Berkursus');
    }

    public function test_semua_pegawai_keluar_memaparkan_keadaan_kosong(): void
    {
        $this->actingAsManagement();
        $officer = $this->officer('En. Bertugas Luar', 1);
        $this->movement($officer, '2026-11-14', 'Kerja luar', ['is_outside' => true]);

        Livewire::test(TodayWidget::class)
            ->assertSee('Tiada pegawai tersedia sekarang');
    }

    public function test_widget_memaparkan_cuti_umum(): void
    {
        $this->actingAsManagement();
        $this->officer('En. Pegawai', 1);
        MovementHoliday::factory()->onDate('2026-11-14')->create(['name' => 'Deepavali']);

        Livewire::test(TodayWidget::class)->assertSee('Deepavali');
    }

    public function test_widget_memberitahu_apabila_roster_kosong(): void
    {
        $this->actingAsManagement();

        Livewire::test(TodayWidget::class)
            ->assertSee('Belum ada pegawai dalam senarai pemantauan');
    }

    public function test_pegawai_tidak_aktif_tidak_dikira(): void
    {
        $this->actingAsManagement();
        $this->officer('En. Dipantau', 1);
        MonitoredOfficer::factory()->inactive()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'En. Sudah Bertukar',
        ]);

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 1)
            ->assertDontSee('En. Sudah Bertukar');
    }

    public function test_widget_tidak_mengira_pegawai_unit_lain(): void
    {
        $this->actingAsManagement();
        $this->officer('En. Unit Ini', 1);

        $lain = MonitoredOfficer::factory()->create([
            'movement_unit_id' => MovementUnit::factory()->create(['name' => 'Unit Zon Selatan'])->id,
            'display_name' => 'En. Unit Lain',
        ]);
        $this->movement($lain, '2026-11-14', 'Entri Unit Lain', ['is_outside' => true]);

        Livewire::test(TodayWidget::class)
            ->assertViewHas('availableCount', 1)
            ->assertDontSee('En. Unit Lain');
    }

    // -------------------------------------------------------- pada dashboard

    public function test_widget_muncul_pada_dashboard_pengurusan(): void
    {
        $user = User::factory()->create();
        $user->assignRole('pengurusan');
        $user->givePermissionTo(['view.report', 'dashboard.viewGraph', 'pergerakan.view']);

        $this->officer('En. Pegawai', 1);

        $this->actingAs($user)->get(route('dashboard.main'))
            ->assertOk()
            ->assertSee('Ketersediaan Pegawai');
    }

    public function test_widget_muncul_pada_dashboard_pegawai_teknikal(): void
    {
        $user = User::factory()->create();
        $user->assignRole('jtk');

        $this->officer('En. Pegawai', 1);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Ketersediaan Pegawai');
    }

    public function test_widget_tidak_muncul_untuk_pengguna_biasa(): void
    {
        $user = User::factory()->create();   // hanya role 'pengguna'

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Ketersediaan Pegawai');
    }

    // ------------------------------------------------- fokus tarikh hari ini

    public function test_jadual_menanda_lajur_hari_ini_untuk_fokus_automatik(): void
    {
        $this->actingAsManagement();
        $this->officer('En. Pegawai', 1);

        // Penanda yang dibaca oleh skrip tatal. Dipadankan dengan nilainya
        // (bukan nama atribut sahaja) kerana pemilih dalam skrip Alpine itu
        // sendiri mengandungi teks "data-today".
        Livewire::test(MovementSchedule::class)
            ->assertSeeHtml('data-today="2026-11-14"')
            ->call('nextMonth')
            ->assertDontSeeHtml('data-today="');
    }

    public function test_tukar_bulan_menghantar_peristiwa_untuk_menatal_semula(): void
    {
        $this->actingAsManagement();
        $this->officer('En. Pegawai', 1);

        Livewire::test(MovementSchedule::class)
            ->call('nextMonth')
            ->assertDispatched('pergerakan-month-changed');

        Livewire::test(MovementSchedule::class)
            ->call('goToToday')
            ->assertDispatched('pergerakan-month-changed');
    }
}
