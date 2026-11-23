<?php

namespace App\Livewire\Pergerakan;

use App\Actions\Movements\SaveMovement;
use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\Ref\MovementType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Borang (modal) cipta/kemaskini satu entri pergerakan.
 *
 * Komponen ini dipanggil melalui peristiwa supaya skrin lain boleh
 * menggunakannya semula: senarai pergerakan saya sekarang, dan jadual bulanan
 * dalam task berikutnya.
 *
 *   dispatch('movement-create')                  -> borang kosong
 *   dispatch('movement-create', date: 'Y-m-d')   -> borang dengan tarikh itu
 *   dispatch('movement-edit', id: 123)           -> kemaskini entri
 *
 * Selepas berjaya simpan ia menghantar 'movement-saved' untuk skrin pemanggil
 * memuat semula senarainya.
 *
 * Kebenaran disemak di sini DAN sekali lagi dalam SaveMovement — borang hanya
 * menentukan apa yang dipaparkan; Action yang melindungi data.
 */
class MovementForm extends Component
{
    public ?int $editId = null;

    public ?int $monitoredOfficerId = null;
    public ?int $movementTypeId = null;
    public string $title = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $startTime = '';
    public string $endTime = '';
    public string $location = '';
    public bool $isOutside = false;
    public string $remarks = '';

    public bool $showModal = false;

    /**
     * Amaran pertindihan cuti ialah amaran, BUKAN halangan: entri disimpan pada
     * hantaran kedua selepas pengguna melihat amaran ini (Req 2.7).
     */
    public string $overlapWarning = '';
    public bool $confirmedOverlap = false;

    protected function rules(): array
    {
        return [
            'monitoredOfficerId' => 'required|integer|exists:monitored_officers,id',
            'movementTypeId' => 'required|integer|exists:_movement_types,id',
            'title' => 'required|string|max:255',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            // Masa hanya dibandingkan apabila aktiviti dalam hari yang sama;
            // entri berbilang hari boleh tamat pada waktu lebih awal.
            'startTime' => 'nullable|date_format:H:i',
            'endTime' => $this->startDate !== '' && $this->startDate === $this->endDate
                ? 'nullable|date_format:H:i|after:startTime'
                : 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'isOutside' => 'boolean',
            'remarks' => 'nullable|string|max:2000',
        ];
    }

    protected function messages(): array
    {
        return [
            'monitoredOfficerId.required' => 'Sila pilih pegawai.',
            'movementTypeId.required' => 'Sila pilih jenis pergerakan.',
            'title.required' => 'Tajuk wajib diisi.',
            'startDate.required' => 'Tarikh mula wajib diisi.',
            'startDate.date' => 'Tarikh mula tidak sah.',
            'endDate.required' => 'Tarikh tamat wajib diisi.',
            'endDate.date' => 'Tarikh tamat tidak sah.',
            'endDate.after_or_equal' => 'Tarikh tamat tidak boleh lebih awal daripada tarikh mula.',
            'startTime.date_format' => 'Masa mula tidak sah.',
            'endTime.date_format' => 'Masa tamat tidak sah.',
            'endTime.after' => 'Masa tamat mesti selepas masa mula.',
        ];
    }

    #[On('movement-create')]
    public function openCreate(?string $date = null, ?int $officerId = null): void
    {
        $this->resetForm();

        $this->startDate = $date ?: now()->toDateString();
        $this->endDate = $this->startDate;

        // Pegawai sel yang diklik hanya dihormati bagi pengguna yang memang
        // boleh mencatat untuk orang lain. Pegawai biasa yang menekan "+" pada
        // baris orang lain mendapat NAMANYA SENDIRI, bukan baris itu — kalau
        // tidak borang akan terbuka dengan pegawai yang dia tidak boleh simpan
        // dan hanya ditolak selepas menekan Simpan.
        $this->monitoredOfficerId = Auth::user()?->can('pergerakan.manageAny')
            ? ($officerId ?? $this->defaultOfficerId())
            : $this->defaultOfficerId();

        $this->showModal = true;
    }

    #[On('movement-edit')]
    public function openEdit(int $id): void
    {
        $movement = Movement::with('officer')->findOrFail($id);
        $this->authorize('update', $movement);

        $this->resetForm();

        $this->editId = $movement->id;
        $this->monitoredOfficerId = $movement->monitored_officer_id;
        $this->movementTypeId = $movement->movement_type_id;
        $this->title = $movement->title;
        $this->startDate = $movement->start_date->toDateString();
        $this->endDate = $movement->end_date->toDateString();
        $this->startTime = $movement->start_time ? substr($movement->start_time, 0, 5) : '';
        $this->endTime = $movement->end_time ? substr($movement->end_time, 0, 5) : '';
        $this->location = (string) $movement->location;
        $this->isOutside = (bool) $movement->is_outside;
        $this->remarks = (string) $movement->remarks;
        $this->showModal = true;
    }

    /** Amaran perlu dinilai semula apabila tarikh, pegawai atau jenis berubah. */
    public function updated(string $property): void
    {
        if (in_array($property, ['startDate', 'endDate', 'monitoredOfficerId', 'movementTypeId'], true)) {
            $this->confirmedOverlap = false;
            $this->overlapWarning = '';
        }

        // Cuti tidak mempunyai lokasi bertugas, jadi penanda luar pejabat
        // dikosongkan apabila jenis cuti dipilih.
        if ($property === 'movementTypeId' && $this->selectedTypeIsLeave()) {
            $this->isOutside = false;
        }

        // Entri sehari: tarikh tamat mengikut tarikh mula secara automatik.
        if ($property === 'startDate' && ($this->endDate === '' || $this->endDate < $this->startDate)) {
            $this->endDate = $this->startDate;
        }
    }

    public function save(SaveMovement $saveMovement): void
    {
        $data = $this->validate();

        if (! $this->confirmedOverlap && $warning = $this->leaveOverlapWarning()) {
            // Hantaran pertama hanya memaparkan amaran; hantaran kedua menyimpan.
            $this->overlapWarning = $warning;
            $this->confirmedOverlap = true;

            return;
        }

        $movement = $saveMovement->handle(
            [
                'monitored_officer_id' => $data['monitoredOfficerId'],
                'movement_type_id' => $data['movementTypeId'],
                'title' => $data['title'],
                'start_date' => $data['startDate'],
                'end_date' => $data['endDate'],
                'start_time' => $data['startTime'],
                'end_time' => $data['endTime'],
                'location' => $data['location'],
                'is_outside' => $data['isOutside'],
                'remarks' => $data['remarks'],
            ],
            $this->editId !== null ? Movement::findOrFail($this->editId) : null,
        );

        $wasEdit = $this->editId !== null;

        $this->closeModal();
        $this->dispatch('movement-saved', id: $movement->id);

        session()->flash(
            'movement-message',
            $wasEdit ? 'Pergerakan berjaya dikemaskini.' : 'Pergerakan berjaya direkod.'
        );
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editId', 'monitoredOfficerId', 'movementTypeId', 'title', 'startDate',
            'endDate', 'startTime', 'endTime', 'location', 'isOutside', 'remarks',
            'overlapWarning', 'confirmedOverlap',
        ]);
        $this->resetErrorBag();
    }

    /**
     * Mesej amaran apabila entri ini bertindih dengan cuti pegawai yang sama —
     * atau apabila entri cuti ini bertindih dengan aktiviti yang sudah dirancang.
     */
    public function selectedTypeIsLeave(): bool
    {
        return $this->movementTypeId !== null
            && (bool) MovementType::whereKey($this->movementTypeId)->value('is_leave');
    }

    private function leaveOverlapWarning(): ?string
    {
        $newTypeIsLeave = $this->selectedTypeIsLeave();

        $clash = Movement::with('type')
            ->where('monitored_officer_id', $this->monitoredOfficerId)
            ->overlapping($this->startDate, $this->endDate)
            ->when($this->editId !== null, fn ($q) => $q->whereKeyNot($this->editId))
            ->when(! $newTypeIsLeave, fn ($q) => $q->whereHas('type', fn ($t) => $t->where('is_leave', true)))
            ->orderBy('start_date')
            ->first();

        if ($clash === null) {
            return null;
        }

        return sprintf(
            'Pegawai ini sudah ada "%s" (%s) pada tarikh yang bertindih: %s. Klik Simpan sekali lagi untuk teruskan.',
            $clash->title,
            $clash->type->name,
            $clash->start_date->isSameDay($clash->end_date)
                ? $clash->start_date->format('d/m/Y')
                : $clash->start_date->format('d/m/Y').' - '.$clash->end_date->format('d/m/Y'),
        );
    }

    /** Baris roster pengguna sendiri, jika ada. */
    private function defaultOfficerId(): ?int
    {
        return MonitoredOfficer::active()
            ->where('user_id', Auth::id())
            ->orderBy('movement_unit_id')
            ->value('id');
    }

    /**
     * Pegawai yang boleh dipilih: diri sendiri sahaja, atau semua pegawai
     * roster aktif bagi pemegang `pergerakan.manageAny` (Req 2.12).
     */
    private function officerOptions()
    {
        $query = MonitoredOfficer::active()->with('unit')->ordered();

        if (! Auth::user()?->can('pergerakan.manageAny')) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
    }

    /** Jenis aktif, ditambah jenis entri yang sedang diedit walaupun sudah tidak aktif. */
    private function typeOptions()
    {
        return MovementType::query()
            ->where(fn ($q) => $q->where('is_active', true)
                ->when($this->movementTypeId !== null, fn ($i) => $i->orWhere('id', $this->movementTypeId)))
            ->ordered()
            ->get();
    }

    public function render()
    {
        return view('livewire.pergerakan.movement-form', [
            'officers' => $this->showModal ? $this->officerOptions() : collect(),
            'types' => $this->showModal ? $this->typeOptions() : collect(),
        ]);
    }
}
