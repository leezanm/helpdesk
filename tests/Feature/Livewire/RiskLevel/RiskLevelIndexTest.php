<?php

namespace Tests\Feature\Livewire\RiskLevel;

use App\Livewire\RiskLevel\RiskLevelIndex;
use App\Models\Ref\RiskLevel;
use App\Models\User;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class RiskLevelIndexTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reference-data maintenance requires manage.category.
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage.category');
        $this->actingAs($admin);
    }

    public function test_lists_all_five_levels()
    {
        Livewire::test(RiskLevelIndex::class)
            ->assertOk()
            ->assertSee('Sangat Rendah')
            ->assertSee('Sederhana')
            ->assertSee('Sangat Tinggi');
    }

    public function test_edit_loads_level()
    {
        $level = RiskLevel::where('level', 3)->first();

        Livewire::test(RiskLevelIndex::class)
            ->call('edit', $level->id)
            ->assertSet('editId', $level->id)
            ->assertSet('level', 3)
            ->assertSet('desc', $level->desc)
            ->assertSet('showModal', true);
    }

    public function test_update_requires_name_and_criteria()
    {
        $level = RiskLevel::first();

        Livewire::test(RiskLevelIndex::class)
            ->call('edit', $level->id)
            ->set('desc', '')
            ->set('reference', '')
            ->call('save')
            ->assertHasErrors(['desc' => 'required', 'reference' => 'required']);
    }

    public function test_update_rejects_unknown_badge_class()
    {
        $level = RiskLevel::first();

        Livewire::test(RiskLevelIndex::class)
            ->call('edit', $level->id)
            ->set('class', 'onmouseover=alert(1)')
            ->call('save')
            ->assertHasErrors(['class']);
    }

    public function test_updates_label_criteria_and_colour()
    {
        $level = RiskLevel::where('level', 2)->first();

        Livewire::test(RiskLevelIndex::class)
            ->call('edit', $level->id)
            ->set('desc', 'Rendah (Dikemaskini)')
            ->set('reference', 'Kriteria baharu.')
            ->set('class', 'badge bg-primary')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $level->refresh();
        $this->assertEquals('Rendah (Dikemaskini)', $level->desc);
        $this->assertEquals('Kriteria baharu.', $level->reference);
        $this->assertEquals('badge bg-primary', $level->class);
        // The numeric level stays system-fixed.
        $this->assertEquals(2, $level->level);
    }

    public function test_deactivate_and_restore_level()
    {
        $level = RiskLevel::where('level', 4)->first();

        Livewire::test(RiskLevelIndex::class)
            ->call('deactivate', $level->id);
        $this->assertFalse($level->fresh()->is_active);
        $this->assertFalse(RiskLevel::active()->where('id', $level->id)->exists());

        Livewire::test(RiskLevelIndex::class)
            ->call('restore', $level->id);
        $this->assertTrue($level->fresh()->is_active);
    }

    public function test_user_without_permission_cannot_edit()
    {
        $this->actingAs(User::factory()->create());
        $level = RiskLevel::first();

        Livewire::test(RiskLevelIndex::class)
            ->call('edit', $level->id)
            ->assertForbidden();
    }

    public function test_maintenance_page_requires_permission()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/aras-risiko')->assertForbidden();
    }
}
