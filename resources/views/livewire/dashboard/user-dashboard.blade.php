<div>
    {{-- Welcome + quick action --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h5 class="fw-bold mb-0">Selamat Datang, {{ Auth::user()->name }}</h5>
            <p class="text-muted mb-0">Ringkasan aduan anda.</p>
        </div>
        <a href="{{ url('/aduan/create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Cipta Aduan</a>
    </div>

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
        <div class="col-12 col-md-4">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--primary me-3"><i class="bi bi-clipboard-data"></i></div>
                <div><p class="text-muted mb-1 small">Jumlah Aduan</p><h3 class="mb-0 fw-bold">{{ $total }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--warning me-3"><i class="bi bi-hourglass-split"></i></div>
                <div><p class="text-muted mb-1 small">Dalam Proses</p><h3 class="mb-0 fw-bold">{{ $dalamProses }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--success me-3"><i class="bi bi-check2-circle"></i></div>
                <div><p class="text-muted mb-1 small">Selesai</p><h3 class="mb-0 fw-bold">{{ $selesai }}</h3></div>
            </div></div>
        </div>
    </div>

    {{-- Recent own reports --}}
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Aduan Saya</h6></div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Tiket</th><th>Subjek</th><th>Kategori</th><th>Pegawai Bertanggungjawab</th><th>Tarikh</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($recent as $report)
                            <tr wire:key="usr-{{ $report->id }}">
                                <td class="fw-semibold">{{ $report->ticket_no ?: '-' }}</td>
                                <td>{{ Str::limit($report->subject, 40) }}</td>
                                <td>{{ $report->category->desc ?? '-' }}</td>
                                <td>{{ $officers[$report->id] ?? '—' }}</td>
                                <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                <td class="text-end"><a href="{{ route('aduan.show', $report) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i> Tiada aduan dalam tempoh ini.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
