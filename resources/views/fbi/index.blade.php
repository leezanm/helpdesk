@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">FBI - Faks Bahagian Inspektorat</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Senarai</li>
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
                        <a class="btn btn-primary btn waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#create_fbi_modal">
                            <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="fbi-datatable" class="table table-bordered table-striped table-hover">
                <thead>
                    <th>#</th>
                    <th>No Rujukan Fail</th>
                    <th>Tarikh</th>
                    <th>Kepada</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Tindakan</th>
                </thead>
                <tbody class="align-middle">
                    @foreach($fbis as $fbi)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <th>
                            <a href="#" data-modal="#show_fbi_modal" data-show="{{route('fbi.show', $fbi->id)}}" onclick="showItem(this)">{{$fbi->rujukan}}</a>
                        </th>
                        <td class="p-0">
                            {{$fbi->tarikh}}
                        </td>
                        <th>{{$fbi->kepada}}</th>
                        <th>{{$fbi->jenis}}</th>
                        <td><span class="badge rounded-pill bg-success bg-gradient font-size-13">Aktif</span></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-outline-primary rounded-circle font-size-13 editFbi" data-id="{{$fbi->id}}"><i class="bx bx-pen align-middle"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-info rounded-circle font-size-13" data-modal="#show_fbi_modal" data-show="{{route('fbi.show', $fbi->id)}}" onclick="showItem(this)"><i class="mdi mdi-eye align-middle"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-danger rounded-circle font-size-13" data-action="{{route('fbi.destroy', $fbi->id)}}" onclick="delRow(this)"><i class="bx bxs-trash align-middle"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@include('fbi.partials.show')
@include('fbi.partials.edit')
@include('fbi.partials.create')

@endsection

@section('script')
<script>
    $('#fbi-datatable').DataTable();
    $(document).ready(function(){0<$("#elm1").length&&tinymce.init({selector:"textarea#elm1",height:300,plugins:["advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker","searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking","save table contextmenu directionality emoticons template paste textcolor"],toolbar:"insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons",style_formats:[{title:"Bold text",inline:"b"},{title:"Red text",inline:"span",styles:{color:"#ff0000"}},{title:"Red header",block:"h1",styles:{color:"#ff0000"}},{title:"Example 1",inline:"span",classes:"example1"},{title:"Example 2",inline:"span",classes:"example2"},{title:"Table styles"},{title:"Table row 1",selector:"tr",classes:"tablerow1"}]})});
    $(document).ready(function(){0<$("#perkara").length&&tinymce.init({selector:"textarea#perkara",height:300,plugins:["advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker","searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking","save table contextmenu directionality emoticons template paste textcolor"],toolbar:"insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons",style_formats:[{title:"Bold text",inline:"b"},{title:"Red text",inline:"span",styles:{color:"#ff0000"}},{title:"Red header",block:"h1",styles:{color:"#ff0000"}},{title:"Example 1",inline:"span",classes:"example1"},{title:"Example 2",inline:"span",classes:"example2"},{title:"Table styles"},{title:"Table row 1",selector:"tr",classes:"tablerow1"}]})});

    function clearError(){
        $('textarea').removeClass('is-invalid');
        $('input').removeClass('is-invalid');
        $('.error').addClass('collapse');
    }

    function clearStatus(){
        $('#show-is_active').removeClass('bg-success')
        $('#show-is_active').removeClass('bg-danger')
    }

    $('#btnCreate').on('click',function(){
        clearError();
        var formData = new FormData( $('#fbi-form-create')[0] );
        formData.append('perkara', tinymce.get('elm1').getContent());
        console.log(formData);
        $.ajax({
            url: "{{route('fbi.store')}}",
            type: "post",
            data: formData,
            processData: false,
            contentType: false,
            success:function(response){
                if(response.success){
                    console.log(response);
                    window.location.reload();
                }
            },
            error:function(errorResponse){
                console.log(errorResponse);
                if(errorResponse.responseJSON.errors){
                    errors = errorResponse.responseJSON.errors;
                    $.each(errors, function (key, error){
                        $('input[name="'+key+'"]').addClass('is-invalid');
                        $('textarea[name="'+key+'"]').addClass('is-invalid');
                        $('#error-'+key).html(error[0]);
                        $('#error-'+key).removeClass('collapse');
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
                    for(var property in response.data){
                        $('#show-'+property).html(response.data[property]);
                    }
                    if(response.data['is_active']){
                        clearStatus();
                        $('#show-is_active').addClass('bg-success')
                        $('#show-is_active').html('Aktif');
                    }else{
                        clearStatus();
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

    $('#fbi-datatable').on('click','.editFbi', function(){
        var fbi_id = $(this).attr('data-id');
        $.ajax({
            url: window.location.origin + '/fbi/edit/'+ fbi_id,
            type:'post',
            data:{
                _token:'{{csrf_token()}}'
            },
            success:function(response){
                // console.log(response.fbi.perkara);
                if(response.success){
                    $('#fbi-form-edit input[name="rujukan"]').val(response.fbi.rujukan);
                    $('#fbi-form-edit input[name="tarikh"]').val(response.fbi.tarikh);
                    $('#fbi-form-edit input[name="kepada"]').val(response.fbi.kepada);
                    $('#fbi-form-edit select[name="jenis"]').val(response.fbi.jenis);
                    tinymce.get('perkara').setContent(response.fbi.perkara);
                    $('#btnEdit').data('id', response.fbi.id);
                    $('#edit_fbi_modal').modal('show');
                }
            },
            error: function(error){
                console.log(error);
            }
        })
    })

    $('#btnEdit').on('click',function(){
        clearError();
        var formData = new FormData( $('#fbi-form-edit')[0] );
        formData.append('answer', tinymce.get('answer').getContent())
        var fbi_id = $('#btnEdit').data('id');
        $.ajax({
            url: window.location.origin + '/fbi/update/'+ fbi_id,
            type: "post",
            data: formData,
            processData: false,
            contentType: false,
            success:function(response){
                if(response.success){
                    console.log(response);
                    window.location.reload();
                }
            },
            error:function(errorResponse){
                console.log(errorResponse);
                if(errorResponse.responseJSON.errors){
                    errors = errorResponse.responseJSON.errors;
                    $.each(errors, function (key, error){
                        $('input[name="'+key+'"]').addClass('is-invalid');
                        $('textarea[name="'+key+'"]').addClass('is-invalid');
                        $('#error-'+key).html(error[0]);
                        $('#error-'+key).removeClass('collapse');
                    })
                }
            }
        })
    });

    $('#fbi-datatable').on('click','.delFbi', function(){
        var fbi_id = $(this).attr('data-id');
        $.ajax({
            url: window.location.origin + '/fbi/delete/'+ fbi_id,
            type:'post',
            data:{
                _token:'{{csrf_token()}}'
            },
            success:function(response){
                console.log(response)
                if(response.success){
                    window.location.reload();
                }
            },
            error: function(error){
                console.log(error);
            }
        });
    });

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
<script src="{{ asset('assets/libs/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/libs/dropzone/min/dropzone.min.js') }}"></script>
<script src="{{ asset('assets/js/pages/ecommerce-select2.init.js') }}"></script>
<script src="{{ asset('assets/libs/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
@endsection
