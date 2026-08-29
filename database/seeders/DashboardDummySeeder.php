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

        Auth::logout();

        $this->command->info("DashboardDummySeeder: created {$made} demo reports.");
    }
}
