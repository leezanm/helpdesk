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
                        <li class="breadcrumb-item active">Peranan Pengguna</li>
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
                    <tr>
                        <td>1</td>
                        <td class="text-start">superadmin</td>
                        <td class="text-start">Pentadbir Sistem</td>
                        <td class="text-center">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_status" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td class="text-start">pengguna</td>
                        <td class="text-start">Pengguna warga KPLB</td>
                        <td class="text-center">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_category" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </a>
                        </td>
                    </tr>
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
