@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Tambah Pengguna</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Pengguna</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col text-end">
                <div class="button-items">
                    <a class="btn btn-primary btn waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#create_user_modal">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah Pengguna
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
       <table id="audit" class="table table-bordered table-striped table-hover">
           <thead>
                <th>#</th>
                <th>Nama Pegawai</th>
                <th>Tugas</th>
                <th width="10%">Status</th>
                <th width="5%">Tindakan</th>
           </thead>
           <tbody class="align-middle">
               <tr>
                    <td>1</td>
                    <td class="p-0">
                        <a href="#" class="list-group-item text-muted border-0">
                            <div class="media align-items-center">
                                <div class="me-3">
                                    <img src="{{asset('images/profile/default.png')}}" alt="" class="avatar-sm h-auto d-block rounded">
                                </div>
                                <div class="media-body overflow-hidden">
                                    <h5 class="font-size-13 text-truncate">Muhammad Hazim bin Md Zulkeflee</h5>
                                    <p class="mb-0 text-truncate">Industri</p>
                                </div>
                            </div>
                        </a>
                    </td>
                    <th>Menilai dan analysis pekerja</th>
                    <td><span class="badge rounded-pill bg-success bg-gradient font-size-13">AKTIF</span></td>
                    <td class="text-center">
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
                    </td>
               </tr>
               <tr>
                <td>2</td>
                <td class="p-0">
                    <a href="#" class="list-group-item text-muted border-0">
                        <div class="media align-items-center">
                            <div class="me-3">
                                <img src="{{asset('images/profile/default.png')}}" alt="" class="avatar-sm h-auto d-block rounded">
                            </div>
                            <div class="media-body overflow-hidden">
                                <h5 class="font-size-13 text-truncate">Syamie Syamil</h5>
                                <p class="mb-0 text-truncate">Pengurusan Fail</p>
                            </div>
                        </div>
                    </a>
                </td>
                <th>Menyimpan rekod dan fail</th>
                <td><span class="badge rounded-pill bg-success bg-gradient font-size-13">AKTIF</span></td>
                <td class="text-center">
                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
                </td>
           </tr>
           <tr>
            <td>3</td>
            <td class="p-0">
                <a href="#" class="list-group-item text-muted border-0">
                    <div class="media align-items-center">
                        <div class="me-3">
                            <img src="{{asset('images/profile/default.png')}}" alt="" class="avatar-sm h-auto d-block rounded">
                        </div>
                        <div class="media-body overflow-hidden">
                            <h5 class="font-size-13 text-truncate">Fatin binti Sakinah</h5>
                            <p class="mb-0 text-truncate">HR</p>
                        </div>
                    </div>
                </a>
            </td>
            <th>Membuat laporan pegawai</th>
            <td><span class="badge rounded-pill bg-danger bg-gradient font-size-13">TIDAK AKTIF</span></td>
            <td class="text-center">
                <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
            </td>
       </tr>
           </tbody>
       </table>
    </div>
</div>

@include('user.partials.show')
@include('user.partials.edit')
@include('user.partials.create')

@endsection

@section('script')
<script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
<script>
    $('#document-datatable').DataTable();
    $('#susunan').TouchSpin();
    $('#susunan-edit').TouchSpin();
    $(document).ready(function(){0<$("#address").length&&tinymce.init({selector:"textarea#content-edit",height:300,plugins:["advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker","searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking","save table contextmenu directionality emoticons template paste textcolor"],toolbar:"insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons",style_formats:[{title:"Bold text",inline:"b"},{title:"Red text",inline:"span",styles:{color:"#ff0000"}},{title:"Red header",block:"h1",styles:{color:"#ff0000"}},{title:"Example 1",inline:"span",classes:"example1"},{title:"Example 2",inline:"span",classes:"example2"},{title:"Table styles"},{title:"Table row 1",selector:"tr",classes:"tablerow1"}]})});
</script>
<script>
    function clearError(){
        $('textarea').removeClass('is-invalid');
        $('input').removeClass('is-invalid');
        $('.error').addClass('collapse');
    }
    function clearStatus(){
        $('#show-is_active').removeClass('bg-success')
        $('#show-is_active').removeClass('bg-danger')
    }

    $('#btnCreate').on('click', function(){
        $('#create_document_modal').modal('show');
    });

    $('.btnSubmit').on('click',function(){
        var form = $(this).closest('form');
        var formId = '#'+form.attr('id');
        var formData = new FormData( $(formId)[0] );
        formData.append('_token', '{{csrf_token()}}')
        clearError(formId);
        console.log(formData)
        console.log(form.serialize());
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success:function(response){
                console.log(response);
                if(response.success){
                    window.location.reload();
                }
            },
            error:function(errorResponse){
                console.log(errorResponse);
                if(errorResponse.responseJSON.errors){
                    errors = errorResponse.responseJSON.errors;
                    $.each(errors, function (key, error){
                        $(formId + ' input[name="'+key+'"]').addClass('is-invalid');
                        $(formId + ' textarea[name="'+key+'"]').addClass('is-invalid');
                        $(formId + ' select[name="'+key+'"]').addClass('is-invalid');
                        $(formId + ' #error-'+key).html(error[0]);
                        $(formId + ' #error-'+key).removeClass('collapse');
                    })
                }
            }
        })
    });

    function showItem(row){
        currentRow = $(row);
        modalId = currentRow.attr('data-modal');
        $.ajax({
            url : currentRow.attr('data-show'),
            method : 'get',
            success : function(response){
                if(response.success){
                    $('#show-file').html(response.data['name']);
                    $('#show-section').html(response.data['section']);
                    $('#show-file').attr('href', response.data['file']);
                    $('#show-file').attr('download', response.data['name']+".pdf");
                    clearStatus();
                    if(response.data['is_active']){
                        $('#show-is_active').addClass('bg-success')
                        $('#show-is_active').html('Aktif');
                    }else{
                        $('#show-is_active').addClass('bg-danger')
                        $('#show-is_active').html('Tidak Aktif');
                    }
                }
                $(modalId).modal('show');
            },
            error : function(error){
                console.log(error);
            }
        });
    }

    function editForm(row){
        currentRow = $(row);
        modalId = currentRow.attr('data-modal');
        form = $(modalId + ' form');
        form.attr('action', currentRow.attr('data-update'));
        formId = '#' + form.attr('id');
        // console.log(modalId);
        clearError(form.attr('action'));
        $.ajax({
            url : currentRow.attr('data-edit'),
            method : 'get',
            success : function(response){
                console.log(response);
                if(response.success){
                    $(formId + ' input[name="name"]').val(response.data['name']);
                    $(formId + ' input[name="section"]').val(response.data['section']);
                    $(formId + ' input[name="sequence"]').val(response.data['sequence']);
                    $('#edit-file').html(response.data['name']);
                    $('#edit-file').attr('href', response.data['file']);
                    $('#edit-file').attr('download', response.data['name']+".pdf");
                    // console.log(formId);
                    $(modalId).modal('show');
                }
            },
            error : function(error){
                console.log(error);
            }
        });
    }

    function delRow(row){
        currentRow= $(row);
        Swal.fire({
            title:"Anda pasti?",
            icon:"warning",
            showCancelButton:!0,
            confirmButtonColor:"#34c38f",
            cancelButtonColor:"#f46a6a",
            confirmButtonText:"Teruskan",
            cancelButtonText:"Batal",
        }).then(function(t){
                if(t.value){
                    $.ajax({
                        url:currentRow.attr('data-action'),
                        method: 'post',
                        data: {
                            _token: '{{csrf_token()}}'
                        },
                        success: function(response){
                            if(response.success){
                                // currentRow.closest('tr').remove();
                                // Swal.fire('Berjaya', 'Maklumat berjaya dipadam', 'success');
                                window.location.reload();
                            }
                        },
                        error: function(error){
                            console.log(error);
                        }
                    })
                }
            });
    }

</script>
@endsection
