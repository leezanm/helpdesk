@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Aduan Baru</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item">Aduan</li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="card-title">Maklumat Aduan</h4>
                    <p class="card-title-desc">Sila isi butiran tersebut</p>
                    <form>                   
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="rujukan">Bentuk Aduan</label>                                    
                            </div>
                            <div class="col">
                                <div class="row">
                                    <div class="col-xl-2 col-sm-4">
                                        <label class="card-radio-label">
                                            <input type="radio" name="bentuk-aduan" id="individu" class="card-radio-input">
                                            <div class="card-radio">
                                                <i class="fas fa-user font-size-24 text-primary align-middle me-2"></i>
                                                <span>Individu</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-xl-2 col-sm-4">
                                        <label class="card-radio-label">
                                            <input type="radio" name="bentuk-aduan" id="wakil" class="card-radio-input" checked>
                                            <div class="card-radio">
                                                <i class="fas fa-user-friends font-size-24 text-primary align-middle me-2"></i>
                                                <span>Wakil</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Nama Pengadu</label>
                            <div class="col-md-4">
                                <input class="form-control" type="text" id="nama-pengadu">
                            </div>
                            <label for="example-text-input" class="col-md-2 col-form-label">Username</label>
                            <div class="col-md-4">
                                <input class="form-control" type="text" id="username">
                            </div>
                        </div>
                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Nama Penuh</label>
                            <div class="col-md-4">
                                <input class="form-control" type="text" id="nama-penuh">
                            </div>
                        </div>
                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Email</label>
                            <div class="col-md-4">
                                <input class="form-control" type="email" id="email">
                            </div>
                            <label for="example-text-input" class="col-md-2 col-form-label">Bahagian</label>
                            <div class="col-md-4">
                                <input class="form-control" type="text" id="bahagian">
                            </div>
                        </div>
                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Subjek</label>
                            <div class="col-md-4">
                                <input class="form-control" type="text" id="nama-penuh">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm">
                                <div class="mb-3">
                                    <label for="productdesc" class="mb-2">Butiran Aduan</label>
                                    <form method="post">
                                        <textarea id="elm1" name="area"></textarea>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 mb-3 row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Muat Naik Laporan / Dokumen</label>
                            <div class="col-md-4">
                                <input class="form-control" type="file" id="laporan">
                            </div>
                        </div>
                        <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="floatingCheck">
                                    <label class="form-check-label" for="floatingCheck">
                                      Dengan ini saya mengaku dan mengesahkan semua kenyataan dan butir-butir yang dikemukakan di atas adalah benar
                                    </label>
                                </div>
                            </div>
                        <div class="d-flex flex-wrap gap-2 float-end">
                            <a href="{{url('aduan')}}" class="btn btn-primary waves-effect waves-light">Simpan</a>
                            <a href="{{url('aduan')}}" class="btn btn-success waves-effect waves-light">Hantar</a>
                            <!-- <button type="submit" class="btn btn-primary waves-effect waves-light">Simpan</button> -->
                            <!-- <button type="button" class="btn btn-success waves-effect waves-light">Hantar</button> -->
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>

@endsection

@section('script')>
<script src="{{ asset('assets/libs/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
@endsection
