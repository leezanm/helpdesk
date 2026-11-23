<?php

namespace App\Http\Controllers;

use App\Models\MovementUnit;
use App\Support\MovementCalendar;
use Illuminate\Http\Request;

/**
 * Modul Pergerakan Pegawai.
 *
 * Skrin interaktif dikendalikan oleh komponen Livewire (App\Livewire\Pergerakan\*);
 * controller ini hanya merender page wrapper yang me-mount komponen berkenaan.
 * Satu pengecualian ialah paparan cetak, yang memulangkan dokumen berdiri
 * sendiri. Eksport CSV pula ialah tindakan komponen (MovementSchedule::exportCsv)
 * dan bukan laluan, mengikut corak Laporan_Keseluruhan.
 */
class MovementController extends Controller
{
    /** Senarai pegawai dipantau (roster) — `pergerakan.manageRoster`. */
    public function roster()
    {
        return view('pergerakan.roster');
    }

    /** Jadual pergerakan bulanan — `pergerakan.view`. */
    public function calendar()
    {
        return view('pergerakan.schedule');
    }

    /** Senarai pergerakan dengan penapis dan carian — `pergerakan.view`. */
    public function index()
    {
        return view('pergerakan.index');
    }

    /** Pergerakan pengguna sendiri — `pergerakan.create`. */
    public function my()
    {
        return view('pergerakan.my');
    }

    /** Jenis pergerakan + cuti umum — `pergerakan.manageRoster`. */
    public function types()
    {
        return view('pergerakan.types');
    }

    /**
     * Paparan jadual bulanan yang sesuai dicetak: landskap, tanpa menu, dengan
     * tajuk unit dan bulan. Pengguna mencetak ke PDF melalui pelayar, jadi
     * tiada pakej penjana PDF diperlukan.
     */
    public function print(Request $request)
    {
        $calendar = $this->calendarFromRequest($request);

        return view('pergerakan.print', [
            'calendar' => $calendar,
            'unit' => $calendar->unit(),
            'weeks' => $calendar->weeks(),
            'days' => $calendar->days(),
            'rows' => $calendar->rows(),
            'monthLabel' => $calendar->label(),
        ]);
    }

    /**
     * Bulan dan unit datang dari query string, jadi ia disahkan sebelum
     * digunakan. Lalai: unit aktif pertama dan bulan semasa.
     */
    private function calendarFromRequest(Request $request): MovementCalendar
    {
        $data = $request->validate([
            'unit' => 'nullable|integer|exists:movement_units,id',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $unit = isset($data['unit'])
            ? MovementUnit::findOrFail($data['unit'])
            : MovementUnit::active()->orderBy('name')->firstOrFail();

        return new MovementCalendar(
            $unit,
            (int) ($data['year'] ?? now()->year),
            (int) ($data['month'] ?? now()->month),
        );
    }
}
