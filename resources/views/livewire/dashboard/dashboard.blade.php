<div>
    {{-- ===== Date range filter ===== --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Dari Tarikh</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Hingga Tarikh</label>
                    <input type="date" wire:model.live="dateTo" class="form-control">
                </div>
                <div class="col-12 col-md-auto">
                    <button class="btn btn-outline-secondary" wire:click="resetFilter">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Set Semula
                    </button>
                </div>
                <div class="col-12 col-md text-md-end">
                    <span class="text-muted small">
                        <i class="bi bi-funnel me-1"></i>
                        {{ \Illuminate\Support\Carbon::parse($dateFrom)->isoFormat('LL') }}
                        &ndash; {{ \Illuminate\Support\Carbon::parse($dateTo)->isoFormat('LL') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Stat cards ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--primary me-3"><i class="bi bi-clipboard-data"></i></div>
                <div><p class="text-muted mb-1 small">Jumlah Aduan</p><h3 class="mb-0 fw-bold">{{ $total }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--warning me-3"><i class="bi bi-hourglass-split"></i></div>
                <div><p class="text-muted mb-1 small">Dalam Tindakan</p><h3 class="mb-0 fw-bold">{{ $inProgress }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--success me-3"><i class="bi bi-check2-circle"></i></div>
                <div><p class="text-muted mb-1 small">Selesai</p><h3 class="mb-0 fw-bold">{{ $resolved }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center">
                <div class="stat-icon stat-icon--danger me-3"><i class="bi bi-pause-circle"></i></div>
                <div><p class="text-muted mb-1 small">KIV</p><h3 class="mb-0 fw-bold">{{ $kiv }}</h3></div>
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

    {{-- ===== Recent activity + officer logs ===== --}}
    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">{{ $listTitle }}</h6></div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Tiket</th><th>Subjek</th><th>Tarikh</th><th>Status</th><th class="text-end"></th></tr></thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr wire:key="dash-report-{{ $report->id }}">
                                        <td class="fw-semibold">{{ $report->ticket_no }}</td>
                                        <td>{{ Str::limit($report->subject, 36) }}</td>
                                        <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                        <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                        <td class="text-end"><a href="{{ route('aduan.show', $report->id) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i> Tiada aduan buat masa ini.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Officer log listing (officers with logs only) --}}
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Jumlah Log Pegawai</h6></div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Pegawai</th><th class="text-end">Jumlah Log</th></tr></thead>
                            <tbody>
                                @forelse ($officerLogs as $officer)
                                    <tr wire:key="officer-{{ $loop->index }}">
                                        <td>
                                            <div class="fw-medium">{{ $officer->name }}</div>
                                            @if ($officer->ldap_bahagian)<small class="text-muted">{{ $officer->ldap_bahagian }}</small>@endif
                                        </td>
                                        <td class="text-end"><span class="badge bg-primary">{{ $officer->total }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2"><div class="empty-state"><i class="bi bi-person-x"></i> Tiada log pegawai dalam tempoh ini.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Charts (server-fed; update live on date filter via Livewire event) ===== --}}
    <script>
        (function () {
            function palette() {
                var css = getComputedStyle(document.documentElement);
                var primary = (css.getPropertyValue('--c-primary') || '#0ea5a4').trim();
                var success = (css.getPropertyValue('--c-success') || '#10b981').trim();
                return { primary: primary, list: [primary, success, '#38bdf8', '#f59e0b', '#a78bfa', '#f472b6', '#fb7185', '#34d399'] };
            }

            function renderCharts(statusData, categoryData) {
                if (typeof echarts === 'undefined') return;
                var p = palette();

                var donutEl = document.getElementById('status-donut');
                if (donutEl) {
                    var donut = echarts.getInstanceByDom(donutEl) || echarts.init(donutEl);
                    donut.setOption({
                        color: p.list, tooltip: { trigger: 'item' }, legend: { bottom: 0, icon: 'circle' },
                        series: [{ type: 'pie', radius: ['50%', '72%'], center: ['50%', '42%'], label: { show: false },
                            data: (statusData || []).map(function (s) { return { name: s.label, value: s.value }; }) }]
                    }, true);
                }

                var barEl = document.getElementById('category-bar');
                if (barEl) {
                    var bar = echarts.getInstanceByDom(barEl) || echarts.init(barEl);
                    bar.setOption({
                        color: [p.primary], tooltip: { trigger: 'axis' },
                        grid: { left: 8, right: 16, top: 20, bottom: 8, containLabel: true },
                        xAxis: { type: 'value', minInterval: 1 },
                        yAxis: { type: 'category', inverse: true, data: (categoryData || []).map(function (c) { return c.label; }) },
                        series: [{ type: 'bar', barMaxWidth: 22, itemStyle: { borderRadius: [0, 6, 6, 0] },
                            data: (categoryData || []).map(function (c) { return c.value; }) }]
                    }, true);
                }
            }

            // Initial render from server data.
            function initial() { renderCharts(@json($statusBreakdown), @json($categoryBreakdown)); }
            document.addEventListener('DOMContentLoaded', initial);
            document.addEventListener('livewire:navigated', initial);
            initial();

            // Live updates when the date filter changes (component re-renders + dispatches).
            document.addEventListener('livewire:init', function () {
                Livewire.on('dashboard-charts', function (e) {
                    var d = Array.isArray(e) ? e[0] : e;
                    renderCharts(d.status, d.category);
                });
            });

            window.addEventListener('resize', function () {
                ['status-donut', 'category-bar'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el && echarts.getInstanceByDom(el)) echarts.getInstanceByDom(el).resize();
                });
            });
        })();
    </script>
</div>
