<?php

namespace App\Livewire\AuditTrail;

use App\Models\AuditTrail;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Log Sistem (audit trail) listing.
 *
 * IMPORTANT: the audit_trails table is very large (tens of thousands of rows).
 * The old page loaded ALL rows into one DataTable, exhausting memory (HTTP 500).
 * This component paginates + filters server-side so only a page loads at a time.
 */
class AuditTrailIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $method = '';   // GET / POST / etc.

    #[Url]
    public string $userId = '';   // penapis pelaku

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMethod(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'method', 'userId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditTrail::query()
            ->with('user:id,name')            // eager load, only needed columns
            ->when($this->method !== '', fn ($q) => $q->where('method', $this->method))
            ->when($this->userId !== '', fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('url', 'like', $term)
                        ->orWhere('ip', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(25);

        // Only list users that actually appear in the audit log, so the dropdown
        // stays small and relevant on this very large table.
        $users = User::query()
            ->select('id', 'name')
            ->whereIn('id', AuditTrail::query()->distinct()->pluck('user_id')->filter())
            ->orderBy('name')
            ->get();

        return view('livewire.audit-trail.audit-trail-index', [
            'logs' => $logs,
            'users' => $users,
        ]);
    }
}
