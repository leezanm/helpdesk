<div>
    @if (session('risk-level-message'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> {{ session('risk-level-message') }}</div>
    @endif

    <div class="card data-card">
        <div class="card-body">
            <p class="text-muted small mb-3">
                Tahap risiko ditetapkan oleh pegawai helpdesk bagi setiap aduan. Tahap 1 hingga 5 adalah tetap;
                nama, kriteria dan warna paparan boleh dikemaskini. Tahap yang dinyahaktifkan tidak lagi boleh
                dipilih untuk aduan baru, tetapi aduan lama yang menggunakannya kekal tidak berubah.
            </p>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:72px">Tahap</th>
                            <th>Nama</th>
                            <th>Kriteria</th>
                            <th style="width:140px">Paparan Badge</th>
                            <th style="width:110px">Bil. Aduan</th>
                            <th style="width:100px">Status</th>
                            <th class="text-end" style="width:120px">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riskLevels as $riskLevel)
                            <tr wire:key="risk-level-{{ $riskLevel->id }}">
                                <td class="fw-semibold">{{ $riskLevel->level }}</td>
                                <td class="fw-medium">{{ $riskLevel->desc }}</td>
                                <td class="text-muted small">{{ $riskLevel->reference }}</td>
                                <td><span class="{{ $riskLevel->class }}">{{ $riskLevel->label }}</span></td>
                                <td>{{ $riskLevel->reports_count }}</td>
                                <td>
                                    @if ($riskLevel->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini"
                                       wire:click.prevent="edit({{ $riskLevel->id }})"><i class="bi bi-pencil"></i></a>
                                    @if ($riskLevel->is_active)
                                        <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Nyahaktif"
                                           wire:click.prevent="deactivate({{ $riskLevel->id }})"
                                           wire:confirm="Nyahaktifkan {{ $riskLevel->label }}? Tahap ini tidak lagi boleh dipilih untuk aduan baru."><i class="bi bi-slash-circle"></i></a>
                                    @else
                                        <a href="#" class="btn btn-sm btn-outline-success btn-icon" title="Aktifkan"
                                           wire:click.prevent="restore({{ $riskLevel->id }})"><i class="bi bi-arrow-counterclockwise"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($showModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">Kemaskini Tahap Risiko</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Tahap (tetap oleh sistem)</label>
                                <input type="text" class="form-control" value="Tahap {{ $level }}" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" wire:model="desc" class="form-control @error('desc') is-invalid @enderror">
                                @error('desc') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kriteria / Keterangan <span class="text-danger">*</span></label>
                                <textarea wire:model="reference" rows="5" class="form-control @error('reference') is-invalid @enderror"></textarea>
                                @error('reference') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Warna Paparan <span class="text-danger">*</span></label>
                                <select wire:model.live="class" class="form-select @error('class') is-invalid @enderror">
                                    @foreach ($badgeClasses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('class') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                <div class="mt-2"><span class="{{ $class }}">Tahap {{ $level }}: {{ $desc }}</span></div>
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
