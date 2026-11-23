<?php

namespace App\Livewire\Pergerakan;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Widget dashboard "Ketersediaan Pegawai".
 *
 * Soalan yang dijawab: siapa boleh diurus kerja SEKARANG. Nama yang
 * disenaraikan ialah pegawai yang tersedia; tiga kategori lain hanya dikira
 * sebagai nombor.
 *
 * Status mengikut MASA dan bukan hanya tarikh, kerana mesyuarat, perbincangan
 * dan bengkel selalunya tidak sepenuh hari: pegawai yang bermesyuarat 3-5
 * petang masih boleh diurus kerja pada pagi itu, dan bebas semula selepas 5
 * petang. Satu entri "menduduki" pegawai hanya dalam tetingkap masanya:
 *
 *   ada masa mula          -> menduduki dari masa mula hingga masa tamat
 *                             (atau hingga akhir hari jika masa tamat kosong)
 *   tiada masa             -> sepanjang hari
 *   entri berbilang hari   -> sepanjang hari
 *
 * Peraturan yang sama dikenakan pada cuti, supaya cuti separuh hari yang
 * direkod dengan masa tidak menandakan pegawai bercuti sepanjang hari.
 *
 * Keutamaan apabila beberapa entri aktif serentak: cuti > luar pejabat >
 * dalam pejabat.
 */
class TodayWidget extends Component
{
    public ?int $unitId = null;

    public function mount(): void
    {
        $this->authorize('pergerakan.view');

        $this->unitId = $this->preferredUnitId();
    }

    /**
     * Unit pengguna sendiri jika dia dipantau, jika tidak unit aktif pertama —
     * supaya pegawai melihat unitnya dahulu tanpa perlu memilih.
     */
    private function preferredUnitId(): ?int
    {
        return MonitoredOfficer::active()->where('user_id', Auth::id())->value('movement_unit_id')
            ?? MovementUnit::active()->orderBy('name')->value('id');
    }

    /** Entri ini menduduki pegawai pada masa `now`? */
    private function occupiesNow(Movement $movement, Carbon $now): bool
    {
        // Entri berbilang hari atau tanpa masa: sepanjang hari.
        if ($movement->isMultiDay() || $movement->start_time === null) {
            return true;
        }

        $start = $this->timeOn($movement->start_time, $now);
        $end = $movement->end_time !== null
            ? $this->timeOn($movement->end_time, $now)
            : $now->copy()->endOfDay();

        return $now->betweenIncluded($start, $end);
    }

    /** "15:00" atau "15:00:00" pada tarikh hari ini. */
    private function timeOn(string $time, Carbon $reference): Carbon
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return $reference->copy()->setTime((int) $hour, (int) $minute);
    }

    /**
     * Komitmen seterusnya pada hari ini bagi pegawai yang kini bebas — supaya
     * pengurusan tahu berapa lama tingkap itu terbuka.
     */
    private function nextCommitment(Collection $entries, Carbon $now): ?Movement
    {
        return $entries
            ->filter(fn (Movement $m) => $m->start_time !== null && ! $m->isMultiDay())
            ->filter(fn (Movement $m) => $this->timeOn($m->start_time, $now)->greaterThan($now))
            ->sortBy('start_time')
            ->first();
    }

    public function render()
    {
        $now = now();
        $today = $now->toDateString();

        $officers = MonitoredOfficer::active()
            ->when($this->unitId !== null, fn ($q) => $q->where('movement_unit_id', $this->unitId))
            ->ordered()
            ->get();

        $byOfficer = $this->unitId === null
            ? collect()
            : Movement::with('type')
                ->forUnit($this->unitId)
                ->overlapping($today, $today)
                ->get()
                ->groupBy('monitored_officer_id');

        $available = [];
        $busyInside = 0;
        $outside = 0;
        $onLeave = 0;

        foreach ($officers as $officer) {
            $entries = $byOfficer->get($officer->id, collect());
            $active = $entries->filter(fn (Movement $m) => $this->occupiesNow($m, $now));

            if ($active->isEmpty()) {
                $next = $this->nextCommitment($entries, $now);

                $available[] = [
                    'officer' => $officer,
                    // Nota "bebas sehingga ..." hanya jika ada komitmen kemudian.
                    'freeUntil' => $next !== null ? substr($next->start_time, 0, 5) : null,
                ];

                continue;
            }

            if ($active->contains(fn (Movement $m) => $m->type->is_leave)) {
                $onLeave++;
            } elseif ($active->contains(fn (Movement $m) => $m->is_outside)) {
                $outside++;
            } else {
                $busyInside++;
            }
        }

        return view('livewire.pergerakan.today-widget', [
            'units' => MovementUnit::active()->orderBy('name')->get(),
            'unit' => MovementUnit::find($this->unitId),
            'dateLabel' => $now->translatedFormat('l, j F Y'),
            'timeLabel' => $now->format('H:i'),
            'available' => $available,
            'availableCount' => count($available),
            'busyInsideCount' => $busyInside,
            'outsideCount' => $outside,
            'onLeaveCount' => $onLeave,
            'officerCount' => $officers->count(),
            'holiday' => $this->unitId === null ? null : MovementHoliday::forUnit($this->unitId)
                ->overlapping($today, $today)
                ->value('name'),
        ]);
    }
}
