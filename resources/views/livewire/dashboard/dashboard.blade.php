<div>
    {{-- ===== Stat cards ===== --}}
    <div class="row g-3 mb-1">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon stat-icon--primary me-3"><i class="bx bx-list-ul"></i></div>
                    <div>
                        <p class="text-muted mb-1 small">Jumlah Aduan</p>
                        <h3 class="mb-0 fw-bold">{{ $total }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon stat-icon--success me-3"><i class="bx bx-list-check"></i></div>
                    <div>
                        <p class="text-muted mb-1 small">Rekod Selesai</p>
                        <h3 class="mb-0 fw-bold">{{ $resolved }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon stat-icon--warning me-3"><i class="bx bx-transfer"></i></div>
                    <div>
                        <p class="text-muted mb-1 small">Dalam Tindakan</p>
                        <h3 class="mb-0 fw-bold">{{ $inProgress }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Quick actions ===== --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ url('/aduan/create') }}" class="btn btn-primary">
            <i class="bx bxs-plus-square align-middle me-1"></i> Cipta Aduan
        </a>
        @if ($canTechnical)
            <a href="{{ url('/aduan/untuk-tindakan') }}" class="btn btn-outline-primary">
                <i class="bx bx-task align-middle me-1"></i> Untuk Tindakan
            </a>
        @endif
        @if ($canAdmin)
            <a href="{{ url('/aduan/dihantar') }}" class="btn btn-outline-primary">
                <i class="bx bx-mail-send align-middle me-1"></i> Aduan Dihantar
            </a>
        @endif
    </div>

    {{-- ===== Recent activity ===== --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">{{ $listTitle }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tiket</th>
                                    <th>Subjek</th>
                                    <th>Tarikh Aduan</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th class="text-end">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr wire:key="dash-report-{{ $report->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $report->ticket_no }}</td>
                                        <td>{{ $report->subject }}</td>
                                        <td>{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                        <td>{!! getDuration($report) !!}</td>
                                        <td><span class="{{ $report->status->class }} font-size-11">{{ $report->status->desc }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('aduan.show', $report->id) }}" class="btn btn-primary btn-sm">Lihat</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Tiada aduan buat masa ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Charts (dashboard.viewGraph) ===== --}}
    @if ($canGraph)
        <div class="row g-3">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0">Aduan Mengikut Kategori (Minggu Ini)</h6></div>
                    <div class="card-body">
                        <div id="sektor" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0">Aduan Vs Aduan Selesai (Minggu Ini)</h6></div>
                    <div class="card-body">
                        <div id="mix-line-bar" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Pecahan Aduan Mengikut Kategori Dan Status (Minggu Ini)</h6></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 table-nowrap">
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

        @script
        <script>
            // Theme palette pulled from CSS tokens so charts match the UI.
            const css = getComputedStyle(document.documentElement);
            const primary = (css.getPropertyValue('--app-primary') || '#0f6e8c').trim();
            const success = (css.getPropertyValue('--app-success') || '#17976a').trim();
            const palette = [primary, success, (css.getPropertyValue('--app-accent') || '#f0a400').trim(),
                             '#5b8def', '#9b6dff', '#e6559b'];

            // Pie: reports by category
            const pieChart = echarts.init(document.getElementById('sektor'));
            pieChart.setOption({
                color: palette,
                tooltip: { trigger: 'item' },
                series: [{ type: 'pie', radius: ['45%', '70%'], data: [],
                    emphasis: { itemStyle: { shadowBlur: 10, shadowColor: 'rgba(0,0,0,0.2)' } } }]
            });
            fetch('{{ route('api.dashboard.pie-chart') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})
                .then(r => r.json()).then(res => { if (res.success) pieChart.setOption({ series:[{ data: res.data }] }); });

            // Bar: sent vs completed this week
            const barChart = echarts.init(document.getElementById('mix-line-bar'));
            barChart.setOption({
                color: [primary, success],
                tooltip: { trigger: 'axis' },
                legend: { data: ['Jumlah Aduan', 'Aduan Selesai'] },
                grid: { left: 40, right: 20, top: 40, bottom: 30 },
                xAxis: [{ type: 'category', data: ['Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu','Ahad'] }],
                yAxis: [{ type: 'value', minInterval: 1 }],
                series: [{ name:'Jumlah Aduan', type:'bar', data: [] }, { name:'Aduan Selesai', type:'bar', data: [] }]
            });
            fetch('{{ route('api.dashboard.bar-chart') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})
                .then(r => r.json()).then(res => { if (res.success) barChart.setOption({ series:[{ data: res.data.sent }, { data: res.data.completed }] }); });

            // Category-by-status table
            fetch('{{ route('api.dashboard.category-table') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})
                .then(r => r.json()).then(res => {
                    if (!res.success) return;
                    const body = document.getElementById('tbody-category_by_status');
                    body.innerHTML = '';
                    res.category_by_status.forEach(c => {
                        body.insertAdjacentHTML('beforeend',
                            `<tr><td class="text-start"><div class="d-flex align-items-center"><img src="${c.image_path}" class="avatar-sm h-auto d-block rounded me-3" alt=""><span class="fw-medium">${c.desc}</span></div></td>`+
                            `<td>${c.dihantar}</td><td>${c.dihantar_ambil_maklum}</td><td>${c.sedang_disemak}</td><td>${c.selesai}</td></tr>`);
                    });
                });

            window.addEventListener('resize', () => { pieChart.resize(); barChart.resize(); });
        </script>
        @endscript
    @endif
</div>
