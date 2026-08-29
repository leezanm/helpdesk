<?php

namespace Tests\Feature\Livewire\Aduan;

use App\Livewire\Aduan\ReportIndex;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class ReportIndexTest extends FeatureTestCase
{
    private function makeReport(User $owner, int $status = 2): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login($owner);

        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = $status;
        $report->submitted_at = now();
        $report->save();

        return $report;
    }

    public function test_user_scope_lists_own_reports()
    {
        $owner = User::factory()->create();
        $report = $this->makeReport($owner, 2);

        Livewire::actingAs($owner)
            ->test(ReportIndex::class, ['scope' => 'user', 'username' => $owner->username])
            ->assertOk()
            ->assertSee($report->subject);
    }

    public function test_search_filters_by_subject()
    {
        $owner = User::factory()->create();
        $a = $this->makeReport($owner, 2);
        $b = $this->makeReport($owner, 2);

        Livewire::actingAs($owner)
            ->test(ReportIndex::class, ['scope' => 'user', 'username' => $owner->username])
            ->set('search', $a->subject)
            ->assertSee($a->subject)
            ->assertDontSee($b->subject);
    }

    public function test_sorting_toggles_direction()
    {
        $owner = User::factory()->create();
        $this->makeReport($owner, 2);

        Livewire::actingAs($owner)
            ->test(ReportIndex::class, ['scope' => 'user'])
            ->call('sortBy', 'ticket_no')
            ->assertSet('sortField', 'ticket_no')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'ticket_no')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_all_scope_shows_submitted_reports()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 2);

        Livewire::actingAs($admin)
            ->test(ReportIndex::class, ['scope' => 'all'])
            ->assertSee($report->subject);
    }
}
