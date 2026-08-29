<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Dummy data to make the dashboard look populated during UI review.
 *
 * Creates recent reports (submitted this week) across the statuses the
 * dashboard surfaces (2 Dihantar, 3 AmbilMaklum, 4 DalamTindakan, 6 KIV),
 * plus some resolved (5), assigned to the superadmin and technical officers.
 *
 * SAFETY: refuses to run in production. Intended for staging/local only.
 * Dummy subjects are prefixed "[DEMO]" so they can be identified/cleaned.
 */
class DashboardDummySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('DashboardDummySeeder is disabled in production.');
            return;
        }

        $admin = User::where('username', 'superadmin')->first()
            ?? User::first();

        $categories = ReportCategory::where('is_active', true)->pluck('id')->all();
        if (empty($categories) || ! $admin) {
            $this->command->error('Need at least one active category and one user.');
            return;
        }

        // Some technical officers to assign "in action" reports to.
        $officers = User::permission('aduan.updateTask')->pluck('id')->all();

        $subjects = [
            'Komputer tidak boleh hidup',
            'Internet perlahan di aras 3',
            'Pencetak tersekat kertas',
            'Emel rasmi tidak dapat dihantar',
            'Skrin monitor berkelip',
            'Perisian Office perlu dipasang semula',
            'Talian VPN terputus',
            'Fail rosak selepas kemas kini',
            'Papan kekunci tidak berfungsi',
            'Permohonan capaian sistem baharu',
        ];

        // Log in as the admin so the ReportObserver (which reads Auth::id())
        // records report_by_id / trail authorship correctly.
        Auth::login($admin);

        // Weighted spread across the statuses the dashboard cares about, all
        // submitted within the past week so the "this week" charts populate.
        $plan = [
            [2, 8],  // Dihantar
            [3, 6],  // Ambil Maklum
            [4, 7],  // Dalam Tindakan (assigned)
            [6, 3],  // KIV
            [5, 6],  // Selesai (this week)
        ];

        $made = 0;
        foreach ($plan as [$status, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $daysAgo = random_int(0, 6);
                $submittedAt = now()->subDays($daysAgo)->subHours(random_int(0, 8));

                $report = new Report();
                $report->subject = '[DEMO] '.$subjects[array_rand($subjects)];
                $report->remark = 'Data demo untuk paparan dashboard.';
                $report->report_category_id = $categories[array_rand($categories)];
                $report->report_status_id = 2; // create as Dihantar so the observer stamps ticket_no + first trail
                $report->submitted_at = $submittedAt;
                $report->save();

                if ($status !== 2) {
                    $report->report_status_id = $status;
                    if ($status === 5) {
                        $report->completed_at = $submittedAt->copy()->addDays(random_int(1, 3));
                        $report->rating = random_int(3, 5);
                    }
                    $report->save();

                    // A trail entry so currentTrail / assigned views work.
                    $trail = new ReportTrail();
                    $trail->message = 'Status demo dikemaskini.';
                    $trail->updated_by_id = $admin->id;
                    $trail->status_id = min($status, 6);
                    if ($status === 4 && ! empty($officers)) {
                        $trail->assigned_to_id = $officers[array_rand($officers)];
                    }
                    $report->trails()->save($trail);
                }

                $made++;
            }
        }

        // --- Lesson Learn: resolved-action records (status 7 + trail 9) ---
        // Realistic punca masalah + langkah penyelesaian across categories, so
        // the knowledge base has meaningful, searchable content.
        $lessons = [
            ['Komputer tidak boleh hidup', 'Bekalan kuasa (PSU) rosak', 'Ganti unit PSU dan uji but semula', 'Simptom: tiada lampu kuasa langsung.'],
            ['Internet perlahan di aras 3', 'Kabel rangkaian longgar di port switch', 'Kabel dipasang semula dan port diuji', 'Guna kabel Cat6 baharu.'],
            ['Pencetak tersekat kertas', 'Roller pencetak kotor dan aus', 'Bersih roller dan ganti bahagian aus', 'Servis berkala setiap 6 bulan.'],
            ['Emel rasmi tidak dapat dihantar', 'Peti emel penuh melebihi kuota', 'Arkib emel lama dan naik taraf kuota', 'Pengguna dilatih arkib sendiri.'],
            ['Skrin monitor berkelip', 'Kabel HDMI longgar / rosak', 'Ganti kabel HDMI dan kemas sambungan', null],
            ['Perisian Office perlu dipasang semula', 'Fail pemasangan Office korup', 'Nyahpasang bersih dan pasang semula dari sumber rasmi', 'Guna Office Deployment Tool.'],
            ['Talian VPN terputus', 'Sijil VPN tamat tempoh', 'Perbaharui sijil dan konfigurasi semula klien', 'Set peringatan sebelum tamat tempoh.'],
            ['Kata laluan AD terkunci', 'Percubaan log masuk gagal berulang', 'Buka kunci akaun dan reset kata laluan', 'Nasihat pengguna guna pengurus kata laluan.'],
            ['Cakera keras hampir penuh', 'Fail sementara & log menimbun', 'Bersih fail sementara dan naik taraf ke SSD', null],
            ['Capaian folder kongsi ditolak', 'Kebenaran NTFS tidak betul', 'Betulkan keahlian kumpulan dan kebenaran folder', 'Rujuk matriks capaian bahagian.'],
        ];

        $lessonMade = 0;
        foreach ($lessons as $i => $lesson) {
            [$subject, $punca, $langkah, $catatan] = $lesson;

            Auth::login($admin);
            $report = new Report();
            $report->subject = '[DEMO] '.$subject;
            $report->remark = 'Data demo lesson learn.';
            $report->report_category_id = $categories[array_rand($categories)];
            $report->report_status_id = 2;
            $report->submitted_at = now()->subDays(random_int(1, 20));
            $report->save();

            $report->report_status_id = 7; // Selesai Tindakan
            $report->save();

            $officerId = ! empty($officers) ? $officers[array_rand($officers)] : $admin->id;

            $trail = new ReportTrail();
            $trail->report_id = $report->id;
            $trail->message = 'Pegawai bertanggungjawab telah menyelesaikan tindakan.';
            $trail->updated_by_id = $officerId;
            $trail->status_id = 9; // Selesai Tindakan
            $trail->punca_masalah = $punca;
            $trail->langkah_tindakan = $langkah;
            $trail->catatan_teknikal = $catatan;
            $trail->save();

            $lessonMade++;
        }

        Auth::logout();

        $this->command->info("DashboardDummySeeder: created {$made} demo reports + {$lessonMade} lesson-learn records.");
    }
}
