<div>
    @if (session('roster-message'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> {{ session('roster-message') }}</div>
    @endif

    <div class="card data-card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <p class="text-muted small mb-0" style="max-width:720px">
                    Hanya pegawai dalam senarai ini dipaparkan dalam Jadual Pergerakan. Pegawai yang tidak lagi
                    dipantau hendaklah <strong>dinyahaktifkan</strong> — pergerakan yang telah direkod kekal dalam
                    jadual bulan-bulan lepas. Susunan paparan menentukan urutan baris dalam jadual.
                </p>

                <div class="d-flex align-items-center gap-2">
                    @if ($units->count() > 1)
                        <select wire:model.live="unitId" class="form-select form-select-sm" style="width:auto">
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button class="btn btn-sm btn-success text-nowrap" wire:click="create">
                        <i class="bi bi-person-plus"></i> Tambah Pegawai
                    </button>
                </div>
            </div>

            @if ($unit)
                <p class="small text-muted mb-3">Unit: <span class="fw-semibold">{{ $unit->name }}</span>@if ($unit->short_name) ({{ $unit->short_name }}) @endif</p>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:72px">Bil.</th>
                            <th>Nama Paparan</th>
                            <th>Akaun Pengguna</th>
                            <th style="width:120px">Bil. Pergerakan</th>
                            <th style="width:110px">Status</th>
                            <th class="text-end" style="width:120px">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($officers as $index => $officer)
                            <tr wire:key="officer-{{ $officer->id }}" class="{{ $officer->is_active ? '' : 'text-muted' }}">
                                <td class="fw-semibold">{{ $index + 1 }}</td>
                                <td class="fw-medium">{{ $officer->display_name }}</td>
                                <td class="small text-muted">
                                    {{ $officer->user?->name ?? '—' }}
                                    @if ($officer->user?->username)
                                        <span class="d-block">{{ $officer->user->username }}</span>
                                    @endif
                                </td>
                                <td>{{ $officer->movements_count }}</td>
                                <td>
                                    @if ($officer->is_active)
                                        <span class="badge bg-success">Dipantau</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak Dipantau</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini"
                                       wire:click.prevent="edit({{ $officer->id }})"><i class="bi bi-pencil"></i></a>
                                    @if ($officer->is_active)
                                        <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Nyahaktif"
                                           wire:click.prevent="deactivate({{ $officer->id }})"
                                           wire:confirm="Nyahaktifkan {{ $officer->display_name }} daripada pemantauan? Pergerakan yang telah direkod kekal dalam jadual."><i class="bi bi-slash-circle"></i></a>
                                    @else
                                        <a href="#" class="btn btn-sm btn-outline-success btn-icon" title="Pantau semula"
                                           wire:click.prevent="restore({{ $officer->id }})"><i class="bi bi-arrow-counterclockwise"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada pegawai dalam senarai pemantauan. Klik <strong>Tambah Pegawai</strong> untuk mula.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($showModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editId ? 'Kemaskini Pegawai Dipantau' : 'Tambah Pegawai Dipantau' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if ($editId)
                                <div class="mb-3">
                                    <label class="form-label">Akaun pengguna</label>
                                    <input type="text" class="form-control" value="{{ $officers->firstWhere('id', $editId)?->user?->name }}" disabled>
                                    <div class="form-text">Akaun tidak boleh ditukar. Nyahaktifkan baris ini dan tambah pegawai lain jika perlu.</div>
                                </div>
                            @else
                                <div class="mb-3">
                                    <label class="form-label">Cari pengguna <span class="text-danger">*</span></label>
                                    <input type="text" wire:model.live.debounce.400ms="userSearch" class="form-control"
                                           placeholder="Nama atau username pegawai">
                                    @if (filled($userSearch))
                                        <select wire:model.live="userId" class="form-select mt-2 @error('userId') is-invalid @enderror" size="5">
                                            <option value="">— Pilih pegawai —</option>
                                            @forelse ($userOptions as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->username }})</option>
                                            @empty
                                                <option value="" disabled>Tiada pengguna sepadan / sudah dalam senarai</option>
                                            @endforelse
                                        </select>
                                    @endif
                                    @error('userId') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Nama paparan <span class="text-danger">*</span></label>
                                <input type="text" wire:model="displayName" class="form-control @error('displayName') is-invalid @enderror"
                                       placeholder="Contoh: En. Syarul Emy bin Abu Samah">
                                @error('displayName') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                <div class="form-text">Nama seperti yang mahu dipaparkan dalam jadual, termasuk gelaran En. / Pn.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Susunan paparan <span class="text-danger">*</span></label>
                                <input type="number" min="0" max="999" wire:model="sortOrder"
                                       class="form-control @error('sortOrder') is-invalid @enderror" style="max-width:140px">
                                @error('sortOrder') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                <div class="form-text">Nombor kecil dipaparkan di atas.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" wire:click="closeModal">Batal</button>
                            <button class="btn btn-success" type="submit" wire:loading.attr="disabled">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
