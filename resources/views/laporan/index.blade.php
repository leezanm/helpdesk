@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai Laporan Sistem</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Laporan</li>
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
            <table id="datatable-laporan" class="table table-bordered">
                <thead class="text-center align-middle">
                    <tr>
                        <th>No</th>
                        <th>Laporan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="text-center align-middle">
                    <tr>
                        <td>1</td>
                        <td class="text-start"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_status">Laporan Mengikut Status Tindakan</a></td>
                        <td class="text-center">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_status" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td class="text-start"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_category">Laporan Mengikut Kategori Aduan</a></td>
                        <td class="text-center">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_category" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td class="text-start"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_user">Jumlah Aduan Mengikut Pengguna dan Bahagian</a></td>
                        <td class="text-center">
                            <a data-bs-toggle="modal" data-bs-target="#modal_laporan_by_user" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td class="text-start"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_role">Jumlah Aduan Mengikut Peranan Pengguna</a></td>
                        <td class="text-center">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_by_role" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td class="text-start"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_soal_selidik">Laporan Soal Selidik</a></td>
                        <td class="text-center">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal_laporan_soal_selidik" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @include('laporan.modal.laporan_by_status')
    @include('laporan.modal.laporan_by_category')
    @include('laporan.modal.laporan_by_user')
    @include('laporan.modal.laporan_by_role')
    @include('laporan.modal.laporan_soal_selidik')

@endsection

@section('script')

<script>
    $('#datatable-laporan').DataTable();
</script>

@endsection
