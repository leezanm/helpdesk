<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Jadual Pergerakan {{ $unit->name }} — {{ $monthLabel }}</title>

    {{-- Halaman cetak berdiri sendiri: tiada sidebar, tiada JS aplikasi.
         Gaya dibenamkan supaya cetakan tidak bergantung pada binaan Vite. --}}
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * { box-sizing: border-box; }

        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            font-size: 8pt;
            color: #000;
            margin: 0;
            padding: 12px;
        }

        .doc-head { text-align: center; margin-bottom: 10px; }
        .doc-head h1 { font-size: 12pt; margin: 0 0 2px; }
        .doc-head p { font-size: 9pt; margin: 0; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }

        th, td {
            border: 0.5pt solid #555;
            padding: 2px 3px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        thead th { text-align: center; font-size: 7pt; background: #eee; }
        thead { display: table-header-group; }   /* ulang tajuk pada setiap halaman */
        tr { page-break-inside: avoid; }

        th.name-col { width: 110px; text-align: left; vertical-align: middle; font-size: 7.5pt; }
        .weekend { background: #e9e9e9; }
        .holiday { background: #fff2cc; }
        .holiday-name { display: block; font-weight: normal; font-size: 6pt; }

        .entry { margin-bottom: 3px; }
        .entry-title { font-weight: 600; }
        .entry-meta { display: block; font-size: 6.5pt; color: #333; }

        .doc-foot { margin-top: 8px; font-size: 6.5pt; color: #444; display: flex; justify-content: space-between; }

        .no-print { margin-bottom: 10px; text-align: center; }
        .no-print button {
            font: inherit; padding: 6px 14px; cursor: pointer;
            border: 1px solid #555; background: #f3f3f3; border-radius: 4px;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Cetak jadual ini</button>
    </div>

    <div class="doc-head">
        <h1>Jadual Pergerakan {{ $unit->name }}@if ($unit->short_name), {{ $unit->short_name }}@endif</h1>
        <p>{{ $monthLabel }}</p>
    </div>

    @if (count($rows) === 0)
        <p>Belum ada pegawai dalam senarai pemantauan untuk unit ini.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th rowspan="3" class="name-col">Nama</th>
                    @foreach ($weeks as $week)
                        <th colspan="{{ count($week['days']) }}">{{ $week['label'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($days as $day)
                        <th class="{{ $day['is_weekend'] ? 'weekend' : '' }} {{ $day['holiday'] ? 'holiday' : '' }}">{{ $day['name'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($days as $day)
                        <th class="{{ $day['is_weekend'] ? 'weekend' : '' }} {{ $day['holiday'] ? 'holiday' : '' }}">
                            {{ $day['date']->format('j M') }}
                            @if ($day['holiday'])
                                <span class="holiday-name">{{ $day['holiday'] }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <th class="name-col">{{ $row['officer']->display_name }}</th>
                        @foreach ($days as $day)
                            <td class="{{ $day['is_weekend'] ? 'weekend' : '' }} {{ $day['holiday'] ? 'holiday' : '' }}">
                                @foreach ($row['cells'][$day['key']] as $movement)
                                    <div class="entry">
                                        <span class="entry-title">{{ $movement->title }}</span>
                                        @if ($movement->start_time)
                                            <span class="entry-meta">{{ substr($movement->start_time, 0, 5) }}@if ($movement->end_time) – {{ substr($movement->end_time, 0, 5) }}@endif</span>
                                        @endif
                                        @if ($movement->location || $movement->is_outside)
                                            <span class="entry-meta">
                                                @if ($movement->is_outside)[Luar] @endif{{ $movement->location }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="doc-foot">
        <span>Sistem Meja Bantuan KKDW — Modul Pergerakan Pegawai</span>
        <span>Dicetak: {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</body>
</html>
