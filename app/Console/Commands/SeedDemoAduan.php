<?php

namespace App\Console\Commands;

use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use App\Support\RiskClassifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/**
 * Creates demo aduan for a given month, spread across the whole flow so a
 * walkthrough has something to show at every stage: new, reviewed, in action,
 * KIV, done-by-officer, needs-further-action and closed.
 *
 * Two aduan are left at "Baru" with NO risk level on purpose, so the rule
 * "helpdesk must classify before forwarding" can be demonstrated live.
 *
 * Existing accounts are reused as reporters and officers — nothing is invented.
 * Notifications are suppressed: nobody should be emailed about demo aduan.
 *
 * Idempotent: an aduan whose subject already exists in the target month is
 * skipped, so re-running does not pile up duplicates.
 *
 * Refuses to run in production.
 */
class SeedDemoAduan extends Command
{
    protected $signature = 'demo:seed-aduan
                            {--month= : Target month as YYYY-MM (default: current month)}
                            {--count=10 : How many aduan to create (max 10)}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Cipta aduan dummy untuk demo bagi satu bulan';

    /**
     * Each entry: subject, remark, production report_categories.id, and the
     * flow stage to leave the aduan at.
     *
     * Stages: 'baru', 'disemak', 'tindakan', 'kiv', 'selesai_tindakan',
     *         'lanjut', 'selesai'
     */
    private const ADUAN = [
        ['Tidak boleh akses emel rasmi', 'Sejak pagi tadi emel rasmi tidak boleh dibuka pada Outlook. Mesej ralat "cannot connect to server" dipaparkan.', 7, 'baru'],
        ['Pencetak di aras 3 tidak berfungsi', 'Pencetak HP LaserJet di aras 3 tidak mencetak walaupun dokumen berada dalam queue. Mohon bantuan teknikal.', 4, 'baru'],
        ['Komputer meja sangat perlahan', 'Komputer mengambil masa lebih 10 minit untuk boot dan kerap hang ketika membuka fail Excel.', 3, 'disemak'],
        ['Rangkaian internet terputus di Bahagian Kewangan', 'Semua pengguna di Bahagian Kewangan tidak dapat mengakses internet sejak 9.00 pagi.', 6, 'tindakan'],
        ['Permohonan akses sharefolder Kewangan', 'Mohon kebenaran akses kepada sharefolder Kewangan bagi tujuan penyediaan laporan bulanan.', 10, 'tindakan'],
        ['Sistem eBantuan tidak dapat dicapai', 'Sistem eBantuan memaparkan ralat 503 apabila cuba log masuk. Pengguna di beberapa bahagian turut terjejas.', 11, 'kiv'],
        ['Permohonan pautan sidang video mesyuarat bulanan', 'Mohon sediakan pautan sidang video bagi Mesyuarat Pengurusan Bulanan pada 20 November 2026, 10.00 pagi.', 5, 'selesai_tindakan'],
        ['Perisian antivirus memaparkan amaran virus', 'Antivirus mengesan virus pada pemacu USB yang digunakan untuk memindahkan fail persidangan.', 8, 'selesai_tindakan'],
        ['Skrin komputer riba berkelip', 'Skrin komputer riba berkelip terutamanya ketika menggunakan aplikasi persembahan semasa taklimat.', 15, 'lanjut'],
        ['Lupa kata laluan akaun domain', 'Mohon set semula kata laluan akaun domain kerana tidak dapat log masuk ke komputer pejabat.', 10, 'selesai'],
    ];

    public function handle(RiskClassifier $classifier): int
    {
        if (app()->environment('production')) {
            $this->error('Arahan ini tidak boleh dijalankan dalam produksi.');

            return self::FAILURE;
        }

        $month = $this->targetMonth();

        if (! $month) {
            $this->error('Format --month tidak sah. Guna YYYY-MM, contoh: --month=2026-11');

            return self::FAILURE;
        }

        $count = max(1, min((int) $this->option('count'), count(self::ADUAN)));

        $reporters = $this->reporters();
        $officers = $this->officers();
        $helpdesk = $this->helpdeskOfficer();

        if ($reporters->isEmpty() || $officers->isEmpty() || ! $helpdesk) {
            $this->error('Tiada akaun yang mencukupi: perlukan pengadu, pegawai (aduan.updateTask) dan peg. helpdesk (aduan.adminTask).');

            return self::FAILURE;
        }

        $this->line('Bulan sasaran : '.$month->translatedFormat('F Y'));
        $this->line('Bilangan      : '.$count.' aduan');
        $this->line('Pengadu       : '.$reporters->count().' akaun, Pegawai: '.$officers->count().' akaun');

        if (! $this->option('force') && ! $this->confirm('Teruskan cipta aduan demo?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        // Demo data must not email anyone.
        Notification::fake();

        $created = 0;
        $skipped = 0;
        $rows = [];

        foreach (array_slice(self::ADUAN, 0, $count) as $index => [$subject, $remark, $categoryId, $stage]) {
            if ($this->alreadyExists($subject, $month)) {
                $skipped++;

                continue;
            }

            $submittedAt = $this->submittedAt($month, $index, $count);
            $reporter = $reporters[$index % $reporters->count()];
            $officer = $officers[$index % $officers->count()];

            $report = $this->createReport($subject, $remark, $categoryId, $submittedAt, $reporter, $stage, $classifier);
            $this->advance($report, $stage, $submittedAt, $helpdesk, $officer);

            $created++;
            $rows[] = [
                $report->ticket_no,
                mb_strimwidth($subject, 0, 38, '...'),
                $submittedAt->format('d/m/Y'),
                $report->fresh()->status->desc,
                $report->fresh()->riskLevel?->label ?? 'Belum Ditetapkan',
            ];
        }

        Auth::logout();

        if ($rows) {
            $this->newLine();
            $this->table(['Tiket', 'Subjek', 'Tarikh', 'Status', 'Tahap Risiko'], $rows);
        }

        $this->info($created.' aduan dicipta'.($skipped ? ", $skipped dilangkau (sudah ada)" : '').'.');

        return self::SUCCESS;
    }

    private function targetMonth(): ?Carbon
    {
        $option = $this->option('month');

        if (blank($option)) {
            return now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $option.'-01')->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Spread the aduan across the month, never into the future. */
    private function submittedAt(Carbon $month, int $index, int $count): Carbon
    {
        $last = $month->copy()->endOfMonth();

        if ($last->isFuture()) {
            $last = now();
        }

        $span = max(1, $month->diffInDays($last));
        $day = (int) round($span * ($index / max(1, $count - 1)));

        return $month->copy()
            ->addDays(min($day, $span))
            ->setTime(8 + ($index % 8), ($index * 7) % 60);
    }

    private function alreadyExists(string $subject, Carbon $month): bool
    {
        return Report::where('subject', $subject)
            ->whereBetween('submitted_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->exists();
    }

    private function createReport(
        string $subject,
        string $remark,
        int $categoryId,
        Carbon $submittedAt,
        User $reporter,
        string $stage,
        RiskClassifier $classifier
    ): Report {
        // The observer stamps the first trail using the authenticated user.
        Auth::login($reporter);

        $category = ReportCategory::find($categoryId) ?? ReportCategory::query()->first();

        $report = new Report();
        $report->subject = $subject;
        $report->remark = $remark;
        $report->report_category_id = $category?->id;
        $report->report_status_id = 2;                 // Baru — observer adds trail
        $report->report_by_id = $reporter->id;
        $report->bahagian_pengadu = $reporter->ldap_bahagian;
        $report->submitted_at = $submittedAt;
        // Ticket number follows the aduan's own date, not today's.
        $report->ticket_no = $this->ticketNoFor($submittedAt);

        // "Baru" aduan stay unclassified on purpose: the helpdesk has not
        // looked at them yet, which is what makes the mandatory-risk rule
        // visible in a demo.
        if ($stage !== 'baru') {
            $level = $classifier->classify($category?->id, $subject, $remark);
            $report->risk_level_id = RiskLevel::where('level', $level)->value('id');
        }

        $report->save();

        return $report;
    }

    /** Next free ticket number for that date, e.g. 261105/HQ/004. */
    private function ticketNoFor(Carbon $date): string
    {
        $prefix = $date->format('ymd').'/HQ/';

        $taken = Report::where('ticket_no', 'like', $prefix.'%')
            ->pluck('ticket_no')
            ->map(fn ($no) => (int) substr((string) $no, -3))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($taken + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Walks the aduan to its demo stage, writing the trail rows the real flow
     * would have produced.
     */
    private function advance(Report $report, string $stage, Carbon $submittedAt, User $helpdesk, User $officer): void
    {
        if ($stage === 'baru') {
            return;
        }

        $role = $officer->roles->first();
        $at = $submittedAt->copy()->addHours(2);

        // Every stage past "baru" has been reviewed by the helpdesk.
        $this->trail($report, $helpdesk, 3, 'Aduan telah disemak oleh peg. helpdesk Sistem Meja Bantuan KKDW', $at);
        $report->report_status_id = 3;

        if ($stage === 'disemak') {
            $report->save();

            return;
        }

        // Forwarded to a technical officer.
        $at = $at->copy()->addHours(3);
        $this->trail(
            $report,
            $helpdesk,
            4,
            'Aduan telah dihantar kepada <a class="userProfile" data-userId="'.$officer->id.'" role="button">'
                .e($officer->name).' ('.e($role->desc ?? $role->name ?? 'Pegawai').')</a> untuk disemak',
            $at,
            $officer,
            $role
        );
        $report->report_status_id = 4;

        match ($stage) {
            'kiv' => $this->toKiv($report, $helpdesk, $at),
            'selesai_tindakan' => $this->toOfficerDone($report, $officer, $at),
            'lanjut' => $this->toFurtherAction($report, $officer, $at),
            'selesai' => $this->toClosed($report, $officer, $helpdesk, $at),
            default => null,   // 'tindakan' — stays in action
        };

        $report->save();
    }

    private function toKiv(Report $report, User $helpdesk, Carbon $at): void
    {
        $start = $at->copy()->addDay();
        $report->kiv_start_at = $start;
        $report->kiv_reason = 'Menunggu bahagian pembekal menggantikan peralatan.';
        $report->report_status_id = 6;

        $this->trail($report, $helpdesk, 7, 'Aduan telah di KIV bermula pada <b>'.$start->format('d/m/Y').'</b>', $start);
    }

    private function toOfficerDone(Report $report, User $officer, Carbon $at): void
    {
        $done = $at->copy()->addDay();
        $report->report_status_id = 7;

        $trail = $this->trail($report, $officer, 9,
            'Pegawai bertanggungjawab telah menyelesaikan tindakan.'
            .'<br><b>Punca Masalah:</b><br>Konfigurasi peralatan tidak tepat selepas kemaskini sistem.'
            .'<br><b>Langkah Penyelesaian:</b><br>Konfigurasi ditetapkan semula dan ujian dijalankan bersama pengadu.',
            $done
        );

        $trail->punca_masalah = 'Konfigurasi peralatan tidak tepat selepas kemaskini sistem.';
        $trail->langkah_tindakan = 'Konfigurasi ditetapkan semula dan ujian dijalankan bersama pengadu.';
        $trail->catatan_teknikal = 'Rujuk log penyelenggaraan bagi butiran tetapan.';
        $trail->save();
    }

    private function toFurtherAction(Report $report, User $officer, Carbon $at): void
    {
        $when = $at->copy()->addDay();
        $report->report_status_id = 8;

        $trail = $this->trail($report, $officer, 10,
            'Aduan memerlukan tindakan lanjut.'
            .'<br><b>Punca Masalah:</b><br>Kerosakan pada panel skrin, bukan isu perisian.'
            .'<br><b>Sebab Perlu Tindakan Lanjut:</b><br>Peralatan perlu dihantar kepada pembekal di bawah waranti.',
            $when
        );

        $trail->punca_masalah = 'Kerosakan pada panel skrin, bukan isu perisian.';
        $trail->kenapa_lanjut = 'Peralatan perlu dihantar kepada pembekal di bawah waranti.';
        $trail->save();
    }

    private function toClosed(Report $report, User $officer, User $helpdesk, Carbon $at): void
    {
        $this->toOfficerDone($report, $officer, $at);

        $closed = $at->copy()->addDays(2);
        $report->report_status_id = 5;
        $report->completed_at = $closed;
        $report->rating = 5;
        $report->feedback = 'Tindakan cepat dan menyelesaikan masalah. Terima kasih.';

        $this->trail($report, $helpdesk, 5, 'Aduan telah ditutup sebagai Selesai.', $closed);
    }

    private function trail(
        Report $report,
        User $actor,
        int $statusId,
        string $message,
        Carbon $at,
        ?User $assignedTo = null,
        ?Role $role = null
    ): ReportTrail {
        $trail = new ReportTrail();
        $trail->message = $message;
        $trail->updated_by_id = $actor->id;
        $trail->status_id = $statusId;
        $trail->assigned_to_id = $assignedTo?->id;
        $trail->assigned_to_role_id = $role?->id;
        $trail->created_at = $at;
        $trail->updated_at = $at;
        $report->trails()->save($trail);

        return $trail;
    }

    /** Ordinary users, preferring ones that already filed aduan. */
    private function reporters()
    {
        $users = User::role('pengguna')->where('is_active', true)->limit(10)->get();

        return $users->isNotEmpty() ? $users : User::limit(5)->get();
    }

    private function officers()
    {
        return User::permission('aduan.updateTask')->where('is_active', true)->limit(5)->get();
    }

    private function helpdeskOfficer(): ?User
    {
        return User::permission('aduan.adminTask')->where('is_active', true)->first()
            ?? User::permission('aduan.adminTask')->first();
    }
}
