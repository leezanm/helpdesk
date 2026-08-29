<div>
    <div class="card">
        <div class="card-body">
            <p class="card-title-desc">Sila isi butiran tersebut</p>

            <form wire:submit.prevent>
                <div class="row mb-3">
                    <div class="col-sm-2"><label>Bentuk Aduan</label></div>
                    <div class="col">
                        <div class="row">
                            <div class="col-xl-2 col-sm-4">
                                <label class="card-radio-label">
                                    <input type="radio" wire:model.live="is_bp" value="0" class="card-radio-input">
                                    <div class="card-radio">
                                        <i class="fas fa-user font-size-24 text-primary align-middle me-2"></i>
                                        <span>Individu</span>
                                    </div>
                                </label>
                            </div>
                            <div class="col-xl-2 col-sm-4">
                                <label class="card-radio-label">
                                    <input type="radio" wire:model.live="is_bp" value="1" class="card-radio-input">
                                    <div class="card-radio">
                                        <i class="fas fa-user-friends font-size-24 text-primary align-middle me-2"></i>
                                        <span>Wakil</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-md-2 col-form-label">ID Pengadu</label>
                    <div class="col-md-4">
                        <input type="text" class="form-control" value="{{ Auth::user()->username }}" disabled>
                    </div>
                </div>

                @if ($is_bp)
                    <div id="bagi-pihak-detail">
                        <div class="row mb-3">
                            <label class="col-md-2 col-form-label">ID Pengguna <span class="text-danger">*</span></label>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input wire:model="bp_username" type="text" class="form-control @error('bp_username') is-invalid @enderror">
                                    <button type="button" class="btn btn-outline-secondary" wire:click="lookupBpUser">Cari</button>
                                </div>
                                @error('bp_username') <p class="text-danger">{{ $message }}</p> @enderror
                                @if ($bpLookupError) <p class="text-danger">{{ $bpLookupError }}</p> @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-md-2 col-form-label">Nama Penuh</label>
                            <div class="col-md-10">
                                <input wire:model="bp_name" type="text" class="form-control" readonly>
                                @error('bp_name') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-md-2 col-form-label">Email</label>
                            <div class="col-md-4">
                                <input wire:model="bp_email" type="text" class="form-control" readonly>
                                @error('bp_email') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>
                            <label class="col-md-2 col-form-label">Bahagian</label>
                            <div class="col-md-4">
                                <input wire:model="bp_bahagian" type="text" class="form-control" readonly>
                                @error('bp_bahagian') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-3 mb-3 row">
                    <label class="col-md-2 col-form-label">Kategori Aduan <span class="text-danger">*</span></label>
                    <div class="col-md-4">
                        <select wire:model="report_category_id" class="form-select @error('report_category_id') is-invalid @enderror">
                            <option value="">Sila Pilih</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->desc }}</option>
                            @endforeach
                        </select>
                        @error('report_category_id') <p class="text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3 mb-3 row">
                    <label class="col-md-2 col-form-label">Subjek <span class="text-danger">*</span></label>
                    <div class="col-md-10">
                        <input wire:model="subject" class="form-control @error('subject') is-invalid @enderror" type="text">
                        @error('subject') <p class="text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-md-2 col-form-label">Ringkasan Aduan <span class="text-danger">*</span></label>
                    <div class="col-md-10">
                        <textarea wire:model="remark" class="form-control @error('remark') is-invalid @enderror" rows="5" maxlength="500"></textarea>
                        @error('remark') <p class="text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if ($existingAttachments->isNotEmpty())
                    <div class="row mb-3">
                        <label class="col-md-2 col-form-label">Lampiran Sedia Ada</label>
                        <div class="col-md-10">
                            <ul class="list-group">
                                @foreach ($existingAttachments as $att)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" wire:key="att-{{ $att->id }}">
                                        <a href="{{ $att->file_url }}" target="_blank">{{ $att->name }}</a>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="deleteExistingAttachment({{ $att->id }})"
                                                wire:confirm="Padam lampiran ini?">Padam</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="row mb-3">
                    <label class="col-md-2 col-form-label">Muat Naik Laporan / Dokumen</label>
                    <div class="col-md-10">
                        <input type="file" wire:model="attachments" multiple accept=".pdf,.jpeg,.jpg,.png"
                               class="form-control @error('attachments.*') is-invalid @enderror">
                        <div wire:loading wire:target="attachments" class="text-muted small mt-1">Memuat naik...</div>
                        @error('attachments.*') <p class="text-danger">{{ $message }}</p> @enderror

                        @if (count($attachments))
                            <ul class="list-group mt-2">
                                @foreach ($attachments as $i => $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" wire:key="new-att-{{ $i }}">
                                        {{ $file->getClientOriginalName() }}
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeAttachment({{ $i }})">Buang</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 float-end">
                    <button type="button" class="btn btn-secondary waves-effect waves-light"
                            wire:click="save(1)" wire:loading.attr="disabled">Draf</button>
                    <button type="button" class="btn btn-success waves-effect waves-light"
                            wire:click="save(2)" wire:loading.attr="disabled">Hantar</button>
                </div>
            </form>
        </div>
    </div>
</div>
