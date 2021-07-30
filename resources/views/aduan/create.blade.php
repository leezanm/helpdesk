@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Aduan Baru</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item">Aduan</li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Maklumat Aduan</h4>
                    <p class="card-title-desc">Sila isi butiran tersebut</p>
                    <form id="report_form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-sm-2">
                                <label for="rujukan">Bentuk Aduan</label>
                            </div>
                            <div class="col">
                                <div class="row">
                                    <div class="col-xl-2 col-sm-4">
                                        <label class="card-radio-label">
                                            <input type="radio" name="is_bp" value="0" class="card-radio-input" @if(old('is_bp') != '1') checked @endif>
                                            <div class="card-radio">
                                                <i class="fas fa-user font-size-24 text-primary align-middle me-2"></i>
                                                <span>Individu</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-xl-2 col-sm-4">
                                        <label class="card-radio-label">
                                            <input type="radio" name="is_bp" value="1" class="card-radio-input" @if(old('is_bp') == '1') checked @endif>
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
                            <label class="col-md-2 col-form-label">ID Pengadu <a role="button" class="align-middle"> <i class="bx bx-info-circle align-middle font-size-16" data-bs-toggle="tooltip" data-bs-placement="right" title="ID daftar masuk komputer"></i> </a></label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" value="{{\Auth::user()->username}}" disabled>
                            </div>
                        </div>

                        <div id="bagi-pihak-detail" class="collapse">
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">ID Pengguna <a role="button" class="align-middle"> <i class="bx bx-info-circle align-middle font-size-16" data-bs-toggle="tooltip" data-bs-placement="right" title="ID daftar masuk komputer"></i> </a> <span class="text-danger">*</span></label>
                                <div class="col-md-4">
                                    <input id="bp_username" name="bp_username" type="text" class="form-control">
                                    <p class="text-danger error-bp_username error"></p>
                                    <input id="bp_user_id" name="bp_user_id" type="hidden" class="form-control">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Nama Penuh</label>
                                <div class="col-md-10">
                                    <input id="bp_name" name="bp_name" type="text" class="form-control"  readonly>
                                    <p class="text-danger error-bp_name error"></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Email</label>
                                <div class="col-md-4">
                                    <input id="bp_email" name="bp_email" type="text" class="form-control"  readonly>
                                    <p class="text-danger error-bp_email error"></p>
                                </div>

                                <label class="col-md-2 col-form-label">Bahagian</label>
                                <div class="col-md-4">
                                    <input id="bp_bahagian" name="bp_bahagian" type="text" class="form-control"  readonly>
                                    <p class="text-danger error-bp_bahagian error"></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Kategori Aduan <span class="text-danger">*</span></label>
                            <div class="col-md-4">
                                <select name="report_category_id" class="form-select">
                                    @foreach ($report_categories as $report_category)
                                        <option value="{{$report_category->id}}">{{$report_category->desc}}</option>
                                    @endforeach
                                </select>
                                @error('report_category_id')
                                    <p class="text-danger" >{{$message}}</p>
                                @enderror
                                <p class="text-danger error-report_category_id error"></p>
                            </div>
                        </div>

                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Subjek <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="subject">
                                @error('subject')
                                    <p class="text-danger">{{$message}}</p>
                                @enderror
                                <p class="text-danger error-subject error"></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-md-2 col-form-label">Ringkasan Aduan <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <textarea id="remark" name="remark" class="form-control" rows="5" maxlength="500"></textarea>
                                @error('remark')
                                    <p class="text-danger">{{$message}}</p>
                                @enderror
                                <p class="text-danger error-remark error"></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="example-text-input" class="col-md-2 col-form-label">Muat Naik Laporan / Dokumen</label>
                            <div class="col-md-10">

                                <div action="#" class="dropzone" id="document-dropzone">
                                    <div class="fallback">
                                        <input name="file" type="file" multiple="multiple" accept=".pdf,.jpeg">
                                    </div>
                                    <div class="dz-message needsclick">
                                        <div class="mb-3">
                                            <i class="display-4 text-muted bx bxs-cloud-upload"></i>
                                        </div>

                                        <h4>Drop files here or click to upload.</h4>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 float-end">
                            <button type="button" name="report_status_id" value="1" class="btn btn-secondary waves-effect waves-light submit">Draf</button>
                            <button type="button" name="report_status_id" value="2" class="btn btn-success waves-effect waves-light submit">Hantar</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('script')>
<script>
    // tinymce.init({
    //     selector: "textarea#remark",
    //     height: 300,
    //     plugins: ["advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
    //         "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
    //         "save table contextmenu directionality emoticons template paste textcolor"
    //     ],
    //     toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink | print fullpage | forecolor backcolor emoticons"
    // });

    $('textarea#remark').maxlength({
            alwaysShow: !0,
            placement: "bottom",
            warningClass: "badge bg-info",
            limitReachedClass: "badge bg-warning"});

    const auth_username = '{{Auth::user()->username}}';
    $('input[name="is_bp"]').on('change', function(){

        if(this.value == '1'){
            $('#bagi-pihak-detail').removeClass('collapse');
        } else {
            $('#bagi-pihak-detail').addClass('collapse');
        }

    });

    $('#bp_username').on('change', function(){
        var username = this.value;

        $('#bp_name').val(null);
        $('#bp_bahagian').val(null);
        $('#bp_email').val(null);
        $('#bp_user_id').val(null);

        if(username == auth_username){
            swal.fire('Gagal', 'Username mestilah dari pengguna lain', 'error');
            return;
        }

        $.ajax({
            url: "{{route('api.get-user')}}",
            type: "post",
            data: {
                bp_username : username
            },
            success: function(response){
                if(response.success){
                    $('#bp_name').val(response.bp_name);
                    $('#bp_bahagian').val(response.bp_bahagian);
                    $('#bp_email').val(response.bp_email);
                    $('#bp_user_id').val(response.bp_user_id);
                } else {
                    swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function(error){

            }
        });
    });

    $('input[name="is_bp"]:checked').trigger('change');
</script>
<script>
  var uploadedDocumentMap = {}, uploadedDocumentType = {}, uploadedDocumentPath = {}, uploadedDocumentUuid = {};
  Dropzone.options.documentDropzone = {
    url: '{{route("aduan.attachments.store")}}',
    maxFilesize: 2, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    success: function (file, response) {

        uploadedDocumentUuid[file.upload.uuid] = file.upload.uuid;
        uploadedDocumentMap[file.upload.uuid] = response.name
        uploadedDocumentPath[file.upload.uuid] = response.path;
        uploadedDocumentType[file.upload.uuid] = response.type;
    },
    removedfile: function (file) {
        file.previewElement.remove()

        delete uploadedDocumentUuid[file.upload.uuid];
        delete uploadedDocumentMap[file.upload.uuid];
        delete uploadedDocumentPath[file.upload.uuid];
        delete uploadedDocumentType[file.upload.uuid];
    }
  }

  $('.submit').on('click', function(){

    form = $('#report_form');
    var formData = new FormData( $('#report_form')[0] );
    formData.append('report_status_id', this.value);
    formData.append('attachment_uuids', JSON.stringify(uploadedDocumentMap));
    formData.append('attachment_names', JSON.stringify(uploadedDocumentMap));
    formData.append('attachment_paths', JSON.stringify(uploadedDocumentPath));
    formData.append('attachment_types', JSON.stringify(uploadedDocumentType));
    // formData.append('remark', tinymce.get('remark').getContent());

    clearError(form);
    $.ajax({
        type:'post',
        data:formData,
        processData: false,
        contentType: false,
        success:function(response){
            if(response.success){
                window.location.href = "{{route('aduan.index-user', Auth::user()->username)}}";
            }
        //
        },
        error:function(error){
            errorValidate(error, form);
        }
    });

  })
</script>
@endsection
