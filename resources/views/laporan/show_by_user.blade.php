@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Jumlah Aduan Ditugaskan Mengikut Pengguna dan Bahagian</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Laporan</li>
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
                <input type="hidden" name="username" value="{{$username}}">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Daripada</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-user-from">
                            <input type="text" name="from" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-user-from" data-date-autoclose="true"
                                data-date-end-date="+0d" autocomplete="off" value="{{$from}}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="col-form-label">Sehingga</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-user-to">
                            <input type="text" name="to" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-user-to" data-date-autoclose="true"
                                data-date-end-date="+0d" autocomplete="off" value="{{$to}}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        {{-- <button class="btn btn-secondary" type="button" onclick="$('input').val('')">Set Semula</button> --}}
                        <button class="btn btn-primary" type="submit">Pilih</button>
                        <a href="{{route('laporan.view-by-user')}}" class="btn btn-secondary" role="button">Set Semula</a>

                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Bahagian</label>
                    </div>
                    <div class="col-md-5">
                        <select class="form-select" name="department">
                            <option value="">Semua Bahagian</option>
                            @foreach ($departments as $department_item)
                                <option value="{{$department_item}}" @if($department) selected @endif>{{$department_item}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </form>
            <div class="row justify-content-md-center">
                <div class="col-md-8">
                    <table class="table table-bordered">
                        <thead class="text-center align-middle">
                            <th>Peg. Teknikal</th>
                            <th>Bilangan</th>
                        </thead>
                        <tbody class="text-center align-middle">
                            @foreach ($laporans as $laporan)
                                <tr>
                                    <td><a href="{{route('laporan.view-by-user', ['username' => $laporan->username, 'department' => $department, 'from' => $from, 'to' => $to])}}">{{$laporan->name}}</a></td>
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
            <table id="datatable-buttons" class="table table-bordered dt-responsive nowrap w-100">
                <thead class="text-center align-middle">
                    <tr>
                        <th>No</th>
                        <th>Tiket</th>
                        <th>Subjek</th>
                        <th>Tarikh & Masa Aduan</th>
                        <th>Peg. Teknikal</th>
                        {{-- <th>Status</th> --}}
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="text-center align-middle">
                    @foreach ($report_trails as $report_trail)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$report_trail->ticket_no}}</td>
                            <td class="text-start">{{$report_trail->subject}}</td>
                            <td>{{convertDateFormat($report_trail->submitted_at, 'd F Y, g:i A')}}</td>
                            <td>
                                {{$report_trail->assignedTo->name}}
                                <br>
                                <small>{{$report_trail->assignedTo->ldap_bahagian}}</small>
                            </td>
                            {{-- <td>
                                <span class="{{$report->status->class}} font-size-11">{{$report->status->desc}}</span>
                                @if ($report->report_status_id == 3)
                                    <br>
                                    <a href="#" class="userProfile" data-userId="{{$report->currentTrail->updated_by_id}}">{{$report->currentTrail->updatedBy->name}}</a>
                                @elseif($report->report_status_id == 4)
                                    <br>
                                    <a href="#" class="userProfile" data-userId="{{$report->currentTrail->assigned_to_id}}">{{$report->currentTrail->assignedTo->name}}</a>
                                @endif
                            </td> --}}
                            <td class="text-center">
                                <a href="{{route('aduan.show', $report_trail->report_id)}}" class="btn btn-primary waves-effect waves-light">
                                    <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('script')

<script>
$(document).ready(function(){$("#datatable-buttons").DataTable({lengthChange:!1,buttons:["copy","excel","pdf"]}).buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)")});
</script>

@endsection
