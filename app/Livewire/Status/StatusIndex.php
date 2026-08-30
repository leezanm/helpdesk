<?php

namespace App\Livewire\Status;

use App\Models\Ref\ReportStatus;
use Livewire\Component;

/**
 * Report status listing + inline edit (replaces the DataTables page + AJAX
 * edit modal). Statuses are seeded reference data: edit-only, no create/delete.
 * Only the public "desc" is editable; "reference" is system-fixed.
 */
class StatusIndex extends Component
{
    public ?int $editId = null;
    public string $desc = '';
    public string $reference = '';
    public bool $showModal = false;

    protected function rules(): array
    {
        return ['desc' => 'required|string|max:255'];
    }

    protected array $messages = [
        'desc.required' => 'Nama untuk kegunaan umum wajib diisi.',
    ];

    public function edit(int $id): void
    {
        $this->authorize('manage.category');
        $status = ReportStatus::findOrFail($id);
        $this->editId = $status->id;
        $this->desc = $status->desc;
        $this->reference = (string) $status->reference;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('manage.category');
        $this->validate();

        $status = ReportStatus::findOrFail($this->editId);
        $status->desc = $this->desc;
        $status->save();

        $this->showModal = false;
        $this->reset(['editId', 'desc', 'reference']);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editId', 'desc', 'reference']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.status.status-index', [
            'report_statuses' => ReportStatus::orderBy('id')->get(),
        ]);
    }
}
