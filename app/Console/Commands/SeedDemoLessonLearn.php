<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\ReportTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills the Knowledge Base for a demo.
 *
 * Why this is needed: the imported production data holds no lesson learn at
 * all. Trail status 9 ("Selesai Tindakan") and the punca_masalah /
 * langkah_tindakan columns arrived with the new flow, so every imported trail
 * has them empty and the Knowledge Base screen is legitimately blank.
 *
 * This command picks aduan that were already completed and attaches the
 * resolution record the new flow would have captured, worded per category and
 * credited to the officer who actually handled that aduan.
 *
 * The text is written for a demo, not by the officer. Every generated record
 * therefore carries the DEMO_MARKER in its technical note, so anyone reading
 * the knowledge base can tell a sample record from a real one.
 *
 * Idempotent (skips aduan that already carry a status-9 trail) and refuses to
 * run in production.
 */
class SeedDemoLessonLearn extends Command
{
    protected $signature = 'demo:seed-lesson-learn
                            {--count=50 : How many completed aduan to document}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Isi rekod Knowledge Base bagi aduan selesai yang diimport (data contoh untuk demo)';

    public const DEMO_MARKER = '[Rekod contoh untuk tujuan demo]';

    /** Trail status that the Knowledge Base screen reads. */
    private const TRAIL_SELESAI_TINDAKAN = 9;

    /**
     * Resolution text per report_categories.id: [punca masalah, langkah penyelesaian, catatan teknikal].
     */
    private const RESOLUTIONS = [
        1 => ['Aksesori komputer telah melebihi jangka hayat penggunaan dan sambungan port longgar.', 'Aksesori digantikan dengan unit stok semasa dan diuji bersama pengguna sebelum diserahkan.', 'Unit lama direkodkan untuk pelupusan mengikut prosedur aset.'],
        2 => ['Perisian tidak serasi dengan versi sistem pengoperasian selepas kemaskini keselamatan.', 'Perisian dipasang semula menggunakan versi yang disahkan serasi dan lesen diaktifkan semula.', 'Pakej pemasangan disimpan dalam repositori perisian bahagian.'],
        3 => ['Cakera keras hampir penuh dan fail sementara tidak dibersihkan menyebabkan prestasi menurun.', 'Fail sementara dibersihkan, cakera dioptimumkan dan servis latar yang tidak perlu dimatikan.', 'Pengguna ditunjukkan cara memantau ruang storan sendiri.'],
        4 => ['Barisan cetakan tersekat dan pemacu pencetak pada komputer pengguna tidak dikemaskini.', 'Barisan cetakan dikosongkan, pemacu dipasang semula dan ujian cetakan dua muka dijalankan.', 'Model dan alamat IP pencetak direkodkan untuk rujukan aduan berulang.'],
        5 => ['Tetapan pautan sidang video tidak dikongsi kepada semua peserta sebelum mesyuarat.', 'Pautan baharu dijana, ujian audio dan video dijalankan 30 minit sebelum mesyuarat bermula.', 'Senarai semak persiapan sidang video digunakan untuk mesyuarat berikutnya.'],
        6 => ['Kabel rangkaian pada suis aras berkenaan tidak berfungsi menyebabkan sambungan terputus.', 'Port suis ditukar dan kabel diganti; sambungan diuji pada semua titik yang terjejas.', 'Pelabelan port suis dikemaskini dalam dokumentasi rangkaian.'],
        7 => ['Peti mel pengguna melebihi kuota yang ditetapkan menyebabkan emel tidak dapat diterima.', 'Kuota peti mel dinaikkan dan pengguna dibantu mengarkibkan emel lama.', 'Amaran kuota automatik diaktifkan untuk akaun berkenaan.'],
        8 => ['Fail yang dijangkiti dibawa masuk melalui pemacu USB luar yang tidak diimbas.', 'Fail dikuarantin dan dibersihkan, imbasan penuh dijalankan pada komputer berkenaan.', 'Pengguna diingatkan mengimbas pemacu luar sebelum digunakan.'],
        9 => ['Keperluan teknikal acara tidak dimaklumkan lebih awal kepada pasukan ICT.', 'Peralatan disediakan dan diuji sehari sebelum acara; pegawai bertugas ditempatkan sepanjang acara.', 'Borang keperluan teknikal acara diedarkan kepada penganjur.'],
        10 => ['Kebenaran capaian pengguna dalam sistem tidak dikemaskini selepas pertukaran jawatan.', 'Peranan pengguna dalam sistem dikemaskini dan ujian log masuk dijalankan bersama pengadu.', 'Permohonan capaian direkodkan untuk jejak audit.'],
        11 => ['Perkhidmatan pada pelayan berhenti selepas kemaskini dan tidak dimulakan semula secara automatik.', 'Perkhidmatan dimulakan semula, pemantauan diaktifkan dan log disemak bagi punca kegagalan.', 'Jadual kemaskini pelayan diselaraskan di luar waktu pejabat.'],
        12 => ['Sambungan audio visual pada bilik mesyuarat longgar dan tetapan input tidak betul.', 'Sambungan dipasang semula dan tetapan input dikonfigurasi; ujian dijalankan di bilik berkenaan.', 'Panduan ringkas penggunaan sistem audio visual ditampal di bilik mesyuarat.'],
        13 => ['Lampu projektor telah lemah dan penapis habuk tidak dibersihkan.', 'Penapis dibersihkan dan lampu digantikan; paparan diuji pada resolusi penuh.', 'Jadual penyelenggaraan berkala projektor dikemaskini.'],
        14 => ['Sumber input paparan TV tidak dipilih dengan betul dan kabel HDMI rosak.', 'Kabel digantikan dan sumber input ditetapkan; paparan diuji bersama pengguna.', 'Kabel ganti disimpan di bilik peralatan untuk kegunaan segera.'],
        15 => ['Bateri komputer riba sudah lemah dan pemacu paparan tidak serasi.', 'Pemacu paparan dikemaskini dan bateri diganti di bawah waranti pembekal.', 'Nombor siri dan tarikh waranti direkodkan dalam daftar aset.'],
        16 => ['Rekod kehadiran tidak dapat disimpan kerana sesi pengguna tamat tempoh.', 'Sesi pengguna ditetapkan semula dan rekod kehadiran dikemaskini secara manual.', 'Pengguna ditunjukkan cara log masuk semula apabila sesi tamat.'],
    ];

    private const FALLBACK_RESOLUTION = [
        'Konfigurasi peralatan tidak tepat selepas kemaskini sistem.',
        'Konfigurasi ditetapkan semula dan ujian dijalankan bersama pengadu.',
        'Butiran tindakan direkodkan dalam log penyelenggaraan.',
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Arahan ini tidak boleh dijalankan dalam produksi.');

            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));

        // Completed aduan (Selesai / Selesai Tindakan) with no resolution record yet.
        $candidates = Report::query()
            ->whereIn('report_status_id', [5, 7])
            ->whereNotNull('submitted_at')
            ->whereDoesntHave('trails', fn ($q) => $q->where('status_id', self::TRAIL_SELESAI_TINDAKAN))
            ->with('category')
            ->orderByDesc('submitted_at')
            ->limit($count)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Tiada aduan selesai yang memerlukan rekod Knowledge Base.');

            return self::SUCCESS;
        }

        $this->warn('Pangkalan data: '.DB::connection()->getDatabaseName());
        $this->line($candidates->count().' aduan selesai akan diberi rekod Knowledge Base contoh.');
        $this->line('Setiap rekod ditanda: '.self::DEMO_MARKER);

        if (! $this->option('force') && ! $this->confirm('Teruskan?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $fallbackOfficer = User::permission('aduan.updateTask')->first();
        $created = 0;

        foreach ($candidates as $report) {
            $officerId = $this->handlingOfficerId($report) ?? $fallbackOfficer?->id;

            if (! $officerId) {
                continue;
            }

            [$punca, $langkah, $catatan] = self::RESOLUTIONS[$report->report_category_id] ?? self::FALLBACK_RESOLUTION;

            $trail = new ReportTrail();
            $trail->status_id = self::TRAIL_SELESAI_TINDAKAN;
            $trail->updated_by_id = $officerId;
            $trail->punca_masalah = $punca;
            $trail->langkah_tindakan = $langkah;
            $trail->catatan_teknikal = $catatan.' '.self::DEMO_MARKER;
            $trail->message = 'Pegawai bertanggungjawab telah menyelesaikan tindakan.'
                .'<br><b>Punca Masalah:</b><br>'.e($punca)
                .'<br><b>Langkah Penyelesaian:</b><br>'.e($langkah)
                .'<br><i>'.e(self::DEMO_MARKER).'</i>';

            // Dated before the aduan was closed, so the newest trail on the
            // timeline is still the closing one.
            $at = $this->resolvedAt($report);
            $trail->created_at = $at;
            $trail->updated_at = $at;

            $report->trails()->save($trail);
            $created++;
        }

        $this->info($created.' rekod Knowledge Base dicipta.');

        return self::SUCCESS;
    }

    /** The officer on the aduan's latest assignment trail. */
    private function handlingOfficerId(Report $report): ?int
    {
        $id = $report->trails()
            ->whereNotNull('assigned_to_id')
            ->orderByDesc('id')
            ->value('assigned_to_id');

        return $id ? (int) $id : null;
    }

    /** A day before completion, falling back to the submission date. */
    private function resolvedAt(Report $report): Carbon
    {
        $completed = $report->completed_at ? Carbon::parse($report->completed_at) : null;
        $submitted = Carbon::parse($report->submitted_at);

        if ($completed && $completed->greaterThan($submitted)) {
            return $completed->copy()->subDay()->max($submitted);
        }

        return $submitted->copy()->addHours(6);
    }
}
