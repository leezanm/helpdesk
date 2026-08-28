<?php

namespace App\Livewire\Category;

use App\Models\ReportCategory;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Report category listing with search + pagination (replaces the DataTables
 * page + jQuery AJAX). Create/edit are delegated to CategoryForm via events;
 * deactivate/restore are handled here.
 */
class CategoryIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $showId = null;   // category being viewed in the show modal

    protected $listeners = ['category-saved' => 'onSaved'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function onSaved(): void
    {
        // Form saved; pagination stays, list re-renders with fresh data.
        $this->resetPage();
    }

    public function create(): void
    {
        $this->dispatch('category-create');
    }

    public function edit(int $id): void
    {
        $this->dispatch('category-edit', id: $id);
    }

    public function show(int $id): void
    {
        $this->showId = $id;
    }

    public function closeShow(): void
    {
        $this->showId = null;
    }

    public function deactivate(int $id): void
    {
        $category = ReportCategory::findOrFail($id);
        $category->is_active = false;
        $category->save();
    }

    public function restore(int $id): void
    {
        $category = ReportCategory::findOrFail($id);
        $category->is_active = true;
        $category->save();
    }

    #[On('category-saved')]
    public function refresh(): void
    {
        // no-op; triggers a re-render when the form dispatches category-saved
    }

    public function render()
    {
        $categories = ReportCategory::query()
            ->when($this->search !== '', fn ($q) => $q->where('desc', 'like', '%'.$this->search.'%'))
            ->orderBy('desc')
            ->paginate(10);

        $showCategory = $this->showId ? ReportCategory::find($this->showId) : null;

        return view('livewire.category.category-index', [
            'categories' => $categories,
            'showCategory' => $showCategory,
        ]);
    }
}
