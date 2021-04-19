@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Tambah Pengguna</h4>

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
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col text-end">
                <div class="button-items">
                    <a class="btn btn-primary btn waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#create">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah Pengguna
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
       <table id="audit" class="table table-bordered table-striped table-hover">
           <thead>
                <th>#</th>
                <th>Nama Pegawai</th>
                <th>Tugas</th>
                <th width="10%">Status</th>
                <th width="5%">Tindakan</th>
           </thead>
           <tbody class="align-middle">
               <tr>
                    <td>1</td>
                    <td class="p-0">
                        <a href="#" class="list-group-item text-muted border-0">
                            <div class="media align-items-center">
                                <div class="me-3">
                                    <img src="{{asset('images/profile/default.png')}}" alt="" class="avatar-sm h-auto d-block rounded">
                                </div>
                                <div class="media-body overflow-hidden">
                                    <h5 class="font-size-13 text-truncate">Muhammad Hazim bin Md Zulkeflee</h5>
                                    <p class="mb-0 text-truncate">Industri</p>
                                </div>
                            </div>
                        </a>
                    </td>
                    <th>Menilai dan analysis pekerja</th>
                    <td><span class="badge rounded-pill bg-success bg-gradient font-size-13">AKTIF</span></td>
                    <td class="text-center">
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
                    </td>
               </tr>
               <tr>
                <td>2</td>
                <td class="p-0">
                    <a href="#" class="list-group-item text-muted border-0">
                        <div class="media align-items-center">
                            <div class="me-3">
                                <img src="{{asset('images/profile/default.png')}}" alt="" class="avatar-sm h-auto d-block rounded">
                            </div>
                            <div class="media-body overflow-hidden">
                                <h5 class="font-size-13 text-truncate">Syamie Syamil</h5>
                                <p class="mb-0 text-truncate">Kejuruteraan</p>
                            </div>
                        </div>
                    </a>
                </td>
                <th>Penyelenggaraan Mesin</th>
                <td><span class="badge rounded-pill bg-success bg-gradient font-size-13">AKTIF</span></td>
                <td class="text-center">
                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
                </td>
           </tr>
           <tr>
            <td>3</td>
            <td class="p-0">
                <a href="#" class="list-group-item text-muted border-0">
                    <div class="media align-items-center">
                        <div class="me-3">
                            <img src="{{asset('images/profile/default.png')}}" alt="" class="avatar-sm h-auto d-block rounded">
                        </div>
                        <div class="media-body overflow-hidden">
                            <h5 class="font-size-13 text-truncate">Fatin binti Sakinah</h5>
                            <p class="mb-0 text-truncate">HR </p>
                        </div>
                    </div>
                </a>
            </td>
            <th>Membuat laporan pegawai</th>
            <td><span class="badge rounded-pill bg-danger bg-gradient font-size-13">TIDAK AKTIF</span></td>
            <td class="text-center">
                <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
            </td>
       </tr>
           </tbody>
       </table>
    </div>
</div>

@include('superadmin.partials.show')
@include('superadmin.partials.edit')
@include('superadmin.partials.create')

@endsection

@section('script')
<script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
@endsection
