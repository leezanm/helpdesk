<?php

namespace Tests\Feature\Livewire\Category;

use App\Livewire\Category\CategoryForm;
use App\Models\ReportCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class CategoryFormTest extends FeatureTestCase
{
    public function test_create_requires_desc()
    {
        Livewire::test(CategoryForm::class)
            ->call('openCreate')
            ->set('desc', '')
            ->call('save')
            ->assertHasErrors(['desc' => 'required']);

        $this->assertDatabaseCount('report_categories', 0);
    }

    public function test_creates_category_without_image()
    {
        Livewire::test(CategoryForm::class)
            ->call('openCreate')
            ->set('desc', 'Rangkaian')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('category-saved');

        $this->assertDatabaseHas('report_categories', ['desc' => 'Rangkaian']);
    }

    public function test_creates_category_with_image()
    {
        Storage::fake('local');

        Livewire::test(CategoryForm::class)
            ->call('openCreate')
            ->set('desc', 'Rangkaian')
            ->set('file', UploadedFile::fake()->image('cat.png'))
            ->call('save')
            ->assertHasNoErrors();

        $category = ReportCategory::first();
        $this->assertNotNull($category->image_path);
        $this->assertStringStartsWith('storage/images/categories', $category->image_path);
    }

    public function test_edits_existing_category()
    {
        $category = ReportCategory::create(['desc' => 'Lama', 'is_active' => true]);

        Livewire::test(CategoryForm::class)
            ->call('openEdit', $category->id)
            ->assertSet('desc', 'Lama')
            ->set('desc', 'Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Baru', $category->fresh()->desc);
    }
}
