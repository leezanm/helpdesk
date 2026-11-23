<div>
    @if ($showModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editId ? 'Kemaskini Pergerakan' : 'Rekod Pergerakan' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if ($overlapWarning)
                                <div class="alert alert-warning d-flex align-items-start gap-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>{{ $overlapWarning }}</span>
                                </div>
                            @endif

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Pegawai <span class="text-danger">*</span></label>
                                    @if ($editId)
                                        <input type="text" class="form-control"
                                               value="{{ $officers->firstWhere('id', $monitoredOfficerId)?->display_name }}" disabled>
                                        <div class="form-text">Pegawai tidak boleh ditukar. Hapus entri ini dan catat yang baharu jika perlu.</div>
                                    @else
                                        <select wire:model.live="monitoredOfficerId" class="form-select @error('monitoredOfficerId') is-invalid @enderror">
                                            <option value="">— Pilih pegawai —</option>
                                            @foreach ($officers as $officer)
                                                <option value="{{ $officer->id }}">{{ $officer->display_name }}</option>
                                            @endforeach
                                        </select>
                                        @error('monitoredOfficerId') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Jenis pergerakan <span class="text-danger">*</span></label>
                                    <select wire:model.live="movementTypeId" class="form-select @error('movementTypeId') is-invalid @enderror">
                                        <option value="">— Pilih jenis —</option>
                                        @foreach ($types as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}{{ $type->is_active ? '' : ' (tidak aktif)' }}</option>
                                        @endforeach
                                    </select>
                                    @error('movementTypeId') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Tajuk <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="title" class="form-control @error('title') is-invalid @enderror"
                                           placeholder="Contoh: Perbincangan Sistem Helpdesk 2.0">
                                    @error('title') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label">Tarikh mula <span class="text-danger">*</span></label>
                                    <input type="date" wire:model.live="startDate" class="form-control @error('startDate') is-invalid @enderror">
                                    @error('startDate') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label">Tarikh tamat <span class="text-danger">*</span></label>
                                    <input type="date" wire:model.live="endDate" class="form-control @error('endDate') is-invalid @enderror">
                                    @error('endDate') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                    <div class="form-text">Sama dengan tarikh mula untuk aktiviti sehari.</div>
                                </div>

                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label">Masa mula</label>
                                    <input type="time" wire:model="startTime" class="form-control @error('startTime') is-invalid @enderror">
                                    @error('startTime') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label">Masa tamat</label>
                                    <input type="time" wire:model="endTime" class="form-control @error('endTime') is-invalid @enderror">
                                    @error('endTime') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                    <div class="form-text">Biarkan kosong jika sepanjang hari.</div>
                                </div>

                                @unless ($this->selectedTypeIsLeave())
                                    <div class="col-md-5">
                                        <label class="form-label">Lokasi aktiviti <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3 pt-1">
                                            <div class="form-check">
                                                <input type="radio" wire:model.live="isOutside" value="0"
                                                       class="form-check-input" id="loc-inside">
                                                <label class="form-check-label small" for="loc-inside">Dalam pejabat</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" wire:model.live="isOutside" value="1"
                                                       class="form-check-input" id="loc-outside">
                                                <label class="form-check-label small" for="loc-outside">Luar pejabat</label>
                                            </div>
                                        </div>
                                        <div class="form-text">
                                            Menentukan sama ada pegawai masih boleh dicari di pejabat pada hari itu.
                                        </div>
                                    </div>
                                @endunless

                                <div class="{{ $this->selectedTypeIsLeave() ? 'col-12' : 'col-md-7' }}">
                                    <label class="form-label">Tempat</label>
                                    <input type="text" wire:model="location" class="form-control @error('location') is-invalid @enderror"
                                           placeholder="Contoh: Bilik Mesyuarat Digitalis, Aras 11">
                                    @error('location') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Catatan</label>
                                    <textarea wire:model="remarks" rows="2" class="form-control @error('remarks') is-invalid @enderror"
                                              placeholder="Maklumat tambahan jika ada"></textarea>
                                    @error('remarks') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" wire:click="closeModal">Batal</button>
                            <button class="btn btn-success" type="submit" wire:loading.attr="disabled">
                                {{ $overlapWarning ? 'Simpan (teruskan)' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
