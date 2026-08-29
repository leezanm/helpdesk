<div>
    {{-- ===== Filters (all on one page) ===== --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> Penapis Laporan</h6>
            <button class="btn btn-sm btn-outline-secondary" wire:click="resetFilters">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Set Semula
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label small">Dari Tarikh</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Hingga Tarikh</label>
                    <input type="date" wire:model.live="dateTo" class="form-control">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small">Bahagian</label>
                    <select wire:model.live="bahagian" class="form-select">
                        <option value="">Semua Bahagian</option>
                        @foreach ($bahagianOptions as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Status Tindakan</label>
                    <select wire:model.live="status" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $s)
                            <option value="{{ $s->id }}">{{ $s->desc }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small">Pegawai Bertanggungjawab</label>
                    <select wire:model.live="pegawai" class="form-select">
                        <option value="">Semua Pegawai</option>
                        @foreach ($pegawaiOptions as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Status summary strip ===== --}}
    <div class="row g-2 mb-3">
        @foreach ($summary as $item)
            <div class="col-6 col-md">
                <div class="card h-100">
                    <div class="card-body py-3 text-center">
                        <div class="mb-1"><span class="{{ $item['class'] }}">{{ $item['desc'] }}</span></div>
                        <h4 class="mb-0 fw-bold">{{ $item['total'] }}</h4>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-6 col-md">
            <div class="card h-100" style="background:var(--c-primary-050)">
                <div class="card-body py-3 text-center">
                    <div class="mb-1 text-muted small fw-semibold">JUMLAH</div>
                    <h4 class="mb-0 fw-bold" style="color:var(--c-primary-700)">{{ $grandTotal }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Results ===== --}}
    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th>Tiket</th>
                            <th>Subjek</th>
                            <th>Bahagian</th>
                            <th>Kategori</th>
                            <th>Tarikh</th>
                            <th>Status</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr wire:key="lap-{{ $report->id }}">
                                <td class="text-muted">{{ $loop->iteration + ($reports->currentPage() - 1) * $reports->perPage() }}</td>
                                <td class="fw-semibold">{{ $report->ticket_no }}</td>
                                <td>{{ Str::limit($report->subject, 40) }}</td>
                                <td><small>{{ $report->bahagian_pengadu ?? '-' }}</small></td>
                                <td>{{ $report->category->desc ?? '-' }}</td>
                                <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('aduan.show', $report->id) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Lihat"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="empty-state"><i class="bi bi-search"></i> Tiada aduan sepadan dengan penapis.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div class="px-2 pt-3">{{ $reports->links() }}</div>
            @endif
        </div>
    </div>
</div>
