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
    {{-- <div class="card-header">
        <div class="row">
            <div class="col text-end">
                <div class="button-items">
                    <a class="btn btn-primary btn waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#create_user_modal">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah Pengguna
                    </a>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="card-body">
       <table id="user-datatable" class="table table-bordered table-striped table-hover">
           <thead>
                <th>#</th>
                <th>Nama Pegawai</th>
                <th>E-mel</th>
                <th>Status</th>
                <th>Tindakan</th>
           </thead>
           <tbody class="align-middle">
               @foreach ($users as $user)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="p-0">
                            <a class="list-group-item text-muted border-0" data-modal="#show_user_modal" data-show="{{route('user.showUser', $user->id)}}" onclick="showItem(this)" role="button">
                                <div class="media align-items-center">
                                    <div class="me-3">
                                        <img src="{{ $user->profile_picture }}" alt="" class="avatar-sm h-auto d-block rounded">
                                    </div>
                                    <div class="media-body overflow-hidden">
                                        <h5 class="font-size-13 text-truncate">{{ $user->name }}</h5>
                                        <p class="mb-0 text-truncate">{{ $user->designation }}</p>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <th>{{ $user->email }}</th>
                        <th>
                            @if ($user->is_active)
                                <span class="badge bg-success">AKTIF</span>
                            @else
                                <span class="badge bg-danger">TIDAK AKTIF</span>
                            @endif
                        </th>
                        <td class="text-center">
                            <a href="{{route('user.showFullUser', $user->id)}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-info"> <i class="mdi mdi-eye"></i></a>
                            <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary" onclick="editForm(this)" data-edit="{{route('user.editUser', $user->id)}}" data-update="{{route('user.updateUser', $user->id)}}" data-modal="#edit_user_modal" > <i class="bx bxs-pen"></i></a>
                            @if ($user->is_active)
                                <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger" data-action="{{route('user.destroy', $user->id)}}" onclick="delRow(this)"> <i class="bx bx-lock"></i></a>
                            @else
                                <a href="{{route('user.restore', $user->id)}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-success"> <i class="bx bx-lock-open"></i></a>
                            @endif

                        </td>
                    </tr>
               @endforeach
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
    $(document).ready(function(){0<$("#address").length&&tinymce.init({selector:"textarea#address",height:300,plugins:["advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker","searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking","save table contextmenu directionality emoticons template paste textcolor"],toolbar:"insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons",style_formats:[{title:"Bold text",inline:"b"},{title:"Red text",inline:"span",styles:{color:"#ff0000"}},{title:"Red header",block:"h1",styles:{color:"#ff0000"}},{title:"Example 1",inline:"span",classes:"example1"},{title:"Example 2",inline:"span",classes:"example2"},{title:"Table styles"},{title:"Table row 1",selector:"tr",classes:"tablerow1"}]})});
</script>

<script>
    $('#user-datatable').DataTable();
    function clearError(){
        $('textarea').removeClass('is-invalid');
        $('input').removeClass('is-invalid');
        $('.error').addClass('collapse');
        $('.error').html('');
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
        // formData.append('address', tinymce.get('address').getContent());
        formData.append('_token', '{{csrf_token()}}')
        clearError(formId);
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
                        $(formId + ' .error-'+key).html(error[0]);
                        $(formId + ' .error-'+key).removeClass('collapse');
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

                    var user = response.data;

                    for (const attribute in user) {
                        if (attribute == 'profile_path') {
                            $('#show-profile_path').attr('src', (response.data[attribute]));
                        } else {
                            $('#show-'+attribute).html(user[attribute]);
                        }
                    }


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

        clearError(form.attr('action'));

        $('.edit_role_check').prop('checked', false);

        $.ajax({
            url : currentRow.attr('data-edit'),
            method : 'get',
            success : function(response){
                data = response.data;
                user_roles = response.user_roles;
                if(response.success){

                    for (var property in data) {
                        form.find('input[name="' + property + '"]').val(data[property]);
                        form.find('select[name="' + property + '"]').val(data[property]);
                        form.find('select[name="' + property + '"]').trigger('change');
                        form.find('textarea[name="' + property + '"]').html(data[property]);

                    }

                    user_roles.forEach(user_role => {
                        form.find('#edit_role_check-'+user_role.id).prop('checked',true);
                    });

                    $('#edit-profile_path').attr('src', (response.data['profile_picture']));
                    $('#edit-user_id').val(data.id);

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
