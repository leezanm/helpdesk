<div>
    {{-- ===== Date range filter ===== --}}
    <x-filter-bar title="Penapis Tempoh" :show-reset="true" reset-action="resetFilter">
        <x-filter-field label="Dari Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateFrom" class="form-control">
        </x-filter-field>

        <x-filter-field label="Hingga Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateTo" class="form-control">
        </x-filter-field>

        <x-filter-field label="Bahagian" col="col-12 col-md-4">
            <select wire:model.live="bahagian" class="form-select">
                <option value="">Semua Bahagian</option>
                @foreach ($bahagianOptions as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field col="col-12 col-md text-md-end">
            <span class="text-muted small">
                <i class="bi bi-calendar-range me-1"></i>
                {{ \Illuminate\Support\Carbon::parse($dateFrom)->isoFormat('LL') }}
                &ndash; {{ \Illuminate\Support\Carbon::parse($dateTo)->isoFormat('LL') }}
            </span>
        </x-filter-field>
    </x-filter-bar>

    {{-- ===== Stat cards ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--primary me-3"><i class="bi bi-clipboard-data"></i></div>
                <div><p class="text-muted mb-1 small">Jumlah Aduan</p><h3 class="mb-0 fw-bold">{{ $total }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--warning me-3"><i class="bi bi-hourglass-split"></i></div>
                <div><p class="text-muted mb-1 small">Dalam Tindakan</p><h3 class="mb-0 fw-bold">{{ $inProgress }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--success me-3"><i class="bi bi-check2-circle"></i></div>
                <div><p class="text-muted mb-1 small">Selesai</p><h3 class="mb-0 fw-bold">{{ $resolved }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--danger me-3"><i class="bi bi-pause-circle"></i></div>
                <div><p class="text-muted mb-1 small">KIV</p><h3 class="mb-0 fw-bold">{{ $kiv }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--primary me-3"><i class="bi bi-stopwatch"></i></div>
                <div><p class="text-muted mb-1 small">Purata Tempoh Selesai</p><h3 class="mb-0 fw-bold">{{ $avgResolutionDays }} <small class="fs-6 fw-normal text-muted">hari</small></h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--warning me-3"><i class="bi bi-star-fill"></i></div>
                <div><p class="text-muted mb-1 small">Purata Rating</p><h3 class="mb-0 fw-bold">{{ $ratingBreakdown['average'] }} <small class="fs-6 fw-normal text-muted">/ 5</small></h3></div>
            </div></div>
        </div>
    </div>

    {{-- ===== Resolution rate ===== --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <p class="text-muted mb-0 small">Kadar Penyelesaian</p>
                <span class="fw-bold">{{ $resolutionRate }}%</span>
            </div>
            <div class="progress" style="height:10px;border-radius:999px;">
                <div class="progress-bar" role="progressbar"
                     style="width: {{ $resolutionRate }}%; background: linear-gradient(90deg,var(--c-primary),var(--c-violet));"
                     aria-valuenow="{{ $resolutionRate }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted">{{ $resolved }} daripada {{ $total }} aduan selesai dalam tempoh dipilih</small>
        </div>
    </div>

    {{-- ===== Quick actions ===== --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ url('/aduan/create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Cipta Aduan</a>
        @if ($canTechnical)<a href="{{ url('/aduan/untuk-tindakan') }}" class="btn btn-outline-primary"><i class="bi bi-list-task me-1"></i> Untuk Tindakan</a>@endif
        @if ($canAdmin)<a href="{{ url('/aduan/dihantar') }}" class="btn btn-outline-primary"><i class="bi bi-send me-1"></i> Aduan Dihantar</a>@endif
    </div>

    {{-- ===== Charts: status donut + category bar ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Aduan Mengikut Status</h6></div>
                <div class="card-body"><div id="status-donut" style="height: 300px;"></div></div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Pecahan Mengikut Kategori</h6></div>
                <div class="card-body"><div id="category-bar" style="height: 300px;"></div></div>
            </div>
        </div>
    </div>

    {{-- ===== Monthly trend: submitted vs resolved ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Trend Bulanan — Aduan Diterima vs Selesai</h6></div>
                <div class="card-body"><div id="trend-line" style="height: 300px;"></div></div>
            </div>
        </div>
    </div>

    {{-- ===== Bahagian breakdown + satisfaction ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Aduan Mengikut Bahagian (10 Teratas)</h6></div>
                <div class="card-body"><div id="bahagian-bar" style="height: 320px;"></div></div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Kepuasan Pengguna (Rating)</h6>
                    <span class="text-muted small">{{ $ratingBreakdown['total'] }} maklum balas</span>
                </div>
                <div class="card-body"><div id="rating-bar" style="height: 320px;"></div></div>
            </div>
        </div>
    </div>

    {{-- ===== Recent activity ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ $listTitle }}</h6></div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Tiket</th><th>Subjek</th><th>Pengadu</th><th>Kategori</th><th>Tarikh</th><th>Status</th><th class="text-end"></th></tr></thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr wire:key="dash-report-{{ $report->id }}">
                                        <td class="fw-semibold">{{ $report->ticket_no }}</td>
                                        <td>{{ Str::limit($report->subject, 40) }}</td>
                                        <td><x-pengadu-cell :report="$report" /></td>
                                        <td>{{ $report->category->desc ?? '-' }}</td>
                                        <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                        <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                        <td class="text-end"><a href="{{ route('aduan.show', $report) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i> Tiada aduan buat masa ini.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Officer workload: stacked bar (Dalam Tindakan + Selesai per officer) ===== --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Beban Tugas Pegawai (Aduan Diterima untuk Tindakan)</h6></div>
                <div class="card-body">
                    @if (count($officerWorkload) > 0)
                        <div id="officer-workload-bar" style="height: {{ max(300, count($officerWorkload) * 38 + 60) }}px;"></div>
                    @else
                        <div class="empty-state"><i class="bi bi-person-x"></i> Tiada pegawai menerima aduan dalam tempoh ini.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Chart data holder: the component writes fresh JSON here on every render.
         The chart script (in the page layout's @section('script')) reads it and
         redraws, so charts react to ANY filter change (date + bahagian). --}}
    <div id="dashboard-chart-data"
         data-status='@json($statusBreakdown)'
         data-category='@json($categoryBreakdown)'
         data-officers='@json($officerWorkloadChart)'
         data-trend='@json($monthlyTrend)'
         data-rating='@json($ratingBreakdown)'
         data-bahagian='@json($bahagianBreakdown)'
         hidden></div>
</div>
