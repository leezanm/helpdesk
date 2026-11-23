<?php

namespace Tests\Feature\Livewire\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * baseQuery() reads its date bounds through fromDate()/toDate(), so a hand
 * edited URL parameter such as ?dateFrom=abc falls back to the default period
 * instead of throwing InvalidFormatException (a 500).
 *
 * Also covers the listQuery() ordering, which is the single row source shared
 * by the aduan table and the CSV export.
 */
class ReportAllDateGuardTest extends FeatureTestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        return $admin;
    }

    private function makeReport(?string $submittedAt = null): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login(User::factory()->create());

        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = $submittedAt ?? now();
        $report->save();

        Auth::logout();

        return $report->fresh();
    }

    public function test_garbage_date_from_in_the_url_renders_the_default_period_instead_of_a_server_error()
    {
        $admin = $this->admin();
        $report = $this->makeReport();

        $this->actingAs($admin)
            ->get('/laporan/keseluruhan?dateFrom=abc')
            ->assertOk()
            ->assertSee($report->ticket_no);
    }

    public function test_garbage_date_values_keep_the_entered_text_and_still_list_the_default_period()
    {
        $admin = $this->admin();
        $report = $this->makeReport();

        Livewire::actingAs($admin)
            ->withQueryParams(['dateFrom' => 'abc', 'dateTo' => '2026-13-45'])
            ->test(ReportAll::class)
            ->assertOk()
            // The sanitisation happens at the point of use, so the control and
            // the URL keep what the user typed.
            ->assertSet('dateFrom', 'abc')
            ->assertSet('dateTo', '2026-13-45')
            ->assertSee($report->ticket_no);
    }

    public function test_reports_are_ordered_by_submitted_at_then_by_id_descending()
    {
        $admin = $this->admin();
        $sameMoment = now()->subDay()->format('Y-m-d H:i:s');

        $older = $this->makeReport(now()->subDays(2)->format('Y-m-d H:i:s'));
        $tieFirst = $this->makeReport($sameMoment);
        $tieSecond = $this->makeReport($sameMoment);

        $ids = Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->viewData('reports')
            ->pluck('id')
            ->all();

        // Equal submitted_at values are broken by id descending, so chunk()
        // based exports cannot skip or repeat a row.
        $this->assertSame([$tieSecond->id, $tieFirst->id, $older->id], $ids);
    }
}
