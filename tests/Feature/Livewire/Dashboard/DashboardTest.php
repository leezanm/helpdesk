<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Dashboard;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class DashboardTest extends FeatureTestCase
{
    private function makeReport(User $owner, int $status = 2): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login($owner);
        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = now();
        $report->save();
        if ($status !== 2) {
            $report->report_status_id = $status;
            $report->save();
        }
        Auth::logout();

        return $report->fresh();
    }

    public function test_renders_for_ordinary_user_with_own_open_reports()
    {
        $user = User::factory()->create();
        $report = $this->makeReport($user, 2);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Aduan Saya')          // role-aware title
            ->assertSee($report->subject)
            ->assertSee('Cipta Aduan');        // quick action
    }

    public function test_admin_sees_admin_title_and_unprocessed_reports()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 2);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSee('Aduan Yang Belum Diproses')
            ->assertSee($report->subject);
    }

    public function test_stat_counts_reflect_data()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $this->makeReport(User::factory()->create(), 5); // resolved
        $this->makeReport(User::factory()->create(), 4); // in progress

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertViewHas('resolved', 1)
            ->assertViewHas('inProgress', 1)
            ->assertViewHas('total', 2);
    }

    public function test_date_filter_excludes_reports_outside_range()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $this->makeReport(User::factory()->create(), 2); // submitted today

        // Filter to a past range that excludes today's report.
        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->set('dateFrom', now()->subMonths(3)->format('Y-m-d'))
            ->set('dateTo', now()->subMonths(2)->format('Y-m-d'))
            ->assertViewHas('total', 0);
    }

    public function test_category_breakdown_present()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $this->makeReport(User::factory()->create(), 2);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertViewHas('categoryBreakdown', function ($breakdown) {
                return collect($breakdown)->contains(fn ($c) => $c['label'] === 'Rangkaian' && $c['value'] >= 1);
            });
    }

    public function test_officer_workload_counts_assigned_reports()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $officer = User::factory()->create(['name' => 'Pegawai Teknikal Satu']);
        $officer->syncRoles(['jtk']);

        // Create a report and assign it to the officer via a trail, then mark
        // it resolved — the officer should show diterima=1, selesai=1.
        $report = $this->makeReport(User::factory()->create(), 4);
        $trail = new \App\Models\ReportTrail();
        $trail->report_id = $report->id;
        $trail->message = 'assigned';
        $trail->updated_by_id = $admin->id;
        $trail->assigned_to_id = $officer->id;
        $trail->status_id = 4;
        $trail->save();

        $report->report_status_id = 5; // now Selesai
        $report->save();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertViewHas('officerWorkload', function ($rows) use ($officer) {
                $row = collect($rows)->firstWhere('name', $officer->name);
                return $row && (int) $row->diterima === 1 && (int) $row->selesai === 1;
            })
            ->assertSee('Beban Tugas Pegawai');
    }

    public function test_main_dashboard_date_filter_defaults_to_start_of_year()
    {
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('dateFrom', now()->startOfYear()->format('Y-m-d'))
            ->assertSet('dateTo', now()->format('Y-m-d'))
            // "Set Semula" returns to the same year-to-date range.
            ->set('dateFrom', now()->subYears(2)->format('Y-m-d'))
            ->call('resetFilter')
            ->assertSet('dateFrom', now()->startOfYear()->format('Y-m-d'));
    }
}
