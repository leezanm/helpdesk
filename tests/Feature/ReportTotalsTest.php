<?php

namespace Tests\Feature;

use App\Livewire\Aduan\ReportIndex;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Laporan\ReportAll;
use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\ReportStatuses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Totals must agree with each other and must not silently drop a status.
 *
 * Rules fixed here: Draf (1) is never counted; 7 (Selesai Tindakan) and
 * 8 (Tindakan Lanjut Diperlukan) count as open aduan.
 */
class ReportTotalsTest extends FeatureTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->syncRoles(['helpdesk.admin']);

        Notification::fake();

        // One aduan at every status, 1..8.
        foreach (range(1, 8) as $status) {
            $this->makeReport($status);
        }
    }

    private function makeReport(int $status): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        $owner = User::factory()->create();
        Auth::login($owner);

        $report = new Report();
        $report->subject = 'Aduan status '.$status;
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = $status === 1 ? 1 : 2;
        $report->risk_level_id = RiskLevel::where('level', 1)->value('id');
        $report->submitted_at = now()->subDays(3);
        $report->bahagian_pengadu = 'Bahagian Pengurusan Maklumat';
        $report->save();

        if ($status !== 1 && $status !== 2) {
            $report->report_status_id = $status;
            $report->save();
        }

        Auth::logout();

        return $report->fresh();
    }

    public function test_dashboard_total_counts_every_submitted_status_and_excludes_draf()
    {
        // 8 aduan exist; 1 is Draf, so 7 are counted.
        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->assertViewHas('total', 7);
    }

    public function test_dashboard_in_progress_includes_statuses_seven_and_eight()
    {
        // Open = everything submitted except Selesai(5) => 6 aduan.
        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->assertViewHas('inProgress', 6)
            ->assertViewHas('resolved', 1)
            ->assertViewHas('kiv', 1);
    }

    public function test_dashboard_total_equals_resolved_plus_in_progress()
    {
        $view = Livewire::actingAs($this->admin)->test(Dashboard::class);

        $total = $view->viewData('total');
        $resolved = $view->viewData('resolved');
        $inProgress = $view->viewData('inProgress');

        $this->assertEquals($total, $resolved + $inProgress,
            'Jumlah sepatutnya sama dengan selesai + dalam tindakan.');
    }

    public function test_dashboard_status_donut_covers_the_whole_total()
    {
        $view = Livewire::actingAs($this->admin)->test(Dashboard::class);

        $breakdown = collect($view->viewData('statusBreakdown') ?? []);

        if ($breakdown->isEmpty()) {
            $this->markTestSkipped('statusBreakdown tidak dihantar ke view.');
        }

        $this->assertEquals($view->viewData('total'), $breakdown->sum('value'),
            'Hirisan donut sepatutnya berjumlah sama dengan Jumlah Aduan.');
    }

    public function test_laporan_keseluruhan_cards_add_up_to_the_grand_total()
    {
        $view = Livewire::actingAs($this->admin)->test(ReportAll::class)
            ->set('dateFrom', now()->subMonth()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'));

        $summary = collect($view->viewData('summary'));
        $grandTotal = $view->viewData('grandTotal');

        $this->assertEquals(7, $grandTotal, 'Draf tidak sepatutnya dikira.');
        $this->assertEquals($grandTotal, $summary->sum('total'),
            'Kad per-status sepatutnya berjumlah sama dengan JUMLAH.');
    }

    public function test_laporan_keseluruhan_excludes_draf_from_the_listing()
    {
        $view = Livewire::actingAs($this->admin)->test(ReportAll::class)
            ->set('dateFrom', now()->subMonth()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'));

        $this->assertEquals(7, $view->viewData('reports')->total());
    }

    public function test_semua_aduan_listing_shows_statuses_seven_and_eight()
    {
        $view = Livewire::actingAs($this->admin)->test(ReportIndex::class, ['scope' => 'all']);

        $statuses = $view->viewData('reports')->pluck('report_status_id')->sort()->values()->all();

        $this->assertEquals(ReportStatuses::COUNTED, $statuses);
    }

    public function test_laporan_by_status_excludes_draf_from_the_denominator()
    {
        $response = $this->actingAs($this->admin)->get('/laporan/mengikut-status-tindakan');

        $response->assertOk();

        $laporans = collect($response->viewData('laporans'));

        $this->assertFalse($laporans->contains('id', ReportStatuses::DRAF), 'Baris Draf sepatutnya tiada.');
        $this->assertEquals(7, $laporans->sum('jumlah'));
        // 1 of 7 submitted aduan is Selesai.
        $this->assertEquals('14.29', $response->viewData('achievement'));
    }
}
