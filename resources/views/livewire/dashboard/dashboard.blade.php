<div>
    {{-- ===== Hero: title, period, primary actions ===== --}}
    <div class="page-hero">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg">
                <div class="page-hero__eyebrow">Dashboard Pengurusan</div>
                <h1>Analisa Keseluruhan Aduan</h1>
                <p class="page-hero__sub mb-2">
                    Prestasi pengendalian aduan KKDW bagi tempoh dan bahagian yang dipilih.
                </p>
                <span class="page-hero__meta">
                    <i class="bi bi-calendar-range"></i>
                    {{ \Illuminate\Support\Carbon::parse($dateFrom)->isoFormat('LL') }}
                    &ndash; {{ \Illuminate\Support\Carbon::parse($dateTo)->isoFormat('LL') }}
                    @if ($bahagian)
                        <span class="opacity-75">&middot;</span> {{ $bahagian }}
                    @endif
                </span>
            </div>
            <div class="col-12 col-lg-auto">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                    <a href="{{ url('/aduan/create') }}" class="btn btn-light">
                        <i class="bi bi-plus-lg me-1"></i> Cipta Aduan
                    </a>
                    @if ($canTechnical)
                        <a href="{{ url('/aduan/untuk-tindakan') }}" class="btn btn-ghost">
                            <i class="bi bi-list-task me-1"></i> Untuk Tindakan
                        </a>
                    @endif
                    @if ($canAdmin)
                        <a href="{{ url('/aduan/dihantar') }}" class="btn btn-ghost">
                            <i class="bi bi-send me-1"></i> Aduan Baru
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Date range / bahagian filter ===== --}}
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
    </x-filter-bar>

    {{-- ===== KPI strip: one instrument panel, hairline-divided ===== --}}
    <div class="kpi-strip mb-3">
        <div class="kpi kpi--feature">
            <div class="kpi__top">
                <span class="kpi__ic stat-icon--accent"><i class="bi bi-clipboard-data"></i></span>
                <span class="kpi__label">Jumlah Aduan</span>
            </div>
            <div class="kpi__value">{{ number_format($total) }}</div>
            <div class="kpi__foot">Dalam tempoh dipilih</div>
        </div>

        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__ic stat-icon--warning"><i class="bi bi-hourglass-split"></i></span>
                <span class="kpi__label">Dalam Tindakan</span>
            </div>
            <div class="kpi__value">{{ number_format($inProgress) }}</div>
            <div class="kpi__foot">{{ $total > 0 ? round($inProgress / $total * 100) : 0 }}% daripada jumlah</div>
        </div>

        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__ic stat-icon--success"><i class="bi bi-check2-circle"></i></span>
                <span class="kpi__label">Selesai</span>
            </div>
            <div class="kpi__value">{{ number_format($resolved) }}</div>
            <div class="kpi__foot">{{ $resolutionRate }}% kadar penyelesaian</div>
        </div>

        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__ic stat-icon--danger"><i class="bi bi-pause-circle"></i></span>
                <span class="kpi__label">KIV</span>
            </div>
            <div class="kpi__value">{{ number_format($kiv) }}</div>
            <div class="kpi__foot">Menunggu tindakan lanjut</div>
        </div>

        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__ic stat-icon--primary"><i class="bi bi-stopwatch"></i></span>
                <span class="kpi__label">Purata Tempoh Selesai</span>
            </div>
            <div class="kpi__value">{{ $avgResolutionDays }} <small>hari</small></div>
            <div class="kpi__foot">Dari terima hingga selesai</div>
        </div>

        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__ic stat-icon--accent"><i class="bi bi-star-fill"></i></span>
                <span class="kpi__label">Purata Rating</span>
            </div>
            <div class="kpi__value">{{ $ratingBreakdown['average'] }} <small>/ 5</small></div>
            <div class="kpi__foot">{{ $ratingBreakdown['total'] }} maklum balas</div>
        </div>

    </div>

    {{-- ===== Kadar penyelesaian ===== --}}
    <div class="kpi-foot mb-4">
        <span class="kpi-foot__label">Kadar Penyelesaian</span>
        <div class="kpi-foot__meter">
            <div class="meter">
                <div class="meter__fill" role="progressbar" style="width: {{ $resolutionRate }}%;"
                     aria-valuenow="{{ $resolutionRate }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        <span class="kpi-foot__value">{{ $resolutionRate }}%</span>
        <span class="text-muted small">{{ $resolved }} daripada {{ $total }} aduan selesai</span>
    </div>

    {{-- ===== Trend ===== --}}
    <div class="panel mb-4">
        <div class="panel__head">
            <div>
                <div class="eyebrow">Prestasi</div>
                <h6 class="panel__title">Trend Bulanan — Aduan Diterima vs Selesai</h6>
            </div>
            <span class="panel__hint">12 bulan terakhir dalam tempoh</span>
        </div>
        <div class="panel__body"><div id="trend-line" style="height: 300px;"></div></div>
    </div>

    {{-- ===== Status + category ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="panel">
                <div class="panel__head">
                    <div>
                        <div class="eyebrow">Taburan</div>
                        <h6 class="panel__title">Aduan Mengikut Status</h6>
                    </div>
                </div>
                <div class="panel__body"><div id="status-donut" style="height: 300px;"></div></div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="panel">
                <div class="panel__head">
                    <div>
                        <div class="eyebrow">Taburan</div>
                        <h6 class="panel__title">Pecahan Mengikut Kategori</h6>
                    </div>
                </div>
                <div class="panel__body"><div id="category-bar" style="height: 300px;"></div></div>
            </div>
        </div>
    </div>

    {{-- ===== Bahagian + satisfaction ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="panel">
                <div class="panel__head">
                    <div>
                        <div class="eyebrow">Organisasi</div>
                        <h6 class="panel__title">Aduan Mengikut Bahagian</h6>
                    </div>
                    <span class="panel__hint">10 teratas</span>
                </div>
                <div class="panel__body"><div id="bahagian-bar" style="height: 320px;"></div></div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="panel">
                <div class="panel__head">
                    <div>
                        <div class="eyebrow">Kualiti Perkhidmatan</div>
                        <h6 class="panel__title">Kepuasan Pengguna</h6>
                    </div>
                    <span class="panel__hint">{{ $ratingBreakdown['total'] }} maklum balas</span>
                </div>
                <div class="panel__body"><div id="rating-bar" style="height: 320px;"></div></div>
            </div>
        </div>
    </div>

    {{-- ===== Officer workload ===== --}}
    <div class="panel mb-4">
        <div class="panel__head">
            <div>
                <div class="eyebrow">Operasi</div>
                <h6 class="panel__title">Beban Tugas Pegawai</h6>
                <div class="panel__hint mt-1">Aduan diterima untuk tindakan</div>
            </div>
            <span class="panel__hint">{{ count($officerWorkload) }} pegawai</span>
        </div>
        <div class="panel__body">
            @if (count($officerWorkload) > 0)
                <div id="officer-workload-bar" style="height: {{ max(300, count($officerWorkload) * 38 + 60) }}px;"></div>
            @else
                <div class="empty-state"><i class="bi bi-person-x"></i> Tiada pegawai menerima aduan dalam tempoh ini.</div>
            @endif
        </div>
    </div>

    {{-- ===== Recent activity ===== --}}
    <div class="panel">
        <div class="panel__head">
            <div>
                <div class="eyebrow">Aktiviti</div>
                <h6 class="panel__title">{{ $listTitle }}</h6>
            </div>
        </div>
        <div class="panel__body panel__body--flush">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tiket</th><th>Subjek</th><th>Pengadu</th><th>Kategori</th>
                            <th>Tarikh</th><th>Status</th><th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr wire:key="dash-report-{{ $report->id }}">
                                <td class="fw-semibold text-nowrap">{{ $report->ticket_no }}</td>
                                <td>{{ Str::limit($report->subject, 40) }}</td>
                                <td><x-pengadu-cell :report="$report" /></td>
                                <td>{{ $report->category->desc ?? '-' }}</td>
                                <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('aduan.show', $report) }}" class="btn btn-sm btn-outline-primary btn-icon">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i> Tiada aduan buat masa ini.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
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
         hidden>
    </div>
</div>
