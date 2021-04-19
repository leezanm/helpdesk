@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Profil Pengguna</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{url('/')}}">Laman Utama</a></li>
                        <li class="breadcrumb-item active">Profil</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')


@include('layouts.notification')

<div class="card overflow-hidden">
    <div class="bg-primary bg-soft">
        <div class="text-primary p-3">
            <h5 class="text-primary">Maklumat profil</h5>
        </div>
    </div>
    <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col"></div>
            <div class="col-lg-4 text-center mb-3">
                <img class="img-thumbnail rounded-circle mb-3" alt="" src="{{asset($user->profile_picture)}}" height="100" width="100">
                <input type="file" name="image" class="form-control text-center">
                <small class="text-muted">Sila pastikan gambar bersaiz kurang dari 1MB dan format (jpg, jpeg dan png) </small>
            </div>
            <div class="col"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <th>Tarikh Dicipta :</th>
                        <td><h5>{{ date('d-m-Y', strtotime($user->created_at)) }}</h5></td>
                    </tr>
                    <tr>
                        <th>Nama Penuh :</th>
                        <td><input type="text" name="name" class="form-control" value="{{ $user->name }}"></td>
                        <th>No. Telefon :</th>
                        <td><input type="text" name="" id="" class="form-control" value="012-387478389"></td>
                    </tr>
                    <tr>
                        <th >E-mel :</th>
                        <td><input type="text" name="email" class="form-control" id="" value="{{ $user->email }}" disabled></td>
                        <th > Peranan :</th>
                        <td><input type="text" name="" id="" class="form-control" value=""></td>
                    </tr>
                    <tr>
                        <th>Nama Syarikat</th>
                        <td><input type="text" name="" class="form-control" id="" value="Loob Holding Sdn Bhd" disabled></td>
                    </tr>
                    <tr>
                        <th>Alamat :</th>
                        <td><textarea name="" id="" rows="3" class="form-control"></textarea></td>
                        <th>Poskod :</th>
                        <td><input type="text" name="" id="" class="form-control"></td>
                    </tr>
                    <tr>
                        <th>Daerah :</th>
                        <td><input type="text" name="" id="" class="form-control"></td>
                        <th>Negeri :</th>
                        <td><input type="text" name="" id="" class="form-control"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-primary m-2">Hantar <i class="bx bx-send"></i></button>
        </div>
        </form>
    </div>
</div>


@endsection

@section('script')

@endsection
