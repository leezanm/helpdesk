/*
 * KKDW Helpdesk — rebranding mockups: chart rendering + theme switching.
 * Charts read their colours from the active theme's CSS variables, so every
 * direction shows its own data-visualisation palette, not just new chrome.
 */
(function () {
    var DESC = {
        e: 'Lavender + blush · fon Manrope — pastel paling neutral, selamat dan rasmi.',
        f: 'Biru langit + pic · DM Sans + Space Grotesk — paling segar, angka tajam.',
        f2: 'SKY MONO (pilihan anda): biru langit sahaja, aksen azure, carta tangga biru + slate. Warna status dikekalkan kerana ia makna.',
        g: 'Plum berdebu + aprikot atas kertas krim · Instrument Sans + Fraunces — hangat, beridentiti.',
        h: 'Periwinkle + mentega · Figtree + Sora — ceria, hero pastel penuh warna.'
    };

    // ---- Fake but realistic data (mirrors the live dashboard shape) ----
    var MONTHS = ['Dis 25', 'Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogs', 'Sep', 'Okt', 'Nov'];
    var SUBMITTED = [31, 38, 44, 52, 47, 61, 58, 49, 66, 71, 63, 43];
    var RESOLVED  = [29, 36, 41, 50, 46, 57, 56, 48, 62, 68, 60, 33];

    var STATUS = [
        { name: 'Selesai', value: 556 }, { name: 'Dalam Tindakan', value: 27 },
        { name: 'KIV', value: 11 }, { name: 'Baru', value: 12 },
        { name: 'Aduan Disemak', value: 8 }, { name: 'Tindakan Lanjut', value: 5 }
    ];
    var CAT = [
        { label: 'Rangkaian', value: 142 }, { label: 'Perkakasan', value: 118 },
        { label: 'Perisian', value: 96 }, { label: 'E-mel', value: 73 },
        { label: 'Portal eKKDW', value: 58 }, { label: 'Capaian Sistem', value: 44 },
        { label: 'Lain-lain', value: 52 }
    ];
    var BHG = [
        { label: 'Pengurusan Maklumat', value: 96 }, { label: 'Khidmat Pengurusan', value: 84 },
        { label: 'Pembangunan Wilayah', value: 71 }, { label: 'Kewangan', value: 63 },
        { label: 'Dasar & Perancangan', value: 55 }, { label: 'Sumber Manusia', value: 48 },
        { label: 'Audit Dalam', value: 39 }, { label: 'Perolehan', value: 34 },
        { label: 'Komunikasi Korporat', value: 28 }, { label: 'Undang-undang', value: 21 }
    ];
    var RATING = [6, 9, 41, 168, 332];
    var OFFICERS = {
        names: ['Azman Hashim', 'Nurhaliza Yusof', 'Chong Kok Wai', 'Faridah Ismail', 'Ravi Kumar', 'Syafiq Rahman'],
        belum: [2, 1, 3, 0, 2, 1],
        proses: [6, 4, 7, 3, 5, 2],
        selesai: [112, 98, 87, 76, 64, 41]
    };

    function v(name, fallback) {
        var x = getComputedStyle(document.documentElement).getPropertyValue(name);
        return (x || '').trim() || fallback;
    }

    function theme() {
        var fam = v('--font-body', 'Inter').split(',')[0].replace(/['"]/g, '').trim();
        return {
            font: fam + ", system-ui, sans-serif",
            series: [v('--chart-1'), v('--chart-2'), v('--chart-3'), v('--chart-4'), v('--chart-5'), v('--chart-6')],
            grid: v('--chart-grid', '#eee'),
            ink: v('--chart-ink', '#111'),
            muted: v('--chart-muted', '#777'),
            tooltip: v('--chart-tooltip-bg', 'rgba(0,0,0,.9)'),
            barR: parseFloat(v('--chart-bar-radius', '4')),
            lineW: parseFloat(v('--chart-line-w', '2.5')),
            dash: v('--chart-grid-dash', 'dashed'),
            surface: v('--surface', '#fff')
        };
    }

    function base(t) {
        return {
            textStyle: { fontFamily: t.font, color: t.muted, fontSize: 12 },
            tooltip: {
                backgroundColor: t.tooltip, borderWidth: 0, padding: [10, 12],
                textStyle: { color: '#fff', fontFamily: t.font, fontSize: 12 },
                extraCssText: 'border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.18);'
            },
            legend: {
                bottom: 0, icon: 'circle', itemWidth: 8, itemHeight: 8, itemGap: 16,
                textStyle: { color: t.muted, fontFamily: t.font, fontSize: 12 }
            }
        };
    }

    function cat(t, data, extra) {
        return Object.assign({
            type: 'category', data: data,
            axisLine: { lineStyle: { color: t.grid } }, axisTick: { show: false },
            axisLabel: { color: t.muted, fontFamily: t.font }
        }, extra || {});
    }

    function val(t, extra) {
        return Object.assign({
            type: 'value', minInterval: 1,
            axisLine: { show: false }, axisTick: { show: false },
            axisLabel: { color: t.muted, fontFamily: t.font },
            splitLine: { lineStyle: { color: t.grid, type: t.dash } }
        }, extra || {});
    }

    function fade(hex, top, bottom) {
        return new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: rgba(hex, top) }, { offset: 1, color: rgba(hex, bottom) }
        ]);
    }

    function rgba(hex, a) {
        hex = (hex || '#000').trim().replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
        var n = parseInt(hex, 16);
        return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
    }

    function hbar(t, color2) {
        return {
            borderRadius: [0, t.barR, t.barR, 0],
            color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                { offset: 0, color: t.series[0] }, { offset: 1, color: color2 || t.series[1] }
            ])
        };
    }

    var charts = {};
    function mount(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        if (charts[id]) { charts[id].dispose(); }
        charts[id] = echarts.init(el);
        return charts[id];
    }

    function draw() {
        var t = theme();

        // Trend
        var trend = mount('c-trend');
        if (trend) {
            trend.setOption(Object.assign(base(t), {
                color: [t.series[0], t.series[1]],
                tooltip: Object.assign(base(t).tooltip, { trigger: 'axis' }),
                grid: { left: 8, right: 20, top: 20, bottom: 36, containLabel: true },
                xAxis: cat(t, MONTHS, { boundaryGap: false }),
                yAxis: val(t),
                series: [
                    { name: 'Diterima', type: 'line', smooth: true, symbol: 'circle', symbolSize: 7,
                      lineStyle: { width: t.lineW }, areaStyle: { color: fade(t.series[0], 0.22, 0) }, data: SUBMITTED },
                    { name: 'Selesai', type: 'line', smooth: true, symbol: 'circle', symbolSize: 7,
                      lineStyle: { width: t.lineW }, areaStyle: { color: fade(t.series[1], 0.20, 0) }, data: RESOLVED }
                ]
            }));
        }

        // Status donut
        var donut = mount('c-donut');
        if (donut) {
            var total = STATUS.reduce(function (a, s) { return a + s.value; }, 0);
            donut.setOption(Object.assign(base(t), {
                color: t.series,
                tooltip: Object.assign(base(t).tooltip, { trigger: 'item' }),
                series: [{
                    type: 'pie', radius: ['58%', '78%'], center: ['50%', '42%'],
                    itemStyle: { borderColor: t.surface, borderWidth: 2 },
                    label: { show: true, position: 'center', formatter: String(total),
                             fontFamily: t.font, fontSize: 26, fontWeight: 700, color: t.ink },
                    emphasis: { label: { show: true, fontSize: 26 }, scale: false },
                    labelLine: { show: false },
                    data: STATUS
                }]
            }));
        }

        // Category
        var c = mount('c-cat');
        if (c) {
            c.setOption(Object.assign(base(t), {
                tooltip: Object.assign(base(t).tooltip, { trigger: 'axis', axisPointer: { type: 'shadow' } }),
                legend: { show: false },
                grid: { left: 8, right: 28, top: 14, bottom: 8, containLabel: true },
                xAxis: val(t),
                yAxis: cat(t, CAT.map(function (r) { return r.label; }).reverse()),
                series: [{ type: 'bar', barMaxWidth: 18, itemStyle: hbar(t),
                    label: { show: true, position: 'right', color: t.muted, fontFamily: t.font, fontSize: 11 },
                    data: CAT.map(function (r) { return r.value; }).reverse() }]
            }));
        }

        // Bahagian
        var b = mount('c-bhg');
        if (b) {
            b.setOption(Object.assign(base(t), {
                tooltip: Object.assign(base(t).tooltip, { trigger: 'axis', axisPointer: { type: 'shadow' } }),
                legend: { show: false },
                grid: { left: 8, right: 28, top: 10, bottom: 8, containLabel: true },
                xAxis: val(t),
                yAxis: cat(t, BHG.map(function (r) { return r.label; }).reverse()),
                series: [{ type: 'bar', barMaxWidth: 14, itemStyle: hbar(t, t.series[2]),
                    data: BHG.map(function (r) { return r.value; }).reverse() }]
            }));
        }

        // Rating
        var r = mount('c-rate');
        if (r) {
            var cols = [t.series[5] || t.series[0], t.series[4], t.series[3], t.series[1], t.series[0]];
            r.setOption(Object.assign(base(t), {
                tooltip: Object.assign(base(t).tooltip, { trigger: 'axis', axisPointer: { type: 'shadow' } }),
                legend: { show: false },
                grid: { left: 8, right: 16, top: 24, bottom: 8, containLabel: true },
                xAxis: cat(t, ['1★', '2★', '3★', '4★', '5★'], { axisLabel: { color: t.ink, fontFamily: t.font, fontSize: 13 } }),
                yAxis: val(t),
                series: [{
                    type: 'bar', barMaxWidth: 40,
                    label: { show: true, position: 'top', color: t.ink, fontFamily: t.font, fontWeight: 600, fontSize: 11 },
                    data: RATING.map(function (n, i) {
                        return { value: n, itemStyle: { color: cols[i], borderRadius: [t.barR, t.barR, 0, 0] } };
                    })
                }]
            }));
        }

        // Officer workload (stacked)
        var o = mount('c-off');
        if (o) {
            o.setOption(Object.assign(base(t), {
                tooltip: Object.assign(base(t).tooltip, { trigger: 'axis', axisPointer: { type: 'shadow' } }),
                grid: { left: 8, right: 24, top: 16, bottom: 36, containLabel: true },
                xAxis: val(t),
                yAxis: cat(t, OFFICERS.names.slice().reverse()),
                series: [
                    { name: 'Belum Diproses', type: 'bar', stack: 's', color: t.muted, barMaxWidth: 18,
                      itemStyle: { borderRadius: [t.barR, 0, 0, t.barR] }, data: OFFICERS.belum.slice().reverse() },
                    { name: 'Dalam Tindakan', type: 'bar', stack: 's', color: t.series[3], barMaxWidth: 18,
                      data: OFFICERS.proses.slice().reverse() },
                    { name: 'Selesai', type: 'bar', stack: 's', color: t.series[0], barMaxWidth: 18,
                      itemStyle: { borderRadius: [0, t.barR, t.barR, 0] }, data: OFFICERS.selesai.slice().reverse() }
                ]
            }));
        }
    }

    function setTheme(name) {
        document.body.setAttribute('data-theme', name);
        document.querySelectorAll('.switcher button').forEach(function (btn) {
            btn.setAttribute('aria-pressed', String(btn.dataset.t === name));
        });
        document.getElementById('sw-desc').textContent = DESC[name] || '';
        try { localStorage.setItem('kkdw-mock-theme-v2', name); } catch (e) {}
        // Let the new variables apply before charts read them.
        requestAnimationFrame(function () { requestAnimationFrame(draw); });
    }

    document.querySelectorAll('.switcher button').forEach(function (btn) {
        btn.addEventListener('click', function () { setTheme(btn.dataset.t); });
    });

    window.addEventListener('resize', function () {
        Object.keys(charts).forEach(function (k) { charts[k] && charts[k].resize(); });
    });

    var saved = null;
    try { saved = localStorage.getItem('kkdw-mock-theme-v2'); } catch (e) {}
    var fromUrl = new URLSearchParams(location.search).get('theme');
    setTheme(fromUrl || (DESC[saved] ? saved : null) || 'f2');
})();
