<div>
    @if (session('movement-message'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> {{ session('movement-message') }}</div>
    @endif

    @unless ($isMonitored)
        <div class="alert alert-info d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill"></i>
            <span>
                Nama anda tiada dalam senarai pemantauan pergerakan. Hubungi pentadbir helpdesk jika
                anda sepatutnya dipantau.
            </span>
        </div>
    @endunless

    <div class="card data-card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary btn-icon" wire:click="previousMonth" title="Bulan sebelum">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <h5 class="card-title mb-0">{{ $monthLabel }}</h5>
                    <button class="btn btn-sm btn-outline-secondary btn-icon" wire:click="nextMonth" title="Bulan seterusnya">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                @if ($isMonitored)
                    <button class="btn btn-sm btn-success text-nowrap" wire:click="create">
                        <i class="bi bi-plus-lg"></i> Rekod Pergerakan
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:190px">Tarikh</th>
                            <th style="width:150px">Jenis</th>
                            <th>Tajuk</th>
                            <th style="width:130px">Masa</th>
                            <th>Lokasi</th>
                            <th class="text-end" style="width:120px">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr wire:key="movement-{{ $movement->id }}">
                                <td class="text-nowrap">
                                    {{ $movement->start_date->format('d/m/Y') }}
                                    @if ($movement->isMultiDay())
                                        <span class="d-block small text-muted">hingga {{ $movement->end_date->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $movement->type->class }}">{{ $movement->type->name }}</span></td>
                                <td class="fw-medium">
                                    {{ $movement->title }}
                                    @if ($movement->remarks)
                                        <span class="d-block small text-muted">{{ $movement->remarks }}</span>
                                    @endif
                                </td>
                                <td class="small text-nowrap">
                                    @if ($movement->start_time)
                                        {{ substr($movement->start_time, 0, 5) }}@if ($movement->end_time) – {{ substr($movement->end_time, 0, 5) }}@endif
                                    @else
                                        <span class="text-muted">Sepanjang hari</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    @if ($movement->is_outside)
                                        <span class="badge bg-primary mb-1">Luar pejabat</span>
                                    @endif
                                    {{ $movement->location ?: ($movement->is_outside ? '' : '—') }}
                                </td>
                                <td class="text-end text-nowrap">
                                    @can('update', $movement)
                                        <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini"
                                           wire:click.prevent="edit({{ $movement->id }})"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('delete', $movement)
                                        <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Hapus"
                                           wire:click.prevent="delete({{ $movement->id }})"
                                           wire:confirm="Hapus pergerakan &quot;{{ $movement->title }}&quot;?"><i class="bi bi-trash"></i></a>
                                    @endcan
                                    @cannot('update', $movement)
                                        <span class="badge bg-light text-dark" title="Tarikh sudah sampai — rujuk pentadbir helpdesk untuk kemaskini">
                                            <i class="bi bi-lock"></i> Terkunci
                                        </span>
                                    @endcannot
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Tiada pergerakan direkod untuk {{ $monthLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-muted small mb-0 mt-3">
                Entri boleh dikemaskini atau dihapus selagi tarikhnya belum sampai. Selepas tarikh mula,
                entri terkunci dan hanya pentadbir helpdesk boleh mengubahnya.
            </p>
        </div>
    </div>

    @livewire('pergerakan.movement-form')
</div>
