<?php

namespace App\Livewire\Pergerakan;

use App\Models\Movement;
use App\Models\MovementUnit;
use App\Support\MovementCalendar;
use App\Support\MovementCsvExporter;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Jadual pergerakan bulanan — paparan yang menggantikan grid Google Sheet:
 * baris = pegawai dipantau, lajur = setiap hari dalam bulan, dikumpulkan
 * mengikut minggu dengan hujung minggu dan cuti umum ditandakan.
 *
 * Data dibina oleh App\Support\MovementCalendar; komponen ini hanya menguruskan
 * pemilihan bulan/unit, modal butiran, dan menghantar peristiwa kepada
 * MovementForm untuk tambah/kemaskini.
 */
class MovementSchedule extends Component
{
    public int $year;
    public int $month;
    public ?int $unitId = null;

    /** Entri yang butirannya sedang dipaparkan. */
    public ?int $detailId = null;

    public function mount(): void
    {
        $this->authorize('pergerakan.view');

        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
        $this->unitId = MovementUnit::active()->orderBy('name')->value('id');
    }

    public function previousMonth(): void
    {
        $this->shiftMonth(-1);
    }

    public function nextMonth(): void
    {
        $this->shiftMonth(1);
    }

    public function goToToday(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
        $this->detailId = null;

        $this->dispatch('pergerakan-month-changed');
    }

    private function shiftMonth(int $months): void
    {
        $cursor = Carbon::create($this->year, $this->month, 1)->addMonths($months);

        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
        $this->detailId = null;

        // Grid menatal semula ke lajur hari ini (atau ke 1 haribulan bagi bulan
        // lain). x-init tidak berjalan semula kerana Livewire menambal DOM.
        $this->dispatch('pergerakan-month-changed');
    }

    /** Tambah entri terus dari sel: tarikh dan pegawai sel itu dibawa ke borang. */
    public function addEntry(string $date, int $officerId): void
    {
        $this->dispatch('movement-create', date: $date, officerId: $officerId);
    }

    public function showDetails(int $id): void
    {
        $this->detailId = $id;
    }

    public function closeDetails(): void
    {
        $this->detailId = null;
    }

    public function edit(int $id): void
    {
        $this->detailId = null;
        $this->dispatch('movement-edit', id: $id);
    }

    public function delete(int $id): void
    {
        $movement = Movement::with('officer')->findOrFail($id);
        $this->authorize('delete', $movement);

        $movement->delete();

        $this->detailId = null;
        session()->flash('movement-message', 'Pergerakan "'.$movement->title.'" telah dihapus.');
    }

    /** Muat semula grid selepas borang menyimpan. */
    #[On('movement-saved')]
    public function onSaved(): void
    {
        $this->detailId = null;
    }

    /**
     * Muat turun entri bulan dan unit yang sedang dipapar sebagai CSV.
     *
     * Tindakan komponen dan bukan laluan baharu, mengikut corak eksport
     * Laporan_Keseluruhan: tiada URL eksport untuk dijaga, dan fail dibina
     * daripada MovementCalendar yang sama seperti grid di skrin.
     */
    public function exportCsv(): ?StreamedResponse
    {
        // Lapisan kebenaran kedua selepas pagar laluan `can:pergerakan.view`.
        $this->authorize('pergerakan.view');

        $unit = MovementUnit::find($this->unitId);

        if ($unit === null) {
            return null;
        }

        $calendar = new MovementCalendar($unit, $this->year, $this->month);

        return MovementCsvExporter::stream(
            $calendar->movements()->load('officer'),
            sprintf(
                'pergerakan-%s-%s.csv',
                str($unit->short_name ?: $unit->name)->slug(),
                $calendar->start()->format('Y-m'),
            ),
        );
    }

    public function render()
    {
        $units = MovementUnit::active()->orderBy('name')->get();
        $unit = $units->firstWhere('id', $this->unitId);

        $calendar = $unit !== null
            ? new MovementCalendar($unit, $this->year, $this->month)
            : null;

        return view('livewire.pergerakan.movement-schedule', [
            'units' => $units,
            'unit' => $unit,
            'calendar' => $calendar,
            // Label minggu (weeks()) hanya digunakan oleh paparan cetak.
            'days' => $calendar?->days() ?? [],
            'rows' => $calendar?->rows() ?? [],
            'agenda' => $calendar?->agenda() ?? [],
            'monthLabel' => $calendar?->label() ?? '',
            'detail' => $this->detailId !== null
                ? Movement::with(['type', 'officer', 'createdBy', 'updatedBy'])->find($this->detailId)
                : null,
        ]);
    }
}
