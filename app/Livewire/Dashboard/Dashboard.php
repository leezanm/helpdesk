<?php

namespace App\Livewire\Dashboard;

use App\Models\Report;
use App\Models\Ref\ReportStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Role-aware dashboard with a date range filter.
 *
 * Behaviour-preserving of the original role-scoped data, plus:
 *  - dateFrom/dateTo filter applied to all stats and breakdowns
 *  - category breakdown (rendered as a chart in the view)
 *  - per-officer log counts (officers with at least one trail log)
 */
class Dashboard extends Component
{
    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $bahagian = '';

    public function mount(): void
    {
        // Default range: this month to today (only if not provided via URL).
        if ($this->dateFrom === '') {
            $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        }
        if ($this->dateTo === '') {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function resetFilter(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->bahagian = '';
    }

    /** Distinct bahagian present on reports, for the filter dropdown. */
    protected function bahagianOptions()
    {
        return Report::query()
            ->whereNotNull('bahagian_pengadu')
            ->where('bahagian_pengadu', '<>', '')
            ->distinct()
            ->orderBy('bahagian_pengadu')
            ->pluck('bahagian_pengadu');
    }

    /** Driver-portable "YYYY-MM" expression (MySQL vs SQLite for tests). */
    protected function yearMonthExpr(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /** Driver-portable day difference (completed - submitted) in days. */
    protected function dayDiffExpr(string $end, string $start): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "julianday({$end}) - julianday({$start})"
            : "DATEDIFF({$end}, {$start})";
    }

    protected function fromDate(): Carbon
    {
        return Carbon::parse($this->dateFrom)->startOfDay();
    }

    protected function toDate(): Carbon
    {
        return Carbon::parse($this->dateTo)->endOfDay();
    }

    /** Base report count query, role-scoped + date-filtered on submitted_at. */
    protected function reportCountQuery()
    {
        return DB::table('reports')
            ->whereBetween('submitted_at', [$this->fromDate(), $this->toDate()])
            ->when($this->bahagian !== '', fn ($q) => $q->where('bahagian_pengadu', $this->bahagian))
            ->when(
                // Admins / helpdesk see all reports; everyone else only their own.
                // Use the permission (aduan.adminTask) the rest of the app uses,
                // not a single role name, so superadmin is covered too.
                ! Auth::user()->can('aduan.adminTask'),
                fn ($q) => $q->where('report_by_id', Auth::id())
            );
    }

    /** Role-scoped list of reports needing attention (not date-filtered). */
    protected function reportsForRole()
    {
        $user = Auth::user();
        $query = Report::query();

        if ($user->can('aduan.adminTask')) {
            $query->whereIn('report_status_id', [2, 3]);
        } elseif ($user->can('aduan.updateTask')) {
            $query->whereIn('report_status_id', [4, 6])
                ->join('report_trails', 'reports.id', '=', 'report_trails.report_id')
                ->select('reports.*', 'report_trails.id as trail_id', 'assigned_to_id')
                ->where('report_trails.id', DB::raw('(select id from report_trails where report_id = reports.id order by id desc limit 1)'))
                ->where('assigned_to_id', $user->id);
        } else {
            $query->where(fn ($q) => $q->where('report_by_id', $user->id)->orWhere('bp_user_id', $user->id))
                ->whereNull('completed_at');
        }

        return $query->with(['status', 'category'])->orderBy('submitted_at', 'desc');
    }

    /** Status breakdown for the donut: [{label, value}]. */
    protected function statusBreakdown(): array
    {
        $counts = (clone $this->reportCountQuery())
            ->whereIn('report_status_id', [2, 3, 4, 5, 6])
            ->select('report_status_id', DB::raw('count(*) as total'))
            ->groupBy('report_status_id')
            ->pluck('total', 'report_status_id');

        return ReportStatus::whereIn('id', [2, 3, 4, 5, 6])->get()
            ->map(fn ($s) => ['label' => $s->desc, 'value' => (int) ($counts[$s->id] ?? 0)])
            ->values()->all();
    }

    /** Category breakdown for the bar chart: [{label, value}], non-zero only. */
    protected function categoryBreakdown(): array
    {
        $rows = (clone $this->reportCountQuery())
            ->join('report_categories', 'reports.report_category_id', '=', 'report_categories.id')
            ->whereIn('report_status_id', [2, 3, 4, 5, 6])
            ->select('report_categories.desc as label', DB::raw('count(*) as total'))
            ->groupBy('report_categories.desc')
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->get();

        return $rows->map(fn ($r) => ['label' => $r->label, 'value' => (int) $r->total])->all();
    }

    /**
     * Per-officer workload within the date range — officers who have been
     * assigned at least one report for action. For each officer:
     *   - diterima      : distinct reports assigned to them (trail assigned_to_id)
     *   - selesai       : those reports now Selesai (status 5)
     *   - dalamTindakan : those reports now In Action / KIV (status 4, 6)
     *
     * The date range filters when the report was assigned (trail created_at).
     */
    protected function officerWorkload()
    {
        return DB::table('report_trails as t')
            ->join('users', 'users.id', '=', 't.assigned_to_id')
            ->join('reports as r', 'r.id', '=', 't.report_id')
            ->whereNotNull('t.assigned_to_id')
            ->whereBetween('t.created_at', [$this->fromDate(), $this->toDate()])
            ->when($this->bahagian !== '', fn ($q) => $q->where('r.bahagian_pengadu', $this->bahagian))
            ->select(
                'users.name',
                'users.ldap_bahagian',
                DB::raw('count(distinct t.report_id) as diterima'),
                DB::raw('count(distinct case when r.report_status_id = 5 then r.id end) as selesai'),
                DB::raw('count(distinct case when r.report_status_id in (4,6) then r.id end) as dalam_tindakan')
            )
            ->groupBy('users.id', 'users.name', 'users.ldap_bahagian')
            ->having('diterima', '>', 0)
            ->orderByDesc('diterima')
            ->get();
    }

    /**
     * Monthly trend within the date range: reports submitted vs resolved per
     * month. Gives management the direction of the workload over time.
     * Returns ['labels'=>[...], 'submitted'=>[...], 'resolved'=>[...]].
     */
    protected function monthlyTrend(): array
    {
        $submittedExpr = $this->yearMonthExpr('submitted_at');
        $completedExpr = $this->yearMonthExpr('completed_at');

        $submitted = (clone $this->reportCountQuery())
            ->whereIn('report_status_id', [2, 3, 4, 5, 6])
            ->select(DB::raw("{$submittedExpr} as ym"), DB::raw('count(*) as total'))
            ->groupBy('ym')->pluck('total', 'ym');

        // Resolved counted by completion month (still respects role + bahagian).
        $resolved = DB::table('reports')
            ->whereBetween('completed_at', [$this->fromDate(), $this->toDate()])
            ->where('report_status_id', 5)
            ->when($this->bahagian !== '', fn ($q) => $q->where('bahagian_pengadu', $this->bahagian))
            ->when(! Auth::user()->can('aduan.adminTask'), fn ($q) => $q->where('report_by_id', Auth::id()))
            ->select(DB::raw("{$completedExpr} as ym"), DB::raw('count(*) as total'))
            ->groupBy('ym')->pluck('total', 'ym');

        // Build a continuous month axis across the selected range.
        $labels = [];
        $subSeries = [];
        $resSeries = [];
        $cursor = $this->fromDate()->copy()->startOfMonth();
        $end = $this->toDate()->copy()->startOfMonth();
        $guard = 0;
        while ($cursor->lte($end) && $guard < 120) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->isoFormat('MMM YY');
            $subSeries[] = (int) ($submitted[$key] ?? 0);
            $resSeries[] = (int) ($resolved[$key] ?? 0);
            $cursor->addMonth();
            $guard++;
        }

        return ['labels' => $labels, 'submitted' => $subSeries, 'resolved' => $resSeries];
    }

    /** Average resolution time in days for reports resolved in range. */
    protected function avgResolutionDays(): float
    {
        $avg = DB::table('reports')
            ->whereBetween('completed_at', [$this->fromDate(), $this->toDate()])
            ->where('report_status_id', 5)
            ->whereNotNull('completed_at')
            ->when($this->bahagian !== '', fn ($q) => $q->where('bahagian_pengadu', $this->bahagian))
            ->when(! Auth::user()->can('aduan.adminTask'), fn ($q) => $q->where('report_by_id', Auth::id()))
            ->avg(DB::raw($this->dayDiffExpr('completed_at', 'submitted_at')));

        return round((float) ($avg ?? 0), 1);
    }

    /**
     * Satisfaction: rating distribution (1..5) for resolved reports in range,
     * plus the average rating.
     * Returns ['counts'=>[1..5 => n], 'average'=>float, 'total'=>int].
     */
    protected function ratingBreakdown(): array
    {
        $rows = (clone $this->reportCountQuery())
            ->whereNotNull('rating')
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $counts = [];
        $sum = 0;
        $n = 0;
        for ($i = 1; $i <= 5; $i++) {
            $c = (int) ($rows[$i] ?? 0);
            $counts[$i] = $c;
            $sum += $i * $c;
            $n += $c;
        }

        return [
            'counts' => $counts,
            'average' => $n > 0 ? round($sum / $n, 1) : 0.0,
            'total' => $n,
        ];
    }

    /** Top bahagian by report count in range (for the management bar). */
    protected function bahagianBreakdown(): array
    {
        $rows = (clone $this->reportCountQuery())
            ->whereIn('report_status_id', [2, 3, 4, 5, 6])
            ->whereNotNull('bahagian_pengadu')
            ->where('bahagian_pengadu', '<>', '')
            ->select('bahagian_pengadu as label', DB::raw('count(*) as total'))
            ->groupBy('bahagian_pengadu')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return $rows->map(fn ($r) => ['label' => $r->label, 'value' => (int) $r->total])->all();
    }

    public function render()
    {
        $user = Auth::user();

        $listTitle = match (true) {
            $user->can('aduan.adminTask') => 'Aduan Yang Belum Diproses',
            $user->can('aduan.updateTask') => 'Tugasan Saya',
            default => 'Aduan Saya',
        };

        $statusBreakdown = $this->statusBreakdown();
        $categoryBreakdown = $this->categoryBreakdown();
        $officerWorkload = $this->officerWorkload();

        // Shape officer workload for a stacked bar chart. Three mutually
        // exclusive series that add up to Diterima:
        //   belumProses (assigned but not yet in-action/resolved),
        //   dalamTindakan (status 4,6), selesai (status 5).
        $names = $officerWorkload->pluck('name')->all();
        $diterima = $officerWorkload->pluck('diterima')->map(fn ($v) => (int) $v)->all();
        $dalamTindakan = $officerWorkload->pluck('dalam_tindakan')->map(fn ($v) => (int) $v)->all();
        $selesai = $officerWorkload->pluck('selesai')->map(fn ($v) => (int) $v)->all();
        $belumProses = array_map(
            fn ($d, $t, $s) => max(0, $d - $t - $s),
            $diterima, $dalamTindakan, $selesai
        );

        $officerWorkloadChart = [
            'names' => $names,
            'diterima' => $diterima,
            'dalamTindakan' => $dalamTindakan,
            'selesai' => $selesai,
            'belumProses' => $belumProses,
        ];

        $monthlyTrend = $this->monthlyTrend();
        $ratingBreakdown = $this->ratingBreakdown();
        $bahagianBreakdown = $this->bahagianBreakdown();

        // Push fresh chart data to the browser so charts update when any
        // filter changes (the chart script re-draws on this event).
        $this->dispatch('dashboard-charts',
            status: $statusBreakdown,
            category: $categoryBreakdown,
            officers: $officerWorkloadChart,
            trend: $monthlyTrend,
            rating: $ratingBreakdown,
            bahagian: $bahagianBreakdown,
        );

        $total = (clone $this->reportCountQuery())->whereIn('report_status_id', [2, 3, 4, 5, 6])->count();
        $resolved = (clone $this->reportCountQuery())->where('report_status_id', 5)->count();
        $inProgress = (clone $this->reportCountQuery())->whereIn('report_status_id', [6, 4, 2, 3])->count();
        $kiv = (clone $this->reportCountQuery())->where('report_status_id', 6)->count();
        $resolutionRate = $total > 0 ? round($resolved / $total * 100) : 0;

        return view('livewire.dashboard.dashboard', [
            'total' => $total,
            'resolved' => $resolved,
            'inProgress' => $inProgress,
            'kiv' => $kiv,
            'resolutionRate' => $resolutionRate,
            'avgResolutionDays' => $this->avgResolutionDays(),
            'statusBreakdown' => $statusBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
            'officerWorkload' => $officerWorkload,
            'officerWorkloadChart' => $officerWorkloadChart,
            'monthlyTrend' => $monthlyTrend,
            'ratingBreakdown' => $ratingBreakdown,
            'bahagianBreakdown' => $bahagianBreakdown,
            'bahagianOptions' => $this->bahagianOptions(),
            'reports' => $this->reportsForRole()->limit(10)->get(),
            'listTitle' => $listTitle,
            'canGraph' => $user->can('dashboard.viewGraph'),
            'canAdmin' => $user->can('aduan.adminTask'),
            'canTechnical' => $user->can('aduan.updateTask'),
        ]);
    }
}
