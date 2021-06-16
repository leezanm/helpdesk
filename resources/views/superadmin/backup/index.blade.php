@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">BACKUP</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pentadbiran</a></li>
                        <li class="breadcrumb-item active">Backup</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')
    <div class="row mb-1">
        <div class="col text-end">
            <a href="{{route('backup.full')}}" class="waves-effect waves-light text-center font-size-16 text-secondary me-3">
                <i class="bx bx-box bx-tada-hover font-size-24 align-middle me-2"></i> <br> <span class="text-dark align-middle">Penuh</span>
            </a>
            <a href="{{route('backup.database')}}" class="waves-effect waves-light text-center font-size-16 text-secondary me-3">
                <i class="bx bx-data bx-tada-hover font-size-24 align-middle me-2"></i> <br> <span class="text-dark align-middle">Pangkalan Data</span>
            </a>
            <a href="{{route('backup.file')}}" class="waves-effect waves-light text-center font-size-16 text-secondary me-3">
                <i class="bx bx-folder bx-tada-hover font-size-24 align-middle me-2"></i> <br> <span class="text-dark align-middle">Fail</span>
            </a>
        </div>
    </div>

    <div class="card rounded shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table id="backup-datatable" class="table table-bordered">
                    <thead class="text-center">
                        <th> Nama Fail </th>
                        <th> Saiz </th>
                        <th> Tarikh / Masa Backup </th>
                        <th> Tindakan </th>
                    </thead>
                    <tbody class="align-middle text-center">
                        @foreach($backups as $backup)
                        <tr>
                            <td class="text-start"> <a href="{{ route('backup.download', $backup->fileName) }}"> {{ $backup->fileName }} </a> </td>
                            <td> {{ formatBytes($backup->fileSize) }} </td>
                            <td> {{ $backup->fileCreatedAt }} </td>
                            <td class="text-center">
                                <a href="{{route('backup.download', $backup->fileName) }}" class="btn btn-sm btn-rounded rounded-circle btn-outline-success"> <i class="bx bx-download align-middle"></i></a>
                                <a href="{{ route('backup.delete', $backup->fileName) }}" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger " onclick="return confirm('Teruskan tindakan?');"><i class="bx bxs-trash align-middle"></i> </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


@endsection

@section('script')
<script>
    $('#backup-datatable').DataTable();
</script>
@endsection
