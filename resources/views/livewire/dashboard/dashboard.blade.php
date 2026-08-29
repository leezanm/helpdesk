<div>
    {{-- ===== Stat cards ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon stat-icon--primary me-3"><i class="bi bi-clipboard-data"></i></div>
                    <div>
                        <p class="text-muted mb-1 small">Jumlah Aduan</p>
                        <h3 class="mb-0 fw-bold">{{ $total }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon stat-icon--warning me-3"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <p class="text-muted mb-1 small">Dalam Tindakan</p>
                        <h3 class="mb-0 fw-bold">{{ $inProgress }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon stat-icon--success me-3"><i class="bi bi-check2-circle"></i></div>
                    <div>
                        <p class="text-muted mb-1 small">Selesai</p>
                        <h3 class="mb-0 fw-bold">{{ $resolved }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon stat-icon--danger me-3"><i class="bi bi-pause-circle"></i></div>
                    <div>
                        <p class="text-muted mb-1 small">KIV</p>
                        <h3 class="mb-0 fw-bold">{{ $kiv }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Secondary reporting stats ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small">Aduan Minggu Ini</p>
                        <h3 class="mb-0 fw-bold">{{ $thisWeek }}</h3>
                    </div>
                    <i class="bi bi-calendar-week fs-1" style="color:var(--c-primary);opacity:.5"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card h-100">
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
                    <small class="text-muted">{{ $resolved }} daripada {{ $total }} aduan selesai</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Quick actions ===== --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ url('/aduan/create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Cipta Aduan</a>
        @if ($canTechnical)
            <a href="{{ url('/aduan/untuk-tindakan') }}" class="btn btn-outline-primary"><i class="bi bi-list-task me-1"></i> Untuk Tindakan</a>
        @endif
        @if ($canAdmin)
            <a href="{{ url('/aduan/dihantar') }}" class="btn btn-outline-primary"><i class="bi bi-send me-1"></i> Aduan Dihantar</a>
        @endif
    </div>

    {{-- ===== Status breakdown (server-fed donut — always works) + recent activity ===== --}}
    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Aduan Mengikut Status</h6></div>
                <div class="card-body">
                    <div id="status-donut" style="height: 280px;"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ $listTitle }}</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tiket</th>
                                    <th>Subjek</th>
                                    <th>Tarikh</th>
                                    <th>Status</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr wire:key="dash-report-{{ $report->id }}">
                                        <td class="fw-semibold">{{ $report->ticket_no }}</td>
                                        <td>{{ Str::limit($report->subject, 40) }}</td>
                                        <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                        <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('aduan.show', $report->id) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a>
                                        </td>
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
    </div>

    {{-- ===== Weekly charts (dashboard.viewGraph) ===== --}}
    @if ($canGraph)
        <div class="row g-3 mt-1">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0">Kategori (Minggu Ini)</h6></div>
                    <div class="card-body"><div id="sektor" style="height: 300px;"></div></div>
                </div>
            </div>
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0">Aduan Vs Selesai (Minggu Ini)</h6></div>
                    <div class="card-body"><div id="mix-line-bar" style="height: 300px;"></div></div>
                </div>
            </div>
        </div>

        <div class="row mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Pecahan Mengikut Kategori Dan Status (Minggu Ini)</h6></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr class="text-center">
                                        <th class="text-start">Kategori</th>
                                        <th>Dihantar</th>
                                        <th>Ambil Maklum</th>
                                        <th>Dalam Tindakan</th>
                                        <th>Selesai</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-category_by_status" class="text-center"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Chart data passed from PHP (status donut always available) --}}
    @php $statusJson = json_encode($statusBreakdown); @endphp

    <script>
        (function () {
            // Guard: only run once echarts is available. It is loaded in <head>,
            // but we also retry on livewire:navigated (SPA navigation).
            function initDashboardCharts() {
                if (typeof echarts === 'undefined') { return; }

                var css = getComputedStyle(document.documentElement);
                var primary = (css.getPropertyValue('--c-primary') || '#0ea5a4').trim();
                var success = (css.getPropertyValue('--c-success') || '#10b981').trim();
                var palette = [primary, success, '#38bdf8', '#f59e0b', '#a78bfa', '#f472b6'];

                // --- Status donut (server-fed; no API needed) ---
                var donutEl = document.getElementById('status-donut');
                if (donutEl && !donutEl.dataset.init) {
                    donutEl.dataset.init = '1';
                    var donut = echarts.init(donutEl);
                    donut.setOption({
                        color: palette,
                        tooltip: { trigger: 'item' },
                        legend: { bottom: 0, icon: 'circle' },
                        series: [{
                            type: 'pie', radius: ['50%', '72%'], center: ['50%', '42%'],
                            avoidLabelOverlap: true, label: { show: false },
                            data: @json($statusBreakdown).map(function (s) { return { name: s.label, value: s.value }; })
                        }]
                    });
                    window.addEventListener('resize', function () { donut.resize(); });
                }

                @if ($canGraph)
                var headers = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };

                var sektorEl = document.getElementById('sektor');
                if (sektorEl && !sektorEl.dataset.init) {
                    sektorEl.dataset.init = '1';
                    var pie = echarts.init(sektorEl);
                    pie.setOption({ color: palette, tooltip: { trigger: 'item' },
                        series: [{ type: 'pie', radius: ['45%', '70%'], data: [] }] });
                    fetch('{{ route('api.dashboard.pie-chart') }}', { method: 'POST', headers: headers })
                        .then(function (r) { return r.json(); })
                        .then(function (res) { if (res.success) pie.setOption({ series: [{ data: res.data }] }); });
                    window.addEventListener('resize', function () { pie.resize(); });
                }

                var barEl = document.getElementById('mix-line-bar');
                if (barEl && !barEl.dataset.init) {
                    barEl.dataset.init = '1';
                    var bar = echarts.init(barEl);
                    bar.setOption({ color: [primary, success], tooltip: { trigger: 'axis' },
                        legend: { data: ['Jumlah Aduan', 'Aduan Selesai'] },
                        grid: { left: 40, right: 20, top: 40, bottom: 30 },
                        xAxis: [{ type: 'category', data: ['Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu','Ahad'] }],
                        yAxis: [{ type: 'value', minInterval: 1 }],
                        series: [{ name: 'Jumlah Aduan', type: 'bar', data: [] }, { name: 'Aduan Selesai', type: 'bar', data: [] }] });
                    fetch('{{ route('api.dashboard.bar-chart') }}', { method: 'POST', headers: headers })
                        .then(function (r) { return r.json(); })
                        .then(function (res) { if (res.success) bar.setOption({ series: [{ data: res.data.sent }, { data: res.data.completed }] }); });
                    window.addEventListener('resize', function () { bar.resize(); });
                }

                var tbody = document.getElementById('tbody-category_by_status');
                if (tbody && !tbody.dataset.init) {
                    tbody.dataset.init = '1';
                    fetch('{{ route('api.dashboard.category-table') }}', { method: 'POST', headers: headers })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (!res.success) return;
                            tbody.innerHTML = '';
                            res.category_by_status.forEach(function (c) {
                                tbody.insertAdjacentHTML('beforeend',
                                    '<tr><td class="text-start fw-medium">' + c.desc + '</td>' +
                                    '<td>' + c.dihantar + '</td><td>' + c.dihantar_ambil_maklum + '</td>' +
                                    '<td>' + c.sedang_disemak + '</td><td>' + c.selesai + '</td></tr>');
                            });
                        });
                }
                @endif
            }

            // Run now (echarts is in <head>) and again after SPA navigation.
            document.addEventListener('DOMContentLoaded', initDashboardCharts);
            document.addEventListener('livewire:navigated', initDashboardCharts);
            initDashboardCharts();
        })();
    </script>
</div>
