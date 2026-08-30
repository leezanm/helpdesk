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
 * Dashboard for a pegawai bertanggungjawab (aduan.updateTask). Shows ONLY the
 * aduan currently assigned to the logged-in officer (i.e. they are the latest
 * assigned officer via report_trails.assigned_to_id), with a date filter that
 * defaults to the current month.
 */
class OfficerDashboard extends Component
{
    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount(): void
    {
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

    protected function yearMonthExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * Reports where the current user is the latest assigned officer, and that
     * assignment happened within the date range. Base for all officer stats.
     */
    protected function assignedQuery()
    {
        $uid = Auth::id();

        return Report::query()
            ->whereExists(function ($sub) use ($uid) {
                $sub->select(DB::raw(1))
                    ->from('report_trails')
                    ->whereColumn('report_trails.report_id', 'reports.id')
                    ->where('report_trails.assigned_to_id', $uid)
                    ->whereBetween('report_trails.created_at', [$this->fromDate(), $this->toDate()])
                    ->where('report_trails.id', DB::raw('(select id from report_trails t2 where t2.report_id = reports.id and t2.assigned_to_id is not null order by t2.id desc limit 1)'));
            });
    }

    protected function statusBreakdown(): array
    {
        $counts = (clone $this->assignedQuery())
            ->select('report_status_id', DB::raw('count(*) as total'))
            ->groupBy('report_status_id')
            ->pluck('total', 'report_status_id');

        return ReportStatus::whereIn('id', [4, 6, 7, 8, 5])->get()
            ->map(fn ($s) => ['label' => $s->desc, 'value' => (int) ($counts[$s->id] ?? 0)])
            ->filter(fn ($r) => $r['value'] > 0)
            ->values()->all();
    }

    protected function monthlyTrend(): array
    {
        $ymAssigned = $this->yearMonthExpr('report_trails.created_at');

        // Assigned to me per month.
        $assigned = DB::table('report_trails')
            ->where('assigned_to_id', Auth::id())
            ->whereBetween('created_at', [$this->fromDate(), $this->toDate()])
            ->select(DB::raw("{$ymAssigned} as ym"), DB::raw('count(distinct report_id) as total'))
            ->groupBy('ym')->pluck('total', 'ym');

        // Resolved by me per completion month (reports I'm the latest officer on).
        $resolved = (clone $this->assignedQuery())
            ->where('report_status_id', 5)
            ->whereNotNull('completed_at')
            ->select(DB::raw($this->yearMonthExpr('completed_at').' as ym'), DB::raw('count(*) as total'))
            ->groupBy('ym')->pluck('total', 'ym');

        $labels = [];
        $a = [];
        $r = [];
        $cursor = $this->fromDate()->copy()->startOfMonth();
        $end = $this->toDate()->copy()->startOfMonth();
        $guard = 0;
        while ($cursor->lte($end) && $guard < 120) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->isoFormat('MMM YY');
            $a[] = (int) ($assigned[$key] ?? 0);
            $r[] = (int) ($resolved[$key] ?? 0);
            $cursor->addMonth();
            $guard++;
        }

        return ['labels' => $labels, 'assigned' => $a, 'resolved' => $r];
    }

    public function render()
    {
        $diterima = (clone $this->assignedQuery())->count();
        $dalamTindakan = (clone $this->assignedQuery())->whereIn('report_status_id', [4, 6])->count();
        $selesaiTindakan = (clone $this->assignedQuery())->whereIn('report_status_id', [7, 8])->count();
        $selesai = (clone $this->assignedQuery())->where('report_status_id', 5)->count();

        $statusBreakdown = $this->statusBreakdown();
        $trend = $this->monthlyTrend();

        $this->dispatch('officer-charts', status: $statusBreakdown, trend: $trend);

        // Recent assigned reports needing my action (In Action / KIV).
        $recent = (clone $this->assignedQuery())
            ->whereIn('report_status_id', [4, 6])
            ->with(['status', 'category', 'reportBy:id,name,email,tel_no,ldap_bahagian'])
            ->orderByDesc('submitted_at')
            ->limit(10)->get();

        return view('livewire.dashboard.officer-dashboard', [
            'diterima' => $diterima,
            'dalamTindakan' => $dalamTindakan,
            'selesaiTindakan' => $selesaiTindakan,
            'selesai' => $selesai,
            'statusBreakdown' => $statusBreakdown,
            'trend' => $trend,
            'recent' => $recent,
        ]);
    }
}
