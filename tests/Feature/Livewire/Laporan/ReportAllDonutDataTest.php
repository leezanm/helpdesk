<?php

namespace Tests\Feature\Livewire\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\ReportStatuses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\FeatureTestCase;

/**
 * statusDonutData() is the Carta_Status payload. It is built from
 * statusCounts() alone, and it carries two different lists:
 *
 *  - 'segments' — the ring. Exactly the statuses with aduan, ascending id,
 *    values adding up to the centre total, percentages computed server side to
 *    one decimal place.
 *  - 'legend'   — the legend rows. All seven COUNTED statuses including the
 *    zero ones, ordered descending by count with ties broken ascending by id.
 *
 * Both lists carry colorIndex, the status's fixed position in
 * ReportStatuses::COUNTED, so Warna_Status follows the status and not the list
 * position. A total of 0 yields neither list: the empty state renders on its
 * own.
 *
 * The builders are protected (render() stays the only point that hands data to
 * the view), so they are exercised through reflection.
 */
class ReportAllDonutDataTest extends FeatureTestCase
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

    private function reportAll(): ReportAll
    {
        $this->actingAs($this->admin());

        $component = new ReportAll();
        $component->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $component->dateTo = now()->format('Y-m-d');

        return $component;
    }

    /** @return array<int, array<string, mixed>> */
    private function statusCounts(ReportAll $component): array
    {
        $method = new ReflectionMethod(ReportAll::class, 'statusCounts');
        $method->setAccessible(true);

        return $method->invoke($component);
    }

    /** @return array{total:int, segments:array<int, array<string, mixed>>} */
    private function donut(ReportAll $component, ?array $statusCounts = null): array
    {
        $method = new ReflectionMethod(ReportAll::class, 'statusDonutData');
        $method->setAccessible(true);

        return $method->invoke($component, $statusCounts);
    }

    public function test_segments_are_exactly_the_statuses_with_aduan_in_ascending_id_order()
    {
        $this->makeReport(ReportStatuses::SELESAI);
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::KIV);
        // Draf was never submitted, so it is neither counted nor plotted.
        $this->makeReport(ReportStatuses::DRAF);

        $component = $this->reportAll();
        $counts = $this->statusCounts($component);
        $donut = $this->donut($component, $counts);

        // The expected segment list, derived from the cards: every non zero
        // status, in the order statusCounts() returns them (ascending id).
        $expected = [];
        foreach ($counts as $row) {
            if ($row['total'] > 0) {
                $expected[] = ['label' => $row['desc'], 'value' => $row['total']];
            }
        }

        $this->assertSame(
            $expected,
            array_map(fn ($s) => ['label' => $s['label'], 'value' => $s['value']], $donut['segments'])
        );

        // Zero statuses stay on the cards and in the legend, but never become
        // a ring segment (Requirement 4 criterion 1).
        $this->assertNotContains(0, array_column($donut['segments'], 'value'));
        $this->assertSame(
            ['Baru', 'Selesai', 'KIV'],
            array_column($donut['segments'], 'label')
        );

        // Ascending id, stated directly rather than only through $expected.
        $this->assertSame(
            [ReportStatuses::BARU, ReportStatuses::SELESAI, ReportStatuses::KIV],
            array_column($donut['segments'], 'id')
        );
    }

    /**
     * The legend is the other list: seven rows whatever the counts are, so a
     * status with no aduan is still readable as "0 (0.0%)".
     *
     * Feature: laporan-keseluruhan-ui-refresh, Property 28: Legenda_Carta_Status
     * lengkap, disusun menurun, dan warnanya stabil
     *
     * **Validates: Requirements 4.16, 4.20**
     */
    public function test_the_legend_lists_all_seven_counted_statuses_including_the_zero_ones()
    {
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::SELESAI);
        $this->makeReport(ReportStatuses::DRAF);

        $component = $this->reportAll();
        $donut = $this->donut($component, $this->statusCounts($component));

        $this->assertCount(7, $donut['legend']);

        // One row per COUNTED status, no duplicates, no Draf.
        $this->assertSame(
            ReportStatuses::COUNTED,
            collect($donut['legend'])->pluck('id')->sort()->values()->all()
        );
        $this->assertNotContains(ReportStatuses::DRAF, array_column($donut['legend'], 'id'));

        // The five statuses with no aduan are present with 0 and 0.0, which is
        // what renders as "0 (0.0%)".
        $zeroes = array_values(array_filter($donut['legend'], fn (array $row) => $row['value'] === 0));
        $this->assertCount(5, $zeroes);
        foreach ($zeroes as $row) {
            $this->assertSame(0, $row['value']);
            $this->assertSame(0.0, $row['percent']);
        }

        // And the legend still agrees with the cards, status for status.
        $this->assertSame(
            collect($this->statusCounts($component))->pluck('total', 'id')->sortKeys()->all(),
            collect($donut['legend'])->pluck('value', 'id')->sortKeys()->all()
        );
    }

    /**
     * Row order: highest count first, and two statuses on the same count are
     * ordered by ascending id, so the order for one filter set is single
     * valued and identical on every render.
     *
     * Feature: laporan-keseluruhan-ui-refresh, Property 28: Legenda_Carta_Status
     * lengkap, disusun menurun, dan warnanya stabil
     *
     * **Validates: Requirements 4.15**
     */
    public function test_legend_rows_are_ordered_by_count_descending_with_ties_broken_by_ascending_id()
    {
        // Counts chosen so that the descending order differs from the id
        // order, and so that two pairs tie: DISEMAK (3) and TINDAKAN_LANJUT
        // (8) both on 2, SELESAI (5) and SELESAI_TINDAKAN (7) both on 1.
        $this->makeReport(ReportStatuses::KIV);
        $this->makeReport(ReportStatuses::KIV);
        $this->makeReport(ReportStatuses::KIV);
        $this->makeReport(ReportStatuses::DISEMAK);
        $this->makeReport(ReportStatuses::DISEMAK);
        $this->makeReport(ReportStatuses::TINDAKAN_LANJUT);
        $this->makeReport(ReportStatuses::TINDAKAN_LANJUT);
        $this->makeReport(ReportStatuses::SELESAI);
        $this->makeReport(ReportStatuses::SELESAI_TINDAKAN);

        $component = $this->reportAll();
        $donut = $this->donut($component, $this->statusCounts($component));

        $this->assertSame(
            [
                ReportStatuses::KIV,              // 3
                ReportStatuses::DISEMAK,          // 2, id 3 before id 8
                ReportStatuses::TINDAKAN_LANJUT,  // 2
                ReportStatuses::SELESAI,          // 1, id 5 before id 7
                ReportStatuses::SELESAI_TINDAKAN, // 1
                ReportStatuses::BARU,             // 0, id 2 before id 4
                ReportStatuses::DALAM_TINDAKAN,   // 0
            ],
            array_column($donut['legend'], 'id')
        );

        $this->assertSame([3, 2, 2, 1, 1, 0, 0], array_column($donut['legend'], 'value'));

        // Identical on a second build, so the order is not incidental to the
        // sort being unstable on ties.
        $this->assertSame($donut, $this->donut($component, $this->statusCounts($component)));
    }

    /**
     * Warna_Status comes from Indeks_Status — the status's position in
     * ReportStatuses::COUNTED — so it cannot move when the counts reorder the
     * legend or drop a segment from the ring.
     *
     * Feature: laporan-keseluruhan-ui-refresh, Property 28: Legenda_Carta_Status
     * lengkap, disusun menurun, dan warnanya stabil
     *
     * **Validates: Requirements 4.17, 4.18, 4.19**
     */
    public function test_the_colour_index_is_the_counted_position_and_survives_a_filter_change()
    {
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::KIV);
        $this->makeReport(ReportStatuses::KIV);

        $component = $this->reportAll();

        // Filter set A: everything in the period.
        $all = $this->donut($component, $this->statusCounts($component));

        // The index is the position in COUNTED, for every row, regardless of
        // where that row sits in the legend.
        foreach ($all['legend'] as $row) {
            $this->assertSame(
                array_search($row['id'], ReportStatuses::COUNTED, true),
                $row['colorIndex'],
                "Indeks warna status {$row['id']} mesti kedudukannya dalam COUNTED."
            );
        }

        // Legend order here is KIV then Baru, i.e. the reverse of the ring's
        // ascending id order — which is exactly the case a position based
        // colour map would get wrong.
        $this->assertSame(
            [ReportStatuses::KIV, ReportStatuses::BARU],
            array_slice(array_column($all['legend'], 'id'), 0, 2)
        );
        $this->assertSame(
            [ReportStatuses::BARU, ReportStatuses::KIV],
            array_column($all['segments'], 'id')
        );

        // A segment carries the same colour as its own legend row.
        $legendColours = collect($all['legend'])->pluck('colorIndex', 'id')->all();
        foreach ($all['segments'] as $segment) {
            $this->assertSame($legendColours[$segment['id']], $segment['colorIndex']);
        }

        // Filter set B: only Baru. KIV's count collapses to 0 and its segment
        // disappears, so both lists are reshaped.
        $component->status = (string) ReportStatuses::BARU;
        $onlyBaru = $this->donut($component, $this->statusCounts($component));

        $this->assertSame([ReportStatuses::BARU], array_column($onlyBaru['segments'], 'id'));
        $this->assertSame(1, $onlyBaru['total']);

        // Same colour per status across both filter sets.
        $this->assertSame(
            collect($all['legend'])->pluck('colorIndex', 'id')->sortKeys()->all(),
            collect($onlyBaru['legend'])->pluck('colorIndex', 'id')->sortKeys()->all()
        );
        $this->assertSame(
            $legendColours[ReportStatuses::BARU],
            collect($onlyBaru['segments'])->pluck('colorIndex', 'id')->all()[ReportStatuses::BARU]
        );
    }

    public function test_segment_values_sum_to_the_centre_total()
    {
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::DALAM_TINDAKAN);
        $this->makeReport(ReportStatuses::DALAM_TINDAKAN);
        $this->makeReport(ReportStatuses::DRAF);

        $component = $this->reportAll();
        $counts = $this->statusCounts($component);
        $donut = $this->donut($component, $counts);

        $this->assertSame(3, $donut['total']);
        $this->assertSame($donut['total'], array_sum(array_column($donut['segments'], 'value')));

        // The centre total is the same figure as the Jumlah card and the
        // filtered aduan count in the database.
        $this->assertSame(array_sum(array_column($counts, 'total')), $donut['total']);
        $this->assertSame(
            Report::whereIn('report_status_id', ReportStatuses::COUNTED)->count(),
            $donut['total']
        );
    }

    public function test_percentages_are_computed_server_side_to_one_decimal_place()
    {
        // 1/3 and 2/3 are the case that exposes rounding: 33.3 and 66.7.
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::SELESAI);
        $this->makeReport(ReportStatuses::SELESAI);

        $component = $this->reportAll();
        $donut = $this->donut($component, $this->statusCounts($component));

        $percents = array_column($donut['segments'], 'percent', 'label');
        $this->assertSame(33.3, $percents['Baru']);
        $this->assertSame(66.7, $percents['Selesai']);

        foreach ($donut['segments'] as $segment) {
            $this->assertIsFloat($segment['percent']);
            $this->assertSame(
                round($segment['value'] / $donut['total'] * 100, 1),
                $segment['percent']
            );
            // One decimal place, so the chart script never has to re-round.
            $this->assertSame($segment['percent'], round($segment['percent'], 1));
        }
    }

    public function test_an_empty_period_yields_a_zero_total_and_no_segments()
    {
        $component = $this->reportAll();

        // No segments AND no legend: the empty state renders the message on
        // its own, so the seven row rule only starts at a total of 1
        // (Requirement 4 criterion 6 against criterion 16).
        $this->assertSame(
            ['total' => 0, 'segments' => [], 'legend' => []],
            $this->donut($component, $this->statusCounts($component))
        );
    }

    public function test_an_invalid_date_range_yields_a_zero_total_and_no_segments()
    {
        $this->makeReport(ReportStatuses::BARU);

        $component = $this->reportAll();
        $component->dateFrom = now()->format('Y-m-d');
        $component->dateTo = now()->subMonth()->format('Y-m-d');

        $donut = $this->donut($component, $this->statusCounts($component));

        $this->assertSame(0, $donut['total']);
        $this->assertSame([], $donut['segments']);
        $this->assertSame([], $donut['legend']);
    }

    public function test_it_reuses_the_given_counts_instead_of_issuing_another_query()
    {
        $this->makeReport(ReportStatuses::BARU);
        $this->makeReport(ReportStatuses::SELESAI);

        $component = $this->reportAll();
        $counts = $this->statusCounts($component);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $fromCards = $this->donut($component, $counts);

        // No second aggregate: the donut cannot drift from the cards.
        $this->assertSame(0, $queries);
        // And recomputing the counts gives the identical payload.
        $this->assertSame($fromCards, $this->donut($component));
    }
}
