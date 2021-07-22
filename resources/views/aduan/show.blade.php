@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Aduan</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item ">Aduan</li>
                        <li class="breadcrumb-item active">{{ $report->ticket_no }}</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <legend class="text-uppercase h5">MAKLUMAT PEMOHON</legend>
            <table class="table table-striped">
                <tbody>
                    <tr>
                        <th width="20%">Pemohon</th>
                        <td>
                            <a class="userProfile" data-userId="{{ $report->reportBy->id }}"
                                role="button">{{ $report->reportBy->name }}</a>
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Jabatan</th>
                        <td>
                            {{ $report->reportBy->ldap_bahagian }}
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Username</th>
                        <td>
                            {{ $report->reportBy->username }}
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Email</th>
                        <td>
                            {{ $report->reportBy->email }}
                        </td>
                    </tr>

                    <tr>
                        <th width="20%">Aset Pengguna</th>
                        <td>
                            <table id="user_asset-datatable" class="table table-bordered">
                                <thead class="text-center align-middle text-uppercase">
                                    <th>#</th>
                                    <th>Jenis</th>
                                    <th>Jenama</th>
                                    <th>Model</th>
                                    <th>No. Siri Pendaftaran</th>
                                    <th>No. Siri Pembuat</th>
                                    <th>Status</th>
                                </thead>
                                <tbody class="text-center align-middle">
                                    @forelse ($report->reportBy->assets as $asset)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$asset->type}}</td>
                                            <td>{{$asset->brand}}</td>
                                            <td>{{$asset->model}}</td>
                                            <td>{{$asset->registration_serial_no}}</td>
                                            <td>{{$asset->supplier_serial_no}}</td>
                                            <td>
                                                @if ($asset->is_active)
                                                    <span class="badge bg-success">AKTIF</span>
                                                @else
                                                <span class="badge bg-danger">TIDAK AKTIF</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7">Tiada Rekod</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>

            <legend class="text-uppercase h5">MAKLUMAT ADUAN</legend>
            <table class="table table-striped">
                <tbody>
                    @if ($report->is_bp)
                        <tr>
                            <th width="20%"></th>
                            <th>Bagi Pihak</th>
                        </tr>
                        <tr>
                            <th width="20%">Nama Penuh Pengguna</th>
                            <td>
                                @if ($report->bp_user_id)
                                    <a data-userId="{{ $report->bp_user_id }}" class="userProfile"
                                        role="button">{{ $report->bp_name }}</a>
                                @else
                                    {{ $report->bp_name }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th width="20%">Nama Pengguna (AD)</th>
                            <td>
                                {{ $report->bp_username }}
                            </td>
                        </tr>
                        <tr>
                            <th width="20%">Email Pengguna</th>
                            <td>
                                {{ $report->bp_email }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <th width="20%">No Tiket</th>
                        <td>
                            {{ $report->ticket_no }}
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Subjek</th>
                        <td>
                            {{ $report->subject }}
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Status</th>
                        <td>
                            <span class="{{ $report->status->class }} font-size-12">{{ $report->status->desc }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Dihantar Pada</th>
                        <td>
                            {{ convertDateFormat($report->submitted_at, 'LL') }}
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Kategori Kerosakan</th>
                        <td>
                            {{ $report->category->desc }}
                        </td>
                    </tr>
                    <tr>
                        <th width="20%">Aduan</th>
                        <td>
                            {!! $report->remark !!}
                        </td>
                    </tr>
                </tbody>
            </table>

            <h6>Lampiran</h6>
            <table id="datatable-lampiran" class="table table-bordered">
                <thead>
                    <th>#</th>
                    <th>Fail</th>
                    <th width="20%">Jenis</th>
                </thead>
                <tbody>
                    @foreach ($attachments as $attachment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><a href="{{ $attachment->file_url }}" target="_blank">{{ $attachment->name }}</a></td>
                            <td>{{ $attachment->type }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @can('aduan.adminTask')
                @if (in_array($report->report_status_id, [3, 4, 6]))
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('aduan.update-task', $report->id) }}" method="POST">
                        @csrf
                        <legend class="text-uppercase h5">Tindakan Admin</legend>

                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label class="col-form-label">Tindakan</label>
                            </div>
                            <div class="col-md-4">
                                <select name="tindakan" class="form-select">
                                    <option value="">Sila Pilih</option>
                                    @if ($report->report_status_id == 3)
                                        <option value="1" @if (old('tindakan') == '1') selected @endif>Majukan Kepada</option>
                                    @elseif($report->report_status_id == 4)
                                        <option value="2" @if (old('tindakan') == '2') selected @endif>Penugasan Semula</option>
                                        <option value="3" @if (old('tindakan') == '3') selected @endif>KIV</option>
                                    @elseif($report->report_status_id == 6)
                                        <option value="4" @if (old('tindakan') == '4') selected @endif>Tamat KIV</option>
                                    @endif

                                </select>
                                @error('tindakan') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div id="tindakan-majukan-kepada" class="@error('assigned_to_id') @else collapse @enderror">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="col-form-label">Peranan Pengguna</label>
                                </div>
                                <div class="col-md-4">
                                    <select name="peranan" id="majukan_kepada_peranan" class="form-select">
                                        <option value="">Sila Pilih</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}" @if ($role->name == old('peranan')) selected @endif>{{ $role->desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3 collapse" id="peg_teknikal_selections">
                                <div class="col-md-2">
                                    <label class="col-form-label">Kepada</label>
                                </div>
                                <div class="col-md-4">
                                    <select name="assigned_to_id" id="assigned_to_id" class="form-select" disabled>

                                    </select>
                                    @error('assigned_to_id') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div id="tindakan-penugasan-semula" class="collapse">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="col-form-label">Sebab Penugasan</label>
                                </div>
                                <div class="col-md-4">
                                    <textarea id="reassignment_remark" name="reassignment_remark" rows="5" class="form-control"
                                        disabled></textarea>
                                </div>
                            </div>
                        </div>

                        <div id="tindakan-kiv-mula" class="collapse">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label>Tarikh Mula KIV <span class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="date_start_kiv" name="kiv_start_at" min="{{\Carbon\Carbon::parse($report->submitted_at)->format('Y-m-d')}}">
                                        <label for="date_start_kiv">Tarikh Mula KIV</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label>Sebab KIV</label>
                                </div>
                                <div class="col-md-3">
                                    <textarea name="kiv_reason" rows="10" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>

                        <div id="tindakan-kiv-tamat" class="collapse">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label >Tarikh Tamat KIV <span class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="date_end_kiv" name="kiv_end_at" min="{{\Carbon\Carbon::parse($report->kiv_start_at)->format('Y-m-d')}}">
                                        <label for="date_end_kiv">Tarikh Tamat KIV</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success waves-effect waves-light">
                                        <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <hr>
                @endif


                <legend class="text-uppercase h5">Tukar Kategori Aduan</legend>
                <form action="{{ route('aduan.set-category', $report->id) }}" method="POST">
                    @csrf


                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Kategori</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="report_category_id">
                                @foreach ($report_categories as $report_category)
                                    <option value="{{$report_category->id}}" @if($report_category->id == $report->report_category_id) selected @endif>{{$report_category->desc}}</option>
                                @endforeach
                            </select>
                            @error('report_category_id') <p class="text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-end">
                                <button class="btn btn-success changeCategory" type="button">
                                    <i class="bx bx-save me-2 align-middle"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @endcan

            @can('aduan.updateTask')
                @if (in_array($report->report_status_id, [4]) && $report->currentTrail->assignedTo->is(Auth::user()))
                    <hr>
                    <form action="{{ route('aduan.trail.update', $report->currentTrail->id) }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="col-form-label">Catatan Peg. Teknikal</label>
                                <textarea id="technical_remark" name="technical_remark" rows="10" class="form-control">{!!$report->currentTrail->technical_remark!!}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="text-end">
                                    <button type="button" class="btn btn-success waves-effect waves-light submit">
                                        <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif
            @endcan

            @if ( ( $report->reportBy->is(\Auth::user()) || Auth::user()->hasAnyRole(['superadmin', 'helpdesk.admin']) ) && $report->report_status_id == 4)
                <form action="{{ route('aduan.update-feedback', $report->id) }}" method="POST">
                    @csrf

                    <legend class="text-uppercase h5">Soal Selidik</legend>
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Rating</label>
                        </div>
                        <div class="col-md-4">
                            <div class="rating-star">
                                <input type="hidden" name="rating" class="rating" data-filled="mdi mdi-star text-warning"
                                    data-empty="mdi mdi-star-outline text-muted" />
                            </div>
                            <p>1=Lemah, 2=Kurang berpuas hati, 3=Puas hati, 4=Baik, 5=Cemerlang</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Komen</label>
                        </div>
                        <div class="col-md-4">
                            <textarea id="feedback-textarea" name="feedback" class="form-control" rows="5" maxlength="255"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-end">
                                <button type="submit" class="btn btn-success waves-effect waves-light">
                                    <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            @endif

            <legend class="text-uppercase h5 mb-3">Kronologi Aduan</legend>

            <div class="mt-5">
                <ul class="verti-timeline list-unstyled">

                    @foreach ($trails as $trail)
                        <li class="event-list">
                            <div class="event-timeline-dot">
                                <i class="bx bx-right-arrow-circle"></i>
                            </div>
                            <div class="media">
                                <div class="me-5">
                                    {{ convertDateFormat($trail->created_at, 'LL, h:mm A') }}
                                    {{-- <i class="bx bx-copy-alt h2 text-primary"></i> --}}
                                </div>
                                <div class="media-body">
                                    <div>
                                        <span
                                            class="{{ $trail->status->class }} font-size-11">{{ $trail->status->desc }}</span>
                                        <p class="text-muted">
                                            {!! $trail->message !!}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>
@endsection

@section('script')
        <script>
        tinymce.init({
            selector: "textarea#technical_remark",
            height: 300,
            plugins: ["advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
                "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                "save table contextmenu directionality emoticons template paste textcolor"
            ],
            toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink | print fullpage | forecolor backcolor emoticons"
        });

        $('#datatable-lampiran').DataTable();

        $('#feedback-textarea').maxlength({
            alwaysShow: !0,
            placement: "bottom-right",
            warningClass: "badge bg-info",
            limitReachedClass: "badge bg-warning"});

        $('select[name="task_status_id"').on('change', function() {
            if (this.value === '4') {
                $('#sebabTakSelesai').removeClass('collapse');
            } else {
                $('#sebabTakSelesai').addClass('collapse');
            }

        });

        $('select[name="tindakan"]').on('change', function() {
            if (this.value == 1 || this.value == 2) {
                $('#tindakan-majukan-kepada').removeClass('collapse');
                $('#majukan_kepada_peranan').trigger('change');
            } else {
                $('#tindakan-majukan-kepada').addClass('collapse');
                $('#assigned_to_id').prop('disabled', true);
                $('#peg_teknikal_selections').addClass('collapse');
            }

            if (this.value == 2) {
                $('#tindakan-penugasan-semula').removeClass('collapse');
                $('#reassignment_remark').attr('disabled', false);
            } else {
                $('#tindakan-penugasan-semula').addClass('collapse');
                $('#reassignment_remark').attr('disabled', true);
            }

            if (this.value == 3) {
                $('#tindakan-kiv-mula').removeClass('collapse');
                $('#date_start_kiv').attr('disabled', false);
            } else {
                $('#tindakan-kiv-mula').addClass('collapse');
                $('#date_start_kiv').attr('disabled', true);
            }

            if (this.value == 4) {
                $('#tindakan-kiv-tamat').removeClass('collapse');
                $('#date_end_kiv').attr('disabled', false);
            } else {
                $('#tindakan-kiv-tamat').addClass('collapse');
                $('#date_end_kiv').attr('disabled', true);
            }

        })

        $('#majukan_kepada_peranan').on('change', function() {
            $('#assigned_to_id').empty();
            if (this.value) {
                var role_name = this.value;
                $.post(
                    "{{ url('api/get-users-by-role') }}", {
                        role_name: role_name
                    },
                    function(response) {

                        if (response.success) {
                            option = '<option value="">Sila Pilih</option>';
                            response.users.forEach(user => {
                                option += '<option value="' + user.id + '">' + user.name + ' (' + user
                                    .ldap_bahagian + ')' + '</option>';
                            });

                            $('#assigned_to_id').append(option);
                            $('#assigned_to_id').prop('disabled', false);
                            $('#peg_teknikal_selections').removeClass('collapse');
                        }
                    }
                )
            } else {
                $('#assigned_to_id').prop('disabled', true);
                $('#peg_teknikal_selections').addClass('collapse');
            }
        })

        $('#majukan_kepada_peranan').trigger('change');
        $('select[name="tindakan"]').trigger('change');

        $('.submit').on('click', function(e){
            e.preventDefault();

            form = this.form;

            action = form.attributes.action.nodeValue;
            method = form.attributes.method.nodeValue;

            var formData = new FormData( form );
            formData.append('_token', '{{csrf_token()}}');
            formData.append('technical_remark', tinymce.get('technical_remark').getContent());
            $.ajax({
                url: action,
                type:method,
                data:formData,
                processData: false,
                contentType: false,
                success:function(response){
                    if(response.success){
                        swal.fire('Berjaya', 'Catatan berjaya di simpan', 'success');
                        window.location.reload();
                    }
                },
                error:function(response){
                    errorValidate(response, $(form));
                }
            });
        });

        $('.changeCategory').on('click', function(e){
            e.preventDefault();

            form = this.form;

            action = form.attributes.action.nodeValue;
            method = form.attributes.method.nodeValue;
            $.ajax({
                url: action,
                type:method,
                data:$(form).serialize(),
                success:function(response){
                    if(response.success){
                        // swal.fire('Berjaya', 'Maklumat berjaya di simpan', 'success');
                        swal.fire({
                            title: 'Berjaya',
                            text: 'Maklumat Berjaya di simpan',
                            icon:'success',
                            timer: 1000
                        }).then(function(){
                            window.location.reload();
                        });

                    }
                },
                error:function(response){
                    errorValidate(response, $(form));
                }
            });
        });
    </script>
@endsection
