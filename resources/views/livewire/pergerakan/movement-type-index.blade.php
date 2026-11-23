<div>
    @if (session('type-message'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> {{ session('type-message') }}</div>
    @endif

    {{-- Jenis pergerakan --}}
    <div class="card data-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-1">Jenis Pergerakan</h5>
                    <p class="text-muted small mb-0" style="max-width:720px">
                        Jenis menentukan warna lencana entri dalam jadual. Jenis yang ditanda
                        <strong>Cuti</strong> digunakan untuk memberi amaran apabila satu aktiviti bertindih
                        dengan cuti pegawai yang sama. Jenis tidak boleh dihapus — nyahaktifkannya supaya ia
                        tidak lagi ditawarkan, sementara entri lama kekal tidak berubah.
                    </p>
                </div>
                <button class="btn btn-sm btn-success text-nowrap" wire:click="createType">
                    <i class="bi bi-plus-lg"></i> Tambah Jenis
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:80px">Susunan</th>
                            <th>Nama</th>
                            <th style="width:160px">Paparan</th>
                            <th style="width:90px">Cuti</th>
                            <th style="width:130px">Bil. Pergerakan</th>
                            <th style="width:110px">Status</th>
                            <th class="text-end" style="width:120px">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($types as $type)
                            <tr wire:key="type-{{ $type->id }}" class="{{ $type->is_active ? '' : 'text-muted' }}">
                                <td>{{ $type->sort_order }}</td>
                                <td class="fw-medium">{{ $type->name }}</td>
                                <td><span class="badge {{ $type->class }}">{{ $type->name }}</span></td>
                                <td>
                                    @if ($type->is_leave)
                                        <span class="badge bg-secondary">Cuti</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $type->movements_count }}</td>
                                <td>
                                    @if ($type->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini"
                                       wire:click.prevent="editType({{ $type->id }})"><i class="bi bi-pencil"></i></a>
                                    @if ($type->is_active)
                                        <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Nyahaktif"
                                           wire:click.prevent="deactivateType({{ $type->id }})"
                                           wire:confirm="Nyahaktifkan {{ $type->name }}? Jenis ini tidak lagi boleh dipilih untuk pergerakan baru."><i class="bi bi-slash-circle"></i></a>
                                    @else
                                        <a href="#" class="btn btn-sm btn-outline-success btn-icon" title="Aktifkan"
                                           wire:click.prevent="restoreType({{ $type->id }})"><i class="bi bi-arrow-counterclockwise"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada jenis pergerakan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Cuti umum --}}
    <div class="card data-card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-1">Cuti Umum</h5>
                    <p class="text-muted small mb-0" style="max-width:720px">
                        Cuti umum ditanda sekali di sini dan dipaparkan untuk semua pegawai dalam jadual
                        bulanan — tidak perlu direkod pada setiap pegawai.
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select wire:model.live="year" class="form-select form-select-sm" style="width:auto">
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-success text-nowrap" wire:click="createHoliday">
                        <i class="bi bi-calendar-plus"></i> Tambah Cuti
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama Cuti</th>
                            <th style="width:160px">Tarikh Mula</th>
                            <th style="width:160px">Tarikh Tamat</th>
                            <th>Unit</th>
                            <th class="text-end" style="width:120px">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($holidays as $holiday)
                            <tr wire:key="holiday-{{ $holiday->id }}">
                                <td class="fw-medium">{{ $holiday->name }}</td>
                                <td>{{ $holiday->start_date->format('d/m/Y') }}</td>
                                <td>{{ $holiday->end_date->format('d/m/Y') }}</td>
                                <td class="small text-muted">{{ $holiday->unit?->name ?? 'Semua unit' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini"
                                       wire:click.prevent="editHoliday({{ $holiday->id }})"><i class="bi bi-pencil"></i></a>
                                    <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Hapus"
                                       wire:click.prevent="deleteHoliday({{ $holiday->id }})"
                                       wire:confirm="Hapus {{ $holiday->name }} daripada senarai cuti umum?"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tiada cuti umum direkod untuk tahun {{ $year }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal jenis --}}
    @if ($showTypeModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="saveType">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $typeId ? 'Kemaskini Jenis Pergerakan' : 'Tambah Jenis Pergerakan' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeTypeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" wire:model="typeName" class="form-control @error('typeName') is-invalid @enderror"
                                       placeholder="Contoh: Mesyuarat">
                                @error('typeName') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Warna Paparan <span class="text-danger">*</span></label>
                                <select wire:model.live="typeClass" class="form-select @error('typeClass') is-invalid @enderror">
                                    @foreach ($badgeClasses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('typeClass') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                <div class="mt-2"><span class="badge {{ $typeClass }}">{{ $typeName !== '' ? $typeName : 'Contoh' }}</span></div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" wire:model="typeIsLeave" class="form-check-input" id="type-is-leave">
                                <label class="form-check-label" for="type-is-leave">Jenis ini adalah cuti</label>
                                <div class="form-text">Digunakan untuk amaran apabila aktiviti bertindih dengan cuti.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Susunan paparan <span class="text-danger">*</span></label>
                                <input type="number" min="0" max="999" wire:model="typeSortOrder"
                                       class="form-control @error('typeSortOrder') is-invalid @enderror" style="max-width:140px">
                                @error('typeSortOrder') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" wire:click="closeTypeModal">Batal</button>
                            <button class="btn btn-success" type="submit" wire:loading.attr="disabled">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Modal cuti umum --}}
    @if ($showHolidayModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="saveHoliday">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $holidayId ? 'Kemaskini Cuti Umum' : 'Tambah Cuti Umum' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeHolidayModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama cuti <span class="text-danger">*</span></label>
                                <input type="text" wire:model="holidayName" class="form-control @error('holidayName') is-invalid @enderror"
                                       placeholder="Contoh: Deepavali">
                                @error('holidayName') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Tarikh mula <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="holidayStart" class="form-control @error('holidayStart') is-invalid @enderror">
                                    @error('holidayStart') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Tarikh tamat <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="holidayEnd" class="form-control @error('holidayEnd') is-invalid @enderror">
                                    @error('holidayEnd') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                    <div class="form-text">Sama dengan tarikh mula untuk cuti sehari.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Unit</label>
                                <select wire:model="holidayUnitId" class="form-select">
                                    <option value="">Semua unit</option>
                                    @foreach ($units as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilih unit hanya jika cuti itu khusus untuk unit tersebut.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" wire:click="closeHolidayModal">Batal</button>
                            <button class="btn btn-success" type="submit" wire:loading.attr="disabled">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
