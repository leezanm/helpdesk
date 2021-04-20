@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Audit Trail</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Laman Utama</a></li>
                        <li class="breadcrumb-item active">Senarai Audit Trail</li>
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
                <th>#</th>
                <th>Tarikh/Masa</th>
                <th>Pelaku</th>
                <th>IP Address</th>
                <th>Method</th>
                <th>Url</th>
           </thead>
           <tbody class="align-middle">
                @foreach ($audit_trails as $audit_trail)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$audit_trail->created_at}}</td>
                        <td><a href="#" class="viewPelaku" data-id="{{$audit_trail->user_id}}">{{$audit_trail->user->name}}</a></td>
                        <td>{{$audit_trail->ip}}</td>
                        <td>{{$audit_trail->method}}</td>
                        <td class="text-left">{{$audit_trail->url}}</td>
                    </tr>
                @endforeach
           </tbody>
       </table>
    </div>
</div>

@endsection

@section('script')
<script>
    $('#audit').DataTable();
</script>
@endsection
