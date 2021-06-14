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
                    <form method="POST">
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
                            <label class="col-md-2 col-form-label">Nama Pengadu</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" value="{{\Auth::user()->username}}" disabled>
                            </div>
                        </div>

                        <div id="bagi-pihak-detail" class="collapse">
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Nama Akaun (AD) <span class="text-danger">*</span></label>
                                <div class="col-md-4">
                                    <input id="bp_username" name="bp_username" type="text" class="form-control">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Nama Penuh</label>
                                <div class="col-md-10">
                                    <input id="bp_name" name="bp_name" type="text" class="form-control"  readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Email</label>
                                <div class="col-md-4">
                                    <input id="bp_email" name="bp_email" type="text" class="form-control"  readonly>
                                </div>

                                <label class="col-md-2 col-form-label">Bahagian</label>
                                <div class="col-md-4">
                                    <input id="bp_bahagian" name="bp_bahagian" type="text" class="form-control"  readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Kategori Aduan</label>
                            <div class="col-md-4">
                                <select name="report_category_id" class="form-select">
                                    @foreach ($report_categories as $report_category)
                                        <option value="{{$report_category->id}}">{{$report_category->desc}}</option>
                                    @endforeach
                                </select>
                                @error('report_category_id')
                                    <p class="text-danger">{{$message}}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Subjek <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <input class="form-control" type="text" name="subject">
                                @error('subject')
                                    <p class="text-danger">{{$message}}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-md-2 col-form-label">Butiran Aduan <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <textarea id="elm1" name="remark"></textarea>
                                @error('remark')
                                    <p class="text-danger">{{$message}}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="example-text-input" class="col-md-2 col-form-label">Muat Naik Laporan / Dokumen</label>
                            <div class="col-md-10">

                                <div action="#" class="dropzone" id="document-dropzone">
                                    <div class="fallback">
                                        <input name="file" type="file" multiple="multiple">
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

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="pengakuan">
                                <label class="form-check-label" for="floatingCheck">
                                    Dengan ini saya mengaku dan mengesahkan semua kenyataan dan butir-butir yang dikemukakan di atas adalah benar
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 float-end">
                            {{-- <a href="{{url('aduan')}}" class="btn btn-primary waves-effect waves-light">Simpan</a>
                            <a href="{{url('aduan')}}" class="btn btn-success waves-effect waves-light">Hantar</a> --}}
                            <button type="submit" name="report_status_id" value="1" class="btn btn-secondary waves-effect waves-light">Draf</button>
                            <button type="submit" name="report_status_id" value="2" class="btn btn-success waves-effect waves-light">Hantar</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('script')>
{{-- <script src="{{ asset('assets/libs/tinymce/tinymce.min.js') }}"></script> --}}
<script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
<script>
    $('input[name="is_bp"]').on('change', function(){

        if(this.value == '1'){
            $('#bagi-pihak-detail').removeClass('collapse');
        } else {
            $('#bagi-pihak-detail').addClass('collapse');
        }

    });

    $('#bp_username').on('change', function(){
        var username = this.value;
        console.log(username);
        $.ajax({
            url: "{{route('api.get-user')}}",
            type: "post",
            data: {
                bp_username : username
            },
            success: function(response){
                console.log(response);
                if(response.success){
                    $('#bp_name').val(response.bp_name);
                    $('#bp_bahagian').val(response.bp_bahagian);
                    $('#bp_email').val(response.bp_email);;
                } else {;
                    swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function(error){
                console.log(error);
            }
        });
    });

    $('input[name="is_bp"]:checked').trigger('change');
</script>
<script>
  var uploadedDocumentMap = {}, uploadedDocumentType = {}, uploadedDocumentPath = {};
  Dropzone.options.documentDropzone = {
    url: '{{route("aduan.attachments.store")}}',
    maxFilesize: 2, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="attachment_names[]" value="' + response.name + '">')
      $('form').append('<input type="hidden" name="attachment_paths[]" value="' + response.path + '">')
      $('form').append('<input type="hidden" name="attachment_types[]" value="' + response.type + '">')
      uploadedDocumentMap[file.name] = response.name
      uploadedDocumentPath[file.name] = response.path
      uploadedDocumentType[file.name] = response.type
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedDocumentMap[file.name]
        type = uploadedDocumentPath[file.name]
        path = uploadedDocumentType[file.name]
      }
      $('form').find('input[name="attachment_names[]"][value="' + name + '"]').remove()
      $('form').find('input[name="attachment_paths[]"][value="' + path + '"]').remove()
      $('form').find('input[name="attachment_types[]"][value="' + type + '"]').remove()
    }
  }
</script>
@endsection
