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
    @livewire('dashboard.dashboard')
@endsection

@section('script')
<script>
(function () {
    function palette() {
        var css = getComputedStyle(document.documentElement);
        var primary = (css.getPropertyValue('--c-primary') || '#0ea5a4').trim();
        var success = (css.getPropertyValue('--c-success') || '#10b981').trim();
        return { primary: primary, list: [primary, success, '#38bdf8', '#f59e0b', '#a78bfa', '#f472b6', '#fb7185', '#34d399'] };
    }

    function renderOfficerBar(officers) {
        if (typeof echarts === 'undefined') return;
        var el = document.getElementById('officer-workload-bar');
        if (!el || !officers || !officers.names) return;
        var p = palette();
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        chart.setOption({
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (params) {
                    if (!params.length) return '';
                    var idx = params[0].dataIndex;
                    var total = (officers.diterima && officers.diterima[idx]) || 0;
                    var s = '<strong>' + params[0].name + '</strong><br>Diterima: ' + total;
                    params.forEach(function (pi) {
                        s += '<br>' + pi.marker + ' ' + pi.seriesName + ': ' + pi.value;
                    });
                    return s;
                }
            },
            legend: { bottom: 0, icon: 'circle' },
            grid: { left: 8, right: 16, top: 16, bottom: 36, containLabel: true },
            xAxis: { type: 'value', minInterval: 1 },
            yAxis: { type: 'category', inverse: true, data: officers.names },
            series: [
                { name: 'Belum Diproses', type: 'bar', stack: 'beban', color: '#94a3b8',
                  barMaxWidth: 24, itemStyle: { borderRadius: [6, 0, 0, 6] }, data: officers.belumProses },
                { name: 'Dalam Tindakan', type: 'bar', stack: 'beban', color: '#f59e0b',
                  barMaxWidth: 24, data: officers.dalamTindakan },
                { name: 'Selesai', type: 'bar', stack: 'beban', color: (p.list[1] || '#10b981'),
                  barMaxWidth: 24, itemStyle: { borderRadius: [0, 6, 6, 0] }, data: officers.selesai }
            ]
        }, true);
        chart.resize();
    }

    function renderTrendLine(trend) {
        var el = document.getElementById('trend-line');
        if (!el || !trend || !trend.labels) return;
        var p = palette();
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        chart.setOption({
            color: [p.primary, (p.list[1] || '#10b981')],
            tooltip: { trigger: 'axis' },
            legend: { bottom: 0, icon: 'circle' },
            grid: { left: 8, right: 16, top: 20, bottom: 36, containLabel: true },
            xAxis: { type: 'category', boundaryGap: false, data: trend.labels },
            yAxis: { type: 'value', minInterval: 1 },
            series: [
                { name: 'Diterima', type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.08 }, data: trend.submitted },
                { name: 'Selesai', type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.08 }, data: trend.resolved }
            ]
        }, true);
        chart.resize();
    }

    function renderBahagianBar(rows) {
        var el = document.getElementById('bahagian-bar');
        if (!el || !rows) return;
        var p = palette();
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        chart.setOption({
            color: [p.primary], tooltip: { trigger: 'axis' },
            grid: { left: 8, right: 16, top: 12, bottom: 8, containLabel: true },
            xAxis: { type: 'value', minInterval: 1 },
            yAxis: { type: 'category', inverse: true, data: (rows || []).map(function (r) { return r.label; }) },
            series: [{ type: 'bar', barMaxWidth: 20, itemStyle: { borderRadius: [0, 6, 6, 0] },
                data: (rows || []).map(function (r) { return r.value; }) }]
        }, true);
        chart.resize();
    }

    function renderRatingBar(rating) {
        var el = document.getElementById('rating-bar');
        if (!el || !rating || !rating.counts) return;
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        var labels = ['1★', '2★', '3★', '4★', '5★'];
        var colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981'];
        var data = [1,2,3,4,5].map(function (i) {
            return { value: rating.counts[i] || 0, itemStyle: { color: colors[i-1] } };
        });
        chart.setOption({
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            grid: { left: 8, right: 16, top: 16, bottom: 8, containLabel: true },
            xAxis: { type: 'category', data: labels },
            yAxis: { type: 'value', minInterval: 1 },
            series: [{ type: 'bar', barMaxWidth: 44, itemStyle: { borderRadius: [6, 6, 0, 0] }, data: data }]
        }, true);
        chart.resize();
    }

    function renderCharts(statusData, categoryData, officersData, trendData, ratingData, bahagianData) {
        if (typeof echarts === 'undefined') return;
        var p = palette();

        if (officersData) renderOfficerBar(officersData);
        if (trendData) renderTrendLine(trendData);
        if (bahagianData) renderBahagianBar(bahagianData);
        if (ratingData) renderRatingBar(ratingData);

        var donutEl = document.getElementById('status-donut');
        if (donutEl) {
            var donut = echarts.getInstanceByDom(donutEl) || echarts.init(donutEl);
            donut.setOption({
                color: p.list, tooltip: { trigger: 'item' }, legend: { bottom: 0, icon: 'circle' },
                series: [{ type: 'pie', radius: ['50%', '72%'], center: ['50%', '42%'], label: { show: false },
                    data: (statusData || []).map(function (s) { return { name: s.label, value: s.value }; }) }]
            }, true);
            donut.resize();
        }

        var barEl = document.getElementById('category-bar');
        if (barEl) {
            var bar = echarts.getInstanceByDom(barEl) || echarts.init(barEl);
            bar.setOption({
                color: [p.primary], tooltip: { trigger: 'axis' },
                grid: { left: 8, right: 16, top: 20, bottom: 8, containLabel: true },
                xAxis: { type: 'value', minInterval: 1 },
                yAxis: { type: 'category', inverse: true, data: (categoryData || []).map(function (c) { return c.label; }) },
                series: [{ type: 'bar', barMaxWidth: 22, itemStyle: { borderRadius: [0, 6, 6, 0] },
                    data: (categoryData || []).map(function (c) { return c.value; }) }]
            }, true);
            bar.resize();
        }
    }

    // Paint from the hidden data holder the Livewire view renders.
    function paintFromDom() {
        var el = document.getElementById('dashboard-chart-data');
        if (!el) return;
        try {
            renderCharts(
                JSON.parse(el.dataset.status || '[]'),
                JSON.parse(el.dataset.category || '[]'),
                JSON.parse(el.dataset.officers || 'null'),
                JSON.parse(el.dataset.trend || 'null'),
                JSON.parse(el.dataset.rating || 'null'),
                JSON.parse(el.dataset.bahagian || 'null')
            );
        } catch (e) { /* ignore malformed */ }
    }

    // Initial paint (page load + SPA navigation).
    document.addEventListener('DOMContentLoaded', paintFromDom);
    document.addEventListener('livewire:navigated', paintFromDom);
    paintFromDom();

    // Live updates: the component dispatches this on every render (any filter).
    document.addEventListener('livewire:init', function () {
        Livewire.on('dashboard-charts', function (e) {
            var d = Array.isArray(e) ? e[0] : e;
            renderCharts(d.status, d.category, d.officers, d.trend, d.rating, d.bahagian);
        });
    });

    window.addEventListener('resize', function () {
        ['status-donut', 'category-bar', 'officer-workload-bar', 'trend-line', 'bahagian-bar', 'rating-bar'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && typeof echarts !== 'undefined' && echarts.getInstanceByDom(el)) {
                echarts.getInstanceByDom(el).resize();
            }
        });
    });
})();
</script>
@endsection
