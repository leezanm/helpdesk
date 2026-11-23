<?php

namespace App\Support;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\MovementHoliday;
use App\Models\MovementUnit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Membina data jadual pergerakan bulanan: baris = pegawai, lajur = hari.
 *
 * Satu entri pergerakan ialah SATU rekod dengan julat tarikh, jadi kelas ini
 * "menebarkan" setiap entri ke atas setiap hari dalam julatnya (dipangkas pada
 * sempadan bulan). Itulah sebabnya aktiviti 18-19 November muncul pada kedua-dua
 * lajur walaupun hanya satu rekod disimpan.
 *
 * Kelas biasa (bukan komponen) supaya ia boleh diuji tanpa HTTP dan diguna
 * semula oleh paparan cetak.
 */
class MovementCalendar
{
    /** Nama hari dalam Bahasa Melayu, diindeks oleh Carbon::dayOfWeek (0 = Ahad). */
    public const DAY_NAMES = ['AHAD', 'ISNIN', 'SELASA', 'RABU', 'KHAMIS', 'JUMAAT', 'SABTU'];

    private const WEEK_LABELS = [
        'MINGGU PERTAMA', 'MINGGU KEDUA', 'MINGGU KETIGA',
        'MINGGU KEEMPAT', 'MINGGU KELIMA', 'MINGGU KEENAM',
    ];

    private Carbon $start;
    private Carbon $end;

    /** @var Collection<int,Movement>|null */
    private ?Collection $movements = null;

    /** @var array<string,string>|null tarikh => nama cuti umum */
    private ?array $holidays = null;

    public function __construct(
        private MovementUnit $unit,
        int $year,
        int $month,
    ) {
        $this->start = Carbon::create($year, $month, 1)->startOfMonth();
        $this->end = $this->start->copy()->endOfMonth();
    }

    public function unit(): MovementUnit
    {
        return $this->unit;
    }

    public function start(): Carbon
    {
        return $this->start->copy();
    }

    public function end(): Carbon
    {
        return $this->end->copy();
    }

    /** "November 2026" */
    public function label(): string
    {
        return $this->start->translatedFormat('F Y');
    }

    /**
     * Setiap hari dalam bulan, dengan nama hari BM, penanda hujung minggu dan
     * nama cuti umum jika ada.
     *
     * @return array<int, array{key:string, date:Carbon, day:int, name:string, is_weekend:bool, is_today:bool, holiday:?string}>
     */
    public function days(): array
    {
        $days = [];

        for ($date = $this->start->copy(); $date->lte($this->end); $date->addDay()) {
            $key = $date->toDateString();

            $days[] = [
                'key' => $key,
                'date' => $date->copy(),
                'day' => (int) $date->day,
                'name' => self::DAY_NAMES[$date->dayOfWeek],
                'is_weekend' => $date->isSaturday() || $date->isSunday(),
                'is_today' => $date->isToday(),
                'holiday' => $this->holidayMap()[$key] ?? null,
            ];
        }

        return $days;
    }

    /**
     * Hari dikumpulkan mengikut minggu (bermula Ahad, seperti jadual asal),
     * untuk baris tajuk "MINGGU PERTAMA" dan seterusnya.
     *
     * @return array<int, array{label:string, days:array<int,array<string,mixed>>}>
     */
    public function weeks(): array
    {
        $weeks = [];
        $current = [];

        foreach ($this->days() as $day) {
            // Minggu baharu bermula pada Ahad, kecuali hari pertama bulan.
            if ($current !== [] && $day['date']->isSunday()) {
                $weeks[] = $current;
                $current = [];
            }

            $current[] = $day;
        }

        if ($current !== []) {
            $weeks[] = $current;
        }

        return collect($weeks)
            ->map(fn (array $days, int $index) => [
                'label' => self::WEEK_LABELS[$index] ?? 'MINGGU '.($index + 1),
                'days' => $days,
            ])
            ->all();
    }

    /**
     * Baris jadual: pegawai roster aktif ikut susunan paparan, dengan entri
     * setiap hari.
     *
     * @return array<int, array{officer:MonitoredOfficer, cells:array<string,array<int,Movement>>}>
     */
    public function rows(): array
    {
        $byOfficer = $this->movements()->groupBy('monitored_officer_id');
        $dayKeys = array_column($this->days(), 'key');

        return $this->officers()
            ->map(function (MonitoredOfficer $officer) use ($byOfficer, $dayKeys) {
                $cells = array_fill_keys($dayKeys, []);

                foreach ($byOfficer->get($officer->id, collect()) as $movement) {
                    foreach ($this->spanKeys($movement) as $key) {
                        $cells[$key][] = $movement;
                    }
                }

                return ['officer' => $officer, 'cells' => $cells];
            })
            ->all();
    }

    /**
     * Pandangan agenda untuk telefon: hanya hari yang ada entri, dikumpulkan
     * mengikut hari. Dibina daripada data yang sama seperti grid supaya tiada
     * logik kedua.
     *
     * @return array<int, array{day:array<string,mixed>, entries:array<int, array{officer:MonitoredOfficer, movement:Movement}>}>
     */
    public function agenda(): array
    {
        $rows = $this->rows();
        $agenda = [];

        foreach ($this->days() as $day) {
            $entries = [];

            foreach ($rows as $row) {
                foreach ($row['cells'][$day['key']] as $movement) {
                    $entries[] = ['officer' => $row['officer'], 'movement' => $movement];
                }
            }

            if ($entries !== [] || $day['holiday'] !== null) {
                $agenda[] = ['day' => $day, 'entries' => $entries];
            }
        }

        return $agenda;
    }

    /** @return Collection<int,MonitoredOfficer> */
    public function officers(): Collection
    {
        return $this->unit->activeOfficers()->get();
    }

    /** Satu pertanyaan untuk keseluruhan bulan — elak N+1 pada grid. */
    public function movements(): Collection
    {
        return $this->movements ??= Movement::with('type')
            ->forUnit($this->unit->id)
            ->overlapping($this->start->toDateString(), $this->end->toDateString())
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->get();
    }

    /** @return array<string,string> */
    public function holidayMap(): array
    {
        if ($this->holidays !== null) {
            return $this->holidays;
        }

        $this->holidays = [];

        $holidays = MovementHoliday::forUnit($this->unit->id)
            ->overlapping($this->start->toDateString(), $this->end->toDateString())
            ->orderBy('start_date')
            ->get();

        foreach ($holidays as $holiday) {
            $from = $holiday->start_date->greaterThan($this->start) ? $holiday->start_date->copy() : $this->start->copy();
            $to = $holiday->end_date->lessThan($this->end) ? $holiday->end_date->copy() : $this->end->copy();

            for ($date = $from; $date->lte($to); $date->addDay()) {
                $this->holidays[$date->toDateString()] = $holiday->name;
            }
        }

        return $this->holidays;
    }

    /**
     * Kunci tarikh yang dilitupi oleh satu entri dalam bulan ini.
     *
     * @return array<int,string>
     */
    private function spanKeys(Movement $movement): array
    {
        $from = $movement->start_date->greaterThan($this->start)
            ? $movement->start_date->copy()->startOfDay()
            : $this->start->copy();

        $to = $movement->end_date->lessThan($this->end)
            ? $movement->end_date->copy()->startOfDay()
            : $this->end->copy();

        $keys = [];

        for ($date = $from; $date->lte($to); $date->addDay()) {
            $keys[] = $date->toDateString();
        }

        return $keys;
    }
}
