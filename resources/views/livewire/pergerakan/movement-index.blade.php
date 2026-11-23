<div>
    @if (session('movement-message'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> {{ session('movement-message') }}</div>
    @endif

    <div class="card data-card">
        <div class="card-body">
            {{-- Penapis --}}
            <div class="row g-2 align-items-end mb-3">
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small mb-1">Dari</label>
                    <input type="date" wire:model.live="from" class="form-control form-control-sm">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small mb-1">Hingga</label>
                    <input type="date" wire:model.live="to" class="form-control form-control-sm">
                </div>

                @if ($units->count() > 1)
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small mb-1">Unit</label>
                        <select wire:model.live="unitId" class="form-select form-select-sm">
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}">{{ $u->short_name ?: $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small mb-1">Pegawai</label>
                    <select wire:model.live="officerId" class="form-select form-select-sm">
                        <option value="">Semua pegawai</option>
                        @foreach ($officers as $officer)
                            <option value="{{ $officer->id }}">{{ $officer->display_name }}{{ $officer->is_active ? '' : ' (tidak dipantau)' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small mb-1">Jenis</label>
                    <select wire:model.live="typeId" class="form-select form-select-sm">
                        <option value="">Semua jenis</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small mb-1">Carian</label>
                    <input type="search" wire:model.live.debounce.400ms="search" class="form-control form-control-sm"
                           placeholder="Tajuk, lokasi, catatan">
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input type="checkbox" wire:model.live="mineOnly" class="form-check-input" id="mine-only">
                        <label class="form-check-label small" for="mine-only">Pergerakan saya sahaja</label>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="resetFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> Set semula penapis
                    </button>
                    @if ($rangeLabel)
                        <span class="small text-muted">Tempoh: {{ $rangeLabel }}</span>
                    @endif
                </div>

                @can('pergerakan.create')
                    <button class="btn btn-sm btn-success text-nowrap" wire:click="create">
                        <i class="bi bi-plus-lg"></i> Rekod Pergerakan
                    </button>
                @endcan
            </div>

            @if ($this->hasInvalidRange())
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Tarikh "Dari" selepas tarikh "Hingga". Sila betulkan tempoh.
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:170px">Tarikh</th>
                            <th style="width:200px">Pegawai</th>
                            <th style="width:150px">Jenis</th>
                            <th>Tajuk</th>
                            <th style="width:120px">Masa</th>
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
                                <td class="small">{{ $movement->officer->display_name }}</td>
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
                                            <i class="bi bi-lock"></i>
                                        </span>
                                    @endcannot
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tiada pergerakan sepadan dengan penapis ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="mt-3">{{ $movements->links() }}</div>
            @endif
        </div>
    </div>

    @livewire('pergerakan.movement-form')
</div>
