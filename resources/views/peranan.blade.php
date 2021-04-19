@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai Peranan</h4>

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
    <div class="card-body">
       <table id="audit" class="table table-bordered table-striped table-hover">
           <thead>
                <th>#</th>
                <th>Peranan</th>
           </thead>
           <tbody class="align-middle text-center">
                <tr>
                    <td>1</td>
                    <td>Pengurusan</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Teknikal</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Penghantaran</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Penyelengaraan</td>
                </tr>
           </tbody>
       </table>
    </div>
</div>


@endsection

@section('script')

@endsection
