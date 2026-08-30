<?php

namespace App\Livewire\Dashboard;

use App\Models\Report;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Simple dashboard for ordinary users. Shows ONLY the aduan they submitted
 * (report_by_id) or that were filed on their behalf (bp_user_id), with a date
 * filter defaulting to the current month.
 */
class UserDashboard extends Component
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

    /** Reports owned by / filed on behalf of the current user, in range. */
    protected function ownQuery()
    {
        $uid = Auth::id();

        return Report::query()
            ->where(fn ($q) => $q->where('report_by_id', $uid)->orWhere('bp_user_id', $uid))
            ->whereBetween('submitted_at', [$this->fromDate(), $this->toDate()]);
    }

    public function render()
    {
        $total = (clone $this->ownQuery())->whereIn('report_status_id', [2, 3, 4, 5, 6, 7, 8])->count();
        $dalamProses = (clone $this->ownQuery())->whereIn('report_status_id', [2, 3, 4, 6, 7, 8])->count();
        $selesai = (clone $this->ownQuery())->where('report_status_id', 5)->count();

        $recent = (clone $this->ownQuery())
            ->with(['status', 'category'])
            ->orderByDesc('submitted_at')
            ->limit(10)->get();

        // Resolve the currently-assigned officer (latest trail assigned_to_id)
        // for each listed report, in one query to avoid N+1.
        $reportIds = $recent->pluck('id');
        $officers = collect();
        if ($reportIds->isNotEmpty()) {
            $latest = DB::table('report_trails as t')
                ->join('users', 'users.id', '=', 't.assigned_to_id')
                ->whereIn('t.report_id', $reportIds)
                ->whereNotNull('t.assigned_to_id')
                ->whereRaw('t.id = (select max(t2.id) from report_trails t2 where t2.report_id = t.report_id and t2.assigned_to_id is not null)')
                ->select('t.report_id', 'users.name')
                ->get();
            $officers = $latest->pluck('name', 'report_id');
        }

        return view('livewire.dashboard.user-dashboard', [
            'total' => $total,
            'dalamProses' => $dalamProses,
            'selesai' => $selesai,
            'recent' => $recent,
            'officers' => $officers,
        ]);
    }
}
