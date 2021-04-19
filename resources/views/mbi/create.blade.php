@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">MBI - Minit Bebas Bahagian Inspektorat</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item">Senarai</li>
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

                    <h4 class="card-title">Butiran MBI</h4>
                    <p class="card-title-desc">Sila isi butiran tersebut</p>

                    <form>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label for="rujukan">Rujukan</label>
                                    <input id="rujukan" name="rujukan" type="text" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="tarikh">Tarikh</label>
                                    <input id="tarikh" name="tarikh" type="date" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label for="kepada">Kepada</label>
                                    <input id="kepada" name="kepada" type="text" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="control-label">Jenis</label>
                                    <select class="form-control select2">
                                        <option>-Sila Pilih-</option>
                                        <option value="sekolah">Sekolah</option>
                                        <option value="perubatan">Perubatan</option>
                                        <option value="kat">Kat</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm">
                                <div class="mb-3">
                                    <label for="productdesc">Perkara</label>
                                    <form method="post">
                                        <textarea id="elm1" name="area"></textarea>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary waves-effect waves-light">Simpan</button>
                            <button type="button" class="btn btn-secondary waves-effect waves-light">Batal</button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
<script src="{{ asset('assets/libs/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/libs/dropzone/min/dropzone.min.js') }}"></script>
<script src="{{ asset('assets/js/pages/ecommerce-select2.init.js') }}"></script>
<script src="{{ asset('assets/libs/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>

@endsection
