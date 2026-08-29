<div>
    {{-- Toolbar --}}
    <div class="list-toolbar">
        <div class="list-search">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                   placeholder="Cari punca, penyelesaian, subjek...">
        </div>
        <select wire:model.live="category" class="form-select" style="max-width:260px">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}">{{ $c->desc }}</option>
            @endforeach
        </select>
    </div>

    @forelse ($entries as $entry)
        <div class="card mb-3" wire:key="ll-{{ $entry->id }}">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary">{{ $entry->report->category->desc ?? '-' }}</span>
                        <span class="fw-semibold">{{ $entry->report->subject ?? '-' }}</span>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-clock me-1"></i>{{ convertDateFormat($entry->created_at, 'LL') }}
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background:rgba(239,68,68,.06)">
                            <div class="small fw-bold text-uppercase mb-1" style="color:var(--c-danger)">
                                <i class="bi bi-exclamation-triangle me-1"></i>Punca Masalah
                            </div>
                            <div>{{ $entry->punca_masalah }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background:rgba(16,185,129,.06)">
                            <div class="small fw-bold text-uppercase mb-1" style="color:var(--c-success)">
                                <i class="bi bi-check2-circle me-1"></i>Langkah Penyelesaian
                            </div>
                            <div>{{ $entry->langkah_tindakan ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                @if ($entry->catatan_teknikal)
                    <div class="mt-3 p-3 rounded-3" style="background:var(--c-surface-2)">
                        <div class="small fw-bold text-uppercase mb-1 text-muted"><i class="bi bi-journal-text me-1"></i>Catatan Teknikal</div>
                        <div>{{ $entry->catatan_teknikal }}</div>
                    </div>
                @endif

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3" style="border-top:1px solid var(--c-border)">
                    <small class="text-muted">
                        <i class="bi bi-person-badge me-1"></i>Diselesaikan oleh: {{ $entry->updatedBy->name ?? 'Pegawai' }}
                        @if ($entry->report)· Tiket {{ $entry->report->ticket_no }}@endif
                    </small>
                    @if ($entry->report)
                        <a href="{{ route('aduan.show', $entry->report->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Lihat Aduan
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-lightbulb"></i>
                    Tiada rekod lesson learn dijumpai.
                    <div class="small mt-1">Rekod akan muncul apabila pegawai menyelesaikan tindakan dengan punca &amp; penyelesaian.</div>
                </div>
            </div>
        </div>
    @endforelse

    @if ($entries->hasPages())
        <div class="mt-2">{{ $entries->links() }}</div>
    @endif
</div>
