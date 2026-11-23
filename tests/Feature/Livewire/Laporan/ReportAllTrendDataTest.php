<?php

namespace Tests\Feature\Livewire\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\ReportStatuses;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;
use Tests\FeatureTestCase;

/**
 * trendData() is the Carta_Tren payload. Its axis comes from the date range
 * and not from the data, which is what makes the two halves of the
 * requirement hold at once: an interval with no aduan is still present with
 * value 0, and no interval outside the range can ever appear.
 *
 * The builders are protected (render() stays the only point that hands data to
 * the view), so they are exercised through reflection, the same way the donut
 * and status count tests do it.
 */
class ReportAllTrendDataTest extends FeatureTestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        return $admin;
    }

    private function makeReport(string $submittedAt, int $status = ReportStatuses::BARU): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login(User::factory()->create());

        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = $submittedAt;
        $report->save();

        if ($status !== 2) {
            $report->report_status_id = $status;
            $report->save();
        }

        Auth::logout();

        return $report->fresh();
    }

    private function reportAll(string $from, string $to): ReportAll
    {
        $this->actingAs($this->admin());

        $component = new ReportAll();
        $component->dateFrom = $from;
        $component->dateTo = $to;

        return $component;
    }

    /** @return array{granularity:string, labels:array<int,string>, values:array<int,int>, total:int} */
    private function trend(ReportAll $component): array
    {
        $method = new ReflectionMethod(ReportAll::class, 'trendData');
        $method->setAccessible(true);

        return $method->invoke($component);
    }

    private function invoke(ReportAll $component, string $name, ...$args)
    {
        $method = new ReflectionMethod(ReportAll::class, $name);
        $method->setAccessible(true);

        return $method->invoke($component, ...$args);
    }

    private function grandTotal(ReportAll $component): int
    {
        $counts = $this->invoke($component, 'statusCounts');

        return (int) array_sum(array_column($counts, 'total'));
    }

    public function test_daily_bar_values_sum_to_the_grand_total()
    {
        $this->makeReport('2026-01-01 09:00:00');
        $this->makeReport('2026-01-01 15:00:00');
        $this->makeReport('2026-01-05 08:00:00', ReportStatuses::SELESAI);
        // Draf was never submitted, so it is neither counted nor plotted.
        $this->makeReport('2026-01-05 10:00:00', ReportStatuses::DRAF);

        $component = $this->reportAll('2026-01-01', '2026-01-10');
        $trend = $this->trend($component);

        $this->assertSame('day', $trend['granularity']);
        $this->assertSame(3, $trend['total']);
        $this->assertSame($trend['total'], array_sum($trend['values']));
        // The same figure the statistic cards and the donut centre show.
        $this->assertSame($this->grandTotal($component), $trend['total']);
    }

    public function test_monthly_bar_values_sum_to_the_grand_total()
    {
        $this->makeReport('2026-01-09 09:00:00');
        $this->makeReport('2026-03-02 09:00:00');
        $this->makeReport('2026-03-28 09:00:00', ReportStatuses::KIV);

        $component = $this->reportAll('2026-01-01', '2026-04-30');
        $trend = $this->trend($component);

        $this->assertSame('month', $trend['granularity']);
        $this->assertSame(['Jan 2026', 'Feb 2026', 'Mac 2026', 'Apr 2026'], $trend['labels']);
        $this->assertSame([1, 0, 2, 0], $trend['values']);
        $this->assertSame(3, $trend['total']);
        $this->assertSame($trend['total'], array_sum($trend['values']));
        $this->assertSame($this->grandTotal($component), $trend['total']);
    }

    public function test_the_granularity_boundary_sits_between_sixty_two_and_sixty_three_days()
    {
        // 1 Jan to 3 Mar 2026 is 31 + 28 + 3 = 62 days counted inclusively.
        $sixtyTwo = $this->reportAll('2026-01-01', '2026-03-03');
        $this->assertSame('day', $this->invoke($sixtyTwo, 'trendGranularity'));
        $this->assertSame(62, $this->invoke($sixtyTwo, 'trendBucketCount'));
        $this->assertCount(62, $this->trend($sixtyTwo)['labels']);

        // One day more flips the chart to monthly buckets.
        $sixtyThree = $this->reportAll('2026-01-01', '2026-03-04');
        $this->assertSame('month', $this->invoke($sixtyThree, 'trendGranularity'));
        $this->assertSame(3, $this->invoke($sixtyThree, 'trendBucketCount'));
        $this->assertSame(['Jan 2026', 'Feb 2026', 'Mac 2026'], $this->trend($sixtyThree)['labels']);

        // A single day is still a valid daily range with exactly one bar.
        $oneDay = $this->reportAll('2026-01-01', '2026-01-01');
        $this->assertSame('day', $this->invoke($oneDay, 'trendGranularity'));
        $this->assertSame(1, $this->invoke($oneDay, 'trendBucketCount'));
        $this->assertSame(['01 Jan'], $this->trend($oneDay)['labels']);
    }

    public function test_intervals_without_aduan_are_present_with_the_value_zero()
    {
        $this->makeReport('2026-03-01 09:00:00');
        $this->makeReport('2026-03-03 09:00:00');
        $this->makeReport('2026-03-03 23:30:00');

        $trend = $this->trend($this->reportAll('2026-03-01', '2026-03-05'));

        // Five calendar days, five bars: the two days with no aduan are still
        // on the axis, carrying 0.
        $this->assertSame(['01 Mac', '02 Mac', '03 Mac', '04 Mac', '05 Mac'], $trend['labels']);
        $this->assertSame([1, 0, 2, 0, 0], $trend['values']);
        $this->assertCount(count($trend['labels']), $trend['values']);

        foreach ($trend['values'] as $value) {
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
        }
    }

    public function test_no_interval_outside_the_date_range_appears_on_the_axis()
    {
        // Aduan on either side of the range, including the day before and the
        // day after, which are the ones a data driven axis would leak in.
        $this->makeReport('2025-12-31 23:50:00');
        $this->makeReport('2026-01-11 00:10:00');
        $this->makeReport('2026-02-01 09:00:00');
        $this->makeReport('2026-01-04 09:00:00');

        $component = $this->reportAll('2026-01-02', '2026-01-10');
        $trend = $this->trend($component);

        $this->assertSame(
            ['02 Jan', '03 Jan', '04 Jan', '05 Jan', '06 Jan', '07 Jan', '08 Jan', '09 Jan', '10 Jan'],
            $trend['labels']
        );
        // Both ends are inclusive and nothing beyond them is plotted.
        $this->assertSame('02 Jan', $trend['labels'][0]);
        $this->assertSame('10 Jan', $trend['labels'][count($trend['labels']) - 1]);
        $this->assertSame(1, $trend['total']);
        $this->assertSame($this->grandTotal($component), $trend['total']);

        // Monthly buckets are limited to the months that overlap the range,
        // and the Jan 2026 bucket still respects the day level bounds: it
        // holds 04 and 11 Jan but not 31 Dec 2025.
        $monthly = $this->trend($this->reportAll('2026-01-02', '2026-04-10'));
        $this->assertSame(['Jan 2026', 'Feb 2026', 'Mac 2026', 'Apr 2026'], $monthly['labels']);
        $this->assertSame([2, 1, 0, 0], $monthly['values']);
        $this->assertSame(3, $monthly['total']);
    }

    public function test_an_inverted_date_range_produces_no_bars()
    {
        $this->makeReport('2026-02-10 09:00:00');

        $component = $this->reportAll('2026-03-01', '2026-01-01');

        $this->assertTrue($component->hasInvalidRange());
        $this->assertSame(0, $this->invoke($component, 'trendBucketCount'));
        $this->assertSame(
            ['granularity' => 'day', 'labels' => [], 'values' => [], 'total' => 0],
            $this->trend($component)
        );
    }

    public function test_an_extreme_date_range_in_the_url_is_capped_instead_of_hanging()
    {
        $component = $this->reportAll('1900-01-01', '2100-12-31');

        // 200 years is 2,412 months; the axis loop stops at the 600 bucket
        // guard rather than building the whole thing.
        $this->assertSame('month', $this->invoke($component, 'trendGranularity'));
        $this->assertSame(600, $this->invoke($component, 'trendBucketCount'));
        $this->assertCount(600, $this->trend($component)['labels']);
    }
}
