<?php

namespace Tests\Feature\Livewire\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Ref\ReportStatus;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\ReportStatuses;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;
use Tests\FeatureTestCase;

/**
 * statusCounts() feeds the eight statistic cards and (later) the donut, so the
 * invariants it has to hold are: exactly seven status entries, every COUNTED
 * status present even with no aduan, counts straight from the filtered query,
 * tone derived from _report_statuses.class, and one distinct icon per status.
 *
 * The builders are protected (the design keeps render() as the only point that
 * hands data to the view), so they are exercised through reflection.
 */
class ReportAllStatusCountsTest extends FeatureTestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        return $admin;
    }

    private function makeReport(int $status): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login(User::factory()->create());

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

    /** @return array<int, array<string, mixed>> */
    private function statusCounts(ReportAll $component): array
    {
        $method = new ReflectionMethod(ReportAll::class, 'statusCounts');
        $method->setAccessible(true);

        return $method->invoke($component);
    }

    private function reportAll(): ReportAll
    {
        $this->actingAs($this->admin());

        $component = new ReportAll();
        $component->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $component->dateTo = now()->format('Y-m-d');

        return $component;
    }

    public function test_every_counted_status_is_present_in_ascending_id_order_even_with_no_aduan()
    {
        $this->makeReport(ReportStatuses::KIV);

        $counts = $this->statusCounts($this->reportAll());

        $this->assertSame(ReportStatuses::COUNTED, array_column($counts, 'id'));

        // Statuses without aduan stay visible with a zero total.
        $byId = array_column($counts, 'total', 'id');
        $this->assertSame(1, $byId[ReportStatuses::KIV]);
        $this->assertSame(0, $byId[ReportStatuses::BARU]);
        $this->assertSame(0, $byId[ReportStatuses::TINDAKAN_LANJUT]);
    }

    public function test_totals_come_from_the_filtered_query_and_sum_to_the_grand_total()
    {
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::SELESAI);
        // Draf was never submitted, so it is not an aduan and is not counted.
        $this->makeReport(ReportStatuses::DRAF);

        $counts = $this->statusCounts($this->reportAll());
        $byId = array_column($counts, 'total', 'id');

        $this->assertSame(2, $byId[ReportStatuses::BARU]);
        $this->assertSame(1, $byId[ReportStatuses::SELESAI]);
        $this->assertSame(3, array_sum(array_column($counts, 'total')));
        $this->assertSame(
            Report::whereIn('report_status_id', ReportStatuses::COUNTED)->count(),
            array_sum(array_column($counts, 'total'))
        );
    }

    public function test_tone_is_the_first_bg_token_of_the_status_badge_class()
    {
        $counts = $this->statusCounts($this->reportAll());
        $byId = array_column($counts, 'tone', 'id');

        // Same source as the table badge: _report_statuses.class.
        $this->assertSame('bg-info', $byId[ReportStatuses::BARU]);
        $this->assertSame('bg-warning', $byId[ReportStatuses::DALAM_TINDAKAN]);
        $this->assertSame('bg-success', $byId[ReportStatuses::SELESAI]);
        $this->assertSame('bg-danger', $byId[ReportStatuses::TINDAKAN_LANJUT]);

        foreach ($counts as $row) {
            $this->assertStringContainsString($row['tone'], (string) $row['class']);
        }
    }

    public function test_tone_falls_back_to_bg_secondary_when_the_class_has_no_bg_token()
    {
        ReportStatus::whereKey(ReportStatuses::BARU)->update(['class' => 'badge']);

        $counts = $this->statusCounts($this->reportAll());

        $this->assertSame('bg-secondary', array_column($counts, 'tone', 'id')[ReportStatuses::BARU]);
    }

    public function test_each_status_gets_a_distinct_stable_icon_and_unknown_ids_fall_back()
    {
        $component = $this->reportAll();
        $icons = array_column($this->statusCounts($component), 'icon', 'id');

        $this->assertCount(7, $icons);
        $this->assertCount(7, array_unique($icons));

        // Stable across renders.
        $this->assertSame($icons, array_column($this->statusCounts($component), 'icon', 'id'));

        $statusIcon = new ReflectionMethod(ReportAll::class, 'statusIcon');
        $statusIcon->setAccessible(true);
        $this->assertSame('bi-circle', $statusIcon->invoke($component, 999));
    }
}
