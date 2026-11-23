@extends('layouts.app')
@section('breadcrumb')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard Pegawai</h4>
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
    @livewire('dashboard.officer-dashboard')

    @can('pergerakan.view')
        @livewire('pergerakan.today-widget')
    @endcan
@endsection

@section('script')
<script>
(function () {
    function palette() {
        var css = getComputedStyle(document.documentElement);
        var primary = (css.getPropertyValue('--c-primary') || '#5a68cf').trim();
        var butter = (css.getPropertyValue('--c-butter') || '#e0ad46').trim();
        return {
            primary: primary, butter: butter,
            list: ['#6b78dc', '#f0c86f', '#9ba7ec', '#8ed0c6', '#c3a7e6', '#ef9f9f']
        };
    }

    function renderCharts(statusData, trendData) {
        if (typeof echarts === 'undefined') return;
        var p = palette();

        var donutEl = document.getElementById('officer-status-donut');
        if (donutEl) {
            var donut = echarts.getInstanceByDom(donutEl) || echarts.init(donutEl);
            donut.setOption({
                color: p.list, tooltip: { trigger: 'item' }, legend: { bottom: 0, icon: 'circle' },
                series: [{ type: 'pie', radius: ['56%', '76%'], center: ['50%', '42%'], label: { show: false },
                    itemStyle: { borderColor: '#fff', borderWidth: 2 },
                    data: (statusData || []).map(function (s) { return { name: s.label, value: s.value }; }) }]
            }, true);
            donut.resize();
        }

        var lineEl = document.getElementById('officer-trend-line');
        if (lineEl && trendData && trendData.labels) {
            var line = echarts.getInstanceByDom(lineEl) || echarts.init(lineEl);
            line.setOption({
                color: ['#6b78dc', p.butter],
                tooltip: { trigger: 'axis' }, legend: { bottom: 0, icon: 'circle' },
                grid: { left: 8, right: 16, top: 20, bottom: 36, containLabel: true },
                xAxis: { type: 'category', boundaryGap: false, data: trendData.labels },
                yAxis: { type: 'value', minInterval: 1 },
                series: [
                    { name: 'Diterima', type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.14 }, data: trendData.assigned },
                    { name: 'Selesai', type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.14 }, data: trendData.resolved }
                ]
            }, true);
            line.resize();
        }
    }

    function paintFromDom() {
        var el = document.getElementById('officer-chart-data');
        if (!el) return;
        try { renderCharts(JSON.parse(el.dataset.status || '[]'), JSON.parse(el.dataset.trend || 'null')); }
        catch (e) { /* ignore */ }
    }

    document.addEventListener('DOMContentLoaded', paintFromDom);
    document.addEventListener('livewire:navigated', paintFromDom);
    paintFromDom();

    document.addEventListener('livewire:init', function () {
        Livewire.on('officer-charts', function (e) {
            var d = Array.isArray(e) ? e[0] : e;
            renderCharts(d.status, d.trend);
        });
    });

    window.addEventListener('resize', function () {
        ['officer-status-donut', 'officer-trend-line'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && typeof echarts !== 'undefined' && echarts.getInstanceByDom(el)) {
                echarts.getInstanceByDom(el).resize();
            }
        });
    });
})();
</script>
@endsection
