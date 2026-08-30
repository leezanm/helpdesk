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
@endsection

@section('script')
<script>
(function () {
    function palette() {
        var css = getComputedStyle(document.documentElement);
        var primary = (css.getPropertyValue('--c-primary') || '#0ea5a4').trim();
        var success = (css.getPropertyValue('--c-success') || '#10b981').trim();
        return { primary: primary, list: [primary, success, '#38bdf8', '#f59e0b', '#a78bfa', '#f472b6'] };
    }

    function renderCharts(statusData, trendData) {
        if (typeof echarts === 'undefined') return;
        var p = palette();

        var donutEl = document.getElementById('officer-status-donut');
        if (donutEl) {
            var donut = echarts.getInstanceByDom(donutEl) || echarts.init(donutEl);
            donut.setOption({
                color: p.list, tooltip: { trigger: 'item' }, legend: { bottom: 0, icon: 'circle' },
                series: [{ type: 'pie', radius: ['50%', '72%'], center: ['50%', '42%'], label: { show: false },
                    data: (statusData || []).map(function (s) { return { name: s.label, value: s.value }; }) }]
            }, true);
            donut.resize();
        }

        var lineEl = document.getElementById('officer-trend-line');
        if (lineEl && trendData && trendData.labels) {
            var line = echarts.getInstanceByDom(lineEl) || echarts.init(lineEl);
            line.setOption({
                color: [p.primary, (p.list[1] || '#10b981')],
                tooltip: { trigger: 'axis' }, legend: { bottom: 0, icon: 'circle' },
                grid: { left: 8, right: 16, top: 20, bottom: 36, containLabel: true },
                xAxis: { type: 'category', boundaryGap: false, data: trendData.labels },
                yAxis: { type: 'value', minInterval: 1 },
                series: [
                    { name: 'Diterima', type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.08 }, data: trendData.assigned },
                    { name: 'Selesai', type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.08 }, data: trendData.resolved }
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
