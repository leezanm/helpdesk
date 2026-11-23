<?php

namespace App\Livewire\Pergerakan;

use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use App\Models\Ref\MovementType;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Penyelenggaraan data rujukan modul Pergerakan: jenis pergerakan dan cuti umum.
 *
 * Jenis pergerakan boleh ditambah dan dikemaskini tetapi TIDAK dihapus — ia
 * dinyahaktifkan supaya tiada entri pergerakan lama tertunjuk kepada jenis yang
 * hilang. Jenis yang dinyahaktifkan tidak lagi ditawarkan untuk entri baharu.
 *
 * Cuti umum pula hanya penanda tarikh tanpa rujukan daripada rekod lain, jadi
 * ia boleh dihapus.
 *
 * Semua tindakan memerlukan `pergerakan.manageRoster`.
 */
class MovementTypeIndex extends Component
{
    /** Warna lencana yang ditawarkan — mengekalkan tema sistem. */
    public const BADGE_CLASSES = [
        'bg-secondary' => 'Kelabu',
        'bg-info' => 'Biru Muda',
        'bg-primary' => 'Biru',
        'bg-success' => 'Hijau',
        'bg-warning' => 'Kuning',
        'bg-danger' => 'Merah',
        'bg-dark' => 'Hitam',
        'bg-light text-dark' => 'Putih',
    ];

    /** Borang jenis pergerakan. */
    public ?int $typeId = null;
    public string $typeName = '';
    public string $typeClass = 'bg-secondary';
    public bool $typeIsLeave = false;
    public int $typeSortOrder = 0;
    public bool $showTypeModal = false;

    /** Borang cuti umum. */
    public ?int $holidayId = null;
    public string $holidayName = '';
    public string $holidayStart = '';
    public string $holidayEnd = '';
    public ?int $holidayUnitId = null;
    public bool $showHolidayModal = false;

    /** Tahun yang dipaparkan dalam senarai cuti umum. */
    public int $year;

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    // ---------------------------------------------------------------- jenis

    protected function typeRules(): array
    {
        return [
            'typeName' => [
                'required', 'string', 'max:255',
                Rule::unique('_movement_types', 'name')->ignore($this->typeId),
            ],
            'typeClass' => 'required|in:'.implode(',', array_keys(self::BADGE_CLASSES)),
            'typeIsLeave' => 'boolean',
            'typeSortOrder' => 'required|integer|min:0|max:999',
        ];
    }

    protected function typeMessages(): array
    {
        return [
            'typeName.required' => 'Nama jenis pergerakan wajib diisi.',
            'typeName.unique' => 'Jenis pergerakan dengan nama ini sudah ada.',
            'typeClass.required' => 'Sila pilih warna paparan.',
            'typeClass.in' => 'Warna paparan tidak sah.',
            'typeSortOrder.required' => 'Susunan paparan wajib diisi.',
            'typeSortOrder.integer' => 'Susunan paparan mesti nombor.',
        ];
    }

    public function createType(): void
    {
        $this->authorize('pergerakan.manageRoster');

        $this->reset(['typeId', 'typeName', 'typeIsLeave']);
        $this->typeClass = 'bg-secondary';
        $this->typeSortOrder = ((int) MovementType::max('sort_order')) + 10;
        $this->resetErrorBag();
        $this->showTypeModal = true;
    }

    public function editType(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $type = MovementType::findOrFail($id);

        $this->typeId = $type->id;
        $this->typeName = $type->name;
        $this->typeClass = $type->class;
        $this->typeIsLeave = $type->is_leave;
        $this->typeSortOrder = $type->sort_order;
        $this->resetErrorBag();
        $this->showTypeModal = true;
    }

    public function saveType(): void
    {
        $this->authorize('pergerakan.manageRoster');
        $this->validate($this->typeRules(), $this->typeMessages());

        $attributes = [
            'name' => $this->typeName,
            'class' => $this->typeClass,
            'is_leave' => $this->typeIsLeave,
            'sort_order' => $this->typeSortOrder,
        ];

        if ($this->typeId !== null) {
            MovementType::findOrFail($this->typeId)->update($attributes);
            $message = $this->typeName.' berjaya dikemaskini.';
        } else {
            MovementType::create($attributes + ['is_active' => true]);
            $message = $this->typeName.' ditambah sebagai jenis pergerakan.';
        }

        $this->closeTypeModal();
        session()->flash('type-message', $message);
    }

    /** Nyahaktif, bukan hapus: entri lama kekal memaparkan jenisnya (Req 5.3). */
    public function deactivateType(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $type = MovementType::findOrFail($id);
        $type->update(['is_active' => false]);

        session()->flash('type-message', $type->name.' tidak lagi boleh dipilih untuk pergerakan baru.');
    }

    public function restoreType(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $type = MovementType::findOrFail($id);
        $type->update(['is_active' => true]);

        session()->flash('type-message', $type->name.' kini boleh dipilih semula.');
    }

    public function closeTypeModal(): void
    {
        $this->showTypeModal = false;
        $this->reset(['typeId', 'typeName', 'typeClass', 'typeIsLeave', 'typeSortOrder']);
        $this->resetErrorBag();
    }

    // ------------------------------------------------------------ cuti umum

    protected function holidayRules(): array
    {
        return [
            'holidayName' => 'required|string|max:255',
            'holidayStart' => 'required|date',
            'holidayEnd' => 'required|date|after_or_equal:holidayStart',
            'holidayUnitId' => 'nullable|integer|exists:movement_units,id',
        ];
    }

    protected function holidayMessages(): array
    {
        return [
            'holidayName.required' => 'Nama cuti wajib diisi.',
            'holidayStart.required' => 'Tarikh mula wajib diisi.',
            'holidayStart.date' => 'Tarikh mula tidak sah.',
            'holidayEnd.required' => 'Tarikh tamat wajib diisi.',
            'holidayEnd.date' => 'Tarikh tamat tidak sah.',
            'holidayEnd.after_or_equal' => 'Tarikh tamat tidak boleh lebih awal daripada tarikh mula.',
        ];
    }

    public function createHoliday(): void
    {
        $this->authorize('pergerakan.manageRoster');

        $this->reset(['holidayId', 'holidayName', 'holidayStart', 'holidayEnd', 'holidayUnitId']);
        $this->resetErrorBag();
        $this->showHolidayModal = true;
    }

    public function editHoliday(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $holiday = MovementHoliday::findOrFail($id);

        $this->holidayId = $holiday->id;
        $this->holidayName = $holiday->name;
        $this->holidayStart = $holiday->start_date->toDateString();
        $this->holidayEnd = $holiday->end_date->toDateString();
        $this->holidayUnitId = $holiday->movement_unit_id;
        $this->resetErrorBag();
        $this->showHolidayModal = true;
    }

    /**
     * Cuti umum yang sama, pada tarikh yang sama, tidak boleh direkod dua kali.
     *
     * Pemeriksaan dibuat pada nama yang sama DAN julat tarikh yang bertindih —
     * bukan hanya tarikh yang serupa — supaya "Deepavali 8 Nov" tidak boleh
     * ditambah semula sebagai "Deepavali 7-9 Nov".
     *
     * Skop unit dihormati: dua unit berbeza boleh ada cuti khusus bernama sama
     * pada tarikh sama, tetapi cuti global (semua unit) bertindih dengan
     * mana-mana cuti unit yang senama kerana ia memang merangkumi unit itu.
     */
    private function duplicateHoliday(string $name): ?MovementHoliday
    {
        return MovementHoliday::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->whereDate('start_date', '<=', $this->holidayEnd)
            ->whereDate('end_date', '>=', $this->holidayStart)
            ->when($this->holidayId !== null, fn ($q) => $q->whereKeyNot($this->holidayId))
            // Skop unit bertindih? Cuti global merangkumi semua unit, jadi ia
            // bercanggah dengan mana-mana cuti senama. Cuti khusus satu unit
            // hanya bercanggah dengan cuti global atau cuti unit yang sama.
            ->when($this->holidayUnitId !== null, fn ($q) => $q->where(
                fn ($inner) => $inner->whereNull('movement_unit_id')
                    ->orWhere('movement_unit_id', $this->holidayUnitId)
            ))
            ->first();
    }

    public function saveHoliday(): void
    {
        $this->authorize('pergerakan.manageRoster');

        // Buang ruang di hujung supaya " Deepavali" tidak terlepas sebagai nama baharu.
        $this->holidayName = trim($this->holidayName);

        $this->validate($this->holidayRules(), $this->holidayMessages());

        if ($existing = $this->duplicateHoliday($this->holidayName)) {
            $this->addError('holidayName', sprintf(
                'Cuti "%s" sudah direkod pada tarikh ini (%s%s).',
                $existing->name,
                $existing->start_date->format('d/m/Y'),
                $existing->start_date->isSameDay($existing->end_date)
                    ? ''
                    : ' - '.$existing->end_date->format('d/m/Y'),
            ));

            return;
        }

        $attributes = [
            'name' => $this->holidayName,
            'start_date' => $this->holidayStart,
            'end_date' => $this->holidayEnd,
            'movement_unit_id' => $this->holidayUnitId,   // null = semua unit
        ];

        if ($this->holidayId !== null) {
            MovementHoliday::findOrFail($this->holidayId)->update($attributes);
            $message = $this->holidayName.' berjaya dikemaskini.';
        } else {
            MovementHoliday::create($attributes);
            $message = $this->holidayName.' ditambah ke senarai cuti umum.';
        }

        // Papar tahun cuti yang baru disimpan supaya ia kelihatan.
        $this->year = (int) substr($this->holidayStart, 0, 4);

        $this->closeHolidayModal();
        session()->flash('type-message', $message);
    }

    /** Cuti umum tiada rujukan daripada rekod lain, jadi ia boleh dihapus. */
    public function deleteHoliday(int $id): void
    {
        $this->authorize('pergerakan.manageRoster');

        $holiday = MovementHoliday::findOrFail($id);
        $name = $holiday->name;
        $holiday->delete();

        session()->flash('type-message', $name.' dikeluarkan daripada senarai cuti umum.');
    }

    public function closeHolidayModal(): void
    {
        $this->showHolidayModal = false;
        $this->reset(['holidayId', 'holidayName', 'holidayStart', 'holidayEnd', 'holidayUnitId']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.pergerakan.movement-type-index', [
            'types' => MovementType::withCount('movements')->ordered()->get(),
            'badgeClasses' => self::BADGE_CLASSES,
            'holidays' => MovementHoliday::with('unit')
                ->whereYear('start_date', $this->year)
                ->orderBy('start_date')
                ->get(),
            'units' => MovementUnit::active()->orderBy('name')->get(),
            'years' => range((int) now()->year - 1, (int) now()->year + 1),
        ]);
    }
}
