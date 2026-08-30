<?php

namespace App\Livewire\Category;

use App\Actions\Categories\SaveCategory;
use App\Models\ReportCategory;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Create / edit form for a report category (replaces the create/edit AJAX
 * modals + hand-written validation JS). Server-side validation via rules().
 */
class CategoryForm extends Component
{
    use WithFileUploads;

    public ?int $categoryId = null;
    public string $desc = '';
    public $file = null;           // new upload (Livewire temporary file)
    public bool $showModal = false;

    protected function rules(): array
    {
        return [
            'desc' => 'required|string|max:255',
            // Image required on create, optional on edit (matches prior UX).
            'file' => ($this->categoryId ? 'nullable' : 'nullable').'|image|max:5120',
        ];
    }

    protected array $messages = [
        'desc.required' => 'Nama wajib diisi.',
        'file.image' => 'Fail mestilah imej.',
        'file.max' => 'Saiz imej maksimum 5MB.',
    ];

    #[On('category-create')]
    public function openCreate(): void
    {
        $this->reset(['categoryId', 'desc', 'file']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    #[On('category-edit')]
    public function openEdit(int $id): void
    {
        $category = ReportCategory::findOrFail($id);
        $this->categoryId = $category->id;
        $this->desc = $category->desc;
        $this->file = null;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(SaveCategory $saveCategory): void
    {
        $this->authorize('manage.category');
        $this->validate();

        $category = $this->categoryId
            ? ReportCategory::findOrFail($this->categoryId)
            : new ReportCategory();

        $saveCategory->handle($category, $this->desc, $this->file);

        $this->showModal = false;
        $this->reset(['categoryId', 'desc', 'file']);

        $this->dispatch('category-saved');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['categoryId', 'desc', 'file']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.category.category-form');
    }
}
