@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Aduan</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Horizontal Layout</li>
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
        <legend class="text-uppercase h5">MAKLUMAT PEMOHON</legend>
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th width="20%">Pemohon</th>
                    <td>
                        <a href="" class="userProfile">Muhammad Hazim</a>
                    </td>
                </tr>
                <tr>
                    <th width="20%">Jabatan</th>
                    <td>
                        Bahagian Teknologi Maklumat
                    </td>
                </tr>
                <tr>
                    <th width="20%">Email</th>
                    <td>
                        hazim@gmail.com
                    </td>
                </tr>
            </tbody>
        </table>
        <legend class="text-uppercase h5">MAKLUMAT ADUAN</legend>
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th width="20%">No Ticket</th>
                    <td>
                        ADU019376
                    </td>
                </tr>
                <tr>
                    <th width="20%">Subjek</th>
                    <td>
                        Aduan Kerosakan Komputer
                    </td>
                </tr>
                <tr>
                    <th width="20%">Dihantar Pada</th>
                    <td>
                        3 June 2021, 2:43 PM
                    </td>
                </tr>
                <tr>
                    <th width="20%">Kategori Kerosakan</th>
                    <td>
                        Komputer Riba (Notebook)
                    </td>
                </tr>
                <tr>
                    <th width="20%">Aduan</th>
                    <td>
                        Komputer hang bila on online meeting menggunakan google meet
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <hr>
        <form>
            <legend class="text-uppercase h5">Tindakan Helpdesk Admin</legend>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="col-form-label">Tindakan</label>
                </div>
                <div class="col-md-3">
                    <select name="" class="form-select">
                        <option value="">Menunggu Tindakan</option>
                        <option value="">Majukan Kepada Juruteknik</option>
                        <option value="">Majukan Kepada PPTM</option>
                        <option value="">Majukan Kepada PTM</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="col-form-label">Majukan Kepada</label>
                </div>
                <div class="col-md-4">
                    <select name="" class="form-select">
                        <option value="">En. Muhammad Farhan Bin Zawawi</option>
                        <option value="">Datuk. Muhammad Arham Bin Zulqarnaen</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="text-end">
                        <a class="btn btn-success waves-effect waves-light save">
                            <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')

@endsection
