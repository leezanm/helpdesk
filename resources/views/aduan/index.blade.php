@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Senarai Aduan</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Senarai</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col text-end">
                    <div class="button-items">
                        <a class="btn btn-primary btn waves-effect waves-light" href="{{url('/aduan/create')}}">
                            <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="datatable-buttons" class="table table-bordered dt-responsive nowrap w-100">
                <thead class="text-center align-middle">
                    <th>No</th>
                    <th>Tiket</th>
                    <th>Subjek</th>
                    <th>Kategori Aduan</th>

                    @can('aduan.assignTask')
                        <th>Pengadu</th>
                    @endcan

                    <th>Tarikh Aduan</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th class="text-center">Tindakan</th>
                </thead>
                <tbody class="text-center align-middle">
                    @foreach ($reports as $report)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$report->ticket_no}}</td>
                            <td class="text-start">{{$report->subject}}</td>
                            <td>{{$report->category->desc}}</td>

                            @can('aduan.assignTask')
                               <td>
                                    <a data-userId="{{$report->reportBy->id}}" class="userProfile" role="button">{{$report->reportBy->name}}</a>
                                    <br>
                                    {{$report->reportBy->ldap_bahagian}}
                               </td>
                             @endcan

                            <td>{{convertDateFormat($report->submitted_at, 'd F Y')}}</td>
                            <td>
                                {!! getDuration($report) !!}
                            </td>
                            <td><span class="{{$report->status->class}}">{{$report->status->desc}}</span></td>
                            <td>
                                <a href="{{route('aduan.show', $report->id)}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-info"> <i class="bx bxs-show align-middle"></i></a>
                                 @if ($report->report_status_id == 1)
                                     <a href="{{route('aduan.edit', $report->id)}}" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"> <i class="bx bxs-pen align-middle"></i></a>
                                 @endif
                                {{-- <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"> <i class="bx bx-pen align-middle"></i></a> --}}
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
