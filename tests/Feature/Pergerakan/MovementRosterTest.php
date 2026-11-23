<?php

namespace Tests\Feature\Pergerakan;

use App\Livewire\Pergerakan\RosterIndex;
use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementUnit;
use App\Models\User;
use Database\Seeders\MovementUnitSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Senarai pegawai dipantau (roster) — Req 1.
 */
class MovementRosterTest extends FeatureTestCase
{
    private MovementUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MovementUnitSeeder::class);
        $this->unit = MovementUnit::firstOrFail();
    }

    private function actingAsRosterAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('pergerakan.manageRoster');
        $this->actingAs($admin);

        return $admin;
    }

    public function test_halaman_roster_memerlukan_kebenaran_manage_roster(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pergerakan.view');

        $this->actingAs($user)->get(route('pergerakan.roster'))->assertForbidden();

        $this->actingAsRosterAdmin();
        $this->get(route('pergerakan.roster'))->assertOk()->assertSee('Pegawai Dipantau');
    }

    public function test_menu_pergerakan_hanya_untuk_pemegang_manage_roster(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pergerakan.view');

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee(route('pergerakan.roster'));

        $this->actingAsRosterAdmin();
        $this->get('/dashboard')->assertOk()->assertSee(route('pergerakan.roster'));
    }

    public function test_pegawai_ditambah_ke_senarai_pemantauan(): void
    {
        $this->actingAsRosterAdmin();
        $pegawai = User::factory()->create(['name' => 'Syarul Emy bin Abu Samah']);

        Livewire::test(RosterIndex::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->set('userId', $pegawai->id)
            // Nama paparan dicadangkan daripada nama akaun.
            ->assertSet('displayName', 'Syarul Emy bin Abu Samah')
            ->set('displayName', 'En. Syarul Emy bin Abu Samah')
            ->set('sortOrder', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('monitored_officers', [
            'movement_unit_id' => $this->unit->id,
            'user_id' => $pegawai->id,
            'display_name' => 'En. Syarul Emy bin Abu Samah',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_pegawai_dan_nama_paparan_wajib_diisi(): void
    {
        $this->actingAsRosterAdmin();

        Livewire::test(RosterIndex::class)
            ->call('create')
            ->set('userId', null)
            ->set('displayName', '')
            ->call('save')
            ->assertHasErrors(['userId' => 'required', 'displayName' => 'required']);
    }

    public function test_pegawai_yang_sama_tidak_boleh_ditambah_dua_kali(): void
    {
        $this->actingAsRosterAdmin();
        $pegawai = User::factory()->create();

        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'user_id' => $pegawai->id,
        ]);

        Livewire::test(RosterIndex::class)
            ->call('create')
            ->set('userId', $pegawai->id)
            ->set('displayName', 'Nama Paparan')
            ->call('save')
            ->assertHasErrors(['userId' => 'unique']);

        $this->assertSame(1, MonitoredOfficer::where('user_id', $pegawai->id)->count());
    }

    /** Pegawai yang dinyahaktifkan masih mengambil tempat — aktifkan semula, bukan tambah baharu. */
    public function test_pegawai_tidak_aktif_tidak_boleh_ditambah_semula_sebagai_baris_baharu(): void
    {
        $this->actingAsRosterAdmin();
        $pegawai = User::factory()->create();

        MonitoredOfficer::factory()->inactive()->create([
            'movement_unit_id' => $this->unit->id,
            'user_id' => $pegawai->id,
        ]);

        Livewire::test(RosterIndex::class)
            ->call('create')
            ->set('userId', $pegawai->id)
            ->set('displayName', 'Nama Paparan')
            ->call('save')
            ->assertHasErrors(['userId' => 'unique']);
    }

    public function test_nama_paparan_dan_susunan_boleh_dikemaskini(): void
    {
        $this->actingAsRosterAdmin();
        $officer = MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'Nama Lama',
            'sort_order' => 5,
        ]);

        Livewire::test(RosterIndex::class)
            ->call('edit', $officer->id)
            ->assertSet('displayName', 'Nama Lama')
            ->assertSet('sortOrder', 5)
            ->set('displayName', 'Pn. Nama Baharu')
            ->set('sortOrder', 2)
            ->call('save')
            ->assertHasNoErrors();

        $officer->refresh();
        $this->assertSame('Pn. Nama Baharu', $officer->display_name);
        $this->assertSame(2, $officer->sort_order);
    }

    public function test_senarai_dipaparkan_mengikut_susunan_paparan(): void
    {
        $this->actingAsRosterAdmin();

        foreach ([['Ketiga', 3], ['Pertama', 1], ['Kedua', 2]] as [$name, $order]) {
            MonitoredOfficer::factory()->create([
                'movement_unit_id' => $this->unit->id,
                'display_name' => $name,
                'sort_order' => $order,
            ]);
        }

        Livewire::test(RosterIndex::class)
            ->assertOk()
            ->assertSeeInOrder(['Pertama', 'Kedua', 'Ketiga']);
    }

    /** Nyahaktif, bukan hapus: entri pergerakan lepas kekal (Req 1.4). */
    public function test_nyahaktif_mengekalkan_pegawai_dan_pergerakannya(): void
    {
        $this->actingAsRosterAdmin();
        $officer = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);
        $movement = Movement::factory()->create(['monitored_officer_id' => $officer->id]);

        Livewire::test(RosterIndex::class)->call('deactivate', $officer->id);

        $this->assertFalse($officer->fresh()->is_active);
        $this->assertDatabaseHas('monitored_officers', ['id' => $officer->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('movements', ['id' => $movement->id, 'deleted_at' => null]);

        Livewire::test(RosterIndex::class)->call('restore', $officer->id);

        $this->assertTrue($officer->fresh()->is_active);
    }

    public function test_pengguna_tanpa_kebenaran_tidak_boleh_memanggil_tindakan_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pergerakan.view');
        $this->actingAs($user);

        $officer = MonitoredOfficer::factory()->create(['movement_unit_id' => $this->unit->id]);

        Livewire::test(RosterIndex::class)
            ->call('deactivate', $officer->id)
            ->assertForbidden();

        $this->assertTrue($officer->fresh()->is_active);
    }

    /** Carian pengguna: hanya akaun aktif dan yang belum ada dalam roster. */
    public function test_carian_pengguna_tidak_menawarkan_akaun_tidak_aktif_atau_yang_sudah_dalam_roster(): void
    {
        $this->actingAsRosterAdmin();

        $boleh = User::factory()->create(['name' => 'Zafri Boleh Pilih']);
        $tidakAktif = User::factory()->create(['name' => 'Zafri Tidak Aktif', 'is_active' => false]);
        $sudahAda = User::factory()->create(['name' => 'Zafri Sudah Ada']);

        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'user_id' => $sudahAda->id,
            'display_name' => 'Dalam Roster',
        ]);

        // Padanan dibuat pada teks pilihan ("Nama (username)") yang hanya muncul
        // dalam senarai pilihan — nama akaun sendiri turut dipaparkan dalam
        // jadual roster, jadi nama semata-mata bukan penanda yang tepat.
        Livewire::test(RosterIndex::class)
            ->call('create')
            ->set('userSearch', 'Zafri')
            ->assertSee($boleh->name.' ('.$boleh->username.')')
            ->assertDontSee($tidakAktif->name.' ('.$tidakAktif->username.')')
            ->assertDontSee($sudahAda->name.' ('.$sudahAda->username.')');
    }

    public function test_roster_terhad_kepada_unit_yang_dipaparkan(): void
    {
        $this->actingAsRosterAdmin();

        MonitoredOfficer::factory()->create([
            'movement_unit_id' => $this->unit->id,
            'display_name' => 'Pegawai Unit Ini',
        ]);
        MonitoredOfficer::factory()->create([
            'movement_unit_id' => MovementUnit::factory()->create()->id,
            'display_name' => 'Pegawai Unit Lain',
        ]);

        Livewire::test(RosterIndex::class)
            ->assertSee('Pegawai Unit Ini')
            ->assertDontSee('Pegawai Unit Lain');
    }

    /** Rekod unit lain tidak boleh disentuh melalui komponen unit ini. */
    public function test_tindakan_menolak_rekod_daripada_unit_lain(): void
    {
        $this->actingAsRosterAdmin();

        $lain = MonitoredOfficer::factory()->create([
            'movement_unit_id' => MovementUnit::factory()->create()->id,
            'display_name' => 'Pegawai Unit Lain',
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(RosterIndex::class)->call('deactivate', $lain->id);
    }
}
