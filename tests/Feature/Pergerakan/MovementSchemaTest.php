<?php

namespace Tests\Feature\Pergerakan;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Database\Seeders\MovementUnitSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Tests\FeatureTestCase;

/**
 * Skema, hubungan dan peraturan asas model modul Pergerakan.
 */
class MovementSchemaTest extends FeatureTestCase
{
    public function test_entri_pergerakan_menghubungkan_pegawai_jenis_dan_pencatat(): void
    {
        $user = User::factory()->create();
        $officer = MonitoredOfficer::factory()->forUser($user)->create();
        $type = MovementType::factory()->create(['name' => 'Mesyuarat']);

        $movement = Movement::factory()->create([
            'monitored_officer_id' => $officer->id,
            'movement_type_id' => $type->id,
            'title' => 'Perbincangan Sistem Helpdesk 2.0',
            'location' => 'Bilik Mesyuarat Digitalis, Aras 11',
            'created_by' => $user->id,
        ]);

        $this->assertTrue($movement->officer->is($officer));
        $this->assertTrue($movement->type->is($type));
        $this->assertTrue($movement->createdBy->is($user));
        $this->assertTrue($officer->movements->first()->is($movement));
        $this->assertTrue($officer->user->is($user));
    }

    public function test_pegawai_hanya_boleh_wujud_sekali_dalam_satu_unit(): void
    {
        $unit = MovementUnit::factory()->create();
        $user = User::factory()->create();

        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $unit->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $unit->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_pegawai_sama_boleh_dipantau_dalam_unit_berbeza(): void
    {
        $user = User::factory()->create();

        MonitoredOfficer::factory()->create([
            'movement_unit_id' => MovementUnit::factory()->create()->id,
            'user_id' => $user->id,
        ]);
        MonitoredOfficer::factory()->create([
            'movement_unit_id' => MovementUnit::factory()->create()->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame(2, MonitoredOfficer::where('user_id', $user->id)->count());
    }

    /** Baris jadual bulanan: pegawai aktif sahaja, ikut susunan paparan (BIL). */
    public function test_pegawai_aktif_dipulangkan_ikut_susunan_paparan(): void
    {
        $unit = MovementUnit::factory()->create();

        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $unit->id, 'display_name' => 'Ketiga', 'sort_order' => 3,
        ]);
        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $unit->id, 'display_name' => 'Pertama', 'sort_order' => 1,
        ]);
        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $unit->id, 'display_name' => 'Kedua', 'sort_order' => 2,
        ]);
        MonitoredOfficer::factory()->inactive()->create([
            'movement_unit_id' => $unit->id, 'display_name' => 'Tidak Aktif', 'sort_order' => 0,
        ]);

        $this->assertSame(
            ['Pertama', 'Kedua', 'Ketiga'],
            $unit->activeOfficers()->pluck('display_name')->all()
        );
    }

    /**
     * Pegawai yang dinyahaktifkan tidak lagi menjadi baris jadual, tetapi entri
     * pergerakan lepasnya kekal utuh (Req 1.4).
     */
    public function test_entri_kekal_apabila_pegawai_dinyahaktifkan(): void
    {
        $officer = MonitoredOfficer::factory()->create();
        $movement = Movement::factory()->create(['monitored_officer_id' => $officer->id]);

        $officer->update(['is_active' => false]);

        $this->assertDatabaseHas('movements', ['id' => $movement->id, 'deleted_at' => null]);
        $this->assertSame($officer->display_name, $movement->fresh()->officer->display_name);
    }

    /** Nama paparan ialah snapshot: jadual lepas tidak berubah bila nama akaun bertukar. */
    public function test_nama_paparan_tidak_berubah_apabila_nama_akaun_bertukar(): void
    {
        $user = User::factory()->create(['name' => 'Syarul Emy bin Abu Samah']);
        $officer = MonitoredOfficer::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'En. Syarul Emy bin Abu Samah',
        ]);

        $user->update(['name' => 'Nama Baharu']);

        $this->assertSame('En. Syarul Emy bin Abu Samah', $officer->fresh()->display_name);
    }

    public function test_seorang_pegawai_boleh_ada_beberapa_entri_pada_hari_yang_sama(): void
    {
        $officer = MonitoredOfficer::factory()->create();
        $date = now()->addDays(5)->toDateString();

        Movement::factory()->onDate($date)->count(2)->create([
            'monitored_officer_id' => $officer->id,
        ]);

        $this->assertSame(2, $officer->movements()->whereDate('start_date', $date)->count());
    }

    public function test_entri_berbilang_hari_dikenali_dan_ditemui_dalam_tempoh(): void
    {
        $officer = MonitoredOfficer::factory()->create();

        $single = Movement::factory()->onDate('2026-11-05')->create([
            'monitored_officer_id' => $officer->id,
        ]);
        $multi = Movement::factory()->spanning('2026-11-18', '2026-11-19')->create([
            'monitored_officer_id' => $officer->id,
        ]);

        $this->assertFalse($single->isMultiDay());
        $this->assertTrue($multi->isMultiDay());

        // Julat yang hanya menyentuh hari TENGAH entri masih menemuinya.
        $found = Movement::overlapping('2026-11-19', '2026-11-19')->pluck('id');

        $this->assertTrue($found->contains($multi->id));
        $this->assertFalse($found->contains($single->id));
    }

    /**
     * Regresi: julat satu hari yang sama mesti ditemui. Perbandingan rentetan
     * biasa gagal di sini pada SQLite ('2026-11-20 00:00:00' <= '2026-11-20'),
     * jadi skop ini membandingkan bahagian tarikh sahaja.
     */
    public function test_entri_sehari_ditemui_oleh_julat_tarikh_yang_sama(): void
    {
        $movement = Movement::factory()->onDate('2026-11-20')->create();

        $this->assertTrue(
            Movement::overlapping('2026-11-20', '2026-11-20')->pluck('id')->contains($movement->id)
        );
        $this->assertTrue(
            Movement::overlapping('2026-11-01', '2026-11-30')->pluck('id')->contains($movement->id)
        );
        $this->assertFalse(
            Movement::overlapping('2026-11-21', '2026-11-30')->pluck('id')->contains($movement->id)
        );
    }

    public function test_entri_ditapis_mengikut_unit(): void
    {
        $unitA = MovementUnit::factory()->create();
        $unitB = MovementUnit::factory()->create();

        $mine = Movement::factory()->create([
            'monitored_officer_id' => MonitoredOfficer::factory()->create(['movement_unit_id' => $unitA->id])->id,
        ]);
        Movement::factory()->create([
            'monitored_officer_id' => MonitoredOfficer::factory()->create(['movement_unit_id' => $unitB->id])->id,
        ]);

        $this->assertSame([$mine->id], Movement::forUnit($unitA->id)->pluck('id')->all());
    }

    /**
     * Tempoh kunci (D12): entri terbuka selagi tarikh mula masih di hadapan;
     * hari ini pun sudah terkunci, begitu juga entri berbilang hari yang sudah
     * bermula walaupun belum tamat.
     */
    public function test_entri_terkunci_sebaik_tarikh_mula_sampai(): void
    {
        Carbon::setTestNow('2026-11-14 10:00:00');

        $officer = MonitoredOfficer::factory()->create();

        $esok = Movement::factory()->onDate('2026-11-15')->create(['monitored_officer_id' => $officer->id]);
        $hariIni = Movement::factory()->onDate('2026-11-14')->create(['monitored_officer_id' => $officer->id]);
        $semalam = Movement::factory()->onDate('2026-11-13')->create(['monitored_officer_id' => $officer->id]);
        $sedangBerjalan = Movement::factory()->spanning('2026-11-13', '2026-11-20')->create([
            'monitored_officer_id' => $officer->id,
        ]);

        $this->assertFalse($esok->isLocked(), 'Entri esok masih boleh diubah oleh pegawai sendiri.');
        $this->assertTrue($hariIni->isLocked(), 'Entri hari ini sudah terkunci.');
        $this->assertTrue($semalam->isLocked());
        $this->assertTrue($sedangBerjalan->isLocked(), 'Entri berbilang hari yang sudah bermula terkunci.');

        Carbon::setTestNow();
    }

    public function test_entri_dihapus_secara_soft_delete(): void
    {
        $movement = Movement::factory()->create();

        $movement->delete();

        $this->assertSoftDeleted('movements', ['id' => $movement->id]);
        $this->assertSame(0, Movement::count());
    }

    public function test_cuti_umum_global_terpakai_kepada_semua_unit(): void
    {
        $unit = MovementUnit::factory()->create();

        $global = MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);
        $unitOnly = MovementHoliday::factory()->onDate('2026-11-09')->create([
            'movement_unit_id' => MovementUnit::factory()->create()->id,
        ]);

        $found = MovementHoliday::forUnit($unit->id)
            ->overlapping('2026-11-01', '2026-11-30')
            ->pluck('id');

        $this->assertTrue($found->contains($global->id));
        $this->assertFalse($found->contains($unitOnly->id));
    }

    public function test_jenis_tidak_aktif_tidak_ditawarkan_tetapi_entri_lama_kekal(): void
    {
        $active = MovementType::factory()->create(['name' => 'Mesyuarat', 'sort_order' => 1]);
        $retired = MovementType::factory()->inactive()->create(['name' => 'Jenis Lama', 'sort_order' => 2]);

        $movement = Movement::factory()->create(['movement_type_id' => $retired->id]);

        $this->assertSame(
            ['Mesyuarat'],
            MovementType::active()->ordered()->pluck('name')->all()
        );
        $this->assertSame('Jenis Lama', $movement->fresh()->type->name);
    }

    public function test_seeder_rujukan_idempoten(): void
    {
        $this->seed(MovementUnitSeeder::class);
        $this->seed(MovementTypeSeeder::class);
        $this->seed(MovementUnitSeeder::class);
        $this->seed(MovementTypeSeeder::class);

        $this->assertSame(1, MovementUnit::where('name', 'Unit Operasi dan Bantuan Teknikal')->count());
        $this->assertSame(count(MovementTypeSeeder::types()), MovementType::count());
        $this->assertSame(3, MovementType::where('is_leave', true)->count());
    }
}
