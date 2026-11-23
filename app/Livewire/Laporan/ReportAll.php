<?php

namespace App\Livewire\Laporan;

use App\Models\Ref\ReportStatus;
use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Models\User;
use App\Support\LaporanCsvExporter;
use App\Support\ReportStatuses;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Combined report ("Laporan Keseluruhan") with all filters on one page:
 *   - date range (submitted_at)
 *   - bahagian (reports.bahagian_pengadu)
 *   - status tindakan (report_status_id)
 *   - pegawai bertanggungjawab (latest assigned trail's assigned_to_id)
 *
 * All filters are reactive (Livewire) and combine. Read-only report screen.
 */
class ReportAll extends Component
{
    use WithPagination;

    /**
     * Bootstrap Icons glyph per status id, one distinct icon for each of the
     * seven COUNTED statuses.
     *
     * Keyed by id, not derived from _report_statuses.class, because the class
     * values are not unique: the seeder gives "badge bg-warning" to both
     * Dalam Tindakan and KIV, and "badge bg-success" to both Selesai and
     * Selesai Tindakan. A class based map could therefore never produce seven
     * different icons. This is presentation data only — no count and no label
     * comes from here, so the figures still have a single source.
     */
    protected const STATUS_ICONS = [
        ReportStatuses::BARU => 'bi-inbox',
        ReportStatuses::DISEMAK => 'bi-clipboard-check',
        ReportStatuses::DALAM_TINDAKAN => 'bi-hourglass-split',
        ReportStatuses::SELESAI => 'bi-check2-circle',
        ReportStatuses::KIV => 'bi-pause-circle',
        ReportStatuses::SELESAI_TINDAKAN => 'bi-clipboard2-check',
        ReportStatuses::TINDAKAN_LANJUT => 'bi-exclamation-triangle',
    ];

    /**
     * Carta_Tren granularity threshold, in days counted inclusively: up to and
     * including this many days the chart groups by day, beyond it by month.
     */
    protected const TREND_DAY_THRESHOLD = 62;

    /** Iteration guards for the Carta_Tren axis loop (see trendBucketCount()). */
    protected const TREND_MAX_DAY_BUCKETS = 400;

    protected const TREND_MAX_MONTH_BUCKETS = 600;

    #[Url]
    public string $dateFrom = '';
    #[Url]
    public string $dateTo = '';
    #[Url]
    public string $bahagian = '';
    #[Url]
    public string $status = '';        // report_status_id
    #[Url]
    public string $pegawai = '';       // users.id (assigned officer)
    #[Url]
    public string $rating = '';        // 1-5 (reports.rating)
    #[Url]
    public string $riskLevel = '';     // _risk_levels.id

    /**
     * Inline message shown after an export attempt that produced no file.
     *
     * Not a #[Url] property: it describes the outcome of one click, not the
     * filter set, so it must not be shareable or survive a page reload.
     */
    public string $exportNotice = '';

    public function mount(): void
    {
        // Org-wide report — management roles only (defence in depth; the route
        // is also gated with can:access-main-dashboard).
        $this->authorize('access-main-dashboard');

        if ($this->dateFrom === '') {
            $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        }
        if ($this->dateTo === '') {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['bahagian', 'status', 'pegawai', 'rating', 'riskLevel']);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function updated($name): void
    {
        // Any filter change returns to page 1.
        if (in_array($name, ['dateFrom', 'dateTo', 'bahagian', 'status', 'pegawai', 'rating', 'riskLevel'])) {
            $this->resetPage();
            // The notice described the previous filter set, so it would be
            // misleading the moment a filter changes.
            $this->exportNotice = '';
        }
    }

    /**
     * Eksport_CSV: download the whole filtered result set, not just the page
     * on screen (Requirement 11 criteria 2 to 4).
     *
     * A component action rather than a route, because Requirement 9 criterion
     * 15 forbids new routes and Livewire 3 forwards a response returned by an
     * action to the browser as a file download. The page, its filters and its
     * page number are therefore untouched — nothing below writes to a filter
     * property and resetPage() is deliberately not called (Requirement 11
     * criterion 15).
     *
     * Returns null when there is nothing to export: no file is sent and the
     * view shows $exportNotice instead.
     */
    public function exportCsv(): ?StreamedResponse
    {
        // First statement, before any data query: the third authorisation
        // layer after the can:access-main-dashboard route gate and mount().
        // A user without the permission is rejected here, so no ticket number
        // or any other aduan data can reach the response (Requirement 11
        // criteria 11 and 12).
        $this->authorize('access-main-dashboard');

        // count() on the filters alone — cheaper than opening a stream to
        // discover it would be empty. An invalid date range yields 0 rows
        // naturally (whereBetween with from > to matches nothing), so the same
        // branch covers it with no extra condition and no exception
        // (Requirement 11 criteria 16 and 17).
        if ((clone $this->baseQuery())->count() === 0) {
            $this->exportNotice = 'Tiada data untuk dieksport.';

            return null;
        }

        $this->exportNotice = '';

        // listQuery() is the same row source the Jadual_Aduan table reads, so
        // a row in the file cannot disagree with a row on screen.
        return LaporanCsvExporter::stream($this->listQuery(), $this->exportFilename());
    }

    /**
     * Export file name, e.g. laporan-keseluruhan-2026-01-01-hingga-2026-01-31.csv
     * (Requirement 11 criterion 9).
     *
     * Built from the sanitised dates, not the raw properties, so a hand edited
     * ?dateFrom=abc cannot put arbitrary text into a Content-Disposition
     * header: normalisedFrom()/normalisedTo() can only ever return Y-m-d.
     */
    protected function exportFilename(): string
    {
        return "laporan-keseluruhan-{$this->normalisedFrom()}-hingga-{$this->normalisedTo()}.csv";
    }

    /**
     * Safe Y-m-d date. Anything that is not a valid Y-m-d value — a hand
     * edited URL such as ?dateFrom=abc, or an empty string — falls back to
     * $fallback instead of throwing. Carbon::parse() on user input would
     * throw InvalidFormatException and surface as a 500.
     */
    protected function safeDate(string $value, Carbon $fallback): Carbon
    {
        return Carbon::hasFormat($value, 'Y-m-d')
            ? Carbon::createFromFormat('Y-m-d', $value)
            : $fallback;
    }

    /** Sanitised start of the reporting period. */
    protected function fromDate(): Carbon
    {
        return $this->safeDate($this->dateFrom, now()->startOfMonth())->startOfDay();
    }

    /** Sanitised end of the reporting period. */
    protected function toDate(): Carbon
    {
        return $this->safeDate($this->dateTo, now())->endOfDay();
    }

    /** Sanitised Y-m-d value, for display and the export filename. */
    protected function normalisedFrom(): string
    {
        return $this->fromDate()->format('Y-m-d');
    }

    /** Sanitised Y-m-d value, for display and the export filename. */
    protected function normalisedTo(): string
    {
        return $this->toDate()->format('Y-m-d');
    }

    /** Julat_Tarikh_Tidak_Sah: "Dari Tarikh" is later than "Hingga Tarikh". */
    public function hasInvalidRange(): bool
    {
        return $this->fromDate()->gt($this->toDate());
    }

    /** Base query with all active filters applied. */
    protected function baseQuery()
    {
        // Sanitised at the point of use: a hand edited ?dateFrom=abc must
        // fall back to the default period instead of throwing a 500.
        $from = $this->fromDate();
        $to = $this->toDate();

        return Report::query()
            // Draf was never submitted, so it is not reported on. Without this
            // the table listed drafts and the JUMLAH card counted them.
            ->whereIn('report_status_id', ReportStatuses::COUNTED)
            ->whereBetween('submitted_at', [$from, $to])
            ->when($this->bahagian !== '', fn ($q) => $q->where('bahagian_pengadu', $this->bahagian))
            ->when($this->status !== '', fn ($q) => $q->where('report_status_id', (int) $this->status))
            ->when($this->rating !== '', fn ($q) => $q->where('rating', (int) $this->rating))
            ->when($this->riskLevel !== '', fn ($q) => $q->where('risk_level_id', (int) $this->riskLevel))
            ->when($this->pegawai !== '', function ($q) {
                // Reports whose latest assigned trail belongs to this officer.
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('report_trails as t')
                        ->whereColumn('t.report_id', 'reports.id')
                        ->whereNotNull('t.assigned_to_id')
                        ->where('t.assigned_to_id', (int) $this->pegawai)
                        ->whereRaw('t.id = (select max(t2.id) from report_trails t2 where t2.report_id = reports.id and t2.assigned_to_id is not null)');
                });
            });
    }

    /**
     * List query: base filters, eager loads and the final ordering. Shared by
     * the aduan table and the CSV export so both produce the same rows in the
     * same order.
     *
     * The orderByDesc('id') tie breaker makes the ordering total: submitted_at
     * is not unique, and without it the export's chunk() (offset/limit) could
     * skip or repeat rows that share a submitted_at value.
     */
    protected function listQuery()
    {
        return $this->baseQuery()
            ->with(['status', 'category', 'riskLevel', 'reportBy'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
    }

    /**
     * Counts per status for every COUNTED status, ascending id.
     *
     * One aggregate query is mapped over the full status list, so a status
     * with no aduan in the current filter set stays present with total 0 and
     * the card grid always has exactly seven status entries. The grand total
     * is the sum of the totals returned here, so the Jumlah card, the donut
     * centre and these cards cannot disagree.
     *
     * @return array<int, array{id:int, desc:string, class:string, tone:string, icon:string, total:int}>
     */
    protected function statusCounts(): array
    {
        $totals = (clone $this->baseQuery())
            ->select('report_status_id', DB::raw('count(*) as total'))
            ->groupBy('report_status_id')
            ->pluck('total', 'report_status_id');

        return ReportStatus::whereIn('id', ReportStatuses::COUNTED)
            ->orderBy('id')
            ->get()
            ->map(fn (ReportStatus $status) => [
                'id' => (int) $status->id,
                'desc' => $status->desc,
                'class' => $status->class,
                'tone' => $this->statusTone($status->class),
                'icon' => $this->statusIcon((int) $status->id),
                'total' => (int) ($totals[$status->id] ?? 0),
            ])
            ->all();
    }

    /**
     * Soft colour tone for a status card: the first bg-* token of the status
     * badge class, e.g. "badge bg-warning" becomes "bg-warning".
     *
     * Keeping this derivation in the component means the card colour and the
     * table badge colour both come from _report_statuses.class, with no
     * per-status colour map anywhere in PHP or Blade.
     */
    protected function statusTone(?string $class): string
    {
        foreach (preg_split('/\s+/', trim((string) $class), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            if (str_starts_with($token, 'bg-')) {
                return $token;
            }
        }

        return 'bg-secondary';
    }

    /** Status icon, falling back to a neutral glyph for any unmapped status. */
    protected function statusIcon(int $statusId): string
    {
        return self::STATUS_ICONS[$statusId] ?? 'bi-circle';
    }

    /**
     * Indeks_Status: the fixed position of a status within
     * ReportStatuses::COUNTED (ascending id), 0..6.
     *
     * This is what Warna_Status is derived from, so a status keeps the same
     * palette colour no matter where its segment sits in the ring or where its
     * row sits in the legend (Requirement 4 criteria 17 to 19). Deriving the
     * colour from a list position instead would recolour a status every time
     * its count moved it up or down the legend.
     *
     * A status outside COUNTED can never reach here (statusCounts() selects on
     * that list), so the fallback is only a guard against a future caller.
     */
    protected function statusColorIndex(int $statusId): int
    {
        $index = array_search($statusId, ReportStatuses::COUNTED, true);

        return $index === false ? 0 : (int) $index;
    }

    /**
     * Carta_Status payload: the ring segments, the legend rows and the centre
     * total.
     *
     * Two lists, because the ring and the legend answer different questions
     * (Requirement 4 criteria 1, 15 and 16):
     *
     *  - 'segments' — only the statuses that actually have aduan, ascending id.
     *    That is the ring: a zero status has no slice to draw.
     *  - 'legend'   — all seven COUNTED statuses including the zeroes, ordered
     *    descending by count with ties broken ascending by id, so the row order
     *    for one filter set is single valued and identical on every render.
     *
     * Both lists carry 'colorIndex' (Indeks_Status) so the browser assigns a
     * colour per status rather than per list position, which is what keeps a
     * legend dot matching its own ring segment under the different ordering.
     *
     * Built from statusCounts() and nothing else — no extra aggregate query —
     * so a donut segment can never disagree with the statistic card for the
     * same status. render() already computes the counts for the cards and
     * passes them in here, so the whole page costs one status query.
     *
     * percent is computed server side, to one decimal place, so the chart
     * script only has to plot the numbers it is handed.
     *
     * @param  array<int, array{id:int, desc:string, class:string, tone:string, icon:string, total:int}>|null  $statusCounts  already computed counts; recomputed only when omitted
     * @return array{
     *     total:int,
     *     segments:array<int, array{id:int, label:string, value:int, percent:float, colorIndex:int}>,
     *     legend:array<int, array{id:int, label:string, value:int, percent:float, colorIndex:int}>
     * }
     */
    protected function statusDonutData(?array $statusCounts = null): array
    {
        $counts = $statusCounts ?? $this->statusCounts();

        $total = 0;
        foreach ($counts as $row) {
            $total += (int) $row['total'];
        }

        // Nothing to plot: the view renders the empty state and never starts a
        // chart, so no segments, no centre value and no legend at all — which
        // is also why the seven row rule only applies from a total of 1
        // (Requirement 4 criterion 6).
        if ($total === 0) {
            return ['total' => 0, 'segments' => [], 'legend' => []];
        }

        $segments = [];
        $legend = [];

        // statusCounts() is already ascending by id, so skipping the zeroes
        // keeps that order for the segments.
        foreach ($counts as $row) {
            $value = (int) $row['total'];

            $entry = [
                'id' => (int) $row['id'],
                'label' => (string) $row['desc'],
                'value' => $value,
                'percent' => round($value / $total * 100, 1),
                'colorIndex' => $this->statusColorIndex((int) $row['id']),
            ];

            // Every status gets a legend row, a zero one included, and it
            // carries value 0 with percent 0.0 (Requirement 4 criterion 16).
            $legend[] = $entry;

            if ($value > 0) {
                $segments[] = $entry;
            }
        }

        // Descending count, ties ascending by id. Comparing arrays applies the
        // second key only when the first is equal, which is exactly the tie
        // break Requirement 4 criterion 15 asks for.
        usort($legend, fn (array $a, array $b) => [$b['value'], $a['id']] <=> [$a['value'], $b['id']]);

        return ['total' => $total, 'segments' => $segments, 'legend' => $legend];
    }

    /**
     * Driver-portable date bucket expression (MySQL in production vs SQLite in
     * the test suite, see phpunit.xml).
     *
     * DATE_FORMAT() does not exist in SQLite and strftime() does not exist in
     * MySQL, so a single fixed expression would break either production or the
     * suite. Same pattern as Dashboard::yearMonthExpr(), with both
     * granularities folded into one method.
     *
     * Both drivers return the bucket as a string ('2026-01-03', '2026-01'), so
     * the PHP axis loop can use format('Y-m-d') / format('Y-m') as the lookup
     * key with no type juggling.
     */
    protected function dateBucketExpr(string $column, string $granularity): string
    {
        $driver = DB::connection()->getDriverName();
        $format = $granularity === 'month' ? '%Y-%m' : '%Y-%m-%d';

        return $driver === 'sqlite'
            ? "strftime('{$format}', {$column})"
            : "DATE_FORMAT({$column}, '{$format}')";
    }

    /**
     * Carta_Tren grouping: one bar per day for a range of 62 days or less
     * (counted inclusively), one bar per calendar month beyond that.
     *
     * 62 days is two months of daily bars, which still fits the axis; past
     * that the bars become unreadable and monthly buckets are the useful view.
     */
    protected function trendGranularity(): string
    {
        if ($this->hasInvalidRange()) {
            return 'day';
        }

        $days = $this->fromDate()->copy()->startOfDay()
            ->diffInDays($this->toDate()->copy()->startOfDay()) + 1;

        return $days <= self::TREND_DAY_THRESHOLD ? 'day' : 'month';
    }

    /**
     * Number of bars the Carta_Tren axis will carry, derived from the date
     * range alone (no query), capped by the iteration guards.
     *
     * The cap is what keeps a hand edited URL such as
     * ?dateFrom=1900-01-01&dateTo=2100-12-31 from building a 73,000 entry axis
     * and hanging the request. An invalid range carries no bars at all.
     */
    protected function trendBucketCount(): int
    {
        if ($this->hasInvalidRange()) {
            return 0;
        }

        if ($this->trendGranularity() === 'month') {
            $months = $this->fromDate()->copy()->startOfMonth()
                ->diffInMonths($this->toDate()->copy()->startOfMonth()) + 1;

            return (int) min($months, self::TREND_MAX_MONTH_BUCKETS);
        }

        $days = $this->fromDate()->copy()->startOfDay()
            ->diffInDays($this->toDate()->copy()->startOfDay()) + 1;

        return (int) min($days, self::TREND_MAX_DAY_BUCKETS);
    }

    /**
     * Carta_Tren data: one bar per interval across the selected date range,
     * ascending in time.
     *
     * The counting is a single aggregate query grouped by bucket, so a long
     * range does not pull thousands of models into memory. The axis is then
     * built in PHP with a Carbon cursor walking fromDate() to toDate(), which
     * is what guarantees both halves of the requirement: an interval with no
     * aduan is present with value 0 (it comes from the range), and no interval
     * outside the range can appear (the data never contributes a label).
     *
     * @return array{granularity:string, labels:array<int, string>, values:array<int, int>, total:int}
     */
    protected function trendData(): array
    {
        $granularity = $this->trendGranularity();
        $limit = $this->trendBucketCount();

        // Julat_Tarikh_Tidak_Sah: no axis, so the view renders the empty state
        // and the chart script never starts.
        if ($limit === 0) {
            return ['granularity' => $granularity, 'labels' => [], 'values' => [], 'total' => 0];
        }

        $expr = $this->dateBucketExpr('submitted_at', $granularity);

        $rows = (clone $this->baseQuery())
            ->select(DB::raw("{$expr} as bucket"), DB::raw('count(*) as total'))
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $monthly = $granularity === 'month';
        $keyFormat = $monthly ? 'Y-m' : 'Y-m-d';
        // Malay labels through the same locale the convertDateFormat() helper
        // uses: "03 Mac" daily, "Mac 2026" monthly.
        $labelFormat = $monthly ? 'MMM YYYY' : 'DD MMM';

        $cursor = $monthly
            ? $this->fromDate()->copy()->startOfMonth()
            : $this->fromDate()->copy()->startOfDay();
        $end = $monthly
            ? $this->toDate()->copy()->startOfMonth()
            : $this->toDate()->copy()->startOfDay();

        $labels = [];
        $values = [];
        $total = 0;
        $guard = 0;

        while ($cursor->lte($end) && $guard < $limit) {
            $value = (int) ($rows[$cursor->format($keyFormat)] ?? 0);

            $labels[] = $cursor->locale('ms')->isoFormat($labelFormat);
            $values[] = $value;
            $total += $value;

            $monthly ? $cursor->addMonth() : $cursor->addDay();
            $guard++;
        }

        return [
            'granularity' => $granularity,
            'labels' => $labels,
            'values' => $values,
            'total' => $total,
        ];
    }

    /**
     * The single point that hands data to the view.
     *
     * Every figure on the page comes from the one statusCounts() call below:
     * the statistic cards read it directly, grandTotal is the sum of its
     * totals, and it is passed into statusDonutData() rather than letting the
     * donut run its own aggregate. So the cards, the Jumlah card and the donut
     * centre are structurally the same numbers and cannot drift apart, and the
     * page costs one status aggregate query instead of two.
     */
    public function render()
    {
        $statusCounts = $this->statusCounts();
        $grandTotal = (int) array_sum(array_column($statusCounts, 'total'));

        // Signal only, no payload: Requirement 5 criterion 10 makes the JSON
        // data holder in the view the single carrier of chart data. A payload
        // here would be a second source that could disagree with it. This just
        // tells the chart script "the holder is fresh, read it again".
        $this->dispatch('laporan-charts-updated');

        $statuses = ReportStatus::whereIn('id', ReportStatuses::COUNTED)->orderBy('id')->get();

        return view('livewire.laporan.report-all', [
            'reports' => $this->listQuery()->paginate(20),
            'statusCounts' => $statusCounts,
            // Same array under the older name. The current card markup and
            // tests/Feature/ReportTotalsTest.php read 'summary', and its rows
            // are a superset of the desc/class/total keys they use; task 9.1
            // moves the markup to 'statusCounts'.
            'summary' => $statusCounts,
            'grandTotal' => $grandTotal,
            'donut' => $this->statusDonutData($statusCounts),
            'trend' => $this->trendData(),
            'invalidRange' => $this->hasInvalidRange(),
            'periodFrom' => $this->normalisedFrom(),
            'periodTo' => $this->normalisedTo(),
            'bahagianOptions' => DB::table('reports')
                ->whereNotNull('bahagian_pengadu')->where('bahagian_pengadu', '<>', '')
                ->distinct()->orderBy('bahagian_pengadu')->pluck('bahagian_pengadu'),
            'statusOptions' => $statuses,
            'pegawaiOptions' => User::permission('aduan.updateTask')->orderBy('name')->get(['id', 'name']),
            'riskLevelOptions' => RiskLevel::orderBy('level')->get(),
        ]);
    }
}
