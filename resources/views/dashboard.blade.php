@extends('layouts.app')

@section('content')
    @livewire('dashboard.dashboard')

    {{-- Pemantauan pergerakan pegawai: siapa keluar hari ini, siapa bercuti.
         Dipasang di sini dan bukan di dalam komponen dashboard supaya carta
         dan penapis aduan tidak tersentuh. --}}
    @can('pergerakan.view')
        @livewire('pergerakan.today-widget')
    @endcan
@endsection

@section('script')
<script>
(function () {
    function cssVar(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name);
        return (v || '').trim() || fallback;
    }

    function palette() {
        var primary = cssVar('--c-primary', '#5a68cf');
        var mid = cssVar('--c-primary-mid', '#9ba7ec');
        var butter = cssVar('--c-butter', '#e0ad46');
        var success = cssVar('--c-success', '#3b9b86');
        var warning = cssVar('--c-warning', '#c08f2b');
        return {
            primary: primary, mid: mid, butter: butter, success: success, warning: warning,
            // Periwinkle -> butter -> pastel sejuk. Enam nada pertama kekal
            // boleh dibezakan pada donut; selebihnya pengisi.
            list: ['#6b78dc', '#f0c86f', '#9ba7ec', '#8ed0c6', '#c3a7e6', '#ef9f9f', '#7fb3e0', '#d9c9a0']
        };
    }

    // Shared chart chrome so every panel reads as one designed family.
    var FONT = "'Figtree', system-ui, sans-serif";
    var FONT_NUM = "'Sora', 'Figtree', system-ui, sans-serif";
    var INK = '#1c2138';
    var MUTED = '#6a7089';
    var HAIRLINE = '#e9ecf9';

    function baseOption() {
        return {
            textStyle: { fontFamily: FONT, color: MUTED, fontSize: 12 },
            tooltip: {
                backgroundColor: 'rgba(28,33,56,0.94)',
                borderWidth: 0,
                padding: [10, 12],
                textStyle: { color: '#fff', fontFamily: FONT, fontSize: 12 },
                extraCssText: 'border-radius:12px;box-shadow:0 12px 30px rgba(28,33,56,0.22);'
            },
            legend: {
                bottom: 0, icon: 'circle', itemWidth: 8, itemHeight: 8, itemGap: 18,
                textStyle: { color: MUTED, fontFamily: FONT, fontSize: 12 }
            }
        };
    }

    function catAxis(data, opts) {
        return Object.assign({
            type: 'category', data: data,
            axisLine: { lineStyle: { color: HAIRLINE } },
            axisTick: { show: false },
            axisLabel: { color: MUTED, fontFamily: FONT }
        }, opts || {});
    }

    function valAxis(opts) {
        return Object.assign({
            type: 'value', minInterval: 1,
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { color: MUTED, fontFamily: FONT },
            splitLine: { lineStyle: { color: HAIRLINE, type: 'dashed' } }
        }, opts || {});
    }

    function merge(extra) {
        var o = baseOption();
        Object.keys(extra).forEach(function (k) {
            if (o[k] && typeof o[k] === 'object' && !Array.isArray(extra[k])) {
                o[k] = Object.assign({}, o[k], extra[k]);
            } else {
                o[k] = extra[k];
            }
        });
        return o;
    }

    function renderOfficerBar(officers) {
        if (typeof echarts === 'undefined') return;
        var el = document.getElementById('officer-workload-bar');
        if (!el || !officers || !officers.names) return;
        var p = palette();
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        chart.setOption(merge({
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
            grid: { left: 8, right: 20, top: 16, bottom: 36, containLabel: true },
            xAxis: valAxis(),
            yAxis: catAxis(officers.names, { inverse: true }),
            series: [
                { name: 'Belum Diproses', type: 'bar', stack: 'beban', color: '#aab0c9',
                  barMaxWidth: 20, itemStyle: { borderRadius: [8, 0, 0, 8] }, data: officers.belumProses },
                { name: 'Dalam Tindakan', type: 'bar', stack: 'beban', color: p.butter,
                  barMaxWidth: 20, data: officers.dalamTindakan },
                { name: 'Selesai', type: 'bar', stack: 'beban', color: '#6b78dc',
                  barMaxWidth: 20, itemStyle: { borderRadius: [0, 8, 8, 0] }, data: officers.selesai }
            ]
        }), true);
        chart.resize();
    }

    function renderTrendLine(trend) {
        var el = document.getElementById('trend-line');
        if (!el || !trend || !trend.labels) return;
        var p = palette();
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        chart.setOption(merge({
            color: ['#6b78dc', p.butter],
            tooltip: { trigger: 'axis' },
            grid: { left: 8, right: 20, top: 20, bottom: 36, containLabel: true },
            xAxis: catAxis(trend.labels, { boundaryGap: false }),
            yAxis: valAxis(),
            series: [
                { name: 'Diterima', type: 'line', smooth: true, symbol: 'circle', symbolSize: 7,
                  lineStyle: { width: 2.5 },
                  areaStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                      { offset: 0, color: 'rgba(107,120,220,0.22)' }, { offset: 1, color: 'rgba(107,120,220,0)' }]) },
                  data: trend.submitted },
                { name: 'Selesai', type: 'line', smooth: true, symbol: 'circle', symbolSize: 7,
                  lineStyle: { width: 2.5 },
                  areaStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                      { offset: 0, color: 'rgba(240,200,111,0.26)' }, { offset: 1, color: 'rgba(240,200,111,0)' }]) },
                  data: trend.resolved }
            ]
        }), true);
        chart.resize();
    }

    function renderBahagianBar(rows) {
        var el = document.getElementById('bahagian-bar');
        if (!el || !rows) return;
        var p = palette();
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        chart.setOption(merge({
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend: { show: false },
            grid: { left: 8, right: 24, top: 12, bottom: 8, containLabel: true },
            xAxis: valAxis(),
            yAxis: catAxis((rows || []).map(function (r) { return r.label; }), { inverse: true }),
            series: [{
                type: 'bar', barMaxWidth: 18,
                itemStyle: {
                    borderRadius: [0, 8, 8, 0],
                    color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                        { offset: 0, color: '#6b78dc' }, { offset: 1, color: p.mid }])
                },
                data: (rows || []).map(function (r) { return r.value; })
            }]
        }), true);
        chart.resize();
    }

    function renderRatingBar(rating) {
        var el = document.getElementById('rating-bar');
        if (!el || !rating || !rating.counts) return;
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        var labels = ['1★', '2★', '3★', '4★', '5★'];
        // Skala pastel: merah lembut -> mentega -> periwinkle.
        var colors = ['#e08a94', '#efab7e', '#f0c86f', '#8ed0c6', '#6b78dc'];
        var data = [1, 2, 3, 4, 5].map(function (i) {
            return { value: rating.counts[i] || 0, itemStyle: { color: colors[i - 1] } };
        });
        chart.setOption(merge({
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend: { show: false },
            grid: { left: 8, right: 16, top: 16, bottom: 8, containLabel: true },
            xAxis: catAxis(labels, { axisLabel: { color: INK, fontFamily: FONT, fontSize: 13 } }),
            yAxis: valAxis(),
            series: [{ type: 'bar', barMaxWidth: 38, itemStyle: { borderRadius: [8, 8, 0, 0] }, data: data }]
        }), true);
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
            var rows = (statusData || []).map(function (s) { return { name: s.label, value: s.value }; });
            var sum = rows.reduce(function (a, r) { return a + (r.value || 0); }, 0);
            donut.setOption(merge({
                color: p.list,
                tooltip: { trigger: 'item' },
                series: [{
                    type: 'pie', radius: ['58%', '78%'], center: ['50%', '42%'],
                    avoidLabelOverlap: true,
                    itemStyle: { borderColor: '#fff', borderWidth: 2 },
                    label: {
                        show: true, position: 'center', formatter: String(sum),
                        fontFamily: FONT_NUM, fontSize: 26, fontWeight: 600, color: INK
                    },
                    emphasis: { label: { show: true, fontSize: 26 }, scale: false },
                    labelLine: { show: false },
                    data: rows
                }]
            }), true);
            donut.resize();
        }

        var barEl = document.getElementById('category-bar');
        if (barEl) {
            var bar = echarts.getInstanceByDom(barEl) || echarts.init(barEl);
            bar.setOption(merge({
                tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
                legend: { show: false },
                grid: { left: 8, right: 24, top: 20, bottom: 8, containLabel: true },
                xAxis: valAxis(),
                yAxis: catAxis((categoryData || []).map(function (c) { return c.label; }), { inverse: true }),
                series: [{
                    type: 'bar', barMaxWidth: 18,
                    itemStyle: {
                        borderRadius: [0, 8, 8, 0],
                        color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                            { offset: 0, color: '#6b78dc' }, { offset: 1, color: p.mid }])
                    },
                    data: (categoryData || []).map(function (c) { return c.value; })
                }]
            }), true);
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
