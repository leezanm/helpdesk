@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Tukar Kata Laluan</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{url('/')}}">Laman Utama</a></li>
                        <li class="breadcrumb-item active">Tukar Kata Laluan</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')

<style>
    .image-upload>input {
      display: none;
    }
</style>

@include('layouts.notification')

<div class="card overflow-hidden">
    <div class="bg-primary bg-soft">
        <div class="text-primary p-3">
            <h5 class="text-primary">Tukar Kata Laluan</h5>
        </div>
    </div>
    <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <th>Kata Laluan Lama :</th>
                        <td><input type="password" name="old_password" class="form-control"></td>
                    </tr>
                    <tr>
                        <th >Kata Laluan Baru :</th>
                        <td><input type="password" name="new_password" class="form-control" id=""></td>
                        <th >Pengesahan Kata Laluan :</th>
                        <td><input type="password" name="new_password_confirmation" class="form-control"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <hr>
        <div class="text-end">
            <button type="submit" class="btn btn-primary m-2 p-2">Hantar<i class="bx bx-send"></i></button>
        </div>
        </form>
    </div>
</div>

@endsection

@section('script')

@endsection
