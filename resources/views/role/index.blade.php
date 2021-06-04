@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai Peranan Pengguna</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Peranan</li>
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
                        <a class="btn btn-primary btn waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#modal_create_role">
                            <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Cipta
                        </a>
                    </div>
                </div>
            </div>
        </div> --}}
        <div class="card-body">
            <table id="datatable-role" class="table table-bordered">
                <thead class="text-center align-middle">
                    <tr>
                        <th>No</th>
                        <th>Peranan</th>
                        <th>Keterangan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="text-center align-middle">
                    @foreach ($roles as $role)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$role->name}}</td>
                            <td>{{$role->desc}}</td>
                            <th><a href="{{route('role.show', $role->name)}}">lihat</a></th>
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
