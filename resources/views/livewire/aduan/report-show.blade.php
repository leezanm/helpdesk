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
                    <div class="info-row">
                        <div class="info-row__label">Tahap Risiko</div>
                        <div class="info-row__value">
                            @if ($report->riskLevel)
                                <span class="{{ $report->riskLevel->class }}">{{ $report->riskLevel->label }}</span>
                            @else
                                <span class="badge bg-light text-dark">Belum Ditetapkan</span>
                            @endif
                        </div>
                    </div>
                    <div class="info-row"><div class="info-row__label">Dihantar Pada</div><div class="info-row__value">{{ convertDateFormat($report->submitted_at, 'LL') }}</div></div>
                    <div class="info-row"><div class="info-row__label">Aduan</div><div class="info-row__value">{!! nl2br(e($report->remark)) !!}</div></div>
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
                    <div class="info-row"><div class="info-row__label">No. Telefon</div><div class="info-row__value">{{ $report->reportBy?->tel_no ?: '-' }}</div></div>
                    @if ($report->is_bp)
                        <div class="info-row"><div class="info-row__label">Direkod Oleh (Wakil)</div><div class="info-row__value">{{ $report->bp_name }} <small class="text-muted">({{ $report->bp_username }})</small>@if ($report->bp_tel_no) <small class="text-muted">&middot; {{ $report->bp_tel_no }}</small>@endif</div></div>
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

            {{-- ===== Tahap Risiko (helpdesk) ===== --}}
            {{-- Wajib ditetapkan sebelum aduan boleh dimajukan kepada pegawai. --}}
            @if ($canAdmin)
                <div class="card mb-3 {{ $this->needsRiskLevel ? 'border-danger' : '' }}">
                    <div class="card-body">
                        <p class="section-title">Tahap Risiko Aduan</p>

                        @if ($this->needsRiskLevel)
                            <div class="alert alert-warning d-flex align-items-center gap-2 py-2">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span class="small">Tahap risiko wajib ditetapkan sebelum aduan boleh dimajukan kepada pegawai.</span>
                            </div>
                        @endif

                        <form wire:submit.prevent="setRiskLevel">
                            <div class="mb-2">
                                <select wire:model.live="risk_level_id" class="form-select @error('risk_level_id') is-invalid @enderror">
                                    <option value="">Sila Pilih Tahap Risiko</option>
                                    @foreach ($riskLevels as $riskLevel)
                                        <option value="{{ $riskLevel->id }}">{{ $riskLevel->label }}</option>
                                    @endforeach
                                </select>
                                @error('risk_level_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Kriteria tahap yang sedang dipilih, untuk rujukan pegawai --}}
                            @php($selectedRisk = $riskLevels->firstWhere('id', (int) $risk_level_id))
                            @if ($selectedRisk)
                                <p class="text-muted small mb-3">{{ $selectedRisk->reference }}</p>
                            @endif

                            <div class="text-end">
                                <button type="submit" class="btn btn-outline-primary btn-sm" wire:loading.attr="disabled"><i class="bi bi-save me-1"></i>Simpan Tahap Risiko</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ===== Tindakan Admin / Helpdesk ===== --}}
            {{-- Aduan Disemak(3): majukan; Dalam Tindakan(4): tugas semula/KIV;
                 Tindakan Lanjut(8): KIV atau tugas semula; KIV(6): tamat KIV --}}
            @if ($canAdmin && in_array($report->report_status_id, [3, 4, 6, 8]))
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="section-title">Tindakan Helpdesk</p>
                        <form wire:submit.prevent="saveAdminTask">
                            <div class="mb-3">
                                <label class="form-label">Tindakan</label>
                                <select wire:model.live="tindakan" class="form-select @error('tindakan') is-invalid @enderror">
                                    <option value="">Sila Pilih</option>
                                    @if ($report->report_status_id == 3)
                                        <option value="1">Majukan Kepada Pegawai</option>
                                    @elseif ($report->report_status_id == 4)
                                        <option value="2">Penugasan Semula</option>
                                        <option value="3">KIV</option>
                                    @elseif ($report->report_status_id == 8)
                                        <option value="2">Penugasan Semula (Pegawai Lain)</option>
                                        <option value="3">KIV</option>
                                    @elseif ($report->report_status_id == 6)
                                        <option value="4">Tamat KIV</option>
                                    @endif
                                </select>
                                @error('tindakan') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>

                            @if (in_array($tindakan, ['1', '2']))
                                <div class="mb-3">
                                    <label class="form-label">Peranan Pegawai</label>
                                    <select wire:model.live="peranan" class="form-select">
                                        <option value="">Sila Pilih</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($peranan)
                                    <div class="mb-3">
                                        <label class="form-label">Tugaskan Kepada</label>
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
                                    <label class="form-label">Sebab Penugasan Semula</label>
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
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"><i class="bi bi-save me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ===== Tindakan Pegawai Bertanggungjawab (Dalam Tindakan = 4) ===== --}}
            @if ($canUpdateTask && $report->report_status_id == 4 && $this->isAssignedOfficer)
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="section-title">Tindakan Pegawai</p>
                        <form wire:submit.prevent="saveOfficerAction">
                            <div class="mb-3">
                                <label class="form-label">Hasil Tindakan <span class="text-danger">*</span></label>
                                <select wire:model.live="outcome" class="form-select @error('outcome') is-invalid @enderror">
                                    <option value="">Sila Pilih</option>
                                    <option value="selesai">Tindakan Selesai</option>
                                    <option value="lanjut">Tindakan Lanjut Diperlukan</option>
                                </select>
                                @error('outcome') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>

                            @if ($outcome)
                                <div class="mb-3">
                                    <label class="form-label">Punca Masalah <span class="text-danger">*</span></label>
                                    <textarea wire:model="punca_masalah" rows="3" class="form-control @error('punca_masalah') is-invalid @enderror"
                                              placeholder="Apakah punca sebenar masalah ini?"></textarea>
                                    @error('punca_masalah') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            @if ($outcome === 'selesai')
                                <div class="mb-3">
                                    <label class="form-label">Langkah Penyelesaian <span class="text-danger">*</span></label>
                                    <textarea wire:model="langkah_tindakan" rows="3" class="form-control @error('langkah_tindakan') is-invalid @enderror"
                                              placeholder="Apakah langkah/tindakan yang diambil untuk selesaikan?"></textarea>
                                    @error('langkah_tindakan') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catatan Teknikal Tambahan</label>
                                    <textarea wire:model="catatan_teknikal" rows="2" class="form-control"
                                              placeholder="Rujukan / konteks tambahan (pilihan)"></textarea>
                                </div>
                            @elseif ($outcome === 'lanjut')
                                <div class="mb-3">
                                    <label class="form-label">Sebab Perlu Tindakan Lanjut <span class="text-danger">*</span></label>
                                    <textarea wire:model="kenapa_lanjut" rows="3" class="form-control @error('kenapa_lanjut') is-invalid @enderror"
                                              placeholder="Kenapa aduan ini tidak dapat diselesaikan?"></textarea>
                                    @error('kenapa_lanjut') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"><i class="bi bi-save me-1"></i>Rekod Tindakan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ===== Tutup Aduan (Selesai Tindakan = 7) — helpdesk atau pengguna ===== --}}
            @if (($report->reportBy?->is(auth()->user()) || auth()->user()->hasAnyRole(['superadmin', 'penyelia', 'helpdesk.admin'])) && $report->report_status_id == 7)
                <div class="card mb-3" style="border:1px solid var(--c-success)">
                    <div class="card-body">
                        <p class="section-title" style="color:var(--c-success)">Tutup Aduan (Selesai)</p>
                        <p class="text-muted small">Pegawai telah menyelesaikan tindakan. Sila sahkan untuk menutup aduan dan beri penilaian.</p>
                        <form wire:submit.prevent="closeReport">
                            <div class="mb-3">
                                <label class="form-label">Rating <span class="text-danger">*</span></label>
                                {{-- Interactive star picker (default 5) --}}
                                <div class="fs-4" x-data="{ r: @entangle('rating') }">
                                    <template x-for="i in 5" :key="i">
                                        <i class="bi" :class="i <= r ? 'bi-star-fill' : 'bi-star'"
                                           style="color:var(--c-butter,#e0ad46);cursor:pointer" x-on:click="r = i"></i>
                                    </template>
                                </div>
                                @error('rating') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Komen / Maklum Balas</label>
                                <textarea wire:model="feedback" class="form-control @error('feedback') is-invalid @enderror" rows="3" maxlength="255"></textarea>
                                @error('feedback') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success" wire:loading.attr="disabled"><i class="bi bi-check2-circle me-1"></i>Tutup & Tanda Selesai</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ===== Tukar kategori (helpdesk) ===== --}}
            @if ($canAdmin)
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="section-title">Tukar Kategori Aduan</p>
                        <form wire:submit.prevent="setCategory">
                            <div class="mb-3">
                                <select wire:model="report_category_id" class="form-select">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->desc }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-save me-1"></i>Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
