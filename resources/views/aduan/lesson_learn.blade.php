@extends('layouts.app')
@section('breadcrumb')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Knowledge Base</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item">Aduan</li>
                        <li class="breadcrumb-item active">Knowledge Base</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill"></i>
        <span>Pangkalan pengetahuan punca masalah &amp; penyelesaian daripada aduan yang telah diselesaikan — rujukan untuk masalah serupa.</span>
    </div>
    @livewire('aduan.lesson-learn')
@endsection
