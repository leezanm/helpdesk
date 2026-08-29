<div>
    {{-- ===== Filters (all on one page) ===== --}}
    <x-filter-bar title="Penapis Laporan" :show-reset="true">
        <x-filter-field label="Dari Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateFrom" class="form-control">
        </x-filter-field>

        <x-filter-field label="Hingga Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateTo" class="form-control">
        </x-filter-field>

        <x-filter-field label="Bahagian" col="col-12 col-md-3">
            <select wire:model.live="bahagian" class="form-select">
                <option value="">Semua Bahagian</option>
                @foreach ($bahagianOptions as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Status Tindakan" col="col-6 col-md-3">
            <select wire:model.live="status" class="form-select">
                <option value="">Semua Status</option>
                @foreach ($statusOptions as $s)
                    <option value="{{ $s->id }}">{{ $s->desc }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Pegawai Bertanggungjawab" col="col-12 col-md-5">
            <select wire:model.live="pegawai" class="form-select">
                <option value="">Semua Pegawai</option>
                @foreach ($pegawaiOptions as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Rating / Maklum Balas" col="col-12 col-md-4">
            <select wire:model.live="rating" class="form-select">
                <option value="">Semua Rating</option>
                <option value="5">⭐⭐⭐⭐⭐ — Cemerlang</option>
                <option value="4">⭐⭐⭐⭐ — Baik</option>
                <option value="3">⭐⭐⭐ — Puas Hati</option>
                <option value="2">⭐⭐ — Kurang Berpuas Hati</option>
                <option value="1">⭐ — Lemah</option>
            </select>
        </x-filter-field>
    </x-filter-bar>

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
                            <th>Rating</th>
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
                                <td class="text-nowrap">
                                    @if ($report->rating)
                                        <span title="{{ $report->feedback ?? '' }}">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star{{ $i <= $report->rating ? '-fill' : '' }}" style="color:#f59e0b;font-size:.8rem;"></i>
                                            @endfor
                                        </span>
                                        @if ($report->feedback)
                                            <br><small class="text-muted">{{ Str::limit($report->feedback, 30) }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('aduan.show', $report) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Lihat"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><div class="empty-state"><i class="bi bi-search"></i> Tiada aduan sepadan dengan penapis.</div></td></tr>
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
