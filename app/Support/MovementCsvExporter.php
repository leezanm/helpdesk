<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eksport CSV jadual pergerakan bulanan ("CSV" pada skrin Jadual Pergerakan).
 *
 * Dibina daripada Laravel dan PHP sahaja — response()->streamDownload() dengan
 * fputcsv() — jadi tiada pakej composer atau npm baharu ditambah, sama seperti
 * App\Support\LaporanCsvExporter.
 *
 * Kelas ini hanya menulis: pemanggil (MovementSchedule::exportCsv) memiliki
 * semakan kebenaran, nama fail, dan koleksi entri yang sudah ditapis serta
 * disusun. Kerana entri itu datang daripada MovementCalendar yang sama seperti
 * grid, baris dalam fail tidak boleh bercanggah dengan apa yang dipapar.
 *
 * Satu baris = satu entri (bukan satu baris setiap hari), jadi aktiviti
 * berbilang hari kekal satu baris dengan tarikh mula dan tamatnya.
 */
final class MovementCsvExporter
{
    public const HEADERS = [
        'Pegawai', 'Jenis', 'Tajuk', 'Tarikh Mula', 'Tarikh Tamat',
        'Masa Mula', 'Masa Tamat', 'Lokasi Aktiviti', 'Tempat', 'Catatan',
    ];

    /**
     * @param  Collection<int,\App\Models\Movement>  $movements  sudah disusun, dengan `type` dan `officer` dimuatkan
     */
    public static function stream(Collection $movements, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($movements) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 supaya aplikasi hamparan mengesan pengekodan dan nama
            // Melayu tidak rosak apabila dibuka.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, self::HEADERS);

            foreach ($movements as $movement) {
                fputcsv($out, [
                    $movement->officer->display_name,
                    $movement->type->name,
                    $movement->title,
                    $movement->start_date->format('d/m/Y'),
                    $movement->end_date->format('d/m/Y'),
                    $movement->start_time ? substr($movement->start_time, 0, 5) : '',
                    $movement->end_time ? substr($movement->end_time, 0, 5) : '',
                    $movement->type->is_leave ? '' : ($movement->is_outside ? 'Luar pejabat' : 'Dalam pejabat'),
                    (string) $movement->location,
                    (string) $movement->remarks,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
