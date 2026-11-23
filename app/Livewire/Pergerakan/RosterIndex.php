<?php

namespace App\Livewire\Pergerakan;

use App\Models\MonitoredOfficer;
use App\Models\MovementUnit;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Senarai pegawai dipantau (roster) bagi satu unit.
 *
 * Pemantauan adalah TERPILIH: hanya pegawai dalam senarai ini menjadi baris
 * dalam jadual bulanan. Pegawai yang tidak lagi dipantau DINYAHAKTIFKAN dan
 * bukan dihapus, supaya entri pergerakan lepasnya kekal utuh dan jadual
 * bulan-bulan lepas tidak berubah.
 *
 * Semua tindakan memerlukan `pergerakan.manageRoster` (lalai: helpdesk.admin
 * dan superadmin). Laluan juga digeret oleh middleware `can:` — semakan di sini
 * ialah lapisan kedua supaya tindakan Livewire tidak boleh dipanggil terus.
 */
class RosterIndex extends Component
{
    public ?int $unitId = null;

    /** Borang tambah/kemaskini. */
    public ?int $editId = null;
    public ?int $userId = null;
    public string $displayName = '';
    public int $sortOrder = 0;
    public bool $showModal = false;

    /** Carian pengguna dalam modal tambah. */
    public string $userSearch = '';

    public function mount(): void
    {
        $this->unitId = MovementUnit::active()->orderBy('name')->value('id');
    }

    protected function rules(): array
    {
        return [
            'userId' => [
                'required',
                'integer',
                'exists:users,id',
                // Seorang pegawai hanya sekali dalam satu unit. Baris yang
                // dinyahaktifkan pun masih mengambil tempat: ia patut
                // diaktifkan semula, bukan ditambah kedua kali.
                Rule::unique('monitored_officers', 'user_id')
                    ->where('movement_unit_id', $this->unitId)
                    ->whereNull('deleted_at')
                    ->ignore($this->editId),
            ],
            'displayName' => 'required|string|max:255',
            'sortOrder' => 'required|integer|min:0|max:999',
        ];
    }

    protected function messages(): array
    {
        return [
            'userId.required' => 'Sila pilih pegawai.',
            'userId.exists' => 'Pengguna tidak sah.',
            'userId.unique' => 'Pegawai ini sudah ada dalam senarai pemantauan unit ini.',
            'displayName.required' => 'Nama paparan wajib diisi.',
            'sortOrder.required' => 'Susunan paparan wajib diisi.',
            'sortOrder.integer' => 'Susunan paparan mesti nombor.',
        ];
    }

    public function create(): void
    {
        $this->authorize('pergerakan.manageRoster');

        $this->reset(['editId', 'userId', 'displayName', 'userSearch']);
        // Letak di hujung senarai supaya susunan sedia ada tidak terganggu.
        $this->sortOrder = ((int) $this->unitQuery()->max('sort_order')) + 1;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $officer = $this->unitQuery()->findOrFail($id);

        $this->editId = $officer->id;
        $this->userId = $officer->user_id;
        $this->displayName = $officer->display_name;
        $this->sortOrder = $officer->sort_order;
        $this->userSearch = '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    /** Nama paparan dicadangkan daripada nama akaun, dan masih boleh diubah. */
    public function updatedUserId($value): void
    {
        if ($this->editId !== null || blank($value)) {
            return;
        }

        $this->displayName = (string) User::whereKey($value)->value('name');
    }

    public function save(): void
    {
        $this->authorize('pergerakan.manageRoster');
        $this->validate();

        if ($this->editId !== null) {
            $officer = $this->unitQuery()->findOrFail($this->editId);
            $officer->update([
                'display_name' => $this->displayName,
                'sort_order' => $this->sortOrder,
            ]);

            $message = $officer->display_name.' berjaya dikemaskini.';
        } else {
            $officer = MonitoredOfficer::create([
                'movement_unit_id' => $this->unitId,
                'user_id' => $this->userId,
                'display_name' => $this->displayName,
                'sort_order' => $this->sortOrder,
                'is_active' => true,
            ]);

            $message = $officer->display_name.' ditambah ke senarai pemantauan.';
        }

        $this->closeModal();
        session()->flash('roster-message', $message);
    }

    /** Nyahaktif, bukan hapus: entri pergerakan lepas kekal utuh. */
    public function deactivate(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $officer = $this->unitQuery()->findOrFail($id);
        $officer->update(['is_active' => false]);

        session()->flash('roster-message', $officer->display_name.' tidak lagi dipantau. Pergerakan lepas kekal dalam jadual.');
    }

    public function restore(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $officer = $this->unitQuery()->findOrFail($id);
        $officer->update(['is_active' => true]);

        session()->flash('roster-message', $officer->display_name.' dipantau semula.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editId', 'userId', 'displayName', 'sortOrder', 'userSearch']);
        $this->resetErrorBag();
    }

    /** Hadkan setiap pencarian rekod kepada unit yang sedang dipaparkan. */
    private function unitQuery()
    {
        return MonitoredOfficer::where('movement_unit_id', $this->unitId);
    }

    /**
     * Pengguna yang boleh ditambah: akaun aktif yang belum ada dalam roster
     * unit ini. Senarai dihadkan dan hanya muncul apabila ada kata carian,
     * kerana sistem ini mempunyai ribuan pengguna.
     */
    private function userOptions()
    {
        if (blank($this->userSearch)) {
            return collect();
        }

        $taken = $this->unitQuery()->pluck('user_id');

        return User::query()
            ->where('is_active', true)
            ->whereNotIn('id', $taken)
            ->where(function ($q) {
                $term = '%'.$this->userSearch.'%';
                $q->where('name', 'like', $term)->orWhere('username', 'like', $term);
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'username', 'designation']);
    }

    public function render()
    {
        $units = MovementUnit::active()->orderBy('name')->get();

        return view('livewire.pergerakan.roster-index', [
            'units' => $units,
            'unit' => $units->firstWhere('id', $this->unitId),
            'officers' => $this->unitQuery()
                ->with('user:id,name,username,designation')
                ->withCount('movements')
                ->ordered()
                ->get(),
            'userOptions' => $this->userOptions(),
        ]);
    }
}
