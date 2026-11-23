<?php

namespace App\Livewire\Pergerakan;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Pergerakan Saya" — entri pergerakan pengguna sendiri bagi satu bulan,
 * tempat pegawai mencatat dan membetulkan pergerakannya.
 *
 * Butang kemaskini/hapus hanya muncul apabila MovementPolicy membenarkan, iaitu
 * selagi tarikh entri belum sampai. Entri yang sudah terkunci dipaparkan dengan
 * penanda kunci; pembetulannya perlu dirujuk kepada pentadbir helpdesk.
 *
 * Senarai penuh semua pegawai dengan penapis ialah skrin berasingan (task 6).
 */
class MyMovements extends Component
{
    public int $year;
    public int $month;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function previousMonth(): void
    {
        $cursor = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
    }

    public function nextMonth(): void
    {
        $cursor = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
    }

    public function create(): void
    {
        // Tarikh lalai: hari ini jika bulan semasa, jika tidak 1 haribulan.
        $cursor = Carbon::create($this->year, $this->month, 1);
        $date = $cursor->isSameMonth(now()) ? now() : $cursor;

        $this->dispatch('movement-create', date: $date->toDateString());
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

    /** Muat semula selepas borang menyimpan. */
    #[On('movement-saved')]
    public function onSaved(): void
    {
        //
    }

    public function render()
    {
        $officerIds = MonitoredOfficer::where('user_id', Auth::id())->pluck('id');

        $start = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $movements = Movement::with(['type', 'officer'])
            ->whereIn('monitored_officer_id', $officerIds)
            ->overlapping($start->toDateString(), $end->toDateString())
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->get();

        return view('livewire.pergerakan.my-movements', [
            'movements' => $movements,
            'monthLabel' => $start->translatedFormat('F Y'),
            'isMonitored' => MonitoredOfficer::active()->where('user_id', Auth::id())->exists(),
        ]);
    }
}
