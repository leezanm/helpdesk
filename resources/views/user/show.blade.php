@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Butiran Pengguna</h4>

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

    {{-- <style>
        .cardDetail {
            margin-bottom: 24px;
            -webkit-box-shadow: 0 0.75rem 1.5rem rgb(18 38 63 / 3%);
            box-shadow: 0 0.75rem 1.5rem rgb(18 38 63 / 13%);
        }

    </style> --}}
    <div class="card">
        <div class="card-body">
            <div class="mb-4 ml-3 me-3">
                <img src="{{ $user->profile_picture }}" class="rounded-circle avatar-lg" alt="">
            </div>
            <div>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th width="20%">Nama Pengguna</th>
                            <td>{{$user->name}}</td>
                        </tr>
                        <tr>
                            <th width="20%">Jawatan</th>
                            <td>{{$user->designation}}</td>
                        </tr>
                        <tr>
                            <th width="20%">No.Telefon</th>
                            <td>{{$user->tel_no}}</td>
                        </tr>
                        <tr>
                            <th width="20%">Bahagian</th>
                            <td>{{$user->ldap_bahagian}}</td>
                        </tr>
                        <tr>
                            <th width="20%">Alamat</th>
                            <td>{!!$user->address!!}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title">Senarai Aset Pengguna</h3>

            <div class="row mb-3">
                <div class="col-md-12 text-end">
                    <a role="button" class="btn btn-primary btn-rounded waves-effect waves-light mb-2 me-2"
                    data-bs-toggle="modal" data-bs-target="#tambah-aset"><i class="mdi mdi-plus me-1"></i>
                    Aset Baru</a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="user_asset-datatable" class="table table-bordered">
                    <thead class="text-center align-middle text-uppercase">
                        <th>#</th>
                        <th>Jenis</th>
                        <th>Jenama</th>
                        <th>Model</th>
                        <th>No. Siri Pendaftaran</th>
                        <th>No. Siri Pembuat</th>
                        <th>Status</th>
                        <th></th>
                    </thead>
                    <tbody class="text-center align-middle">
                        @foreach ($user->assets as $asset)
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
                                <td>
                                    <a class="btn btn-sm btn-rounded rounded-circle btn-outline-primary edit" data-edit="{{route('user.aset.edit', $asset->id)}}" data-action="{{route('user.aset.update', $asset->id)}}" data-modal="#kemaskini-aset" role="button"> <i class="bx bxs-pen"></i></a>
                                    <a class="btn btn-sm btn-rounded rounded-circle btn-outline-danger delete" data-delete="{{route('user.aset.destroy', $asset->id)}}" role="button"> <i class="bx bx-trash"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('user.partials.modal_asset_create')
    @include('user.partials.modal_asset_edit')

@endsection

@section('script')
    <script>
        $('#user_asset-datatable').DataTable();

        $('#user_asset-datatable').on('click', '.edit', function(e){
            e.preventDefault();
            url = this.dataset.edit;
            modal = $(this.dataset.modal);
            action = this.dataset.action;
            form = modal.find('form')[0];

            $.ajax({
                url: url,
                success:function(response){
                    if(response.success){
                        populateData(response.data, $(form));
                        form.action = action;
                        modal.modal('show');
                    }
                }
            });

        });

        $('#user_asset-datatable').on('click', '.delete', function(e){
            e.preventDefault();
            url = this.dataset.delete;

            Swal.fire({
                title:"Anda pasti untuk memadam rekod ini?",
                icon:"warning",
                showCancelButton:!0,
                confirmButtonColor:"#34c38f",
                cancelButtonColor:"#f46a6a",
                confirmButtonText:"Teruskan",
                cancelButtonText:"Batal",
            })
            .then(function(t){
                if(t.value){
                    $.ajax({
                        url: url,
                        type:'post',
                        data:{
                            _token:'{{csrf_token()}}'
                        },
                        success:function(response){
                            if(response.success){
                                window.location.reload();
                            }
                        }
                    });
                }
            });

        });

        $('.submit').on('click', function(e){
            e.preventDefault();

            form = this.form;
            action = form.action;
            method = form.attributes.method.nodeValue;

            var formData = new FormData( form );
            formData.append('_token', '{{csrf_token()}}');
            $.ajax({
                url: action,
                type:method,
                data:formData,
                processData: false,
                contentType: false,
                success:function(response){
                    if(response.success){
                        window.location.reload();
                    }
                },
                error:function(response){
                    errorValidate(response, $(form));
                }
            });
        });

    </script>
@endsection
