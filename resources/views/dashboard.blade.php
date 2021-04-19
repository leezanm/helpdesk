@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Laman Utama</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('content')

<div class="row">
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex flex-wrap">
                    <div class="me-3">
                        <p class="text-muted mb-2">Pengguna Baru</p>
                        <h5 class="mb-0">120</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-user-plus"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex flex-wrap">
                    <div class="me-3">
                        <p class="text-muted mb-2">Rekod Baru - MBI</p>
                        <h5 class="mb-0">20</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bxs-book-bookmark"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex flex-wrap">
                    <div class="me-3">
                        <p class="text-muted mb-2">Rekod Baru - FBI</p>
                        <h5 class="mb-0">10</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-archive-out"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap">
            <h5 class="card-title me-2">Rekod Baru</h5>
            <div class="ms-auto">
                <div class="toolbar button-items text-end">
                    <button type="button" class="btn btn-light btn-sm">
                        Semua
                    </button>
                    <button type="button" class="btn btn-light btn-sm">
                        1M
                    </button>
                    <button type="button" class="btn btn-light btn-sm">
                        6M
                    </button>
                    <button type="button" class="btn btn-light btn-sm active">
                        1Y
                    </button>

                </div>
            </div>
        </div>

        <div class="row text-center">
            <div class="col-lg-4">
                <div class="mt-4">
                    <p class="text-muted mb-1">Hari ini</p>
                    <h5>10</h5>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="mt-4">
                    <p class="text-muted mb-1">Bulan ini</p>
                    <h5>123 <span class="text-success font-size-13">0.2 % <i class="mdi mdi-arrow-up ms-1"></i></span></h5>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="mt-4">
                    <p class="text-muted mb-1">Tahun</p>
                    <h5>1023<span class="text-success font-size-13">0.1 % <i class="mdi mdi-arrow-up ms-1"></i></span></h5>
                </div>
            </div>
        </div>

        <hr class="mb-4">

        <div class="apex-charts" id="area-chart" dir="ltr"></div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ asset('assets/js/pages/dashboard-blog.init.js')}}"></script>
<script src="{{ asset('assets/js/pages/saas-dashboard.init.js') }}"></script>
<script src="{{ asset('assets/js/pages/dashboard.init.js') }}"></script>
@endsection
