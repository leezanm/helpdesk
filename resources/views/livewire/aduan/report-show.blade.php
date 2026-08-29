<div>
    <div class="card">
        <div class="card-body">

            @if (session('report-message'))
                <div class="alert alert-success">{{ session('report-message') }}</div>
            @endif

            <legend class="text-uppercase h5">MAKLUMAT PEMOHON</legend>
            <table class="table table-striped">
                <tbody>
                    <tr><th width="20%">Pemohon</th><td>{{ $report->reportBy->name ?? '-' }}</td></tr>
                    <tr><th>Jabatan</th><td>{{ $report->reportBy->ldap_bahagian ?? '-' }}</td></tr>
                    <tr><th>Username</th><td>{{ $report->reportBy->username ?? '-' }}</td></tr>
                    <tr><th>Email</th><td>{{ $report->reportBy->email ?? '-' }}</td></tr>
                </tbody>
            </table>

            <legend class="text-uppercase h5">MAKLUMAT ADUAN</legend>
            <table class="table table-striped">
                <tbody>
                    @if ($report->is_bp)
                        <tr><th width="20%"></th><th>Bagi Pihak</th></tr>
                        <tr><th>Nama Penuh Pengguna</th><td>{{ $report->bp_name }}</td></tr>
                        <tr><th>Nama Pengguna (AD)</th><td>{{ $report->bp_username }}</td></tr>
                        <tr><th>Email Pengguna</th><td>{{ $report->bp_email }}</td></tr>
                    @endif
                    <tr><th width="20%">No Tiket</th><td>{{ $report->ticket_no }}</td></tr>
                    <tr><th>Subjek</th><td>{{ $report->subject }}</td></tr>
                    <tr><th>Status</th><td><span class="{{ $report->status->class }} font-size-12">{{ $report->status->desc }}</span></td></tr>
                    <tr><th>Dihantar Pada</th><td>{{ convertDateFormat($report->submitted_at, 'LL') }}</td></tr>
                    <tr><th>Kategori Kerosakan</th><td>{{ $report->category->desc ?? '-' }}</td></tr>
                    <tr><th width="30%">Aduan</th><td>{!! $report->remark !!}</td></tr>
                </tbody>
            </table>

            <h6>Lampiran</h6>
            <table class="table table-bordered">
                <thead><th>#</th><th>Fail</th><th width="20%">Jenis</th></thead>
                <tbody>
                    @forelse ($attachments as $attachment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><a href="{{ $attachment->file_url }}" target="_blank">{{ $attachment->name }}</a></td>
                            <td>{{ $attachment->type }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">Tiada lampiran.</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{-- ===== Admin Task ===== --}}
            @if ($canAdmin)
                @if (in_array($report->report_status_id, [3, 4, 6]))
                    <form wire:submit.prevent="saveAdminTask">
                        <legend class="text-uppercase h5">Tindakan Admin</legend>

                        <div class="row mb-3">
                            <div class="col-md-2"><label class="col-form-label">Tindakan</label></div>
                            <div class="col-md-4">
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
                                @error('tindakan') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if (in_array($tindakan, ['1', '2']))
                            <div class="row mb-3">
                                <div class="col-md-2"><label class="col-form-label">Peranan Pengguna</label></div>
                                <div class="col-md-4">
                                    <select wire:model.live="peranan" class="form-select">
                                        <option value="">Sila Pilih</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($peranan)
                                <div class="row mb-3">
                                    <div class="col-md-2"><label class="col-form-label">Kepada</label></div>
                                    <div class="col-md-4">
                                        <select wire:model="assigned_to_id" class="form-select @error('assigned_to_id') is-invalid @enderror">
                                            <option value="">Sila Pilih</option>
                                            @foreach ($officers as $officer)
                                                <option value="{{ $officer->id }}">{{ $officer->name }} ({{ $officer->ldap_bahagian }})</option>
                                            @endforeach
                                        </select>
                                        @error('assigned_to_id') <p class="text-danger">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if ($tindakan === '2')
                            <div class="row mb-3">
                                <div class="col-md-2"><label class="col-form-label">Sebab Penugasan</label></div>
                                <div class="col-md-4"><textarea wire:model="reassignment_remark" rows="5" class="form-control"></textarea></div>
                            </div>
                        @endif

                        @if ($tindakan === '3')
                            <div class="row mb-3">
                                <div class="col-md-2"><label>Tarikh Mula KIV <span class="text-danger">*</span></label></div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control @error('kiv_start_at') is-invalid @enderror" wire:model="kiv_start_at"
                                           min="{{ \Carbon\Carbon::parse($report->submitted_at)->format('Y-m-d') }}">
                                    @error('kiv_start_at') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-2"><label>Sebab KIV</label></div>
                                <div class="col-md-3"><textarea wire:model="kiv_reason" rows="4" class="form-control"></textarea></div>
                            </div>
                        @endif

                        @if ($tindakan === '4')
                            <div class="row mb-3">
                                <div class="col-md-2"><label>Tarikh Tamat KIV <span class="text-danger">*</span></label></div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control @error('kiv_end_at') is-invalid @enderror" wire:model="kiv_end_at"
                                           min="{{ \Carbon\Carbon::parse($report->kiv_start_at)->format('Y-m-d') }}">
                                    @error('kiv_end_at') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="text-end">
                            <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                                <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                            </button>
                        </div>
                    </form>
                    <hr>
                @endif

                <legend class="text-uppercase h5">Tukar Kategori Aduan</legend>
                <form wire:submit.prevent="setCategory">
                    <div class="row mb-3">
                        <div class="col-md-2"><label class="col-form-label">Kategori</label></div>
                        <div class="col-md-4">
                            <select wire:model="report_category_id" class="form-select">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->desc }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success"><i class="bx bx-save me-2 align-middle"></i> Simpan</button>
                    </div>
                </form>
            @endif

            {{-- ===== Technical remark ===== --}}
            @if ($canUpdateTask && $report->report_status_id == 4 && optional($report->currentTrail)->assigned_to_id === auth()->id())
                <hr>
                <form wire:submit.prevent="saveTechnicalRemark">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="col-form-label">Catatan Peg. Teknikal</label>
                            <textarea wire:model="technical_remark" rows="6" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success"><i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan</button>
                    </div>
                </form>
            @endif

            {{-- ===== Feedback ===== --}}
            @if (($report->reportBy?->is(auth()->user()) || auth()->user()->hasAnyRole(['superadmin', 'helpdesk.admin'])) && $report->report_status_id == 4)
                <hr>
                <form wire:submit.prevent="saveFeedback">
                    <legend class="text-uppercase h5">Soal Selidik</legend>
                    <div class="row mb-3">
                        <div class="col-md-2"><label class="col-form-label">Rating</label></div>
                        <div class="col-md-4">
                            <select wire:model="rating" class="form-select @error('rating') is-invalid @enderror">
                                <option value="">Sila Pilih</option>
                                <option value="1">1 - Lemah</option>
                                <option value="2">2 - Kurang berpuas hati</option>
                                <option value="3">3 - Puas hati</option>
                                <option value="4">4 - Baik</option>
                                <option value="5">5 - Cemerlang</option>
                            </select>
                            @error('rating') <p class="text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-2"><label class="col-form-label">Komen</label></div>
                        <div class="col-md-4">
                            <textarea wire:model="feedback" class="form-control @error('feedback') is-invalid @enderror" rows="5" maxlength="255"></textarea>
                            @error('feedback') <p class="text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success"><i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan</button>
                    </div>
                </form>
            @endif

            {{-- ===== Trail timeline ===== --}}
            <legend class="text-uppercase h5 mb-3">Kronologi Aduan</legend>
            <div class="mt-5">
                <ul class="verti-timeline list-unstyled">
                    @foreach ($trails as $trail)
                        <li class="event-list">
                            <div class="event-timeline-dot"><i class="bx bx-right-arrow-circle"></i></div>
                            <div class="media">
                                <div class="me-5">{{ convertDateFormat($trail->created_at, 'LL, h:mm A') }}</div>
                                <div class="media-body">
                                    <span class="{{ $trail->status->class ?? '' }} font-size-11">{{ $trail->status->desc ?? '' }}</span>
                                    <p class="text-muted">{!! $trail->message !!}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>
</div>
