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
                                            <input type="radio" name="bentuk_aduan" id="individu" class="card-radio-input">
                                            <div class="card-radio">
                                                <i class="fas fa-user font-size-24 text-primary align-middle me-2"></i>
                                                <span>Individu</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-xl-2 col-sm-4">
                                        <label class="card-radio-label">
                                            <input type="radio" name="bentuk_aduan" id="wakil" class="card-radio-input" checked>
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
                            <label class="col-md-3 col-form-label">Nama Akaun Pengguna</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" value="{{\Auth::user()->username}}" disabled>
                            </div>
                        </div>

                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-3 col-form-label">Subjek</label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="subject">
                            </div>
                        </div>

                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-3 col-form-label">Kategori Aduan</label>
                            <div class="col-md-4">
                                <select name="report_category_id" class="form-select">
                                    @foreach ($report_categories as $report_category)
                                        <option value="{{$report_category->id}}">{{$report_category->desc}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm">
                                <div class="mb-3">
                                    <label for="productdesc" class="mb-2">Butiran Aduan</label>
                                    <textarea id="elm1" name="remark"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label for="example-text-input" class="col-md-2 col-form-label">Muat Naik Laporan / Dokumen</label>
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
                                    <input class="form-check-input" type="checkbox" id="floatingCheck">
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
