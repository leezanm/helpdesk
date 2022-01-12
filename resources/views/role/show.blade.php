@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai {{isset($role)? $role->desc : 'Pengguna'}} </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item">Peranan</li>
                        <li class="breadcrumb-item active">Pengguna</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col">
            <div class="text-end">
                <a href="{{route('role.index')}}" class="waves-effect waves-light text-center font-size-16 text-info me-3">
                    <i class="bx bx-list-ol bx-tada-hover font-size-24 align-middle me-2"></i> <br> <span class="text-dark align-middle">Senarai Peranan</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <tbody>
                    <tr>
                        <th width="20%">Peranan</th>
                        <td>{{$role->name}}</td>
                    </tr>
                    <tr>
                        <th>Keterangan</th>
                        <td>{{$role->desc}}</td>
                    </tr>
                    <tr>
                        <th>Permissions</th>
                        <td>
                            <ul>
                                @foreach ($permissions as $permission)
                                    <li>{{$permission}}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
            <table id="datatable-role" class="table table-bordered dt-responsive">
                <thead class="text-center align-middle">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Emel</th>
                    </tr>
                </thead>
                <tbody class="text-center align-middle">
                    @foreach ($users as $user)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$user->name}}</td>
                            <td>{{$user->email}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection

@section('script')

<script>
    $('#datatable-role').DataTable();
</script>

@endsection
