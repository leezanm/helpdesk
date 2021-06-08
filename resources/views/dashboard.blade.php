@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Laman Utama</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('content')

<div class="row">
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex align-items-stretch">
                    <div class="me-3">
                        <p class="text-muted mb-2">Jumlah Aduan</p>
                        <h5 class="mb-0">{{ $newUser }}</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-list-ul"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex align-items-stretch">
                    <div class="me-3">
                        <p class="text-muted mb-2">Rekod Selesai</p>
                        <h5 class="mb-0">{{ $mbiTotal }}</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-list-check"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex align-items-stretch">
                    <div class="me-3">
                        <p class="text-muted mb-2">Rekod Dalam Tindakan</p>
                        <h5 class="mb-0">{{ $fbiTotal }}</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-transfer"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-sm-12 col-md-8">
        <div class="card" style="max-height: 30rem;">
            <div class="card-body">
                <div class="d-flex flex-wrap">
                    <h5 class="card-title me-2 mb-4">Aduan Hari ini</h5>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">No</th>
                                <th class="align-middle">Ticket</th>
                                <th class="align-middle">Subject</th>
                                <th class="align-middle">Status</th>
                                <th class="align-middle">Tindakan</th>

                            </tr>
                        </thead>
                        @hasrole('helpdesk')
                            <tbody>
                                <tr>
                                    <td><a href="javascript: void(0);" class="text-body fw-bold">1</a> </td>
                                    <td>97216-72612</td>
                                    <td>
                                        Komputer Riba Rosak
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-soft-info font-size-11">Dihantar</span>
                                    </td>
                                    <td>
                                        <a href="/aduan/show" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light">
                                            Lihat
                                        </a>
                                    </td>
                                </tr>    

                                <tr>
                                    <td><a href="javascript: void(0);" class="text-body fw-bold">3</a> </td>
                                    <td>51838-21235</td>
                                    <td>
                                        Internet Slow
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-soft-info font-size-11">Dihantar</span>
                                    </td>
                                    <td>
                                        <a href="/aduan/show" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light">
                                            Lihat
                                        </a>
                                    </td>
                                </tr>

                                <tr>
                                    <td><a href="javascript: void(0);" class="text-body fw-bold">4</a> </td>
                                    <td>82718-82790</td>
                                    <td>
                                        Editor Word mempunyai masalah
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-soft-info font-size-11">Dihantar</span>
                                    </td>
                                    <td>
                                        <a href="/aduan/show" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light">
                                            Lihat
                                        </a>
                                    </td>
                                </tr>

                            </tbody>
                        @else
                            <tbody>
                                <tr>
                                    <td><a href="javascript: void(0);" class="text-body fw-bold">1</a> </td>
                                    <td>97216-72612</td>
                                    <td>
                                        Komputer Riba Rosak
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-soft-info font-size-11">Dalam Tindakan</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#view_report_modal">
                                            Lihat
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm btn-rounded waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#user_feedback">
                                            Selesai
                                        </button>
                                    </td>
                                </tr>    

                                <tr>
                                    <td><a href="javascript: void(0);" class="text-body fw-bold">3</a> </td>
                                    <td>51838-21235</td>
                                    <td>
                                        Internet Slow
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-soft-info font-size-11">Dihantar (Ambil Maklum)</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#view_report_modal">
                                            Lihat
                                        </button>
                                    </td>
                                </tr>

                                <tr>
                                    <td><a href="javascript: void(0);" class="text-body fw-bold">4</a> </td>
                                    <td>82718-82790</td>
                                    <td>
                                        Editor Word mempunyai masalah
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-soft-info font-size-11">Dihantar</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#view_report_modal">
                                            Lihat
                                        </button>
                                    </td>
                                </tr>

                            </tbody>
                        @endhasrole
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-md-4">
        <div class="card" style="max-height: 30rem;">
            <div class="card-body">
                <div class="d-flex">
                    <div class="me-2">
                        <h5 class="card-title mb-4">Jumlah Tindakan Mengikut Jawatan</h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Jawatan</th>
                            <th scope="col">Jumlah</th>
                        </tr>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>
                                    <h5 class="font-size-13 text-truncate mb-1"><a href="#" class="text-dark">Pegawai Admin</a></h5>
                                    <p class="text-muted mb-0">Dikemaskini : 10 Nov, 2020</p>
                                </td>
                                <td> 125</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>
                                    <h5 class="font-size-13 text-truncate mb-1"><a href="#" class="text-dark">Pegawai Helpdesk</a></h5>
                                    <p class="text-muted mb-0">Dikemaskini :  02 Nov, 2020</p>
                                </td>
                                <td> 102</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>
                                    <h5 class="font-size-13 text-truncate mb-1"><a href="#" class="text-dark">Pegawai Teknologi</a></h5>
                                    <p class="text-muted mb-0">Dikemaskini :  24 Oct, 2020</p>
                                </td>
                                <td> 98</td>           
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade transaction-detailModal" tabindex="-1" role="dialog" aria-labelledby="transaction-detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transaction-detailModalLabel">Maklumat Aduan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap">
                        <tbody>
                            <tr>
                                <td>No. Tiket : </td>
                                <td><span class="text-primary">97216-72612</span></td>
                            </tr>
                            <tr>
                                <td>Nama pengadu : </td>
                                <td><span class="text-primary">Malik Kunanlan</span></td>
                            </tr>
                            <tr>
                                <td>Butiran Aduan : </td>
                                <td>Aduan Komputer riba rosak yang tidak boleh dibuka akibat bateri yang sudah kembung dan mempunyai bateri hayat yang pendek</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<!-- end modal -->
    @include('partials.modal.view_report')
    @include('partials.modal.feedback')
@endsection

@section('script')

@endsection
