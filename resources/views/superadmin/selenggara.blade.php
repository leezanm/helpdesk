@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Selenggara</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Selenggara</li>
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
       <table id="audit" class="table table-bordered table-striped table-hover">
           <thead>
                <th width="5">Bil</th>
                <th>Nama</th>
                <th class="text-center" width="10%">Tindakan</th>
           </thead>
           <tbody class="align-middle">
               <tr>
                    <td>1</td>
                    <td>Peranan</td>
                    <td class="text-center">
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                    </td>
               </tr>
               <tr>
                    <td>2</td>
                    <td>Kategori</td>
                    <td class="text-center">
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Jawatan</td>
                    <td class="text-center">
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
                    </td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Negeri</td>
                    <td class="text-center">
                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"  title="Papar Dokumen" data-bs-toggle="modal" data-bs-target="#show"> <i class="mdi mdi-eye align-middle"></i></a>
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

@endsection
