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

    function renderCharts(statusData, categoryData) {
        if (typeof echarts === 'undefined') return;
        var p = palette();

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
            renderCharts(JSON.parse(el.dataset.status || '[]'), JSON.parse(el.dataset.category || '[]'));
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
            renderCharts(d.status, d.category);
        });
    });

    window.addEventListener('resize', function () {
        ['status-donut', 'category-bar'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && typeof echarts !== 'undefined' && echarts.getInstanceByDom(el)) {
                echarts.getInstanceByDom(el).resize();
            }
        });
    });
})();
</script>
@endsection
