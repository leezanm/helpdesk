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

                <div class="d-flex align-items-stretch">
                    <div class="me-3">
                        <p class="text-muted mb-2">Jumlah Aduan</p>
                        <h5 class="mb-0">{{ $newUser }}</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-list-ul"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex align-items-stretch">
                    <div class="me-3">
                        <p class="text-muted mb-2">Rekod Selesai</p>
                        <h5 class="mb-0">{{ $mbiTotal }}</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-list-check"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col">
        <div class="card mini-stats-wid">
            <div class="card-body">

                <div class="d-flex align-items-stretch">
                    <div class="me-3">
                        <p class="text-muted mb-2">Rekod Dalam Tindakan</p>
                        <h5 class="mb-0">{{ $fbiTotal }}</h5>
                    </div>

                    <div class="avatar-sm ms-auto">
                        <div class="avatar-title bg-light rounded-circle text-primary font-size-20">
                            <i class="bx bx-transfer"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 col-md-8">
        <div class="card">
            <div class="card-body">

                @can('aduan.assignTask')
                    <div class="d-flex flex-wrap">
                        <h5 class="card-title me-2 mb-4">Aduan Yang Belum Diproses</h5>
                    </div>
                @elsecan('aduan.updateTask')
                    <div class="d-flex flex-wrap">
                        <h5 class="card-title me-2 mb-4">Tugasan Saya</h5>
                    </div>
                @endcan

                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">No</th>
                                <th class="align-middle">Ticket</th>
                                <th class="align-middle">Subject</th>
                                <th class="align-middle">Tarikh Aduan</th>
                                <th class="align-middle">Status</th>
                                <th class="align-middle">Tindakan</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$report->ticket_no}}</td>
                                    <td>{{$report->subject}}</td>
                                    <td>{{convertDateFormat($report->submitted_at, 'd F Y')}}</td>
                                    <td><span class="{{$report->status->class}}">{{$report->status->desc}}</span></td>
                                    <td>
                                        <a href="{{route('aduan.show',$report->id)}}" class="btn btn-primary btn-sm btn-rounded waves-effect waves-light">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-md-4">
        <div class="card" style="max-height: 30rem;">
            <div class="card-body">
                <div class="d-flex">
                    <div class="me-2">
                        <h5 class="card-title mb-4">Jumlah Tindakan Mengikut Jawatan</h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Jawatan</th>
                            <th scope="col">Jumlah</th>
                        </tr>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>
                                    <h5 class="font-size-13 text-truncate mb-1"><a href="#" class="text-dark">Pegawai Admin</a></h5>
                                    <p class="text-muted mb-0">Dikemaskini : 10 Nov, 2020</p>
                                </td>
                                <td> 125</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>
                                    <h5 class="font-size-13 text-truncate mb-1"><a href="#" class="text-dark">Pegawai Helpdesk</a></h5>
                                    <p class="text-muted mb-0">Dikemaskini :  02 Nov, 2020</p>
                                </td>
                                <td> 102</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>
                                    <h5 class="font-size-13 text-truncate mb-1"><a href="#" class="text-dark">Pegawai Teknologi</a></h5>
                                    <p class="text-muted mb-0">Dikemaskini :  24 Oct, 2020</p>
                                </td>
                                <td> 98</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@can('dashboard.viewChart')
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Jumlah Aduan Mengikut Kategori Minggu Ini</h4>
                    {{-- <div id="pie-chart" class="e-charts"></div>--}}
                    <div id="sektor" class="e-charts"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Jumlah Aduan Vs Aduan Selesai Minggu Ini</h4>
                    <div id="mix-line-bar" class="e-charts"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Pecahan Aduan Mengikut Kategori Dan Status Minggu Ini</h4>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-nowrap">
                            <thead class="table-secondary text-center">
                                <tr>
                                    <th>Kategori</th>
                                    <th>Dihantar</th>
                                    <th>Dihantar (Ambil Maklum)</th>
                                    <th>Dalam Tindakan</th>
                                    <th>Selesai</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-category_by_status" class="text-center">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan

    @include('partials.modal.view_report')
    @include('partials.modal.feedback')
@endsection

@section('script')
    @can('dashboard.viewGraph')
        <script type="text/javascript">

            var dom = document.getElementById("sektor");
            var pieChart = echarts.init(dom);
            var app = {};
            var option;
            var pieData = [];
            option = {

                tooltip: {
                    trigger: 'item'
                },

                series: [
                    {
                        // name: 'ss',
                        type: 'pie',
                        radius: '50%',
                        data:pieData,
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        }
                    }
                ]
            };

            if (option && typeof option === 'object') {
                pieChart.setOption(option);
            }

            $.ajax({
                url:'{{route("api.dashboard.pie-chart")}}',
                type:'post',
                success: function(response){
                    if(response.success){
                        pieData = response.data;
                        pieChart.setOption({
                            series:[
                                {
                        // name: 'ss',
                                    type: 'pie',
                                    radius: '50%',
                                    data:pieData,
                                    // data: [
                                    //     {value: 1048, name: 'Event'},
                                    //     {value: 735, name: 'Komputer Riba (Notebook)'},
                                    //     {value: 580, name: 'Komputer Meja (Desktop)'},
                                    //     {value: 484, name: 'Peralatan Komputer'},
                                    // ],
                                    emphasis: {
                                        itemStyle: {
                                            shadowBlur: 10,
                                            shadowOffsetX: 0,
                                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                                        }
                                    }
                                }
                            ]
                        });
                    }
                }
            });

        </script>
        <script>
            dom=document.getElementById("mix-line-bar"),
            barChart = echarts.init(dom);
            option = null, (app = {}).title = "Data view", option = {
                grid: {
                    zlevel: 0,
                    x: 80,
                    x2: 50,
                    y: 30,
                    y2: 30,
                    borderWidth: 0,
                    backgroundColor: "rgba(0,0,0,0)",
                    borderColor: "rgba(0,0,0,0)"
                },
                tooltip: {
                    trigger: "axis"
                },
                toolbox: {
                    orient: "center",
                    left: 0,
                    top: 20,
                    feature: {
                        magicType: {
                            type: ["line", "bar"],
                            title: {
                                line: "For line chart",
                                bar: "For bar chart"
                            }
                        },
                        saveAsImage: {
                            title: "Download Image"
                        }
                    }
                },
                color: ["#34c38f", "#556ee6"],
                legend: {
                    data: ["Jumlah Aduan", "Aduan Selesai"],
                    textStyle: {
                        color: "#8791af"
                    }
                },
                xAxis: [{
                    type: "category",
                    data: ["Isnin", "Selasa", "Rabu", "Khamis", "Jumaat", "Sabtu", "Ahad"],
                    axisPointer: {
                        type: "shadow"
                    },
                    axisLine: {
                        lineStyle: {
                            color: "#8791af"
                        }
                    }
                }],
                yAxis: [{
                    type: "value",
                    name: "Bilangan Aduan",
                    min: 0,
                    interval: 50,
                    axisLine: {
                        lineStyle: {
                            color: "#8791af"
                        }
                    },
                    splitLine: {
                        lineStyle: {
                            color: "rgba(166, 176, 207, 0.1)"
                        }
                    },
                    axisLabel: {
                        formatter: "{value}"
                    }
                }],
                series: [{
                    name: "Jumlah Aduan",
                    type: "bar",
                    data: [10, 4, 7, 23, 25, 16, 15]
                }, {
                    name: "Aduan Selesai",
                    type: "bar",
                    data: [8, 4, 5, 20, 21, 16, 14]
                }]
            }, option && "object" == typeof option && barChart.setOption(option, !0);

            $.ajax({
                url:'{{route("api.dashboard.bar-chart")}}',
                type:'post',
                success: function(response){
                    if(response.success){
                        barChart.setOption({
                                series: [{
                                        name: "Jumlah Aduan",
                                        type: "bar",
                                        data: response.data.sent
                                    }, {
                                        name: "Aduan Selesai",
                                        type: "bar",
                                        data: response.data.completed
                                    }]
                            })
                    }
                }
            });
        </script>
        <script>
            $('#tbody-category_by_status').empty();
            $.ajax({
                url:'{{route("api.dashboard.category-table")}}',
                type:'post',
                success: function(response){
                    if(response.success){
                        var category_by_status = response.category_by_status;
                        category_by_status.forEach(category => {
                            row = '<tr>';
                            row +=  '<td class="text-start">'+
                                        '<div class="media align-items-center">'+
                                            '<div class="me-3">'+
                                                '<img src="'+category.image_path+'" alt=""title="" class="avatar-sm h-auto d-block rounded" />'+
                                            '</div>'+
                                            '<div class="media-body overflow-hidden">'+
                                                '<h5 class="font-size-13 text-truncate"> '+category.desc+'</h5>'+
                                            '</div>'+
                                        '</div>'+
                                    '</td>';
                            row += '<td>'+category.dihantar+'</td>';
                            row += '<td>'+category.dihantar_ambil_maklum+'</td>';
                            row += '<td>'+category.sedang_disemak+'</td>';
                            row += '<td>'+category.selesai+'</td>';
                            row += '</tr>';
                            $('#tbody-category_by_status').append(row);
                        });
                    }
                }
            });
        </script>
    @endcan
@endsection
