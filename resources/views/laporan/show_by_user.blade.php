@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Jumlah Aduan Mengikut Pengguna dan Bahagian</h4>
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
    <div class="row mb-3">
        <div class="col">
            <div class="text-end">
                <a href="{{url('/laporan')}}" class="waves-effect waves-light text-center font-size-16 text-info me-3">
                    <i class="bx bx-list-ol bx-tada-hover font-size-24 align-middle me-2"></i> <br> <span class="text-dark align-middle">Senarai Laporan</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="col-form-label">Daripada</label>
                </div>
                <div class="col-md-3">
                    <div class="position-relative" id="laporan-user-from">
                        <input type="text" name="from" class="form-control text-center" data-provide="datepicker"
                            data-date-container="#laporan-user-from" data-date-autoclose="true"
                            data-date-end-date="+0d" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="col-form-label">Sehingga</label>
                </div>
                <div class="col-md-3">
                    <div class="position-relative" id="laporan-user-to">
                        <input type="text" name="to" class="form-control text-center" data-provide="datepicker"
                            data-date-container="#laporan-user-to" data-date-autoclose="true"
                            data-date-end-date="+0d" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary">Pilih</button>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="col-form-label">Bahagian</label>
                </div>
                <div class="col-md-5">
                    <select class="form-select">
                        <option value="">Semua Bahagian</option>
                        <option value="">Divisyen 1</option>
                        <option value="">Divisyen 2</option>
                    </select>
                </div>
            </div>
            <div class="row justify-content-md-center">
                <div class="col-md-8">
                    <table class="table table-bordered">
                        <thead class="text-center align-middle">
                            <th>Bahagian</th>
                            <th>Bilangan</th>
                        </thead>
                        <tbody class="text-center align-middle">
                            <tr>
                                <td class="text-start">Hardware</td>
                                <td>16</td>
                            </tr>
                            <tr>
                                <td class="text-start">Software</td>
                                <td>602</td>
                            </tr>
                        </tbody>
                        <tfoot class="text-center align-middle">
                            <tr>
                                <th>Jumlah</th>
                                <th>618</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
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
                        <td>
                            <span class="badge bg-success font-size-12">Dalam Tindakan JTK</span>
                            <br>
                            <a href="">Muhammad Hazim</a>
                        </td>
                        <td class="text-center">
                            <a href="{{url('/aduan/show')}}" class="btn btn-primary waves-effect waves-light">
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
$(document).ready(function(){$("#datatable-buttons").DataTable({lengthChange:!1,buttons:["copy","excel","pdf"]}).buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)")});
</script>

@endsection
