<div>
    {{-- Date filter --}}
    <x-filter-bar title="Penapis Tempoh" :show-reset="true" reset-action="resetFilter">
        <x-filter-field label="Dari Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateFrom" class="form-control">
        </x-filter-field>
        <x-filter-field label="Hingga Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateTo" class="form-control">
        </x-filter-field>
        <x-filter-field col="col-12 col-md text-md-end">
            <span class="text-muted small">
                <i class="bi bi-calendar-range me-1"></i>
                {{ \Illuminate\Support\Carbon::parse($dateFrom)->isoFormat('LL') }}
                &ndash; {{ \Illuminate\Support\Carbon::parse($dateTo)->isoFormat('LL') }}
            </span>
        </x-filter-field>
    </x-filter-bar>

    {{-- Stat cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--primary me-3"><i class="bi bi-inbox"></i></div>
                <div><p class="text-muted mb-1 small">Aduan Diterima</p><h3 class="mb-0 fw-bold">{{ $diterima }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--warning me-3"><i class="bi bi-hourglass-split"></i></div>
                <div><p class="text-muted mb-1 small">Dalam Tindakan</p><h3 class="mb-0 fw-bold">{{ $dalamTindakan }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--primary me-3"><i class="bi bi-check2"></i></div>
                <div><p class="text-muted mb-1 small">Selesai Tindakan</p><h3 class="mb-0 fw-bold">{{ $selesaiTindakan }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--success me-3"><i class="bi bi-check2-circle"></i></div>
                <div><p class="text-muted mb-1 small">Selesai</p><h3 class="mb-0 fw-bold">{{ $selesai }}</h3></div>
            </div></div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Tugasan Saya Mengikut Status</h6></div>
                <div class="card-body"><div id="officer-status-donut" style="height: 300px;"></div></div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Trend Bulanan — Diterima vs Selesai (Saya)</h6></div>
                <div class="card-body"><div id="officer-trend-line" style="height: 300px;"></div></div>
            </div>
        </div>
    </div>

    {{-- Recent assigned needing action --}}
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Tugasan Perlu Tindakan</h6></div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Tiket</th><th>Subjek</th><th>Pengadu</th><th>Tarikh</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($recent as $report)
                            <tr wire:key="off-{{ $report->id }}">
                                <td class="fw-semibold">{{ $report->ticket_no }}</td>
                                <td>{{ Str::limit($report->subject, 40) }}</td>
                                <td>
                                    <div class="fw-medium">{{ $report->reportBy->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $report->reportBy->ldap_bahagian ?? '' }}</small>
                                </td>
                                <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                <td class="text-end"><a href="{{ route('aduan.show', $report) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i> Tiada tugasan memerlukan tindakan dalam tempoh ini.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Chart data holder --}}
    <div id="officer-chart-data"
         data-status='@json($statusBreakdown)'
         data-trend='@json($trend)'
         hidden></div>
</div>
