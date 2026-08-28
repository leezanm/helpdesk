@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Butiran Pengguna</h4>

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
        <div class="card-body">
            <div class="mb-4 ml-3 me-3">
                <img src="{{ $user->profile_picture }}" class="rounded-circle avatar-lg" alt="">
            </div>
            <div>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th width="20%">Nama Pengguna</th>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th width="20%">Jawatan</th>
                            <td>{{ $user->designation }}</td>
                        </tr>
                        <tr>
                            <th width="20%">No.Telefon</th>
                            <td>{{ $user->tel_no }}</td>
                        </tr>
                        <tr>
                            <th width="20%">Bahagian</th>
                            <td>{{ $user->ldap_bahagian }}</td>
                        </tr>
                        <tr>
                            <th width="20%">Alamat</th>
                            <td>{!! $user->address !!}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @livewire('user.user-asset-manager', ['user' => $user])

@endsection
