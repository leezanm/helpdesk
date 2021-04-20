@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Jenis</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item ">Selenggara</li>
                        <li class="breadcrumb-item active">Jenis</li>
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
               @foreach ($types as $type)
                   <tr>
                       <td>{{$loop->iteration}}</td>
                       <td>{{$type->desc}}</td>
                       <td></td>
                   </tr>
               @endforeach
           </tbody>
       </table>
    </div>
</div>


@include('superadmin.partials.show_selenggara')
@include('superadmin.partials.edit')
@include('superadmin.partials.create')

@endsection

@section('script')

@endsection
