<div>
    @if (session('report-message'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> {{ session('report-message') }}</div>
    @endif

    <div class="row g-3">
        {{-- ============ LEFT: details, attachments, timeline ============ --}}
        <div class="col-12 col-lg-7">

            {{-- Header card: ticket + status --}}
            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <div class="text-muted small">No. Tiket</div>
                        <h4 class="mb-0 fw-bold">{{ $report->ticket_no ?? '—' }}</h4>
                    </div>
                    <span class="{{ $report->status->class }} fs-6">{{ $report->status->desc }}</span>
                </div>
            </div>

            {{-- Complaint info --}}
            <div class="card mb-3">
                <div class="card-body">
                    <p class="section-title">Maklumat Aduan</p>
                    <div class="info-row"><div class="info-row__label">Subjek</div><div class="info-row__value fw-medium">{{ $report->subject }}</div></div>
                    <div class="info-row"><div class="info-row__label">Kategori</div><div class="info-row__value">{{ $report->category->desc ?? '-' }}</div></div>
                    <div class="info-row"><div class="info-row__label">Dihantar Pada</div><div class="info-row__value">{{ convertDateFormat($report->submitted_at, 'LL') }}</div></div>
                    <div class="info-row"><div class="info-row__label">Aduan</div><div class="info-row__value">{!! $report->remark !!}</div></div>
                </div>
            </div>

            {{-- Applicant --}}
            <div class="card mb-3">
                <div class="card-body">
                    <p class="section-title">Maklumat Pemohon</p>
                    <div class="info-row"><div class="info-row__label">Pemohon</div><div class="info-row__value fw-medium">{{ $report->reportBy->name ?? '-' }}</div></div>
                    <div class="info-row"><div class="info-row__label">Jabatan</div><div class="info-row__value">{{ $report->reportBy->ldap_bahagian ?? '-' }}</div></div>
                    <div class="info-row"><div class="info-row__label">Username</div><div class="info-row__value">{{ $report->reportBy->username ?? '-' }}</div></div>
                    <div class="info-row"><div class="info-row__label">Emel</div><div class="info-row__value">{{ $report->reportBy->email ?? '-' }}</div></div>
                    @if ($report->is_bp)
                        <div class="info-row"><div class="info-row__label">Bagi Pihak</div><div class="info-row__value">{{ $report->bp_name }} <small class="text-muted">({{ $report->bp_username }})</small></div></div>
                    @endif
                </div>
            </div>

            {{-- Attachments --}}
            <div class="card mb-3">
                <div class="card-body">
                    <p class="section-title">Lampiran</p>
                    @forelse ($attachments as $attachment)
                        <a href="{{ $attachment->file_url }}" target="_blank" class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:var(--c-border)!important">
                            <i class="bi bi-paperclip"></i>
                            <span class="flex-fill text-truncate">{{ $attachment->name }}</span>
                            <span class="badge bg-secondary">{{ $attachment->type }}</span>
                        </a>
                    @empty
                        <p class="text-muted mb-0">Tiada lampiran.</p>
                    @endforelse
                </div>
            </div>

            {{-- Timeline --}}
            <div class="card">
                <div class="card-body">
                    <p class="section-title">Kronologi Aduan</p>
                    <ul class="timeline">
                        @foreach ($trails as $trail)
                            <li class="timeline__item">
                                <span class="timeline__dot"></span>
                                <div class="timeline__time mb-1">{{ convertDateFormat($trail->created_at, 'LL, h:mm A') }}</div>
                                <span class="{{ $trail->status->class ?? '' }} mb-1 d-inline-block">{{ $trail->status->desc ?? '' }}</span>
                                <div class="timeline__body">{!! $trail->message !!}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- ============ RIGHT: action panels ============ --}}
        <div class="col-12 col-lg-5">

            {{-- Admin Task --}}
            @if ($canAdmin)
                @if (in_array($report->report_status_id, [3, 4, 6]))
                    <div class="card mb-3">
                        <div class="card-body">
                            <p class="section-title">Tindakan Admin</p>
                            <form wire:submit.prevent="saveAdminTask">
                                <div class="mb-3">
                                    <label class="form-label">Tindakan</label>
                                    <select wire:model.live="tindakan" class="form-select @error('tindakan') is-invalid @enderror">
                                        <option value="">Sila Pilih</option>
                                        @if ($report->report_status_id == 3)
                                            <option value="1">Majukan Kepada</option>
                                        @elseif ($report->report_status_id == 4)
                                            <option value="2">Penugasan Semula</option>
                                            <option value="3">KIV</option>
                                        @elseif ($report->report_status_id == 6)
                                            <option value="4">Tamat KIV</option>
                                        @endif
                                    </select>
                                    @error('tindakan') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>

                                @if (in_array($tindakan, ['1', '2']))
                                    <div class="mb-3">
                                        <label class="form-label">Peranan Pengguna</label>
                                        <select wire:model.live="peranan" class="form-select">
                                            <option value="">Sila Pilih</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->name }}">{{ $role->desc }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if ($peranan)
                                        <div class="mb-3">
                                            <label class="form-label">Kepada</label>
                                            <select wire:model="assigned_to_id" class="form-select @error('assigned_to_id') is-invalid @enderror">
                                                <option value="">Sila Pilih</option>
                                                @foreach ($officers as $officer)
                                                    <option value="{{ $officer->id }}">{{ $officer->name }} ({{ $officer->ldap_bahagian }})</option>
                                                @endforeach
                                            </select>
                                            @error('assigned_to_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                @endif

                                @if ($tindakan === '2')
                                    <div class="mb-3">
                                        <label class="form-label">Sebab Penugasan</label>
                                        <textarea wire:model="reassignment_remark" rows="3" class="form-control"></textarea>
                                    </div>
                                @endif

                                @if ($tindakan === '3')
                                    <div class="mb-3">
                                        <label class="form-label">Tarikh Mula KIV <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('kiv_start_at') is-invalid @enderror" wire:model="kiv_start_at"
                                               min="{{ \Carbon\Carbon::parse($report->submitted_at)->format('Y-m-d') }}">
                                        @error('kiv_start_at') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sebab KIV</label>
                                        <textarea wire:model="kiv_reason" rows="3" class="form-control"></textarea>
                                    </div>
                                @endif

                                @if ($tindakan === '4')
                                    <div class="mb-3">
                                        <label class="form-label">Tarikh Tamat KIV <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('kiv_end_at') is-invalid @enderror" wire:model="kiv_end_at"
                                               min="{{ \Carbon\Carbon::parse($report->kiv_start_at)->format('Y-m-d') }}">
                                        @error('kiv_end_at') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                <div class="text-end">
                                    <button type="submit" class="btn btn-success" wire:loading.attr="disabled"><i class="bi bi-save me-1"></i>Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Change category --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="section-title">Tukar Kategori Aduan</p>
                        <form wire:submit.prevent="setCategory">
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select wire:model="report_category_id" class="form-select">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->desc }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Technical remark --}}
            @if ($canUpdateTask && $report->report_status_id == 4 && optional($report->currentTrail)->assigned_to_id === auth()->id())
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="section-title">Catatan Peg. Teknikal</p>
                        <form wire:submit.prevent="saveTechnicalRemark">
                            <div class="mb-3">
                                <textarea wire:model="technical_remark" rows="5" class="form-control"></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Feedback --}}
            @if (($report->reportBy?->is(auth()->user()) || auth()->user()->hasAnyRole(['superadmin', 'helpdesk.admin'])) && $report->report_status_id == 4)
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="section-title">Soal Selidik</p>
                        <form wire:submit.prevent="saveFeedback">
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <select wire:model="rating" class="form-select @error('rating') is-invalid @enderror">
                                    <option value="">Sila Pilih</option>
                                    <option value="1">1 - Lemah</option>
                                    <option value="2">2 - Kurang berpuas hati</option>
                                    <option value="3">3 - Puas hati</option>
                                    <option value="4">4 - Baik</option>
                                    <option value="5">5 - Cemerlang</option>
                                </select>
                                @error('rating') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Komen</label>
                                <textarea wire:model="feedback" class="form-control @error('feedback') is-invalid @enderror" rows="4" maxlength="255"></textarea>
                                @error('feedback') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
