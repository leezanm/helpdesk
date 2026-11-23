<?php

namespace Tests\Feature\Pergerakan;

use App\Livewire\Pergerakan\MovementTypeIndex;
use App\Models\Movement;
use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Penyelenggaraan jenis pergerakan dan cuti umum — Req 5.
 */
class MovementTypeTest extends FeatureTestCase
{
    private function actingAsRosterAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('pergerakan.manageRoster');
        $this->actingAs($admin);

        return $admin;
    }

    public function test_halaman_memerlukan_kebenaran_manage_roster(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['pergerakan.view', 'pergerakan.manageAny']);

        $this->actingAs($user)->get(route('pergerakan.types'))->assertForbidden();

        $this->actingAsRosterAdmin();
        $this->get(route('pergerakan.types'))->assertOk()->assertSee('Jenis Pergerakan');
    }

    public function test_jenis_asas_disemai_dengan_warna_yang_ditawarkan_skrin(): void
    {
        $this->seed(MovementTypeSeeder::class);

        $allowed = array_keys(MovementTypeIndex::BADGE_CLASSES);

        foreach (MovementType::all() as $type) {
            $this->assertContains(
                $type->class,
                $allowed,
                "Warna {$type->class} ({$type->name}) tiada dalam senarai pilihan skrin."
            );
        }

        $this->assertSame(3, MovementType::where('is_leave', true)->count());
    }

    public function test_jenis_baharu_ditambah(): void
    {
        $this->actingAsRosterAdmin();

        Livewire::test(MovementTypeIndex::class)
            ->call('createType')
            ->assertSet('showTypeModal', true)
            ->set('typeName', 'Taklimat')
            ->set('typeClass', 'bg-info')
            ->set('typeSortOrder', 15)
            ->call('saveType')
            ->assertHasNoErrors()
            ->assertSet('showTypeModal', false);

        $this->assertDatabaseHas('_movement_types', [
            'name' => 'Taklimat',
            'class' => 'bg-info',
            'is_leave' => false,
            'is_active' => true,
            'sort_order' => 15,
        ]);
    }

    public function test_jenis_cuti_ditanda_sebagai_cuti(): void
    {
        $this->actingAsRosterAdmin();

        Livewire::test(MovementTypeIndex::class)
            ->call('createType')
            ->set('typeName', 'Cuti Bersalin')
            ->set('typeIsLeave', true)
            ->call('saveType')
            ->assertHasNoErrors();

        $this->assertTrue(MovementType::where('name', 'Cuti Bersalin')->first()->is_leave);
    }

    public function test_nama_jenis_wajib_dan_tidak_boleh_berulang(): void
    {
        $this->actingAsRosterAdmin();
        MovementType::factory()->create(['name' => 'Mesyuarat']);

        Livewire::test(MovementTypeIndex::class)
            ->call('createType')
            ->set('typeName', '')
            ->call('saveType')
            ->assertHasErrors(['typeName' => 'required']);

        Livewire::test(MovementTypeIndex::class)
            ->call('createType')
            ->set('typeName', 'Mesyuarat')
            ->call('saveType')
            ->assertHasErrors(['typeName' => 'unique']);

        $this->assertSame(1, MovementType::where('name', 'Mesyuarat')->count());
    }

    public function test_warna_di_luar_senarai_ditolak(): void
    {
        $this->actingAsRosterAdmin();

        Livewire::test(MovementTypeIndex::class)
            ->call('createType')
            ->set('typeName', 'Jenis Baharu')
            ->set('typeClass', 'onmouseover=alert(1)')
            ->call('saveType')
            ->assertHasErrors(['typeClass']);
    }

    public function test_jenis_boleh_dikemaskini_termasuk_namanya_sendiri(): void
    {
        $this->actingAsRosterAdmin();
        $type = MovementType::factory()->create(['name' => 'Mesyuarat', 'class' => 'bg-secondary']);

        Livewire::test(MovementTypeIndex::class)
            ->call('editType', $type->id)
            ->assertSet('typeName', 'Mesyuarat')
            // Nama sendiri tidak dikira berulang semasa kemaskini.
            ->set('typeClass', 'bg-primary')
            ->call('saveType')
            ->assertHasNoErrors();

        $type->refresh();
        $this->assertSame('Mesyuarat', $type->name);
        $this->assertSame('bg-primary', $type->class);
    }

    /** Nyahaktif, bukan hapus: entri lama kekal memaparkan jenisnya (Req 5.3). */
    public function test_jenis_dinyahaktifkan_tidak_ditawarkan_tetapi_entri_lama_kekal(): void
    {
        $this->actingAsRosterAdmin();
        $type = MovementType::factory()->create(['name' => 'Jenis Lama']);
        $movement = Movement::factory()->create(['movement_type_id' => $type->id]);

        Livewire::test(MovementTypeIndex::class)->call('deactivateType', $type->id);

        $this->assertFalse($type->fresh()->is_active);
        $this->assertFalse(MovementType::active()->pluck('id')->contains($type->id));
        $this->assertSame('Jenis Lama', $movement->fresh()->type->name);

        Livewire::test(MovementTypeIndex::class)->call('restoreType', $type->id);

        $this->assertTrue($type->fresh()->is_active);
    }

    public function test_cuti_umum_ditambah_untuk_semua_unit(): void
    {
        $this->actingAsRosterAdmin();

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Deepavali')
            ->set('holidayStart', '2026-11-08')
            ->set('holidayEnd', '2026-11-08')
            ->call('saveHoliday')
            ->assertHasNoErrors()
            ->assertSet('showHolidayModal', false)
            // Senarai bertukar ke tahun cuti yang baru disimpan supaya ia kelihatan.
            ->assertSet('year', 2026);

        // Dibandingkan melalui model: lajur tarikh disimpan dengan masa pada
        // SQLite ujian tetapi sebagai DATE pada MySQL.
        $holiday = MovementHoliday::where('name', 'Deepavali')->sole();

        $this->assertSame('2026-11-08', $holiday->start_date->toDateString());
        $this->assertSame('2026-11-08', $holiday->end_date->toDateString());
        $this->assertNull($holiday->movement_unit_id);
    }

    public function test_cuti_umum_khusus_satu_unit(): void
    {
        $this->actingAsRosterAdmin();
        $unit = MovementUnit::factory()->create();

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Cuti Unit')
            ->set('holidayStart', '2026-06-01')
            ->set('holidayEnd', '2026-06-01')
            ->set('holidayUnitId', $unit->id)
            ->call('saveHoliday')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('movement_holidays', [
            'name' => 'Cuti Unit',
            'movement_unit_id' => $unit->id,
        ]);
    }

    public function test_cuti_umum_menolak_tarikh_tamat_lebih_awal(): void
    {
        $this->actingAsRosterAdmin();

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Cuti Salah')
            ->set('holidayStart', '2026-11-10')
            ->set('holidayEnd', '2026-11-09')
            ->call('saveHoliday')
            ->assertHasErrors(['holidayEnd' => 'after_or_equal']);

        $this->assertDatabaseMissing('movement_holidays', ['name' => 'Cuti Salah']);
    }

    public function test_nama_dan_tarikh_cuti_umum_wajib(): void
    {
        $this->actingAsRosterAdmin();

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->call('saveHoliday')
            ->assertHasErrors([
                'holidayName' => 'required',
                'holidayStart' => 'required',
                'holidayEnd' => 'required',
            ]);
    }

    /** Cuti yang sama pada tarikh yang sama tidak boleh direkod dua kali. */
    public function test_cuti_umum_yang_sama_pada_tarikh_sama_ditolak(): void
    {
        $this->actingAsRosterAdmin();
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Deepavali')
            ->set('holidayStart', '2026-11-08')
            ->set('holidayEnd', '2026-11-08')
            ->call('saveHoliday')
            ->assertHasErrors('holidayName');

        $this->assertSame(1, MovementHoliday::where('name', 'Deepavali')->count());
    }

    /** Padanan nama tidak peka huruf besar/kecil dan ruang di hujung. */
    public function test_cuti_umum_pendua_dikesan_walaupun_huruf_dan_ruang_berbeza(): void
    {
        $this->actingAsRosterAdmin();
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', '  deepavali  ')
            ->set('holidayStart', '2026-11-08')
            ->set('holidayEnd', '2026-11-08')
            ->call('saveHoliday')
            ->assertHasErrors('holidayName');

        $this->assertSame(1, MovementHoliday::count());
    }

    /** Julat yang bertindih pun pendua, bukan hanya tarikh yang serupa. */
    public function test_cuti_umum_dengan_julat_bertindih_ditolak(): void
    {
        $this->actingAsRosterAdmin();
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Deepavali')
            ->set('holidayStart', '2026-11-07')
            ->set('holidayEnd', '2026-11-09')
            ->call('saveHoliday')
            ->assertHasErrors('holidayName');

        $this->assertSame(1, MovementHoliday::count());
    }

    public function test_cuti_sama_pada_tarikh_lain_dibenarkan(): void
    {
        $this->actingAsRosterAdmin();
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Deepavali')
            ->set('holidayStart', '2027-10-28')
            ->set('holidayEnd', '2027-10-28')
            ->call('saveHoliday')
            ->assertHasNoErrors();

        $this->assertSame(2, MovementHoliday::where('name', 'Deepavali')->count());
    }

    /** Dua cuti berlainan pada tarikh yang sama masih dibenarkan. */
    public function test_cuti_berbeza_pada_tarikh_sama_dibenarkan(): void
    {
        $this->actingAsRosterAdmin();
        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Hari Keputeraan')
            ->set('holidayStart', '2026-11-08')
            ->set('holidayEnd', '2026-11-08')
            ->call('saveHoliday')
            ->assertHasNoErrors();

        $this->assertSame(2, MovementHoliday::count());
    }

    public function test_kemaskini_cuti_tidak_menganggap_dirinya_sendiri_pendua(): void
    {
        $this->actingAsRosterAdmin();
        $holiday = MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        Livewire::test(MovementTypeIndex::class)
            ->set('year', 2026)
            ->call('editHoliday', $holiday->id)
            ->set('holidayEnd', '2026-11-09')
            ->call('saveHoliday')
            ->assertHasNoErrors();

        $this->assertSame('2026-11-09', $holiday->fresh()->end_date->toDateString());
    }

    public function test_cuti_senama_untuk_unit_berbeza_dibenarkan_tetapi_cuti_global_dikira_pendua(): void
    {
        $this->actingAsRosterAdmin();
        $unitA = MovementUnit::factory()->create();
        $unitB = MovementUnit::factory()->create();

        MovementHoliday::factory()->onDate('2026-06-01')->create([
            'name' => 'Cuti Peristiwa',
            'movement_unit_id' => $unitA->id,
        ]);

        // Unit lain: bukan pendua.
        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Cuti Peristiwa')
            ->set('holidayStart', '2026-06-01')
            ->set('holidayEnd', '2026-06-01')
            ->set('holidayUnitId', $unitB->id)
            ->call('saveHoliday')
            ->assertHasNoErrors();

        // Global merangkumi unit A, jadi ia pendua.
        Livewire::test(MovementTypeIndex::class)
            ->call('createHoliday')
            ->set('holidayName', 'Cuti Peristiwa')
            ->set('holidayStart', '2026-06-01')
            ->set('holidayEnd', '2026-06-01')
            ->call('saveHoliday')
            ->assertHasErrors('holidayName');

        $this->assertSame(2, MovementHoliday::count());
    }

    public function test_cuti_umum_boleh_dikemaskini_dan_dihapus(): void
    {
        $this->actingAsRosterAdmin();
        $holiday = MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali']);

        Livewire::test(MovementTypeIndex::class)
            ->set('year', 2026)
            ->call('editHoliday', $holiday->id)
            ->assertSet('holidayName', 'Deepavali')
            ->assertSet('holidayStart', '2026-11-08')
            ->set('holidayEnd', '2026-11-09')
            ->call('saveHoliday')
            ->assertHasNoErrors();

        $this->assertSame('2026-11-09', $holiday->fresh()->end_date->toDateString());

        Livewire::test(MovementTypeIndex::class)->call('deleteHoliday', $holiday->id);

        $this->assertDatabaseMissing('movement_holidays', ['id' => $holiday->id]);
    }

    public function test_senarai_cuti_umum_ditapis_mengikut_tahun(): void
    {
        Carbon::setTestNow('2026-11-14 10:00:00');
        $this->actingAsRosterAdmin();

        MovementHoliday::factory()->onDate('2026-11-08')->create(['name' => 'Deepavali 2026']);
        MovementHoliday::factory()->onDate('2027-01-01')->create(['name' => 'Tahun Baharu 2027']);

        Livewire::test(MovementTypeIndex::class)
            ->assertSet('year', 2026)
            ->assertSee('Deepavali 2026')
            ->assertDontSee('Tahun Baharu 2027')
            ->set('year', 2027)
            ->assertSee('Tahun Baharu 2027')
            ->assertDontSee('Deepavali 2026');

        Carbon::setTestNow();
    }

    public function test_pengguna_tanpa_kebenaran_tidak_boleh_memanggil_tindakan_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pergerakan.view');
        $this->actingAs($user);

        $type = MovementType::factory()->create();

        Livewire::test(MovementTypeIndex::class)
            ->call('deactivateType', $type->id)
            ->assertForbidden();

        $this->assertTrue($type->fresh()->is_active);
    }
}
