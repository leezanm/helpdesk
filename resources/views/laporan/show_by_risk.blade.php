@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai Aduan {{$title}}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item ">Laporan</li>
                        <li class="breadcrumb-item active">{{$title}}</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col">
            <div class="text-end">
                <a href="{{url('/laporan')}}" class="waves-effect waves-light text-center font-size-16 text-info me-3">
                    <i class="bx bx-list-ol bx-tada-hover font-size-24 align-middle me-2"></i> <br> <span class="text-dark align-middle">Senarai Laporan</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">

            <form>
                <input type="hidden" name="risk" value="{{$risk}}">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Daripada</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-risk-from">
                            <input type="text" name="from" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-risk-from" data-date-autoclose="true"
                                data-date-end-date="+0d" autocomplete="off" value="{{$from}}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="col-form-label">Sehingga</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-risk-to">
                            <input type="text" name="to" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-risk-to" data-date-autoclose="true"
                                data-date-end-date="+0d" autocomplete="off" value="{{$to}}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">Pilih</button>
                        <a href="{{route('laporan.view-by-risk')}}" class="btn btn-secondary" role="button">Set Semula</a>
                    </div>
                </div>
            </form>

            <div class="row justify-content-md-center">
                <div class="col-md-8">
                    <table class="table table-bordered">
                        <thead class="text-center align-middle">
                            <th>Tahap Risiko</th>
                            <th>Bilangan</th>
                        </thead>
                        <tbody class="text-center align-middle">
                            @foreach ($laporans as $laporan)
                                <tr>
                                    <td>
                                        @if ($laporan->level)
                                            <a href="{{route('laporan.view-by-risk', ['risk' => $laporan->level, 'from' => $from, 'to' => $to])}}">
                                                <span class="{{$laporan->class}}">{{$laporan->desc}}</span>
                                            </a>
                                        @else
                                            <span class="{{$laporan->class}}">{{$laporan->desc}}</span>
                                        @endif
                                    </td>
                                    <td>{{$laporan->jumlah}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="text-center align-middle">
                            <tr>
                                <th>Jumlah</th>
                                <th>{{$laporans->sum('jumlah')}}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable-buttons" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tiket</th>
                            <th>Subjek</th>
                            <th>Tahap Risiko</th>
                            <th>Tarikh Aduan</th>
                            <th>Status</th>
                            <th class="text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @foreach ($reports as $report)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$report->ticket_no}}</td>
                                <td>{{$report->subject}}</td>
                                <td>
                                    @if ($report->riskLevel)
                                        <span class="{{$report->riskLevel->class}} font-size-11">{{$report->riskLevel->label}}</span>
                                    @else
                                        <span class="badge bg-light text-dark font-size-11">Belum Ditetapkan</span>
                                    @endif
                                </td>
                                <td>{{convertDateFormat($report->submitted_at, 'LL')}}</td>
                                <td>
                                    <span class="{{$report->status->class}} font-size-11">{{$report->status->desc}}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{route('aduan.show', $report)}}" class="btn btn-primary waves-effect waves-light">
                                        <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script')

<script>
$(document).ready(function(){$("#datatable-buttons").DataTable({lengthChange:!1,buttons:["copy","excel","pdf"]}).buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)")});
</script>

@endsection
