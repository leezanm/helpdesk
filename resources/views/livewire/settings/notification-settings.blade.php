<div>
    @if (session('settings-message'))
        <div class="alert alert-success">{{ session('settings-message') }}</div>
    @endif

    <div class="alert alert-light border small d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle text-primary"></i>
        <span>Tetapkan penerima emel bagi setiap peristiwa aduan. Penerima boleh gabungan peranan, pihak berkaitan aduan (pengadu / pegawai bertanggungjawab), dan alamat emel tambahan.</span>
    </div>

    <form wire:submit="save">
        @foreach ($rows as $i => $row)
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ $row['label'] }}</h6>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="enabled-{{ $i }}" wire:model="rows.{{ $i }}.enabled">
                        <label class="form-check-label small" for="enabled-{{ $i }}">
                            {{ $row['enabled'] ? 'Aktif' : 'Dimatikan' }}
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-semibold">Peranan Penerima</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($allRoles as $role)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               id="role-{{ $i }}-{{ $role->name }}"
                                               value="{{ $role->name }}"
                                               wire:model="rows.{{ $i }}.roles">
                                        <label class="form-check-label" for="role-{{ $i }}-{{ $role->name }}">{{ $role->desc }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-semibold">Pihak Berkaitan Aduan</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rep-{{ $i }}"
                                       wire:model="rows.{{ $i }}.include_reporter">
                                <label class="form-check-label" for="rep-{{ $i }}">Pengadu (pemilik aduan)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="off-{{ $i }}"
                                       wire:model="rows.{{ $i }}.include_assigned_officer">
                                <label class="form-check-label" for="off-{{ $i }}">Pegawai Bertanggungjawab (semasa ditugaskan)</label>
                            </div>

                            <label class="form-label small fw-semibold mt-3">Emel Tambahan</label>
                            <textarea class="form-control" rows="2"
                                      placeholder="cth: unitict@rurallink.gov.my, penyelia@rurallink.gov.my"
                                      wire:model="rows.{{ $i }}.extra_emails_text"></textarea>
                            <small class="text-muted">Asingkan dengan koma. Alamat tidak sah akan diabaikan.</small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <i class="bi bi-check2 me-1"></i> Simpan Tetapan
            </button>
        </div>
    </form>
</div>
