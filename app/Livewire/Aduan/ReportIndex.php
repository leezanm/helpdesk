<?php

namespace App\Livewire\Aduan;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\Ref\ReportStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
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

    #[Url]
    public string $bahagian = '';

    #[Url]
    public string $categoryId = '';

    #[Url]
    public string $officerId = '';

    #[Url]
    public string $statusId = '';

    public function mount(string $scope = 'user', ?string $username = null): void
    {
        $this->scope = $scope;
        $this->username = $username;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBahagian(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingOfficerId(): void
    {
        $this->resetPage();
    }

    public function updatingStatusId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'bahagian', 'categoryId', 'officerId', 'statusId']);
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
            ->when($this->bahagian !== '', fn ($q) => $q->where('bahagian_pengadu', $this->bahagian))
            ->when($this->categoryId !== '', fn ($q) => $q->where('report_category_id', $this->categoryId))
            ->when($this->statusId !== '', fn ($q) => $q->where('report_status_id', $this->statusId))
            ->when($this->officerId !== '', function ($q) {
                // Reports whose latest trail is assigned to the chosen officer.
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('report_trails')
                        ->whereColumn('report_trails.report_id', 'reports.id')
                        ->where('report_trails.assigned_to_id', $this->officerId)
                        ->where('report_trails.id', DB::raw('(select id from report_trails t2 where t2.report_id = reports.id order by t2.id desc limit 1)'));
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.aduan.report-index', [
            'reports' => $reports,
            'canAdmin' => Auth::user()?->can('aduan.adminTask') ?? false,
            'bahagianOptions' => $this->bahagianOptions(),
            'categoryOptions' => ReportCategory::orderBy('desc')->get(['id', 'desc']),
            'statusOptions' => ReportStatus::whereIn('id', $this->scopeStatusIds())->orderBy('id')->get(['id', 'desc']),
            'officerOptions' => $this->officerOptions(),
        ]);
    }

    /**
     * Distinct bahagian that actually appear on reports (keeps the list short
     * and relevant instead of listing every org unit).
     */
    protected function bahagianOptions()
    {
        return Report::query()
            ->whereNotNull('bahagian_pengadu')
            ->where('bahagian_pengadu', '<>', '')
            ->distinct()
            ->orderBy('bahagian_pengadu')
            ->pluck('bahagian_pengadu');
    }

    /**
     * Officers that have been assigned on at least one trail.
     */
    protected function officerOptions()
    {
        $ids = ReportTrail::query()->whereNotNull('assigned_to_id')->distinct()->pluck('assigned_to_id');

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Statuses relevant to the current scope, so the dropdown only shows
     * options that can actually appear in the listing.
     */
    protected function scopeStatusIds(): array
    {
        return match ($this->scope) {
            'all' => [2, 3, 4, 5, 6],
            'dihantar' => [2, 3],
            'tindakan' => [4, 6],
            default => [1, 2, 3, 4, 5, 6, 7, 8],
        };
    }
}
