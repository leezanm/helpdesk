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
                        <a href="#" class="userProfile">{{$report->reportBy->name}}</a>
                    </td>
                </tr>
                <tr>
                    <th width="20%">Jabatan</th>
                    <td>
                        Bahagian Teknologi Maklumat
                    </td>
                </tr>
                <tr>
                    <th width="20%">Username</th>
                    <td>
                        {{$report->reportBy->username}}
                    </td>
                </tr>
                <tr>
                    <th width="20%">Email</th>
                    <td>
                        {{$report->reportBy->email}}
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
                        {{$report->subject}}
                    </td>
                </tr>
                <tr>
                    <th width="20%">Dihantar Pada</th>
                    <td>
                        {{ convertDateFormat($report->submitted_at, 'd F Y') }}
                    </td>
                </tr>
                <tr>
                    <th width="20%">Kategori Kerosakan</th>
                    <td>
                        {{$report->category->desc}}
                    </td>
                </tr>
                <tr>
                    <th width="20%">Aduan</th>
                    <td>
                        {!!$report->remark!!}
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <hr>

        @can('aduan.assignTask')
            @if ($report->report_status_id == 2)
                <form action="{{route('aduan.task.store', $report->id)}}" method="POST">
                    @csrf
                    {{-- <legend class="text-uppercase h5">Tindakan Helpdesk Admin</legend> --}}

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Majukan Kepada</label>
                        </div>
                        <div class="col-md-4">
                            <select id="majukan_kepada_peranan" class="form-select">
                                <option value="">Sila Pilih</option>
                                @foreach ($roles as $role)
                                    <option value="{{$role->name}}">{{$role->desc}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Kepada</label>
                        </div>
                        <div class="col-md-4">
                            <select name="assigned_to_id" id="assigned_to_id" class="form-select">

                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-end">
                                <button type="submit" class="btn btn-success waves-effect waves-light">
                                    <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        @endcan

        @isset($current_task)
            @can('aduan.updateTask')
                @if (in_array($current_task->task_status_id, [1,2]) && $current_task->assignedTo->is(\Auth::user()) )
                    <form action="{{route('aduan.task.update', $current_task->id)}}" method="POST"> 
                        @csrf
                        {{-- <legend class="text-uppercase h5">Tindakan Juruteknik</legend> --}}

                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label class="col-form-label">Status Aduan</label>
                            </div>
                            <div class="col-md-4">
                                <select name="task_status_id" class="form-select">
                                    @foreach ($task_statuses as $task_status)
                                        <option value="{{$task_status->id}}" @if( $current_task->task_status_id  == $task_status->id ) selected @endif >{{$task_status->desc}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="sebabTakSelesai" class="collapse">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="col-form-label">Sebab</label>
                                </div>
                                <div class="col-md-4">
                                    <textarea name="remark" class="form-control"></textarea>
                                    <p>Sila kemukakan sebab bagi aduan yang tidak dapat diselesaikan</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success waves-effect waves-light">
                                        <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>
                @endif
            @endcan
        @endisset

        @can($report->reportBy->is(\Auth::user()) && $report->report_status_id == 10 )
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
        @endif

        <legend class="text-uppercase h5 mb-3">Kronologi Tindakan</legend>
        <table class="table table-bordered">
            <thead class="text-center align-middle">
                <th>No.</th>
                <th>Diberikan Oleh</th>
                <th>Diberikan Kepada</th>
                <th>Diberikan Pada</th>
                <th>Status</th>
            </thead>
            <tbody class="text-center align-middle">
                @foreach ($tasks as $task)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$task->assignedBy->name}}</td>
                        <td>{{$task->assignedTo->name}}</td>
                        <td>{{convertDateFormat($task->assigned_at, 'd F Y')}}</td>
                        <td><span class="{{$task->taskStatus->class}}">{{$task->taskStatus->desc}}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection

@section('script')
    <script>
        $('select[name="task_status_id"').on('change', function(){
            if(this.value === '4'){
                $('#sebabTakSelesai').removeClass('collapse');
            } else {
                $('#sebabTakSelesai').addClass('collapse');
            }

        });

        $('#majukan_kepada_peranan').on('change', function(){
            $('#assigned_to_id').empty();
            if(this.value){
                var role_name = this.value;
                $.post(
                    "{{url('api/get-users-by-role')}}",
                    {
                        role_name : role_name
                    },
                    function(response){
                        option = '<option value="">Sila Pilih</option>'
                        if(response.success){
                            response.users.forEach(user => {
                                option += '<option value="'+ user.id +'">'+ user.name +'</option>';
                            });
                        } 

                        $('#assigned_to_id').append(option);
                    }
                )
            } 
        })

    </script>
@endsection
