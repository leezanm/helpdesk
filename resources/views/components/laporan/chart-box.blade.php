{{--
    Bekas carta Laporan Keseluruhan.

    Ketinggian datang daripada `.chart-box` dalam `resources/css/app.css`
    (300px, 260px pada <=991px) dan dikongsi oleh KETIGA-TIGA keadaan —
    carta dilukis, keadaan kosong pelayan, dan mesej fallback — supaya susun
    atur tidak beranjak sebelum, semasa atau selepas memuat (Requirement 7
    kriteria 5).

    Apabila `$empty` benar, elemen sasaran carta TIDAK dirender. Skrip carta
    melakukan `document.getElementById($id)`, tidak menemui apa-apa, dan keluar
    dengan senyap (Requirement 4 kriteria 6, Requirement 5 kriteria 9 dan 11).

    `.chart-fallback` sentiasa hadir dalam HTML tetapi tersembunyi
    (`display:none`), jadi tiada kilatan mesej apabila carta berjaya dilukis.
    Skrip carta tugasan 9.3 menambah kelas `is-shown` padanya apabila pustaka
    carta tiada atau JSON tidak boleh dihurai (Requirement 4 kriteria 9,
    Requirement 5 kriteria 12). Elemen itu ialah adik-beradik sasaran carta dan
    turut membawa id `{{ $id }}-fallback`, jadi skrip boleh mencarinya sama ada
    melalui id atau melalui `.chart-fallback` dalam `.chart-box` yang sama.
--}}
@props(['id', 'empty' => false, 'emptyText' => 'Tiada data untuk tempoh ini'])

<div {{ $attributes->class('chart-box') }}>
    @if ($empty)
        <div class="chart-empty">
            <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
            <span>{{ $emptyText }}</span>
        </div>
    @else
        <div id="{{ $id }}" style="width: 100%; height: 100%;"></div>
    @endif

    <div id="{{ $id }}-fallback" class="chart-fallback" role="status">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <span>Carta tidak dapat dipaparkan.</span>
    </div>
</div>
