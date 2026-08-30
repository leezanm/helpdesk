<?php

namespace App\Livewire\Laporan;

use App\Models\Ref\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
        $this->reset(['bahagian', 'status', 'pegawai', 'rating']);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function updated($name): void
    {
        // Any filter change returns to page 1.
        if (in_array($name, ['dateFrom', 'dateTo', 'bahagian', 'status', 'pegawai', 'rating'])) {
            $this->resetPage();
        }
    }

    /** Base query with all active filters applied. */
    protected function baseQuery()
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        return Report::query()
            ->whereBetween('submitted_at', [$from, $to])
            ->when($this->bahagian !== '', fn ($q) => $q->where('bahagian_pengadu', $this->bahagian))
            ->when($this->status !== '', fn ($q) => $q->where('report_status_id', (int) $this->status))
            ->when($this->rating !== '', fn ($q) => $q->where('rating', (int) $this->rating))
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

    public function render()
    {
        $reports = $this->baseQuery()
            ->with(['status', 'category', 'reportBy'])
            ->orderByDesc('submitted_at')
            ->paginate(20);

        // Summary counts per status for the currently filtered set.
        $summary = (clone $this->baseQuery())
            ->select('report_status_id', DB::raw('count(*) as total'))
            ->groupBy('report_status_id')
            ->pluck('total', 'report_status_id');

        $statuses = ReportStatus::whereIn('id', [1, 2, 3, 4, 5, 6])->orderBy('id')->get();

        return view('livewire.laporan.report-all', [
            'reports' => $reports,
            'summary' => $statuses->map(fn ($s) => [
                'desc' => $s->desc,
                'class' => $s->class,
                'total' => (int) ($summary[$s->id] ?? 0),
            ])->all(),
            'grandTotal' => (int) $summary->sum(),
            'bahagianOptions' => DB::table('reports')
                ->whereNotNull('bahagian_pengadu')->where('bahagian_pengadu', '<>', '')
                ->distinct()->orderBy('bahagian_pengadu')->pluck('bahagian_pengadu'),
            'statusOptions' => $statuses,
            'pegawaiOptions' => User::permission('aduan.updateTask')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
