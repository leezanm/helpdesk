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
            ->when(
                ! Auth::user()->hasRole('helpdesk.admin'),
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
     * Per-officer log counts within the date range — officers WITH logs only.
     * "Log" = a report_trail authored by the user (updated_by_id).
     */
    protected function officerLogs()
    {
        return DB::table('report_trails')
            ->join('users', 'report_trails.updated_by_id', '=', 'users.id')
            ->whereBetween('report_trails.created_at', [$this->fromDate(), $this->toDate()])
            ->select('users.name', 'users.ldap_bahagian', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.name', 'users.ldap_bahagian')
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->get();
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

        // Push fresh chart data to the browser so charts update when the date
        // filter changes (inline <script> does not re-run on Livewire updates).
        $this->dispatch('dashboard-charts', status: $statusBreakdown, category: $categoryBreakdown);

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
            'statusBreakdown' => $statusBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
            'officerLogs' => $this->officerLogs(),
            'reports' => $this->reportsForRole()->limit(10)->get(),
            'listTitle' => $listTitle,
            'canGraph' => $user->can('dashboard.viewGraph'),
            'canAdmin' => $user->can('aduan.adminTask'),
            'canTechnical' => $user->can('aduan.updateTask'),
        ]);
    }
}
