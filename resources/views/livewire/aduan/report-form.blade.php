<div>
    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-4">Sila isi butiran aduan di bawah.</p>

            <form wire:submit.prevent>
                {{-- Bentuk Aduan --}}
                <div class="mb-4">
                    <label class="form-label">Bentuk Aduan</label>
                    <div class="d-flex flex-wrap gap-2">
                        <label class="flex-fill" style="max-width:220px">
                            <input type="radio" wire:model.live="is_bp" value="0" class="btn-check" id="bp-0">
                            <span class="btn btn-outline-primary w-100 text-start"><i class="bi bi-person me-2"></i> Individu</span>
                        </label>
                        <label class="flex-fill" style="max-width:220px">
                            <input type="radio" wire:model.live="is_bp" value="1" class="btn-check" id="bp-1">
                            <span class="btn btn-outline-primary w-100 text-start"><i class="bi bi-people me-2"></i> Wakil</span>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ID Pengadu</label>
                    <input type="text" class="form-control" value="{{ Auth::user()->username }}" disabled>
                </div>

                @if ($is_bp)
                    <div class="border rounded-3 p-3 mb-3" style="background:var(--c-surface-2);border-color:var(--c-border)!important">
                        <div class="mb-3">
                            <label class="form-label">ID Pengguna <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input wire:model="bp_username" type="text" class="form-control @error('bp_username') is-invalid @enderror">
                                <button type="button" class="btn btn-primary" wire:click="lookupBpUser"><i class="bi bi-search me-1"></i>Cari</button>
                            </div>
                            @error('bp_username') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            @if ($bpLookupError) <p class="text-danger small mt-1">{{ $bpLookupError }}</p> @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Penuh</label>
                            <input wire:model="bp_name" type="text" class="form-control" readonly>
                            @error('bp_name') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emel</label>
                                <input wire:model="bp_email" type="text" class="form-control" readonly>
                                @error('bp_email') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bahagian</label>
                                <input wire:model="bp_bahagian" type="text" class="form-control" readonly>
                                @error('bp_bahagian') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori Aduan <span class="text-danger">*</span></label>
                        <select wire:model="report_category_id" class="form-select @error('report_category_id') is-invalid @enderror">
                            <option value="">Sila Pilih</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->desc }}</option>
                            @endforeach
                        </select>
                        @error('report_category_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subjek <span class="text-danger">*</span></label>
                    <input wire:model="subject" class="form-control @error('subject') is-invalid @enderror" type="text">
                    @error('subject') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Ringkasan Aduan <span class="text-danger">*</span></label>
                    <textarea wire:model="remark" class="form-control @error('remark') is-invalid @enderror" rows="5" maxlength="500"></textarea>
                    @error('remark') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                @if ($existingAttachments->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">Lampiran Sedia Ada</label>
                        <div class="list-group">
                            @foreach ($existingAttachments as $att)
                                <div class="list-group-item d-flex justify-content-between align-items-center" wire:key="att-{{ $att->id }}">
                                    <a href="{{ $att->file_url }}" target="_blank"><i class="bi bi-paperclip me-1"></i>{{ $att->name }}</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon"
                                            wire:click="deleteExistingAttachment({{ $att->id }})" wire:confirm="Padam lampiran ini?"><i class="bi bi-trash"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="form-label">Muat Naik Laporan / Dokumen</label>
                    <input type="file" wire:model="attachments" multiple accept=".pdf,.jpeg,.jpg,.png"
                           class="form-control @error('attachments.*') is-invalid @enderror">
                    <div wire:loading wire:target="attachments" class="text-muted small mt-1"><i class="bi bi-hourglass-split me-1"></i>Memuat naik...</div>
                    @error('attachments.*') <p class="text-danger small mt-1">{{ $message }}</p> @enderror

                    @if (count($attachments))
                        <div class="list-group mt-2">
                            @foreach ($attachments as $i => $file)
                                <div class="list-group-item d-flex justify-content-between align-items-center" wire:key="new-att-{{ $i }}">
                                    <span><i class="bi bi-file-earmark me-1"></i>{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" wire:click="removeAttachment({{ $i }})"><i class="bi bi-x-lg"></i></button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <button type="button" class="btn btn-secondary" wire:click="save(1)" wire:loading.attr="disabled">
                        <i class="bi bi-file-earmark me-1"></i>Simpan Draf
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="save(2)" wire:loading.attr="disabled">
                        <i class="bi bi-send me-1"></i>Hantar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
