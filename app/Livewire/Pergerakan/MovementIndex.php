<?php

namespace App\Livewire\Pergerakan;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Senarai pergerakan dengan penapis dan carian — untuk menjawab soalan seperti
 * "siapa keluar minggu ini" atau "semua kursus bulan ini", yang sukar dilihat
 * pada grid bulanan.
 *
 * Tempoh lalai ialah bulan semasa. Penapis tempoh menggunakan logik
 * pertindihan yang sama seperti jadual: entri berbilang hari muncul apabila
 * mana-mana sebahagian julatnya berada dalam tempoh yang ditapis.
 */
class MovementIndex extends Component
{
    use WithPagination;

    public ?int $unitId = null;
    public ?int $officerId = null;
    public ?int $typeId = null;
    public string $from = '';
    public string $to = '';
    public string $search = '';

    /** Tapis kepada pergerakan sendiri sahaja. */
    public bool $mineOnly = false;

    public function mount(): void
    {
        $this->authorize('pergerakan.view');

        $this->unitId = MovementUnit::active()->orderBy('name')->value('id');
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
    }

    public function updatingUnitId(): void
    {
        // Pegawai yang dipilih mungkin bukan daripada unit baharu.
        $this->officerId = null;
        $this->resetPage();
    }

    public function updatingOfficerId(): void
    {
        $this->resetPage();
    }

    public function updatingTypeId(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMineOnly(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['officerId', 'typeId', 'search', 'mineOnly']);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function create(): void
    {
        $this->dispatch('movement-create');
    }

    public function edit(int $id): void
    {
        $this->dispatch('movement-edit', id: $id);
    }

    public function delete(int $id): void
    {
        $movement = Movement::with('officer')->findOrFail($id);
        $this->authorize('delete', $movement);

        $movement->delete();

        session()->flash('movement-message', 'Pergerakan "'.$movement->title.'" telah dihapus.');
    }

    #[On('movement-saved')]
    public function onSaved(): void
    {
        $this->resetPage();
    }

    /** Tempoh terbalik: beritahu pengguna dan bukan pulangkan senarai kosong tanpa sebab. */
    public function hasInvalidRange(): bool
    {
        return $this->from !== '' && $this->to !== '' && $this->from > $this->to;
    }

    public function render()
    {
        $movements = Movement::with(['type', 'officer'])
            ->when($this->unitId !== null, fn ($q) => $q->forUnit($this->unitId))
            ->when($this->officerId !== null, fn ($q) => $q->where('monitored_officer_id', $this->officerId))
            ->when($this->typeId !== null, fn ($q) => $q->where('movement_type_id', $this->typeId))
            ->when($this->mineOnly, fn ($q) => $q->whereHas('officer', fn ($o) => $o->where('user_id', auth()->id())))
            // Tempoh terbalik: jangan pulangkan segalanya seolah-olah tiada
            // penapis — pengguna melihat amaran dan senarai kosong.
            ->when($this->hasInvalidRange(), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($this->from !== '' && $this->to !== '' && ! $this->hasInvalidRange(),
                fn ($q) => $q->overlapping($this->from, $this->to))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(fn ($inner) => $inner->where('title', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhere('remarks', 'like', $term));
            })
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->paginate(25);

        return view('livewire.pergerakan.movement-index', [
            'movements' => $movements,
            'units' => MovementUnit::active()->orderBy('name')->get(),
            'officers' => MonitoredOfficer::query()
                ->when($this->unitId !== null, fn ($q) => $q->where('movement_unit_id', $this->unitId))
                ->ordered()
                ->get(),
            'types' => MovementType::ordered()->get(),
            'rangeLabel' => $this->from !== '' && $this->to !== ''
                ? Carbon::parse($this->from)->format('d/m/Y').' - '.Carbon::parse($this->to)->format('d/m/Y')
                : '',
        ]);
    }
}
