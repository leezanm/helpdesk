@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Backup</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Laman Utama</a></li>
                        <li class="breadcrumb-item active">Senarai Backup</li>
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
                        Semua Sistem dan DB
                    </a>
                    <a class="btn btn-primary btn waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#create">
                        Sistem Sahaja
                    </a>
                    <a class="btn btn-primary btn waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#create">
                        DB Sahaja
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
       <table id="backup" class="table table-bordered table-striped table-hover">
           <thead>
                <th>#</th>
                <th>Nama</th>
                <th>Jenis Fail</th>
                <th>Saiz</th>
                <th>Tarikh/Masa</th>
           </thead>
           <tbody class="align-middle">
                <tr>
                    <td>1</td>
                    <td><a href="#">2020/09/02 10:20AM.db</a></td>
                    <td>DB</td>
                    <td>10 MB</td>
                    <td>2020/09/02 10:20AM</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td><a href="#">2020/09/02 10:20AM.zip</a></td>
                    <td>Zip</td>
                    <td>2 GB</td>
                    <td>2020/09/02 10:20AM</td>
                </tr>
           </tbody>
       </table>
    </div>
</div>

@endsection

@section('script')
<script>
    $('#backup').DataTable();
</script>
@endsection
