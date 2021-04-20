@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Bahagian</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item ">Selenggara</li>
                        <li class="breadcrumb-item active">Bahagian</li>
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
                    <a class="btn btn-primary btn waves-effect waves-light" id="btnCreate">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah Baru
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
       <table id="audit" class="table table-bordered table-striped table-hover">
           <thead>
                <th width="5">Bil</th>
                <th>Nama</th>
                <th class="text-center" width="10%">Tindakan</th>
           </thead>
           <tbody class="align-middle">
               @foreach ($divisions as $division)
                   <tr>
                       <td>{{$loop->iteration}}</td>
                       <td>{{$division->desc}}</td>
                       <td class="text-center">
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary" onclick="editForm(this)" data-edit="{{route('division.edit', $division->id)}}" data-update="{{route('division.update', $division->id)}}" data-modal="#edit_division_modal" > <i class="bx bxs-pen"></i></a>
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-success" data-modal="#show_division_modal" data-show="{{route('division.show', $division->id)}}" onclick="showItem(this)" > <i class="mdi mdi-eye"></i></a>
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger" data-action="{{route('division.destroy', $division->id)}}" onclick="delRow(this)"> <i class="bx bxs-trash"></i></a>
                       </td>
                   </tr>
               @endforeach
           </tbody>
       </table>
    </div>
</div>


@include('division._modal.create')
@include('division._modal.show')
@include('division._modal.edit')

@endsection

@section('script')
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
        $('#create_division_modal').modal('show');
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
        console.log('helo');
        currentRow = $(row);
        modalId = currentRow.attr('data-modal');
        $.ajax({
            url : currentRow.attr('data-show'),
            method : 'get',
            success : function(response){
                console.log(response.success);
                if(response.success){
                    $('#show-desc').html(response.data.desc);
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
        console.log('helo');
        currentRow = $(row);
        modalId = currentRow.attr('data-modal');
        form = $(modalId + ' form');
        form.attr('action', currentRow.attr('data-update'));
        formId = '#' + form.attr('id');
        console.log(modalId);
        clearError(form.attr('action'));
        $.ajax({
            url : currentRow.attr('data-edit'),
            method : 'get',
            success : function(response){
                console.log(response);
                if(response.success){
                    $(formId + ' input[name="desc"]').val(response.data['desc']);
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
