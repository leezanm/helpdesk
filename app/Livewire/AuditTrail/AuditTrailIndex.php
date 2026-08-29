<?php

namespace App\Livewire\AuditTrail;

use App\Models\AuditTrail;
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMethod(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditTrail::query()
            ->with('user:id,name')            // eager load, only needed columns
            ->when($this->method !== '', fn ($q) => $q->where('method', $this->method))
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

        return view('livewire.audit-trail.audit-trail-index', [
            'logs' => $logs,
        ]);
    }
}
