<?php

namespace App\Livewire\Aduan;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Report (aduan) listing with search, sort and pagination. Replaces the
 * DataTables page + jQuery. One component serves the four legacy variants
 * via $scope:
 *   'all'      -> ReportController::index (status 2..6)         [can:aduan.all]
 *   'user'     -> indexUser (own / bagi-pihak reports)
 *   'dihantar' -> indexDihantar (status 2,3)
 *   'tindakan' -> indexUntukTindakan (assigned to me, status 4,6)
 */
class ReportIndex extends Component
{
    use WithPagination;

    public string $scope = 'user';
    public ?string $username = null;    // for the 'user' scope
    public string $search = '';
    public string $sortField = 'submitted_at';
    public string $sortDirection = 'desc';

    public function mount(string $scope = 'user', ?string $username = null): void
    {
        $this->scope = $scope;
        $this->username = $username;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    protected function baseQuery()
    {
        $userId = Auth::id();

        return match ($this->scope) {
            'all' => Report::whereIn('report_status_id', [2, 3, 4, 5, 6]),
            'dihantar' => Report::whereIn('report_status_id', [2, 3]),
            'tindakan' => Report::whereIn('report_status_id', [4, 6])
                ->join('report_trails', 'reports.id', '=', 'report_trails.report_id')
                ->select('reports.*', 'report_trails.id as trail_id', 'assigned_to_id')
                ->where('report_trails.id', DB::raw('(select id from report_trails where report_id = reports.id order by id desc limit 1)'))
                ->where('assigned_to_id', $userId),
            default => Report::where(function ($q) use ($userId) {
                $q->where('report_by_id', $userId)->orWhere('bp_user_id', $userId);
            }),
        };
    }

    public function render()
    {
        $reports = $this->baseQuery()
            ->with(['category', 'status', 'reportBy'])
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('subject', 'like', '%'.$this->search.'%')
                        ->orWhere('ticket_no', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.aduan.report-index', [
            'reports' => $reports,
            'canAdmin' => Auth::user()?->can('aduan.adminTask') ?? false,
        ]);
    }
}
