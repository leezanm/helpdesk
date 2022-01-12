@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Status Aduan</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item ">Selenggara</li>
                        <li class="breadcrumb-item active">Status Aduan</li>
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
        <div class="table-responsive">
            <table id="audit" class="table table-bordered table-striped table-hover">
                <thead>
                     <th width="5">Bil</th>
                     <th>Keterangan</th>
                     <th>Nama Kegunaan Sistem</th>
                     <th>Badge Colour</th>
                     <th class="text-center" width="10%">Tindakan</th>
                </thead>
                <tbody class="align-middle">
                    @foreach ($report_statuses as $report_status)
                        <tr>
                             <td>{{$loop->iteration}}</td>
                             <td>{{$report_status->reference}}</td>
                             <td>{{$report_status->desc}}</td>
                             <td><span class="{{$report_status->class}} font-size-11">{{$report_status->desc}}</span></td>
                             <td class="text-center">
                                 <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary" onclick="editForm(this)" data-edit="{{route('status.edit', $report_status->id)}}" data-update="{{route('status.update', $report_status->id)}}" data-modal="#edit_status_modal" > <i class="bx bxs-pen"></i></a>
                             </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('status._modal.edit')


@endsection

@section('script')
<script>

    $('.btnSubmit').on('click',function(){
        var form = $(this).closest('form');
        var formId = '#'+form.attr('id');
        var formData = new FormData( $(formId)[0] );
        formData.append('_token', '{{csrf_token()}}')
        clearError($(formId));
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success:function(response){
                if(response.success){
                    window.location.reload();
                }
            },
            error:function(errorResponse){
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

    function editForm(row){
        currentRow = $(row);
        modalId = currentRow.attr('data-modal');
        form = $(modalId + ' form');
        form.attr('action', currentRow.attr('data-update'));
        formId = '#' + form.attr('id');
        clearError(form);
        $.ajax({
            url : currentRow.attr('data-edit'),
            method : 'post',
            data:{
                _token: '{{csrf_token()}}'
            },
            success : function(response){
                if(response.success){
                    $(formId + ' input[name="desc"]').val(response.object['desc']);
                    $(formId + ' input[name="reference"]').val(response.object['reference']);
                    $(modalId).modal('show');
                }
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
                            } else {
                                Swal.fire('Gagal', response.message, 'error');
                            }
                        }
                    })
                }
            });
    }
</script>
@endsection
