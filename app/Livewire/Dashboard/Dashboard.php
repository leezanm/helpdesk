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

    public function render()
    {
        $user = Auth::user();

        $listTitle = match (true) {
            $user->can('aduan.adminTask') => 'Aduan Yang Belum Diproses',
            $user->can('aduan.updateTask') => 'Tugasan Saya',
            default => 'Aduan Saya',
        };

        return view('livewire.dashboard.dashboard', [
            'total' => (clone $this->reportCountQuery())->count(),
            'resolved' => (clone $this->reportCountQuery())->where('report_status_id', 5)->count(),
            'inProgress' => (clone $this->reportCountQuery())->whereIn('report_status_id', [6, 4, 2, 3])->count(),
            'reports' => $this->reportsForRole()->limit(10)->get(),
            'listTitle' => $listTitle,
            'canGraph' => $user->can('dashboard.viewGraph'),
            'canAdmin' => $user->can('aduan.adminTask'),
            'canTechnical' => $user->can('aduan.updateTask'),
        ]);
    }
}
