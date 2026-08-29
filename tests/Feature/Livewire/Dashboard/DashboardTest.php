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
}
