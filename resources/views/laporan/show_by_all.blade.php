@extends('layouts.app')
@section('breadcrumb')
    {{--
        Kepala halaman statik Laporan Keseluruhan.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 6.1)

        Tajuk, subtitle dan breadcrumb tidak bergantung kepada Set_Penapis_Semasa,
        jadi ia kekal di sini. Ringkasan tempoh yang reaktif berada dalam DOM
        komponen Livewire (lihat report-all.blade.php).

        Teks "Laporan Keseluruhan" dirender di sini sahaja (Requirement 1 kriteria 6).
        Jarak menegak ke Penapis_Laporan datang daripada `.page-title-box`
        (margin-bottom: 1.25rem = 20px, iaitu <= 24px pada >= 992px); subtitle
        `.report-head__sub` tiada margin bawah jadi ia tidak menambah jarak itu.
    --}}
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-sm-center justify-content-between">
                <div>
                    <h4 class="mb-0 font-size-18">Laporan Keseluruhan</h4>
                    <p class="report-head__sub">Ringkasan dan analisis keseluruhan aduan/helpdesk dalam sistem.</p>
                </div>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Keseluruhan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')
    @livewire('laporan.report-all')
@endsection
@section('script')
{{--
    Skrip carta Laporan Keseluruhan.
    Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 9.3)

    Pembantu chrome (cssVar, palette, FONT/FONT_NUM/INK/MUTED/HAIRLINE,
    baseOption, catAxis, valAxis, merge) ialah SALINAN nilai yang sama seperti
    `resources/views/dashboard.blade.php`. Fail itu di luar senarai fail yang
    dibenarkan diubah oleh spesifikasi ini (Requirement 9 kriteria 8), jadi
    pembantu disalin ke sini dan bukan diekstrak kepada aset yang dikongsi.
    Jika nilai chrome dashboard berubah pada masa hadapan, kedua-dua salinan
    perlu dikemas kini bersama.

    Tiada pustaka baharu: ECharts sudah dimuatkan secara global oleh
    `resources/views/layouts/app.blade.php` (Requirement 9 kriteria 11).
--}}
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

    var CHART_IDS = ['laporan-status-donut', 'laporan-trend-bar'];

    // Pemisah ribuan, sama bagi nilai tengah donat, legenda dan tooltip.
    function fmt(n) { return Number(n || 0).toLocaleString('en-US'); }

    // Peratus datang daripada pelayan (satu tempat perpuluhan). Jangan kira
    // semula di sini — legenda, tooltip dan Kad_Statistik mesti sepadan
    // (Requirement 4 kriteria 3 dan 8).
    function pct(value) { return Number(value || 0).toFixed(1); }

    // Label_Paksi_Tarikh dua baris: "01 Nov" -> "01" / "Nov", "Nov 2025" ->
    // "Nov" / "2025" (Requirement 5 kriteria 14 dan 15).
    //
    // Rentetan label dijana oleh `trendData()` dan TIDAK diubah di pelayan:
    // pemecahan berlaku di sini, semasa label dilukis pada paksi
    // (Requirement 5 kriteria 16). Pemisah ialah ruang pertama, jadi kedua-dua
    // format pelayan terurus tanpa mengetahui granulariti.
    function twoLineLabel(value) {
        var text = value == null ? '' : String(value);
        var gap = text.indexOf(' ');
        return gap === -1 ? text : text.slice(0, gap) + '\n' + text.slice(gap + 1);
    }

    // Elemen fallback diselesaikan MELALUI sasaran carta, bukan terus melalui
    // id "{id}-fallback". Apabila carta berada dalam keadaan kosong pelayan,
    // `x-laporan.chart-box` tidak merender sasaran carta sama sekali dan
    // merender `.chart-empty` sebagai gantinya, tetapi `.chart-fallback` yang
    // tersembunyi SENTIASA hadir. Oleh kerana `.chart-fallback` ialah tindanan
    // `position:absolute; inset:0` dengan latar belakang legap, menunjukkannya
    // dalam keadaan itu akan menutup keadaan kosong yang sah dengan mesej
    // "Carta tidak dapat dipaparkan." yang mengelirukan. Jadi: tiada sasaran
    // carta bermakna tiada apa-apa yang gagal — keluar awal.
    function fallbackEl(id) {
        var target = document.getElementById(id);
        if (!target) return null;
        var box = target.closest ? target.closest('.chart-box') : null;
        return (box && box.querySelector('.chart-fallback')) || document.getElementById(id + '-fallback');
    }

    function showFallback(id) {
        var el = fallbackEl(id);
        if (el) el.classList.add('is-shown');
    }

    // Lukisan yang berjaya membuang mesej fallback, supaya kegagalan sementara
    // (contohnya satu muatan JSON yang rosak) tidak kekal selepas penapis
    // seterusnya berjaya dirender.
    function hideFallback(id) {
        var el = fallbackEl(id);
        if (el) el.classList.remove('is-shown');
    }

    // Warna_Status: warna Palet_Carta pada Indeks_Status, iaitu kedudukan
    // status dalam `ReportStatuses::COUNTED` menaik id (0-6), yang dihantar
    // pelayan sebagai `colorIndex` pada setiap entri.
    //
    // Sengaja BUKAN kedudukan dalam senarai: baris legenda disusun menurun
    // mengikut bilangan (kriteria 15) manakala segmen cincin disusun menaik
    // mengikut id (kriteria 1), jadi pemetaan mengikut kedudukan akan
    // memberikan satu status dua warna berbeza pada halaman yang sama, dan
    // menukar warnanya setiap kali bilangannya berubah kedudukan
    // (Requirement 4 kriteria 17 hingga 19).
    //
    // `fallbackIndex` hanya menjaga payload lama tanpa `colorIndex`.
    function statusColor(colors, entry, fallbackIndex) {
        var i = entry && typeof entry.colorIndex === 'number' ? entry.colorIndex : fallbackIndex;
        return colors[i % colors.length];
    }

    // Legenda_Carta_Status: satu baris bagi setiap status, nama status di kiri
    // dan bilangan + peratusan dijajarkan pada tepi kanan yang sama
    // (Requirement 4 kriteria 11). Susun atur — menegak di sebelah donat pada
    // >=992px, di bawah donat pada <=991px — milik `.chart-split` dan
    // `.chart-legend` dalam `app.css` (Requirement 4 kriteria 10 dan 12);
    // pemendekan nama + nama penuh sebagai tooltip milik `.chart-legend__name`
    // (Requirement 4 kriteria 13).
    //
    // Legenda terbina di sini dan bukan dalam pilihan ECharts kerana ECharts
    // 4.3.1, iaitu versi yang sudah dimuatkan secara global oleh
    // `resources/views/layouts/app.blade.php`, tiada pemendekan teks per-token
    // dalam legenda, jadi lajur berlebar tetap akan melimpah dan merosakkan
    // tepi kanan yang dikongsi.
    //
    // Sumber baris ialah `donut.legend`: kesemua tujuh status COUNTED termasuk
    // yang bilangannya 0, sudah disusun MENURUN mengikut bilangan dengan seri
    // dipecahkan menaik mengikut id (Requirement 4 kriteria 15 dan 16). Tiada
    // pengurutan di sini — susunan datang daripada pelayan supaya ia sama pada
    // setiap render. `donut.segments` ialah senarai yang BERBEZA dan ia milik
    // cincin sahaja.
    //
    // Dibina melalui DOM API (textContent), bukan innerHTML: nama status
    // datang daripada pangkalan data.
    function renderStatusLegend(donut, colors) {
        var box = document.getElementById('laporan-status-legend');
        if (!box) return;

        box.textContent = '';

        var rows = donut.legend && donut.legend.length ? donut.legend : donut.segments;

        rows.forEach(function (s, i) {
            var dot = document.createElement('span');
            dot.className = 'chart-legend__dot';
            // Warna per-status, bukan per-baris: titik ini ialah warna yang
            // sama seperti segmen cincin status yang sama (kriteria 17).
            dot.style.backgroundColor = statusColor(colors, s, i);
            dot.setAttribute('aria-hidden', 'true');

            var name = document.createElement('span');
            name.className = 'chart-legend__name';
            name.textContent = s.label;
            name.title = s.label;

            var value = document.createElement('span');
            value.className = 'chart-legend__value';
            value.textContent = fmt(s.value);

            // Berkurungan: "(38.5%)", bukan "38.5%" (kriteria 20). Nilai
            // peratus itu sendiri kekal nilai pelayan.
            var percent = document.createElement('span');
            percent.className = 'chart-legend__pct';
            percent.textContent = '(' + pct(s.percent) + '%)';

            box.appendChild(dot);
            box.appendChild(name);
            box.appendChild(value);
            box.appendChild(percent);
        });
    }

    // Nilai tengah donat mengecil bersama bekasnya. Lubang donat ialah
    // peratusan daripada sisi terkecil bekas, jadi pada kad sempit (donat
    // berkongsi lebar dengan legenda pada >=992px) nombor 26px akan melimpah
    // keluar daripada lubang itu.
    function centreFontSize(el) {
        var side = Math.min(el.clientWidth || 0, el.clientHeight || 0);
        if (side >= 260) return 26;
        if (side >= 200) return 22;
        if (side >= 150) return 19;
        return 16;
    }

    function renderDonut(donut) {
        var el = document.getElementById('laporan-status-donut');
        if (!el || !donut || !donut.segments || !donut.segments.length) return;

        var total = donut.total;
        var colors = palette().list;
        // Warna diberikan secara EKSPLISIT pada setiap item data, daripada
        // `colorIndex` status itu sendiri. Tatasusunan `color` carta sengaja
        // tidak digunakan: ia memetakan warna mengikut kedudukan segmen, jadi
        // satu status akan bertukar warna apabila status lain menjadi sifar dan
        // segmennya hilang daripada senarai (Requirement 4 kriteria 18 dan 19).
        var rows = donut.segments.map(function (s, i) {
            return { name: s.label, value: s.value, itemStyle: { color: statusColor(colors, s, i) } };
        });
        var percents = {};
        donut.segments.forEach(function (s) { percents[s.label] = s.percent; });
        var totalSize = centreFontSize(el);

        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        // notMerge: buang siri penapis terdahulu sepenuhnya supaya tiada
        // segmen tertinggal (Requirement 4 kriteria 5).
        chart.setOption(merge({
            tooltip: {
                trigger: 'item',
                formatter: function (p) {
                    return '<strong>' + p.name + '</strong><br>' +
                           fmt(p.value) + ' aduan (' + pct(percents[p.name]) + '%)';
                }
            },
            // Legenda ECharts dimatikan: Legenda_Carta_Status ialah senarai
            // HTML di sebelah donat (lihat renderStatusLegend). Oleh kerana
            // tiada lagi jalur legenda di bawah donat, donat mengambil
            // keseluruhan bekas dan pusatnya kembali ke tengah.
            legend: { show: false },
            series: [{
                type: 'pie', radius: ['58%', '74%'], center: ['50%', '50%'],
                avoidLabelOverlap: true,
                itemStyle: { borderColor: '#fff', borderWidth: 2 },
                label: {
                    show: true, position: 'center',
                    // Dua baris: jumlah di atas, label "Jumlah Tiket" di bawah
                    // (Requirement 4 kriteria 2).
                    formatter: '{v|' + fmt(total) + '}\n{l|Jumlah Tiket}',
                    rich: {
                        v: { fontFamily: FONT_NUM, fontSize: totalSize, fontWeight: 600, color: INK, lineHeight: totalSize + 6 },
                        l: { fontFamily: FONT, fontSize: 11, color: MUTED, letterSpacing: 1 }
                    }
                },
                emphasis: { label: { show: true }, scale: false },
                labelLine: { show: false },
                data: rows
            }]
        }), true);
        chart.resize();
        renderStatusLegend(donut, colors);
        hideFallback('laporan-status-donut');
    }

    function renderTrend(trend) {
        var el = document.getElementById('laporan-trend-bar');
        if (!el || !trend || !trend.labels || !trend.labels.length) return;

        var p = palette();
        var labels = trend.labels;
        var chart = echarts.getInstanceByDom(el) || echarts.init(el);
        // notMerge: tiada bar daripada paksi penapis terdahulu kekal
        // (Requirement 5 kriteria 8).
        chart.setOption(merge({
            tooltip: {
                trigger: 'axis', axisPointer: { type: 'shadow' },
                formatter: function (params) {
                    if (!params.length) return '';
                    return '<strong>' + params[0].name + '</strong><br>' +
                           fmt(params[0].value) + ' aduan';
                }
            },
            legend: { show: false },
            // `containLabel: true` bermakna `bottom` ialah jarak dari tepi
            // bawah bekas ke tepi BAWAH label paksi, bukan ke garis paksi.
            // Jadi ketinggian label dua baris (2 x lineHeight 15 = 30px)
            // diserap oleh grid dengan sendirinya dan 28px di bawahnya kekal
            // ruang lapang — paksi dua baris tidak boleh terpotong oleh tepi
            // bekas `.chart-box` (Requirement 5 kriteria 14 dan 15).
            grid: { left: 8, right: 20, top: 16, bottom: 28, containLabel: true },
            xAxis: catAxis(labels, {
                axisLabel: {
                    color: MUTED, fontFamily: FONT,
                    // Pelangkauan automatik pada SEMUA bilangan baldi
                    // (Requirement 5 kriteria 13). `interval: 0` yang dahulu
                    // dipaksa pada <=31 baldi memaksa setiap label dilukis,
                    // dan itulah punca "01 No02 No03 No" pada julat 15 hari:
                    // label bertindih sehingga bersambung menjadi satu
                    // rentetan.
                    //
                    // ECharts mengira selang langkauan daripada label yang
                    // TELAH diformat, jadi pemecahan dua baris di bawah
                    // mengecilkan lebar yang diukur daripada "01 Nov" kepada
                    // "Nov" — lebih banyak label kekal dapat dipaparkan
                    // sebelum langkauan diperlukan.
                    //
                    // Tiada `hideOverlap` di sini: pilihan itu diperkenalkan
                    // dalam ECharts 5 dan Pustaka_Carta yang dimuatkan ialah
                    // 4.3.1, jadi ia diabaikan tanpa ralat dan hanya menyamar
                    // sebagai perlindungan yang tidak pernah berjalan
                    // (Requirement 9 kriteria 16).
                    interval: 'auto',
                    formatter: twoLineLabel,
                    lineHeight: 15
                }
            }),
            yAxis: valAxis(),            // minInterval: 1 — bermula 0, integer sahaja
            series: [{
                type: 'bar', barMaxWidth: 28,
                itemStyle: {
                    borderRadius: [8, 8, 0, 0],
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: '#6b78dc' }, { offset: 1, color: p.list[2] }])
                },
                data: trend.values
            }]
        }), true);
        chart.resize();
        hideFallback('laporan-trend-bar');
    }

    // Pemegang JSON dalam DOM komponen ialah satu-satunya pengangkut data
    // carta; acara `laporan-charts-updated` tiada payload (Requirement 5
    // kriteria 10).
    function paintFromDom() {
        if (typeof echarts === 'undefined') {
            CHART_IDS.forEach(showFallback);
            return;
        }
        var el = document.getElementById('laporan-chart-data');
        if (!el) return;
        try {
            renderDonut(JSON.parse(el.dataset.status || 'null'));
            renderTrend(JSON.parse(el.dataset.trend || 'null'));
        } catch (e) {
            CHART_IDS.forEach(showFallback);
        }
    }

    // Lukisan awal: muat halaman penuh dan navigasi SPA Livewire.
    document.addEventListener('DOMContentLoaded', paintFromDom);
    document.addEventListener('livewire:navigated', paintFromDom);
    paintFromDom();

    // Setiap render() ReportAll memancarkan isyarat ini (tanpa payload);
    // skrip membaca SEMULA pemegang data dan melukis semula.
    document.addEventListener('livewire:init', function () {
        Livewire.on('laporan-charts-updated', paintFromDom);
    });

    // Resize: saiz semula serta-merta supaya carta mengikut bekas tanpa
    // lengahan, kemudian satu lukisan semula yang dilengahkan selepas resize
    // reda. Lukisan semula itu diperlukan kerana saiz fon nilai tengah donat
    // dikira daripada lebar bekas, dan ia hanya boleh dikira semula melalui
    // setOption.
    var resizeTimer = null;

    window.addEventListener('resize', function () {
        if (typeof echarts === 'undefined') return;
        CHART_IDS.forEach(function (id) {
            var el = document.getElementById(id);
            if (el && echarts.getInstanceByDom(el)) {
                echarts.getInstanceByDom(el).resize();
            }
        });

        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(paintFromDom, 200);
    });
})();
</script>
@endsection
