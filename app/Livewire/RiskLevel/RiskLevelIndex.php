<?php

namespace App\Livewire\RiskLevel;

use App\Models\Ref\RiskLevel;
use Livewire\Component;

/**
 * Risk level (tahap risiko) maintenance. Levels 1..5 are seeded reference
 * data: edit-only, no create/delete, so no aduan can end up pointing at a
 * level that was removed. The numeric `level` is system-fixed; the label,
 * criteria text and badge colour are editable, and a level can be deactivated
 * so it is no longer offered on new assessments.
 */
class RiskLevelIndex extends Component
{
    public ?int $editId = null;
    public int $level = 0;
    public string $desc = '';
    public string $reference = '';
    public string $class = '';
    public bool $showModal = false;

    /** Badge classes offered in the form — keeps the theme consistent. */
    public const BADGE_CLASSES = [
        'badge bg-secondary' => 'Kelabu',
        'badge bg-info' => 'Biru Muda',
        'badge bg-primary' => 'Biru',
        'badge bg-success' => 'Hijau',
        'badge bg-warning' => 'Kuning',
        'badge bg-danger' => 'Merah',
        'badge bg-dark' => 'Hitam',
    ];

    protected function rules(): array
    {
        return [
            'desc' => 'required|string|max:255',
            'reference' => 'required|string|max:2000',
            'class' => 'required|in:'.implode(',', array_keys(self::BADGE_CLASSES)),
        ];
    }

    protected array $messages = [
        'desc.required' => 'Nama tahap risiko wajib diisi.',
        'reference.required' => 'Keterangan / kriteria tahap risiko wajib diisi.',
        'class.required' => 'Sila pilih warna paparan.',
        'class.in' => 'Warna paparan tidak sah.',
    ];

    public function edit(int $id): void
    {
        $this->authorize('manage.category');

        $riskLevel = RiskLevel::findOrFail($id);
        $this->editId = $riskLevel->id;
        $this->level = $riskLevel->level;
        $this->desc = $riskLevel->desc;
        $this->reference = $riskLevel->reference;
        $this->class = $riskLevel->class;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('manage.category');
        $this->validate();

        $riskLevel = RiskLevel::findOrFail($this->editId);
        $riskLevel->desc = $this->desc;
        $riskLevel->reference = $this->reference;
        $riskLevel->class = $this->class;
        $riskLevel->save();

        $this->closeModal();
        session()->flash('risk-level-message', 'Tahap risiko berjaya dikemaskini.');
    }

    /** Deactivated levels stay on the aduan that already use them. */
    public function deactivate(int $id): void
    {
        $this->authorize('manage.category');

        $riskLevel = RiskLevel::findOrFail($id);
        $riskLevel->is_active = false;
        $riskLevel->save();

        session()->flash('risk-level-message', $riskLevel->label.' tidak lagi boleh dipilih untuk aduan baru.');
    }

    public function restore(int $id): void
    {
        $this->authorize('manage.category');

        $riskLevel = RiskLevel::findOrFail($id);
        $riskLevel->is_active = true;
        $riskLevel->save();

        session()->flash('risk-level-message', $riskLevel->label.' kini boleh dipilih semula.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editId', 'level', 'desc', 'reference', 'class']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.risk-level.risk-level-index', [
            'riskLevels' => RiskLevel::withCount('reports')->orderBy('level')->get(),
            'badgeClasses' => self::BADGE_CLASSES,
        ]);
    }
}
