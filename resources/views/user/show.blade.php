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

    <style>
        .cardDetail {
            margin-bottom: 24px;
            -webkit-box-shadow: 0 0.75rem 1.5rem rgb(18 38 63 / 3%);
            box-shadow: 0 0.75rem 1.5rem rgb(18 38 63 / 13%);
        }

    </style>
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4 ml-3 me-3">
                        <img src="{{ asset('images/profile/default.png') }}" class="rounded-circle avatar-lg" alt="">
                    </div>
                    <div>
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th width="20%">Nama Pengguna</th>
                                    <td>
                                        <a class="userProfile" role="button"></a>
                                    </td>
                                </tr>
                                <tr>
                                    <th width="20%">Jawatan</th>
                                    <td>

                                    </td>
                                </tr>
                                <tr>
                                    <th width="20%">No.Telefon</th>
                                    <td>

                                    </td>
                                </tr>
                                <tr>
                                    <th width="20%">Kad Pengenalan</th>
                                    <td>

                                    </td>
                                </tr>
                                <tr>
                                    <th width="20%">Alamat</th>
                                    <td>

                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <h3 class="card-title">Senarai Penempatan Aset</h3>
                        </div>
                        <div class="col-sm-8">
                            <div class="text-sm-end">
                                <a role="button" class="btn btn-primary btn-rounded waves-effect waves-light mb-2 me-2"
                                    data-bs-toggle="modal" data-bs-target="#tambah-aset"><i class="mdi mdi-plus me-1"></i>
                                    Aset Baru</a>
                            </div>
                        </div><!-- end col-->
                    </div>
                    <div class="row" style="max-height: 50rem; overflow-x:auto;">
                        {{-- buat foreach kat sini --}}
                        <div class="col-lg-6">
                            <div class="card cardDetail">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h4 class="card-title mt-0">Bahagian</h4>
                                            <p class="card-text">ICT</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h4 class="card-title mt-0">Jenis</h4>
                                            <p class="card-text">Pencetak (Printer)</p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h4 class="card-title mt-0">Jenama</h4>
                                            <p class="card-text">HP</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h4 class="card-title mt-0">Model</h4>
                                            <p class="card-text">HP-C 6000</p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h4 class="card-title mt-0">No Siri Pendaftaran</h4>
                                            <p class="card-text">KKLW(AUDIO/155/02)1/2/5(g)1/02</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h4 class="card-title mt-0">Status</h4>
                                            <p class="card-text"><span
                                                    class="badge rounded-pill bg-success font-size-13">Aktif</span></p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h4 class="card-title mt-0">No Siri Pembuat</h4>
                                            <p class="card-text">SGH950S18W</p>
                                        </div>
                                        <div class="col-md-6">
                                        </div>

                                    </div>
                                    <div class="text-end">
                                        <a role="button" kemaskini-aset class="card-link mt-5" data-bs-toggle="modal"
                                            data-bs-target="#kemaskini-aset">Kemaskini</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @include('user.partials.modal_asset_create')
    @include('user.partials.modal_asset_edit')

@endsection

@section('script')
    <script>
        $('#aset').DataTable();
        $('#audit').DataTable();
    </script>
@endsection
