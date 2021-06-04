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
                        <a href="#" class="userProfile">Muhammad Hazim</a>
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

        @hasrole('helpdesk')
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
        @endhasrole

        @hasrole('jtk')
            <form> 
                <legend class="text-uppercase h5">Tindakan Juruteknik</legend>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Tindakan</label>
                    </div>
                    <div class="col-md-4">
                        <select name="status_id" class="form-select">
                            <option value="">Menunggu Tindakan</option>
                            <option value="1">Selesai</option>
                            <option value="0">Tidak Selesai</option>
                        </select>
                    </div>
                </div>

                <div id="sebabTakSelesai" class="collapse">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Sebab</label>
                        </div>
                        <div class="col-md-4">
                            <textarea name="" class="form-control"></textarea>
                            <p>Sila kemukakan sebab bagi aduan yang tidak dapat diselesaikan</p>
                        </div>
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
        @endhasrole

        @hasrole('pptm')
            <form> 
                <legend class="text-uppercase h5">Tindakan Pembantu Pegawai Teknologi Maklumat</legend>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Tindakan</label>
                    </div>
                    <div class="col-md-4">
                        <select name="status_id" class="form-select">
                            <option value="">Menunggu Tindakan</option>
                            <option value="1">Selesai</option>
                            <option value="0">Tidak Selesai</option>
                        </select>
                    </div>
                </div>

                <div id="sebabTakSelesai" class="collapse">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Sebab</label>
                        </div>
                        <div class="col-md-4">
                            <textarea name="" class="form-control"></textarea>
                            <p>Sila kemukakan sebab bagi aduan yang tidak dapat diselesaikan</p>
                        </div>
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
        @endhasrole

        @hasrole('ptm')
            <form> 
                <legend class="text-uppercase h5">Tindakan Pegawai Pembantu Teknologi Maklumat</legend>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Tindakan</label>
                    </div>
                    <div class="col-md-4">
                        <select name="" class="form-select">
                            <option value="">Menunggu Tindakan</option>
                            <option value="">Selesai</option>
                            <option value="">Tidak Selesai</option>
                        </select>
                    </div>
                </div>

                <div id="sebabTakSelesai" class="collapse">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Sebab</label>
                        </div>
                        <div class="col-md-4">
                            <textarea name="" class="form-control"></textarea>
                            <p>Sila kemukakan sebab bagi aduan yang tidak dapat diselesaikan</p>
                        </div>
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
        @endhasrole

        @hasrole('pengguna')
            <form> 
                <legend class="text-uppercase h5">Soal Selidik</legend>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Rating</label>
                    </div>
                    <div class="col-md-4">
                        <div class="rating-star">
                            <input type="hidden" class="rating" data-filled="mdi mdi-star text-warning" data-empty="mdi mdi-star-outline text-muted"/>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Komen</label>
                    </div>
                    <div class="col-md-4">
                        <textarea name="" class="form-control"></textarea>
                        <p>Komen anda adalah amat dihargai</p>
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
        @endhasrole

        <legend class="text-uppercase h5 mb-3">Kronologi Aduan</legend>
        <div class="row">
            <div class="col-lg-12">
                <div>
                    <ul class="verti-timeline list-unstyled">
                        <li class="event-list">
                            <div class="event-timeline-dot">
                                <i class="bx bx-right-arrow-circle"></i>
                            </div>
                            <div class="media">
                                <div class="me-3">
                                    {{-- <i class="bx bx-copy-alt h2 text-primary"></i> --}}
                                    1 June 2021, 10.00 AM
                                </div>
                                <div>
                                    <p class="text-muted">Aduan dihantar oleh <a href="#">Muhammad Hazim</a>.</p>
                                </div>
                                {{-- <div class="media-body">
                                    <div>
                                        <h5>Ordered</h5>
                                        <p class="text-muted">New common language will be more simple and regular than the existing.</p>
                                    </div>
                                </div> --}}
                            </div>
                        </li>
                        <li class="event-list">
                            <div class="event-timeline-dot">
                                <i class="bx bx-right-arrow-circle"></i>
                            </div>
                            <div class="media">
                                <div class="me-3">
                                    {{-- <i class="bx bx-package h2 text-primary"></i> --}}
                                    1 June 2021, 11.00 AM
                                </div>
                                <div>
                                    <p class="text-muted">Aduan dimajukan kepada juruteknik <a href="#">En. Muhammad Hafiz</a> oleh admin helpdesk <a href="#">Pn. Aishah</a> untuk diambil tindakan.</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        $('select[name="status_id"').on('change', function(){
            if(this.value === '0'){
                $('#sebabTakSelesai').removeClass('collapse');
            } else {
                $('#sebabTakSelesai').addClass('collapse');
            }

        });

    </script>
@endsection
