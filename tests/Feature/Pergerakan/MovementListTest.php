<?php

namespace Tests\Feature\Pergerakan;

use App\Livewire\Pergerakan\MovementIndex;
use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Senarai pergerakan dengan penapis dan carian — Req 4.
 */
class MovementListTest extends FeatureTestCase
{
    private MovementUnit $unit;
    private MovementType $mesyuarat;
    private MovementType $kursus;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-14 10:00:00');

        $this->unit = MovementUnit::factory()->create(['name' => 'Unit Operasi dan Bantuan Teknikal']);
        $this->mesyuarat = MovementType::factory()->create(['name' => 'Mesyuarat', 'sort_order' => 1]);
        $this->kursus = MovementType::factory()->create(['name' => 'Kursus / Latihan', 'sort_order' => 2]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actingAsViewer(array $permissions = ['pergerakan.view']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }

    private function officer(string $name, ?User $user = null, ?MovementUnit $unit = null): MonitoredOfficer
    {
        $factory = MonitoredOfficer::factory();

        if ($user !== null) {
            $factory = $factory->forUser($user);
        }

        return $factory->create([
            'movement_unit_id' => ($unit ?? $this->unit)->id,
            'display_name' => $name,
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

    public function test_halaman_senarai_memerlukan_kebenaran_view(): void
    {
        $this->actingAs(User::factory()->create())->get(route('pergerakan.index'))->assertForbidden();

        $this->actingAsViewer();
        $this->get(route('pergerakan.index'))->assertOk()->assertSee('Senarai Pergerakan');
    }

    public function test_tempoh_lalai_ialah_bulan_semasa(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        $this->movement($officer, '2026-11-20', 'Mesyuarat November');
        $this->movement($officer, '2026-12-02', 'Mesyuarat Disember');
        $this->movement($officer, '2026-10-20', 'Mesyuarat Oktober');

        Livewire::test(MovementIndex::class)
            ->assertSet('from', '2026-11-01')
            ->assertSet('to', '2026-11-30')
            ->assertSee('Mesyuarat November')
            ->assertDontSee('Mesyuarat Disember')
            ->assertDontSee('Mesyuarat Oktober');
    }

    public function test_tapis_mengikut_tempoh(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        $this->movement($officer, '2026-11-03', 'Awal bulan');
        $this->movement($officer, '2026-11-25', 'Akhir bulan');

        Livewire::test(MovementIndex::class)
            ->set('from', '2026-11-01')
            ->set('to', '2026-11-07')
            ->assertSee('Awal bulan')
            ->assertDontSee('Akhir bulan');
    }

    /** Entri berbilang hari muncul apabila mana-mana sebahagian julatnya dalam tempoh. */
    public function test_entri_berbilang_hari_ditemui_oleh_tempoh_yang_bertindih(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        Movement::factory()->spanning('2026-11-23', '2026-11-25')->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $this->kursus->id,
            'title' => 'Kursus tiga hari',
        ]);

        Livewire::test(MovementIndex::class)
            // Tempoh hanya menyentuh hari tengah kursus.
            ->set('from', '2026-11-24')
            ->set('to', '2026-11-24')
            ->assertSee('Kursus tiga hari');
    }

    public function test_tapis_mengikut_pegawai(): void
    {
        $this->actingAsViewer();
        $satu = $this->officer('En. Pegawai Satu');
        $dua = $this->officer('Pn. Pegawai Dua');

        $this->movement($satu, '2026-11-10', 'Entri Pegawai Satu');
        $this->movement($dua, '2026-11-11', 'Entri Pegawai Dua');

        Livewire::test(MovementIndex::class)
            ->set('officerId', $satu->id)
            ->assertSee('Entri Pegawai Satu')
            ->assertDontSee('Entri Pegawai Dua');
    }

    public function test_tapis_mengikut_jenis(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        $this->movement($officer, '2026-11-10', 'Satu mesyuarat');
        $this->movement($officer, '2026-11-11', 'Satu kursus', ['movement_type_id' => $this->kursus->id]);

        Livewire::test(MovementIndex::class)
            ->set('typeId', $this->kursus->id)
            ->assertSee('Satu kursus')
            ->assertDontSee('Satu mesyuarat');
    }

    public function test_tapis_mengikut_unit_dan_pegawai_diset_semula(): void
    {
        $this->actingAsViewer();
        // Dinamakan selepas unit utama mengikut abjad: komponen memilih unit
        // pertama mengikut nama sebagai unit lalai.
        $unitLain = MovementUnit::factory()->create(['name' => 'Unit Zon Selatan']);

        $officerIni = $this->officer('En. Unit Ini');
        $this->movement($officerIni, '2026-11-10', 'Entri Unit Ini');

        $officerLain = $this->officer('En. Unit Lain', null, $unitLain);
        $this->movement($officerLain, '2026-11-10', 'Entri Unit Lain');

        Livewire::test(MovementIndex::class)
            ->assertSee('Entri Unit Ini')
            ->assertDontSee('Entri Unit Lain')
            ->set('officerId', $officerIni->id)
            ->set('unitId', $unitLain->id)
            // Pegawai unit lama tidak relevan selepas unit bertukar.
            ->assertSet('officerId', null)
            ->assertSee('Entri Unit Lain')
            ->assertDontSee('Entri Unit Ini');
    }

    public function test_pergerakan_saya_sahaja(): void
    {
        $user = $this->actingAsViewer();
        $saya = $this->officer('En. Saya', $user);
        $lain = $this->officer('En. Orang Lain');

        $this->movement($saya, '2026-11-10', 'Entri saya');
        $this->movement($lain, '2026-11-11', 'Entri orang lain');

        Livewire::test(MovementIndex::class)
            ->assertSee('Entri orang lain')
            ->set('mineOnly', true)
            ->assertSee('Entri saya')
            ->assertDontSee('Entri orang lain');
    }

    public function test_carian_meliputi_tajuk_lokasi_dan_catatan(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        $this->movement($officer, '2026-11-10', 'Perbincangan Sistem Helpdesk', ['location' => 'Aras 11', 'remarks' => null]);
        $this->movement($officer, '2026-11-11', 'Mesyuarat lain', ['location' => 'Makmal GIS, Aras 10', 'remarks' => null]);
        $this->movement($officer, '2026-11-12', 'Taklimat', ['location' => null, 'remarks' => 'Bawa laptop sendiri']);

        Livewire::test(MovementIndex::class)
            ->set('search', 'Helpdesk')
            ->assertSee('Perbincangan Sistem Helpdesk')
            ->assertDontSee('Mesyuarat lain')
            ->set('search', 'Makmal GIS')
            ->assertSee('Mesyuarat lain')
            ->assertDontSee('Perbincangan Sistem Helpdesk')
            ->set('search', 'laptop')
            ->assertSee('Taklimat')
            ->assertDontSee('Mesyuarat lain');
    }

    public function test_set_semula_penapis(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');
        $this->movement($officer, '2026-11-20', 'Mesyuarat November');

        Livewire::test(MovementIndex::class)
            ->set('search', 'tiada padanan')
            ->set('officerId', $officer->id)
            ->set('typeId', $this->kursus->id)
            ->set('from', '2026-01-01')
            ->set('to', '2026-01-31')
            ->assertDontSee('Mesyuarat November')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('officerId', null)
            ->assertSet('typeId', null)
            ->assertSet('from', '2026-11-01')
            ->assertSet('to', '2026-11-30')
            ->assertSee('Mesyuarat November');
    }

    public function test_keadaan_kosong_dipaparkan(): void
    {
        $this->actingAsViewer();
        $this->officer('En. Pegawai');

        Livewire::test(MovementIndex::class)
            ->assertSee('Tiada pergerakan sepadan dengan penapis ini');
    }

    public function test_tempoh_terbalik_dimaklumkan(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');
        $this->movement($officer, '2026-11-20', 'Mesyuarat November');

        Livewire::test(MovementIndex::class)
            ->set('from', '2026-11-30')
            ->set('to', '2026-11-01')
            ->assertSee('selepas tarikh')
            ->assertDontSee('Mesyuarat November');
    }

    public function test_senarai_disusun_mengikut_tarikh_dan_masa(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        $this->movement($officer, '2026-11-20', 'Petang 20 Nov', ['start_time' => '15:00']);
        $this->movement($officer, '2026-11-20', 'Pagi 20 Nov', ['start_time' => '09:00']);
        $this->movement($officer, '2026-11-05', 'Lima Nov');

        Livewire::test(MovementIndex::class)
            ->assertSeeInOrder(['Lima Nov', 'Pagi 20 Nov', 'Petang 20 Nov']);
    }

    public function test_paginasi_selepas_25_entri(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');

        for ($day = 1; $day <= 26; $day++) {
            $this->movement($officer, sprintf('2026-11-%02d', $day), 'Entri hari '.$day);
        }

        Livewire::test(MovementIndex::class)
            ->assertSee('Entri hari 1')
            ->assertDontSee('Entri hari 26')
            ->call('gotoPage', 2)
            ->assertSee('Entri hari 26');
    }

    public function test_butang_kemaskini_dan_hapus_mengikut_peraturan_kunci(): void
    {
        $user = $this->actingAsViewer(['pergerakan.view', 'pergerakan.create']);
        $officer = $this->officer('En. Saya', $user);

        $akanDatang = $this->movement($officer, '2026-11-20', 'Entri akan datang');
        $terkunci = $this->movement($officer, '2026-11-10', 'Entri terkunci');

        Livewire::test(MovementIndex::class)->call('delete', $terkunci->id)->assertForbidden();
        $this->assertDatabaseHas('movements', ['id' => $terkunci->id, 'deleted_at' => null]);

        Livewire::test(MovementIndex::class)->call('delete', $akanDatang->id);
        $this->assertSoftDeleted('movements', ['id' => $akanDatang->id]);
    }

    public function test_entri_yang_dihapus_tidak_disenaraikan(): void
    {
        $this->actingAsViewer();
        $officer = $this->officer('En. Pegawai');
        $movement = $this->movement($officer, '2026-11-20', 'Entri dihapus');

        $movement->delete();

        Livewire::test(MovementIndex::class)->assertDontSee('Entri dihapus');
    }
}
