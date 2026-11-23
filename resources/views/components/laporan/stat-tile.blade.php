{{--
    Kad statistik Laporan Keseluruhan.

    Nada warna TIDAK ditentukan di sini: prop `tone` membawa satu token `bg-*`
    yang diterbitkan oleh komponen ReportAll daripada `_report_statuses.class`,
    jadi kad dan badge status berkongsi satu sumber warna (Requirement 3.6).

    Token tersebut DINAMAKAN SEMULA kepada `stat-tone-*` sebelum dirender.
    Sebabnya: `bg-info`, `bg-primary`, `bg-warning`, `bg-success`, `bg-danger`
    dan `bg-secondary` ialah nama kelas utiliti Bootstrap, dan Bootstrap 5.3.3
    mengisytiharkan `background-color` utiliti tersebut dengan `!important`,
    jadi isian penuh bertenggek menang ke atas tint pastel tanpa mengira
    susunan fail — itulah blok warna penuh yang kelihatan pada halaman
    sebelum tugasan 15.1. Penamaan semula ialah penggantian rentetan tulen
    (`bg-x` -> `stat-tone-x`), bukan pemetaan warna per-status, jadi hue
    setiap kad masih datang daripada `_report_statuses.class` sahaja
    (Requirement 3.6, 3.18 dan 8.15).

    Kelas `.status-tile`, `.status-tile__label`, `.status-tile__value` dan
    `.status-tile--total` sudah ada dalam `resources/css/app.css` dan diguna
    semula tanpa diubah; `.status-tile__head`, `.status-tile__icon`,
    `.status-tile--tone` dan `.stat-tone-*` ditambah oleh blok CSS halaman ini.

    Kad ini bukan kawalan: tiada `wire:click` dan tiada `href` (Requirement 3.13).
--}}
@props(['label', 'value', 'tone' => 'bg-secondary', 'icon' => 'bi-circle', 'total' => false])

@php
    // `Str::after` mengupas awalan `bg-`; penapis aksara menahan nilai
    // `_report_statuses.class` yang ganjil daripada menyuntik kelas lain.
    // Nada yang tiada blok CSS jatuh balik kepada lalai `.status-tile`.
    $toneClass = 'stat-tone-'.preg_replace('/[^a-z0-9-]/', '', \Illuminate\Support\Str::after((string) $tone, 'bg-'));
@endphp

<div {{ $attributes->class([
        'status-tile',
        'status-tile--tone',
        'status-tile--total' => $total,
        $toneClass => ! $total,
    ]) }}
     title="{{ $total ? 'Jumlah keseluruhan' : $label }}: {{ number_format((int) $value) }} aduan">
    <div class="status-tile__head">
        <span class="status-tile__icon" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>
        <div class="status-tile__label">{{ $label }}</div>
    </div>
    <div class="status-tile__value">{{ number_format((int) $value) }}</div>
</div>
