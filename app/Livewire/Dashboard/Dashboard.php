<?php

namespace App\Livewire\Dashboard;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Role-aware dashboard. Behaviour-preserving port of DashboardController@index:
 * the same counts and role-scoped report list, presented with the refreshed UI.
 *
 * Charts (ECharts) are still fed by the existing api/dashboard/* endpoints and
 * live in the Blade view; only their styling/containers are refreshed.
 */
class Dashboard extends Component
{
    protected function reportCountQuery()
    {
        return DB::table('reports')->when(
            ! Auth::user()->hasRole('helpdesk.admin'),
            fn ($q) => $q->where('report_by_id', Auth::id())
        );
    }

    /** Role-scoped list of reports needing attention (same logic as the controller). */
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

    /**
     * Status breakdown for the reporting donut: [{label, value, class}].
     * Uses the ref status descriptions/classes so it stays meaningful & themed.
     */
    protected function statusBreakdown(): array
    {
        $counts = (clone $this->reportCountQuery())
            ->whereIn('report_status_id', [2, 3, 4, 5, 6])
            ->select('report_status_id', DB::raw('count(*) as total'))
            ->groupBy('report_status_id')
            ->pluck('total', 'report_status_id');

        $statuses = \App\Models\Ref\ReportStatus::whereIn('id', [2, 3, 4, 5, 6])->get();

        return $statuses->map(fn ($s) => [
            'label' => $s->desc,
            'value' => (int) ($counts[$s->id] ?? 0),
        ])->values()->all();
    }

    public function render()
    {
        $user = Auth::user();

        $listTitle = match (true) {
            $user->can('aduan.adminTask') => 'Aduan Yang Belum Diproses',
            $user->can('aduan.updateTask') => 'Tugasan Saya',
            default => 'Aduan Saya',
        };

        $total = (clone $this->reportCountQuery())->whereIn('report_status_id', [2, 3, 4, 5, 6])->count();
        $resolved = (clone $this->reportCountQuery())->where('report_status_id', 5)->count();
        $inProgress = (clone $this->reportCountQuery())->whereIn('report_status_id', [6, 4, 2, 3])->count();
        $kiv = (clone $this->reportCountQuery())->where('report_status_id', 6)->count();
        $thisWeek = (clone $this->reportCountQuery())
            ->where('submitted_at', '>=', now()->startOfWeek())->count();
        $resolutionRate = $total > 0 ? round($resolved / $total * 100) : 0;

        return view('livewire.dashboard.dashboard', [
            'total' => $total,
            'resolved' => $resolved,
            'inProgress' => $inProgress,
            'kiv' => $kiv,
            'thisWeek' => $thisWeek,
            'resolutionRate' => $resolutionRate,
            'statusBreakdown' => $this->statusBreakdown(),
            'reports' => $this->reportsForRole()->limit(10)->get(),
            'listTitle' => $listTitle,
            'canGraph' => $user->can('dashboard.viewGraph'),
            'canAdmin' => $user->can('aduan.adminTask'),
            'canTechnical' => $user->can('aduan.updateTask'),
        ]);
    }
}
