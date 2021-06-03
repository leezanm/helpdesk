@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai Aduan</h4>
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
                        <a class="btn btn-primary btn waves-effect waves-light" href="{{url('/aduan/create')}}">
                            <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="datatable-buttons" class="table table-bordered dt-responsive nowrap w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tiket</th>
                        <th>Subjek</th>
                        <th>Tarikh & Masa Aduan</th>
                        <th>Status</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="align-middle">
                    <tr>
                        <td>1</td>
                        <td>281222020-10000</td>
                        <td>Masalah Komputer</td>
                        <td>28/12/2020</td>
                        <td><span class="badge bg-success font-size-12">Untuk Tindakan JTK</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


@endsection

@section('script')

<script>
    $(document).ready(function(){$("#datatable-buttons").DataTable({lengthChange:!1,buttons:["copy","excel","pdf"]}).buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)")});
</script>

@endsection
