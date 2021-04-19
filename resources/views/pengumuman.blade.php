@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai Pengumuman</h4>

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
                    <a class="btn btn-primary btn waves-effect waves-light" href="#" data-bs-toggle="modal" data-bs-target="#create">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah Pengumuman
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
       <table id="audit" class="table table-bordered table-striped table-hover">
           <thead>
                <th>#</th>
                <th>Nama</th>
                <th>Tarikh / Masa</th>
                <th>Status</th>
                <th>Tindakan</th>
           </thead>
           <tbody class="align-middle">
               <tr>
                    <td>1</td>
                    <td>Hadir ke Majlis Perpisahan</td>
                    <td>2020/09/01 10:02AM</td>
                    <td><span class="badge rounded-pill bg-success bg-gradient font-size-13">AKTIF</span></td>
                    <td class="text-center">
                        <a href="{{url('/laporan-latihan-show')}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                        <a href="{{url('/laporan-latihan-edit')}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
                    </td>
               </tr>
               <tr>
                <td>2</td>
                <td>Program Khidmat Masyarakat</td>
                <td>2021/11/21 10:02AM</td>
                <td><span class="badge rounded-pill bg-danger bg-gradient font-size-13">TIDAK AKTIF</span></td>
                <td class="text-center">
                    <a href="{{url('/laporan-latihan-show')}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                    <a href="{{url('/laporan-latihan-edit')}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Kemaskini Dokumen" data-bs-toggle="modal" data-bs-target="#edit"> <i class="bx bxs-pen align-middle"></i></a>
                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger deletedokumen"  title="Padam Dokumen"> <i class="bx bxs-trash align-middle"></i></a>
                </td>
           </tr>
           </tbody>
       </table>
    </div>
</div>

<div id="create" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-2 col-form-label"><b>Tajuk :</b> </div>
                    <div class="col"><input type="text" class="form-control"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-2 col-form-label"><b>Muat Naik :</b> </div>
                    <div class="col"><input type="file" class="form-control"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-2 col-form-label"><b>Keterangan :</b> </div>
                    <div class="col">
                        <form method="post">
                            <textarea id="elm1" name="area"></textarea>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-end">
                <button type="reset" class="btn btn-secondary waves-effect waves-light"><i class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula</button>
                <a href="#" class="btn btn-success waves-effect waves-light" onclick="Swal.fire('Berjaya','Maklumat Pengguna ditambah','success');" data-bs-dismiss="modal"><i class="bx bx-save font-size-16 align-middle me-2"></i> Hantar</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>



@endsection

@section('script')

<script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>

@endsection
