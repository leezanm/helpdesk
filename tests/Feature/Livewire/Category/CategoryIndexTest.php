<?php

namespace Tests\Feature\Livewire\Category;

use App\Livewire\Category\CategoryIndex;
use App\Models\ReportCategory;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class CategoryIndexTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Category CRUD requires manage.category.
        $admin = \App\Models\User::factory()->create();
        $admin->givePermissionTo('manage.category');
        $this->actingAs($admin);
    }

    public function test_lists_categories()
    {
        ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);
        ReportCategory::create(['desc' => 'Perkakasan', 'is_active' => true]);

        Livewire::test(CategoryIndex::class)
            ->assertOk()
            ->assertSee('Rangkaian')
            ->assertSee('Perkakasan');
    }

    public function test_search_filters_categories()
    {
        ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);
        ReportCategory::create(['desc' => 'Perkakasan', 'is_active' => true]);

        Livewire::test(CategoryIndex::class)
            ->set('search', 'Rangkai')
            ->assertSee('Rangkaian')
            ->assertDontSee('Perkakasan');
    }

    public function test_deactivate_sets_inactive()
    {
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        Livewire::test(CategoryIndex::class)
            ->call('deactivate', $category->id);

        $this->assertFalse((bool) $category->fresh()->is_active);
    }

    public function test_restore_sets_active()
    {
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => false]);

        Livewire::test(CategoryIndex::class)
            ->call('restore', $category->id);

        $this->assertTrue((bool) $category->fresh()->is_active);
    }

    public function test_show_loads_selected_category()
    {
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        Livewire::test(CategoryIndex::class)
            ->call('show', $category->id)
            ->assertSet('showId', $category->id)
            ->assertSee('Papar Maklumat Kategori Aduan');
    }
}
