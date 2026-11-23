# Implementation Plan: Laporan Keseluruhan UI Refresh

## Overview

Pelaksanaan dibahagikan mengikut arah aliran kebergantungan: kerja asas yang dikongsi mendarat
dahulu (kelas CSS, hierarki sidebar), kemudian lapisan data komponen `ReportAll`, kemudian markup
Blade yang menggunakan data tersebut, kemudian Eksport_CSV, kemudian keadaan memuat dan kosong,
dan akhirnya pas regresi merentas halaman yang berkongsi sidebar, `x-filter-bar` dan CSS yang
disentuh.

Pengadang tarikh `safeDate()` diletakkan dalam wave pertama kerana ia membetulkan ralat 500 yang
berlaku hari ini (`Carbon::parse('abc')` pada parameter URL yang disunting tangan), dan kesemua
pembina data carta bergantung kepada `fromDate()`/`toDate()` yang dibersihkan.

Bahasa pelaksanaan ialah PHP 8 / Laravel / Livewire 3 seperti yang digunakan oleh `design.md`;
tiada pseudokod dalam reka bentuk, jadi tiada pemilihan bahasa diperlukan.

## Tasks

- [x] 1. Asas CSS dalam `resources/css/app.css`
  - [x] 1.1 Tambah kelas komponen halaman sebagai blok baharu di penghujung fail
    - Tambah `.report-head__sub`, `.report-meta`, `.report-meta--warn`, `.report-notice`, `.stat-grid`, `.status-tile__head`, `.status-tile__icon`, `.chart-box`, `.chart-empty`, `.chart-fallback`, `.chart-fallback.is-shown`, `.report-busy`, `.report-busy__veil`, `.table-head--primary`, `.cell-muted`
    - Setiap nilai warna, radius dan bayang dirujuk melalui `var(--token)` sedia ada; tiada literal baharu
    - `.stat-grid`: 4 lajur >=1200px, 4 lajur 992–1199px, 3 lajur 768–991px, 2 lajur <=575px
    - `.chart-box`: ketinggian tetap 300px, 260px pada <=991px, dikongsi oleh keadaan carta, kosong dan fallback
    - Jangan buang atau tukar nilai mana-mana pengisytiharan sedia ada, dan jangan sentuh blok `:root`
    - _Requirements: 3.4, 4.4, 6.4, 6.13, 7.5, 8.1, 8.2, 8.4, 8.5, 8.6, 8.7, 8.8, 9.9, 9.14_

  - [x] 1.2 Tambah blok CSS hierarki sidebar dan tukar warna tajuk bahagian
    - Tambah `.app-sidebar__nav > .app-nav-link` dan `.app-sidebar__nav > div > .app-nav-link` (`font-size: 0.9rem; font-weight: 600`)
    - Tambah blok `.app-nav-sub .app-nav-link` (`font-weight: 500; padding-left: 2.6rem`) selepas blok sedia ada
    - Tambah `.app-nav-sub .app-nav-link.active::before` (`left: 1.6rem; height: 18px`) supaya rel aktif tidak bertindih `.app-nav-sub::before`
    - Tukar pengisytiharan `color` `.app-sidebar__title` daripada literal `#9ba1ba` kepada `var(--c-text-muted)` — satu-satunya pengisytiharan sedia ada yang nilainya diubah
    - Tambah `@media (max-width: 575.98px) { .app-nav-link { min-height: 40px; } }`
    - Jangan sentuh `.app-sidebar`, `.app-sidebar__brand`, `.app-main`, `.app-nav-link` asal, `.app-nav-link.active`, `.app-nav-sub`, atau media query drawer 991.98px
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.14, 10.16, 10.17, 10.18_

- [x] 2. Markup hierarki dan pembetulan pepijat Sidebar_Sistem
  - [x] 2.1 Betulkan markup rosak dan syarat buka kumpulan Laporan dalam `resources/views/layouts/navbar.blade.php`
    - Tutup elemen `<div x-data>` dalam blok `@can('view.report')` yang kini dibuka tanpa `</div>`
    - Tukar syarat buka kumpulan Laporan daripada `str_starts_with($path, 'report')` kepada `str_starts_with($path, 'laporan')`
    - Jangan tambah, buang atau namakan semula mana-mana item menu, dan jangan tukar susunan, `href`, atau pengadang `@can`/`@canany`/`@hasrole`
    - _Requirements: 10.8, 10.10, 10.11, 10.12, 10.19, 10.20_

  - [x] 2.2 Tambah kelas `active` pada Item_Submenu yang tiada penanda aktif
    - Tambah syarat `active` berasaskan `request()->path()` pada submenu Laporan Aduan, Cipta Aduan, Aduan Saya, Aduan Baru, Senarai Aduan, Pengurusan Pengguna dan Manual
    - Gunakan mekanisme kelas `active` sedia ada sahaja; jangan perkenalkan mekanisme keadaan aktif baharu dan jangan ubah `href`
    - _Requirements: 10.6, 10.7, 10.20_

  - [ ]* 2.3 Tulis ujian properti Sidebar_Sistem dalam `tests/Feature/Laporan/SidebarHierarchyTest.php`
    - **Property 26: Penanda aktif sidebar tepat satu bagi setiap laluan menu**
    - **Property 27: Struktur dan keterlihatan sidebar tidak berubah per peranan**
    - Gelung >=100 lelaran dengan `fake()->seed(...)` tetap merentas gabungan peranan dan laluan menu
    - **Validates: Requirements 10.6, 10.7, 10.8, 10.10, 10.11, 10.12**

- [x] 3. Pengadang parameter URL dan pertanyaan yang dikongsi dalam `app/Livewire/Laporan/ReportAll.php`
  - [x] 3.1 Tambah pengadang tarikh yang membetulkan ralat 500 parameter URL
    - Tambah `safeDate(string $value, Carbon $fallback): Carbon` menggunakan `Carbon::hasFormat($value, 'Y-m-d')`, tanpa `Carbon::parse()` pada input pengguna
    - Tambah `fromDate()`, `toDate()`, `normalisedFrom()`, `normalisedTo()`, `hasInvalidRange()`
    - Jangan tulis semula nilai sifat awam `dateFrom`/`dateTo`; pembersihan hanya pada titik penggunaan supaya nilai yang dimasukkan kekal dalam kawalan dan URL
    - _Requirements: 1.8, 1.9, 2.9, 2.10, 9.12, 9.13_

  - [x] 3.2 Alihkan `baseQuery()` kepada pengadang tarikh dan tambah `listQuery()`
    - Tukar ungkapan tarikh `baseQuery()` kepada `fromDate()`/`toDate()`; kekalkan semantik penapis yang lain tanpa perubahan
    - Tambah `listQuery()`: `baseQuery()->with(['status','category','riskLevel','reportBy'])->orderByDesc('submitted_at')->orderByDesc('id')`
    - Pemecah seri `orderByDesc('id')` diperlukan supaya `chunk()` eksport tidak melangkau atau mengulang baris
    - Gunakan `listQuery()` sebagai satu-satunya sumber baris bagi Jadual_Aduan dan Eksport_CSV
    - _Requirements: 6.10, 9.3, 11.4_

  - [ ]* 3.3 Tulis ujian properti penapis dan pengadang dalam `tests/Feature/Livewire/Laporan/ReportAllFilterPropertiesTest.php`
    - **Property 7: Julat tarikh tidak sah menghasilkan amaran, nilai dikekalkan dan keadaan kosong**
    - **Property 8: Tiada kombinasi parameter menghasilkan ralat pelayan**
    - **Property 9: Penapis ialah round-trip URL**
    - **Property 10: Set Semula sentiasa mengembalikan keadaan lalai**
    - **Property 16: Semantik pertanyaan asas dikekalkan**
    - Tambah pembantu generator tempatan `randomFilterSet()`, `randomDateRange()`, `garbageFilterSet()`, `randomReports(n)` dengan benih `fake()` tetap; laporkan input yang gagal dalam mesej pengesahan
    - **Validates: Requirements 2.6, 2.7, 2.8, 2.9, 2.10, 9.3, 9.12, 9.13**

- [x] 4. Pembina data Kad_Statistik dan carta dalam `app/Livewire/Laporan/ReportAll.php`
  - [x] 4.1 Laksanakan `statusCounts()` beserta peta ikon status
    - Satu pertanyaan agregat `groupBy('report_status_id')` dipetakan ke atas `ReportStatus::whereIn('id', ReportStatuses::COUNTED)->orderBy('id')->get()` supaya status tanpa aduan kekal hadir dengan `total = 0`
    - Terbitkan `tone` daripada token `bg-*` pertama dalam `_report_statuses.class`, dengan fallback `bg-secondary`
    - Tambah pemalar `STATUS_ICONS` (id status → ikon Bootstrap Icons) dan `statusIcon(int $statusId): string` dengan fallback `bi-circle`; ikon dipetakan mengikut id kerana nilai `class` tidak unik
    - Kekalkan `grandTotal` sebagai hasil tambah agregat yang sama
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6, 3.9, 3.11, 3.12, 9.6_

  - [x] 4.2 Laksanakan `statusDonutData()`
    - Bina daripada `statusCounts()` sahaja, bukan pertanyaan tambahan, supaya segmen donat tidak boleh berbeza daripada kad statistik
    - Sertakan hanya status dengan `total > 0`, susunan menaik id dikekalkan
    - Kira `percent` di pelayan: `round($value / $total * 100, 1)`
    - Pulangkan `segments` kosong apabila `total === 0`
    - _Requirements: 4.1, 4.2, 4.3, 4.6_

  - [x] 4.3 Laksanakan pengumpulan Carta_Tren beserta ungkapan SQL per-pemacu
    - Tambah `dateBucketExpr(string $column, string $granularity): string` — `strftime` bagi `sqlite`, `DATE_FORMAT` bagi MySQL
    - Tambah `trendGranularity()` (`'day'` apabila `diffInDays + 1 <= 62`, `'month'` apabila lebih) dan `trendBucketCount()`
    - Tambah `trendData()`: satu pertanyaan agregat `groupBy('bucket')`, kemudian bina paksi dalam PHP dengan kursor Carbon dari `fromDate()` hingga `toDate()` dan tulis `0` bagi baldi tanpa data
    - Format label Bahasa Melayu: `DD MMM` harian, `MMM YYYY` bulanan
    - Tambah pengadang lelaran (maksimum 400 harian, 600 bulanan) supaya julat tarikh melampau dalam URL tidak menggantung permintaan
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.8, 5.11_

  - [x] 4.4 Sambungkan `render()` kepada pembina data baharu
    - Hantar `statusCounts`, `grandTotal`, `donut`, `trend`, `invalidRange`, `periodFrom`, `periodTo` bersama pilihan penapis sedia ada
    - Kekalkan `reports` sebagai `listQuery()->paginate(20)`
    - Tambah `$this->dispatch('laporan-charts-updated')` tanpa payload supaya pemegang JSON kekal satu-satunya pengangkut data
    - _Requirements: 1.4, 5.10, 6.10, 9.6, 9.7_

  - [ ]* 4.5 Tulis ujian properti data carta dalam `tests/Feature/Livewire/Laporan/ReportAllChartDataTest.php`
    - **Property 1: Jumlah sentiasa konsisten merentas tiga paparan**
    - **Property 2: Segmen donat ialah tepat status bukan sifar dengan peratus yang betul**
    - **Property 3: Hasil tambah bar Carta_Tren sama dengan jumlah keseluruhan**
    - **Property 4: Paksi Carta_Tren ialah fungsi tulen julat tarikh**
    - **Property 12: Paparan sentiasa mencerminkan pangkalan data**
    - **Property 14: Ikon status unik dan stabil**
    - **Property 15: Lapan kad sentiasa hadir dengan integer bukan negatif**
    - Tambah ujian contoh sempadan ambang 62/63 hari bagi `trendGranularity()`
    - **Validates: Requirements 3.2, 3.3, 3.5, 3.9, 3.11, 3.12, 4.1, 4.2, 4.3, 5.1, 5.2, 5.3, 5.4, 5.5, 5.8, 9.6, 9.7**

  - [x] 4.6 Tulis ujian unit `dateBucketExpr()` bagi kedua-dua pemacu dalam `tests/Unit/Support/DateBucketExprTest.php`
    - Sahkan rentetan ungkapan yang dikembalikan bagi nama pemacu `sqlite` dan `mysql`, bagi granulariti `day` dan `month`, tanpa menjalankan pertanyaan
    - Cabang `DATE_FORMAT` tidak dilaksanakan oleh CI kerana `phpunit.xml` menetapkan `DB_CONNECTION=sqlite`, jadi ujian rentetan ini ialah satu-satunya perlindungan automatik bagi laluan produksi MySQL
    - Nota untuk pelaksana: sahkan juga nilai baldi harian dan bulanan secara manual pada pangkalan data MySQL pementasan sebelum pelepasan
    - _Requirements: 5.3, 5.4, 9.3_

- [x] 5. Checkpoint — lapisan data siap
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Kepala_Halaman dan ringkasan tempoh
  - [x] 6.1 Tulis kepala halaman statik dalam `resources/views/laporan/show_by_all.blade.php`
    - `@section('breadcrumb')`: tajuk "Laporan Keseluruhan", subtitle "Ringkasan dan analisis keseluruhan aduan/helpdesk dalam sistem." dengan kelas `.report-head__sub`, dan breadcrumb tiga aras "Laman Utama" (pautan ke `dashboard.index`) → "Laporan" → "Keseluruhan" (teks)
    - Kekalkan `@section('content')` yang merender `@livewire('laporan.report-all')`
    - Pastikan teks "Laporan Keseluruhan" dirender di sini sahaja, dan jarak menegak ke Penapis_Laporan tidak melebihi 24px pada >=992px
    - _Requirements: 1.1, 1.2, 1.3, 1.6_

  - [x] 6.2 Tulis ringkasan tempoh reaktif dalam `resources/views/livewire/laporan/report-all.blade.php`
    - Pil `.report-meta` memaparkan `convertDateFormat($periodFrom, 'LL')` + "hingga" + `convertDateFormat($periodTo, 'LL')`
    - Papar nama Bahagian (dipendekkan kepada 60 aksara) apabila penapis Bahagian mempunyai nilai, dan "Semua Bahagian" apabila tiada
    - Apabila `$invalidRange`, gunakan `.report-meta--warn` dan papar mesej amaran Bahasa Melayu tanpa mengubah nilai tarikh yang dimasukkan
    - Blok ini mesti berada dalam DOM komponen Livewire supaya ia dimorf semula pada setiap render
    - _Requirements: 1.4, 1.5, 1.7, 1.8, 1.9_

  - [ ]* 6.3 Tulis ujian Kepala_Halaman dalam `tests/Feature/Laporan/LaporanHeaderTest.php`
    - **Property 6: Ringkasan tempoh sentiasa memaparkan julat yang digunakan**
    - Ujian contoh: teks "Laporan Keseluruhan" muncul tepat sekali dalam kandungan halaman
    - **Validates: Requirements 1.4, 1.5, 1.6**

- [x] 7. Komponen Blade baharu khusus halaman ini
  - [x] 7.1 Cipta `resources/views/components/laporan/stat-tile.blade.php`
    - `@props(['label', 'value', 'tone' => 'bg-secondary', 'icon' => 'bi-circle', 'total' => false])`
    - Guna semula kelas `.status-tile`, `.status-tile__label`, `.status-tile__value`, nada `.status-tile.bg-*` dan `.status-tile--total` sedia ada; tambah `.status-tile__head` dan `.status-tile__icon`
    - Papar nilai dengan `number_format()` dalam fon `--font-display` pada >=1.5rem, label pada <=0.875rem
    - Tiada pemetaan warna per-status dalam komponen: nada datang daripada prop `tone` yang diterbitkan daripada `_report_statuses.class`
    - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.12, 8.2_

  - [x] 7.2 Cipta `resources/views/components/laporan/chart-box.blade.php`
    - `@props(['id', 'empty' => false, 'emptyText' => 'Tiada data untuk tempoh ini'])`
    - Render bekas `.chart-box` berketinggian tetap, keadaan kosong `.chart-empty` pelayan dengan ikon Bootstrap Icons, dan elemen `.chart-fallback` yang tersembunyi secara lalai dengan mesej "Carta tidak dapat dipaparkan."
    - Apabila `$empty` benar, jangan render elemen sasaran carta supaya skrip keluar dengan senyap
    - _Requirements: 4.6, 4.9, 5.9, 5.12, 7.5_

- [x] 8. Kad Penapis_Laporan dan butang Eksport CSV
  - [x] 8.1 Tambah prop `resetBusyTarget` pada `resources/views/components/filter-bar.blade.php`
    - Tambah `'resetBusyTarget' => null` kepada `@props`
    - Apabila prop diberi nilai, pancarkan `wire:loading.attr="disabled"` dan `wire:target="{{ $resetBusyTarget }}"` pada butang "Set Semula"
    - Apabila prop `null`, markup yang terhasil mesti sama seperti sebelum suntingan supaya kesembilan view pemanggil lain tidak terjejas
    - Jangan ubah `title`, `icon`, `showReset`, `resetAction`, slot `$action`, kelas CSS atau susunan elemen
    - _Requirements: 7.6, 8.3, 8.13, 11.13_

  - [x] 8.2 Tulis bahagian penapis dalam `resources/views/livewire/laporan/report-all.blade.php`
    - Kekalkan tujuh `x-filter-field` dalam susunan Dari Tarikh, Hingga Tarikh, Bahagian, Status Tindakan, Pegawai Bertanggungjawab, Tahap Risiko, Rating / Maklum Balas dengan `wire:model.live` sedia ada
    - Tetapkan `$busy = 'dateFrom,dateTo,bahagian,status,pegawai,rating,riskLevel,resetFilters,gotoPage,previousPage,nextPage'` dan hantar `:reset-busy-target="$busy"`
    - Tambah butang "Eksport CSV" dalam `<x-slot:action>` dengan ikon Bootstrap Icons, `wire:click="exportCsv"`, `wire:loading.attr="disabled"` dan `wire:target="exportCsv"`
    - Papar penanda amaran sebaris pada kedua-dua kawalan tarikh apabila `$invalidRange`, tanpa mengosongkan kawalan
    - Papar jalur `.report-notice` apabila `$exportNotice` tidak kosong
    - Tepat dua butang pada kepala kad; tiada butang "Jana Laporan"
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.10, 2.12, 7.6, 11.1, 11.13_

  - [ ]* 8.3 Tulis ujian regresi `x-filter-bar` dalam `tests/Feature/Laporan/FilterBarComponentTest.php`
    - Render komponen tanpa `reset-busy-target` dan sahkan tiada atribut `wire:loading`, `wire:target` atau `disabled` dipancarkan pada butang "Set Semula"
    - Render dengan `reset-busy-target` dan sahkan kedua-dua atribut hadir
    - **Validates: Requirements 8.3, 8.13**

- [x] 9. Kad_Statistik, bekas carta, skrip carta dan Jadual_Aduan
  - [x] 9.1 Tulis grid Kad_Statistik dalam `resources/views/livewire/laporan/report-all.blade.php`
    - Grid `.stat-grid` dengan tujuh `<x-laporan.stat-tile>` daripada `$statusCounts` ditambah satu kad "Jumlah" daripada `$grandTotal`
    - Tiada bilangan, label status atau pemetaan warna ditulis secara tetap dalam view
    - Kad tidak boleh diklik: tiada `wire:click` atau `href` pada mana-mana kad
    - _Requirements: 3.1, 3.2, 3.3, 3.6, 3.7, 3.9, 3.10, 3.11, 3.13, 9.6_

  - [x] 9.2 Tulis baris carta dan pemegang data JSON dalam `resources/views/livewire/laporan/report-all.blade.php`
    - Dua `<x-laporan.chart-box>` dengan id `laporan-status-donut` dan `laporan-trend-bar` dalam baris dua lajur sama lebar pada >=992px, satu lajur pada <=991px
    - Hantar `:empty` daripada `$donut['total'] === 0` dan `empty($trend['values'])`
    - Tambah `<div id="laporan-chart-data" data-status='@json($donut)' data-trend='@json($trend)' hidden>` mengikut corak `livewire/dashboard/dashboard.blade.php`
    - _Requirements: 4.4, 4.6, 5.9, 5.10, 5.11, 8.5, 8.6, 8.7_

  - [x] 9.3 Tulis skrip carta dalam `@section('script')` `resources/views/laporan/show_by_all.blade.php`
    - Satu IIFE dengan pembantu `cssVar`, `palette`, `baseOption`, `catAxis`, `valAxis`, `merge`, `fmt`, `showFallback` — salinan nilai yang sama seperti `dashboard.blade.php`, yang tidak boleh diubah
    - `renderDonut()`: pie `radius: ['58%','74%']`, label tengah `rich` (jumlah + "Jumlah"), legenda bawah ikon bulat dengan bilangan dan peratus, tooltip per item
    - `renderTrend()`: bar `borderRadius: [8,8,0,0]`, gradien, `valAxis` `minInterval: 1`, label paksi `interval: labels.length > 31 ? 'auto' : 0` dengan `hideOverlap: true`
    - **Pembetulan selepas tugasan 15.2:** `hideOverlap: true` dalam bullet di atas ialah **arahan yang salah** — pilihan tersebut hanya wujud dari ECharts 5 dan Pustaka_Carta yang dibundalkan ialah ECharts **4.3.1**, jadi ia diabaikan tanpa ralat dan tidak pernah melindungi apa-apa. Perlindungan yang sebenar ialah `interval: 'auto'` seperti yang ditetapkan oleh tugasan 15.2 dan 15.3. Rujuk Requirement 9 kriteria 16 dan `design.md` seksyen 4.3
    - `setOption(..., true)` (notMerge) pada kedua-dua carta supaya tiada segmen atau bar daripada penapis terdahulu kekal
    - `paintFromDom()` dengan pengadang `typeof echarts === 'undefined'` dan `try/catch` di sekeliling `JSON.parse`, kedua-duanya memanggil `showFallback()`
    - Daftar pada `DOMContentLoaded`, `livewire:navigated`, dan `Livewire.on('laporan-charts-updated', paintFromDom)`; tambah pengendali `resize`
    - _Requirements: 4.5, 4.7, 4.8, 4.9, 5.6, 5.7, 5.8, 5.12, 5.13, 9.6, 9.11_

  - [x] 9.4 Kemas Jadual_Aduan dalam `resources/views/livewire/laporan/report-all.blade.php`
    - Sepuluh lajur: Bil., Tiket, Subjek, Bahagian, Kategori, Risiko, Tarikh, Status, Rating, Tindakan — lajur pertama berlabel "Bil." menggantikan "#"
    - Kepala jadual guna `.table-head--primary`; nombor tiket berat fon >=600; sel lain <=400
    - Tarikh melalui `convertDateFormat($report->submitted_at, 'LL')` pada satu baris; badge status daripada `_report_statuses.class`; badge risiko daripada `_risk_levels.class`
    - Subjek dipendekkan 40 aksara dengan Subjek penuh sebagai tooltip; penanda tiada nilai `.cell-muted` bagi Bahagian, Kategori, Risiko dan Rating yang kosong
    - Jajaran tengah bagi Bil. dan Rating, jajaran kanan bagi Tindakan; butang "Lihat" ke `route('aduan.show', $report)`
    - Baris keadaan kosong merentas sepuluh lajur dengan ikon dan mesej "Tiada aduan sepadan dengan penapis.", tanpa pautan penomboran
    - Bekas jadual boleh tatal mendatar pada <=575px tanpa limpahan badan halaman
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 6.10, 6.11, 6.12, 6.13, 6.14, 6.15_

  - Ujian properti paparan yang dahulunya tugasan 9.5 telah **dipindahkan** ke tugasan 16.1 supaya ia
    mengesahkan markup akhir selepas pembetulan visual tugasan 15 mendarat.

- [x] 10. Checkpoint — paparan siap
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Eksport_CSV
  - [x] 11.1 Cipta `app/Support/LaporanCsvExporter.php`
    - `HEADERS` sembilan lajur Bahasa Melayu: Bil., Tiket, Subjek, Bahagian, Kategori, Risiko, Tarikh, Status, Rating
    - `CHUNK_SIZE = 500`; `stream(Builder $query, string $filename): StreamedResponse` menggunakan `response()->streamDownload()`, BOM UTF-8, `fputcsv`, `chunk()` + `flush()` per kelompok
    - `row(Report $report, int $bil): array` — Subjek penuh tanpa `Str::limit()`, Risiko daripada aksesori `label`, Tarikh melalui `convertDateFormat(..., 'LL')`, Rating integer mentah, tepat `-` bagi medan kosong
    - Pengepala `Content-Type: text/csv; charset=UTF-8`
    - Guna fungsi terbina Laravel dan PHP sahaja; tiada entri `composer.json` atau `package.json` baharu
    - _Requirements: 11.5, 11.6, 11.7, 11.8, 11.10, 11.18, 11.19_

  - [x] 11.2 Tambah tindakan `exportCsv()` dan sifat `$exportNotice` pada `app/Livewire/Laporan/ReportAll.php`
    - Pernyataan pertama: `$this->authorize('access-main-dashboard')` sebagai lapisan kebenaran ketiga
    - Kira `(clone $this->baseQuery())->count()`; apabila sifar, tetapkan `$exportNotice = 'Tiada data untuk dieksport.'` dan pulangkan `null` tanpa muat turun
    - Jika tidak, kosongkan `$exportNotice` dan pulangkan `LaporanCsvExporter::stream($this->listQuery(), $this->exportFilename())`
    - Tambah `exportFilename()`: `laporan-keseluruhan-{normalisedFrom}-hingga-{normalisedTo}.csv`
    - Kosongkan `$exportNotice` dalam `updated()` bersama panggilan `resetPage()` sedia ada
    - `exportCsv()` tidak menyentuh sifat penapis dan tidak memanggil `resetPage()`; laksanakan sebagai tindakan komponen tanpa laluan baharu
    - _Requirements: 11.2, 11.3, 11.4, 11.9, 11.11, 11.12, 11.15, 11.16, 11.17, 9.15_

  - [ ]* 11.3 Tulis ujian Eksport_CSV dalam `tests/Feature/Livewire/Laporan/ReportAllExportTest.php`
    - **Property 19: Bilangan baris eksport sama dengan kiraan tanpa penomboran**
    - **Property 20: Baris eksport ialah gabungan semua halaman jadual**
    - **Property 21: Pengekodan CSV ialah round-trip**
    - **Property 22: Medan kosong dalam eksport menjadi sengkang**
    - **Property 23: Nama Fail_Eksport mengikut julat tarikh yang dibersihkan**
    - **Property 24: Eksport tiada kesan sampingan pada keadaan halaman**
    - **Property 25: Tiada data aduan sampai kepada pengguna tanpa kebenaran**
    - Tangkap kandungan strim dengan `ob_start()` + `$response->sendContent()`
    - Ujian contoh: baris kepala tepat sembilan lajur dalam susunan; pengepala `Content-Type` dan `Content-Disposition`; set kosong dan julat terbalik menghasilkan "Tiada data untuk dieksport." tanpa pengecualian; ~1,200 rekod menghasilkan `ceil(n/500)` pertanyaan kelompok yang dikira melalui `DB::listen`
    - **Validates: Requirements 11.3, 11.4, 11.5, 11.6, 11.7, 11.8, 11.9, 11.10, 11.12, 11.15, 11.16, 11.17, 11.18**

- [x] 12. Keadaan memuat dan keadaan kosong
  - [x] 12.1 Tambah tindanan memuat dalam `resources/views/livewire/laporan/report-all.blade.php`
    - Balut Kad_Statistik, baris carta dan kad Jadual_Aduan dengan `.report-busy`, masing-masing mengandungi `.report-busy__veil` dengan `wire:loading.flex` dan `wire:target="{{ $busy }}"` yang sama supaya ketiga-tiganya muncul dan hilang bersama
    - Tindanan, bukan penggantian: nilai kad dan baris jadual daripada hasil terakhir kekal dirender sepanjang permintaan
    - Jangan tambah lapisan pengendalian ralat, tamat masa atau mesej kegagalan baharu
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.7_

  - [x] 12.2 Lengkapkan keadaan kosong merentas bahagian dalam `resources/views/livewire/laporan/report-all.blade.php`
    - Julat tarikh tidak sah: lapan kad memaparkan 0, dan Carta_Status, Carta_Tren serta Jadual_Aduan memaparkan keadaan kosong
    - Carta memaparkan "Tiada data untuk tempoh ini"; jadual memaparkan "Tiada aduan sepadan dengan penapis."
    - Ketinggian bekas carta kekal sama dalam ketiga-tiga keadaan: carta dilukis, kosong dan fallback
    - Kekalkan teks antara muka Bahasa Melayu bagi semua mesej keadaan kosong
    - _Requirements: 4.6, 5.9, 5.11, 6.14, 7.5, 8.12, 9.12, 9.13_

  - Ujian keadaan memuat yang dahulunya tugasan 12.3 telah **dipindahkan** ke tugasan 16.2 supaya ia
    mengesahkan markup akhir selepas pembetulan visual tugasan 15 mendarat.

- [x] 13. Pas regresi
  - [x] 13.1 Tulis ujian regresi kelakuan dan kekangan repositori dalam `tests/Feature/Laporan/LaporanRegressionTest.php`
    - Sahkan enam ujian `tests/Feature/Livewire/Laporan/ReportAllTest.php` sedia ada lulus tanpa diubah, bersama `tests/Feature/Laporan/ViewLaporanTest.php`, `SmokeTest`, `DashboardRoutingTest`, `ReportTotalsTest` dan `tests/Feature/Api/*`
    - `GET /laporan/keseluruhan` oleh pengguna tanpa kebenaran menghasilkan 403; `Livewire::test(ReportAll::class)` sebagai pengguna biasa membuang `AuthorizationException` daripada `mount()`
    - Draf (status 1) tidak muncul dalam jadual, kad, carta atau CSV
    - Penomboran kekal 20 per halaman dengan susunan `submitted_at` menurun
    - Sahkan `php artisan route:list` tidak berubah, iaitu tiada laluan eksport baharu
    - Sahkan `git diff` kosong bagi `composer.json`, `package.json`, `database/migrations/`, `routes/`, `resources/views/components/filter-field.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/livewire/dashboard/dashboard.blade.php`
    - Sahkan blok `:root` `app.css` tidak berubah dan satu-satunya pengisytiharan sedia ada yang diubah ialah warna `.app-sidebar__title`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.8, 9.9, 9.10, 9.11, 9.14, 9.15, 10.18, 11.11, 11.12_

  - [x] 13.2 Tulis ujian render halaman yang berkongsi sidebar, `x-filter-bar` dan CSS yang disentuh dalam `tests/Feature/Laporan/SharedLayoutSmokeTest.php`
    - Sahkan status 200, setiap satu dengan peranan yang mempunyai kebenaran yang betul, bagi halaman bukan laporan: `/dashboard`, `/dashboard/utama`, `/aduan`, `/aduan/untuk-tindakan`, `/aduan/lesson-learn`, `/kategori`, `/aras-risiko`, `/status`, `/pengguna`, `/peranan`, `/audit-trail`
    - Sahkan status 200 bagi kesemua tujuh skrin laporan lain seperti yang didaftarkan dalam `routes/web.php`: `/laporan`, `/laporan/mengikut-status-tindakan`, `/laporan/mengikut-kategori`, `/laporan/mengikut-tahap-risiko`, `/laporan/soal-selidik`, `/laporan/mengikut-bahagian`, `/laporan/mengikut-peranan` — perhatikan bahawa laluan rating ialah `/laporan/soal-selidik` dan laluan `mengikut-user` masih dikomen keluar, jadi kedua-duanya jangan diuji sebagai `/laporan/mengikut-rating` atau `/laporan/mengikut-pengguna`
    - Bagi kesembilan view pengguna `x-filter-bar` yang lain (`livewire/dashboard/dashboard`, `livewire/dashboard/officer-dashboard`, `livewire/dashboard/user-dashboard`, `livewire/aduan/report-index`, `livewire/aduan/lesson-learn`, `livewire/audit-trail/audit-trail-index`, `livewire/category/category-index`, `livewire/role/role-index`, `livewire/user/user-index`), sahkan butang "Set Semula" dirender tanpa atribut `disabled`, `wire:loading` atau `wire:target` tambahan
    - Sahkan markup sidebar pada halaman tersebut mengandungi set item menu, teks label dan nilai `href` yang sama seperti sebelum penambahbaikan, bagi setiap peranan
    - _Requirements: 8.13, 9.9, 9.10, 10.10, 10.11, 10.12, 10.14_

- [x] 14. Checkpoint akhir
  - Ensure all tests pass, ask the user if questions arise.

- [x] 15. Pembetulan kecacatan visual selepas semakan mockup
  - [x] 15.1 Tukar Kad_Statistik kepada Nada_Pastel dalam `resources/css/app.css` dan `resources/views/components/laporan/stat-tile.blade.php`
    - **Langkah pertama — pastikan perubahan boleh dibalikkan:** jalankan `grep -rn "status-tile" resources/views tests app` dan sahkan sama ada ada pengguna `.status-tile` atau `.status-tile.bg-*` di luar halaman ini. Semakan pada sesi ini mendapati hanya `resources/views/components/laporan/stat-tile.blade.php`, `resources/views/livewire/laporan/report-all.blade.php`, `tests/Feature/Laporan/LaporanRegressionTest.php` dan `tests/Feature/Laporan/LaporanEmptyStateTest.php` menggunakannya. Jika semakan semula mengesahkan ini, **utamakan kelas berskop baharu** (contoh `.stat-tile--tone-info`) dan biarkan blok `.status-tile.bg-*` sedia ada tidak disentuh, supaya perubahan boleh dibalikkan dengan membuang kelas baharu sahaja
    - **Punca kecacatan yang perlu dibetulkan:** `app.css` sudah menetapkan tint pastel melalui `--tile-bg`/`--tile-fg`/`--tile-bd` pada `.status-tile.bg-*`, tetapi markup membawa nama kelas utiliti Bootstrap (`bg-info`, `bg-primary`, `bg-warning`, `bg-success`, `bg-danger`, `bg-secondary`). Bootstrap 5.3.3 mengisytiharkan `background-color` utiliti tersebut dengan `!important` (dimuatkan pada baris 17 `resources/views/layouts/app.blade.php`), jadi isian penuh menang ke atas tint tanpa mengira susunan fail — itulah blok warna penuh yang kelihatan pada halaman langsung. Nama kelas nada mesti berhenti bertindan dengan utiliti Bootstrap
    - Tambah Token_Nada **baharu** pada blok `:root` (pasangan tint + nada tegas bagi setiap Hue_Status), rujuk melalui `var(--token)`; **jangan** ubah nilai mana-mana token sedia ada
    - Nada mesti berada dalam keluarga Palet_Carta (periwinkle, mentega, pastel sejuk) supaya kad tidak bercanggah dengan segmen Carta_Status
    - Kekalkan penerbitan nada daripada `_report_statuses.class`; sentuh `app/Livewire/Laporan/ReportAll.php` **hanya** jika penerbitan `tone` perlu dilaraskan kepada nama kelas baharu, dan jangan tambah pemetaan warna per-status dalam view
    - Kekalkan kad tidak boleh diklik: tiada `wire:click`, `href`, `<a>` atau `<button>` dalam kad
    - Kekalkan nama kelas `status-tile__value`, `status-tile__label`, `status-tile__head` dan `status-tile__icon` supaya `tests/Feature/Laporan/LaporanEmptyStateTest.php` (mengira `status-tile__value`) dan `tests/Feature/Laporan/LaporanRegressionTest.php` (mengesahkan kelas laporan hadir dalam `app.css`) kekal lulus; jika kelas baharu ditambah, kemas kini senarai kelas dalam `LaporanRegressionTest.php` tanpa melemahkan pengesahan sedia ada
    - **Laporkan nisbah kontras sebenar** yang diukur bagi bilangan dan label pada kesemua lapan kad, termasuk kad "Jumlah"; setiap pasangan mesti >=4.5:1
    - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.8, 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 8.1, 8.2, 8.14, 8.15, 9.9, 9.14_

  - [x] 15.2 Kemaskan Label_Paksi_Tarikh dan Legenda_Carta_Status dalam `resources/views/laporan/show_by_all.blade.php`
    - Dalam IIFE `@section('script')`, tukar `axisLabel` `renderTrend()` daripada `interval: labels.length > 31 ? 'auto' : 0` kepada pelangkauan automatik pada semua bilangan baldi (`interval: 'auto'`), dan tambah `formatter` yang memecahkan rentetan label pelayan kepada dua baris (`"01 Nov"` → `01` / `Nov`, `"Mac 2025"` → `Mac` / `2025`)
    - **Penemuan semasa tugasan ini:** Pustaka_Carta yang dibundalkan ialah ECharts **4.3.1** (`version:"4.3.1"` dalam `public/assets/libs/echarts/echarts.min.js`), bukan 5, jadi `hideOverlap: true` yang diarahkan oleh tugasan 9.3 diabaikan tanpa ralat dan tidak pernah berfungsi. `interval: 'auto'` ialah satu-satunya perlindungan, dan ia disahkan secara aritmetik jelas pada 15, 31 dan 62 baldi. Jangan masukkan semula pilihan khusus ECharts 5 — Requirement 9 kriteria 16
    - **Jangan** ubah format rentetan label yang dijana pelayan dalam `trendData()`: `tests/Feature/Livewire/Laporan/ReportAllTrendDataTest.php` mengesahkannya, dan Requirement 5 kriteria 16 menuntut pemecahan dua baris berlaku pada masa melukis
    - Dalam `renderDonut()`, tukar legenda daripada jalur mendatar bawah (`bottom: 0`) kepada senarai menegak di sebelah donat: `orient: 'vertical'`, kedudukan kanan, satu baris bagi setiap segmen, nama status kiri dan bilangan beserta peratusan dijajarkan kanan; laraskan `center`/`radius` pie supaya donat dan legenda tidak bertindih
    - Kekalkan `pct()` sebagai pembaca nilai peratus pelayan tanpa pengiraan semula di penyemak imbas
    - Kekalkan `setOption(..., true)` (notMerge) pada kedua-dua carta, pengadang `typeof echarts === 'undefined'`, `try/catch` di sekeliling `JSON.parse`, dan peraturan bahawa `showFallback` tidak melakukan apa-apa apabila elemen sasaran carta tiada
    - Sentuh `resources/views/livewire/laporan/report-all.blade.php` dan `resources/css/app.css` **hanya** jika legenda memerlukan bekas susun atur; jika ya, ketinggian tetap `.chart-box` mesti kekal diisytiharkan **sekali sahaja** dan nilainya tidak berubah
    - **Sahkan secara manual tiada pertindihan label** pada 15, 31 dan 62 baldi (contohnya julat 1–15 Nov, 1–31 Dis, dan julat 62 hari), serta pada pengumpulan bulanan
    - **Nota pelaksanaan:** legenda akhirnya dibina sebagai senarai HTML (`renderStatusLegend()` + `.chart-legend`/`.chart-split` dalam `app.css`), bukan sebagai `legend` ECharts `orient: 'vertical'` seperti bullet di atas, kerana legenda ECharts 4.3.1 tiada pemendekan teks per-token dan lajur berlebar tetap akan merosakkan tepi kanan yang dikongsi (Requirement 4 kriteria 11 dan 13)
    - _Requirements: 4.3, 4.4, 4.5, 4.7, 4.8, 4.9, 4.10, 4.11, 4.12, 4.13, 4.14, 5.5, 5.6, 5.12, 5.13, 5.14, 5.15, 5.16, 7.5, 8.4_

  - [x] 15.3 Selaraskan Legenda_Carta_Status dan nilai tengah donat dengan mockup
    - Empat perbezaan yang perlu dihapuskan, kesemuanya dikodkan dalam Requirement 4 yang telah dipinda; rujuk Keputusan Yang Telah Diputuskan 15 dalam `requirements.md`
    - **(a) Susunan baris legenda.** Susun baris legenda **menurun mengikut bilangan**, bilangan tertinggi di baris pertama, dengan seri dipecahkan **menaik mengikut `_report_statuses.id`** supaya susunan bagi satu Set_Penapis_Semasa adalah tunggal dan sama pada setiap render (kriteria 15)
    - **(b) Status berbilangan 0 hadir dalam legenda.** Legenda menyenaraikan tepat tujuh baris, satu bagi setiap status `ReportStatuses::COUNTED`, termasuk yang bilangannya 0 (contoh baris "Baru 0 (0.0%)"), manakala Cincin_Donat kekal **tanpa** segmen bagi status bilangan 0 (kriteria 1 dan 16)
    - **(c) Label tengah donat.** Tukar label `rich` daripada "Jumlah" kepada **"Jumlah Tiket"**, kekal dua baris dengan jumlah di atas label (kriteria 2)
    - **(d) Format peratusan.** Render peratusan baris legenda dalam tanda kurungan — `(38.5%)`, bukan `38.5%` — dengan nilai peratus kekal dikira di pelayan kepada satu tempat perpuluhan dan `pct()` kekal sebagai pembaca nilai pelayan tanpa pengiraan semula di penyemak imbas (kriteria 3 dan 20)
    - **Warna mesti dipisahkan daripada kedudukan, kalau tidak (a) menjadikan keadaan lebih buruk.** `renderStatusLegend()` kini memetakan warna melalui `colors[i % colors.length]`, iaitu kedudukan dalam senarai. Jika susunan ditukar tanpa pembetulan ini, warna satu status akan berubah setiap kali bilangannya berubah kedudukan. Terbitkan Warna_Status daripada **Indeks_Status** (kedudukan status dalam `ReportStatuses::COUNTED` menaik id, nilai 0–6, tidak bergantung kepada penapis), hantar indeks atau warna itu dalam payload, dan berikan warna kepada setiap segmen secara eksplisit (contohnya `itemStyle.color` pada setiap item data pie) dan bukan melalui tatasusunan `color` carta mengikut kedudukan segmen (kriteria 17, 18 dan 19)
    - **Payload `statusDonutData()` dalam `app/Livewire/Laporan/ReportAll.php`** mesti mengeluarkan kesemua tujuh status, bukan hanya yang bukan sifar. Dua bentuk yang dibenarkan: (i) kekalkan satu senarai dan tambah bendera boolean `plotted` beserta indeks warna pada setiap entri, atau (ii) tambah kunci `legend` yang berasingan di sebelah `segments`. Dalam kedua-dua bentuk, Cincin_Donat kekal **hanya** memakan entri bukan sifar dalam susunan menaik id, dan `percent` kekal `round($value / $total * 100, 1)` di pelayan
    - **`total === 0` kekal memulangkan senarai kosong**: keadaan kosong "Tiada data untuk tempoh ini" dirender tanpa segmen, tanpa nilai tengah dan tanpa legenda (kriteria 6), jadi kriteria 16 (tujuh baris) hanya terpakai apabila jumlah >= 1
    - Fail: `app/Livewire/Laporan/ReportAll.php` (payload `statusDonutData()`), `resources/views/laporan/show_by_all.blade.php` (pembina DOM `renderStatusLegend()` dan formatter `rich` tengah donat), dan `resources/css/app.css` **hanya** jika grid `.chart-legend` perlu dilaraskan untuk lajur peratusan berkurungan
    - **Mesti dikekalkan:** susunan menaik id Cincin_Donat; warna setiap status stabil merentas perubahan penapis; peratusan dikira di pelayan; ketinggian `.chart-box` diisytiharkan **tepat sekali** dan nilainya tidak berubah; `setOption(..., true)` (notMerge) pada kedua-dua carta; pengadang `typeof echarts === 'undefined'`; `try/catch` di sekeliling `JSON.parse`; dan peraturan bahawa `showFallback` tidak melakukan apa-apa apabila elemen sasaran carta tiada
    - **Kemas kini ujian payload yang sedia ada tanpa melemahkannya** (Requirement 9 kriteria 10): `tests/Feature/Livewire/Laporan/ReportAllDonutDataTest.php` kini mengesahkan `segments` ialah **tepat** status bukan sifar dalam susunan menaik id (`test_segments_are_exactly_the_statuses_with_aduan_in_ascending_id_order`, termasuk `assertNotContains(0, ...)` dan senarai label `['Baru', 'Selesai', 'KIV']`). Pengesahan tersebut kekal **betul bagi `segments`** dan tidak boleh dibuang; tambah pengesahan baharu bagi senarai legenda: tujuh entri, susunan menurun bilangan dengan seri menaik id, entri sifar hadir, indeks/warna per-status stabil apabila bilangan berubah, dan `percent` 0.0 bagi entri sifar
    - Kekalkan `tests/Feature/Laporan/LaporanEmptyStateTest.php`, `tests/Feature/Laporan/LaporanLoadingStateTest.php` dan `tests/Feature/Laporan/LaporanRegressionTest.php` lulus; jika kelas CSS baharu ditambah, kemas kini senarai kelas dalam `LaporanRegressionTest.php` tanpa melemahkan pengesahan sedia ada
    - Kemas kini juga tandatangan `statusDonutData()` dalam `design.md` seksyen 1.4 dan contoh payload dalam seksyen "Bentuk data paparan" supaya kedua-duanya memaparkan bentuk payload yang akhirnya dipilih (bendera `plotted` atau kunci `legend`), dan rujuk **Property 28** dalam `design.md` sebagai properti yang mengawal legenda
    - **Selaraskan dua petikan `design.md` yang kini salah:** seksyen 4.1 masih memaparkan `legend: { bottom: 0, ... }` ECharts dan menerangkan legenda sebagai jalur di bawah carta dengan label tengah "Jumlah" — gantikan dengan senarai HTML di sebelah cincin, susunan menurun bilangan, tujuh baris, warna per-Indeks_Status dan label "Jumlah Tiket"; seksyen 5 masih menyatakan Kad_Statistik "menggunakan semula" nada `.status-tile.bg-*` sedia ada, sedangkan tugasan 15.1 telah berpindah kepada kelas nada berskop baharu kerana utiliti `bg-*` Bootstrap menang dengan `!important` — gantikan dengan kelas yang sebenarnya digunakan, dan tambah `.chart-split`, `.chart-legend`, `.chart-legend__dot`, `.chart-legend__name`, `.chart-legend__value` dan `.chart-legend__pct` ke dalam jadual kelas CSS
    - **Sahkan secara manual** pada lebar 992px, 1200px dan 1920px: tujuh baris legenda muat tanpa melimpah keluar bekas kad, tepi kanan lajur bilangan dan lajur peratusan kekal sejajar, dan titik warna setiap baris sepadan dengan segmen status yang sama pada cincin
    - _Requirements: 4.1, 4.2, 4.3, 4.6, 4.7, 4.10, 4.11, 4.12, 4.13, 4.14, 4.15, 4.16, 4.17, 4.18, 4.19, 4.20, 8.2, 8.4, 9.6, 9.8, 9.10, 9.16_

- [x] 16. Ujian tertunda yang kini diwajibkan (jalankan selepas tugasan 15 mendarat)
  - [x] 16.1 Tulis ujian properti paparan dalam `tests/Feature/Livewire/Laporan/ReportAllTablePropertiesTest.php`
    - Dahulunya tugasan 9.5 bertanda pilihan; kini diwajibkan dan dipindahkan ke sini supaya ia mengesahkan markup akhir
    - **Property 5: Pemegang data JSON ialah round-trip data carta**
    - **Property 11: Perubahan penapis mengemas kini kesemua paparan dan kembali ke halaman pertama**
    - **Property 13: Warna status mempunyai satu sumber tunggal**
    - **Property 17: Penomboran dan susunan Jadual_Aduan**
    - **Property 18: Setiap baris jadual lengkap**
    - Liputan jadual: sepuluh lajur dalam susunan, berat fon nombor tiket, badge status daripada `_report_statuses.class` dan badge risiko daripada `_risk_levels.class`, Subjek dipendekkan 40 aksara dengan teks penuh sebagai tooltip, penanda tiada nilai `.cell-muted`, Bil. berturutan merentas halaman, penomboran 20 per halaman dan susunan `submitted_at` menurun
    - Tambah ujian contoh: markup `.chart-fallback` hadir dalam HTML, dan keadaan kosong carta dirender apabila jumlah sifar
    - **Validates: Requirements 2.5, 3.6, 4.5, 4.9, 5.10, 5.12, 6.1, 6.2, 6.3, 6.5, 6.6, 6.7, 6.10, 6.11, 6.12, 6.13**

  - [x] 16.2 Tulis ujian keadaan memuat dan kosong dalam `tests/Feature/Laporan/LaporanLoadingStateTest.php`
    - Dahulunya tugasan 12.3 bertanda pilihan; kini diwajibkan dan dipindahkan ke sini supaya ia mengesahkan markup akhir
    - Sahkan ketiga-tiga tabir `wire:loading` hadir pada Kad_Statistik, baris carta dan kad Jadual_Aduan, dengan nilai `wire:target` yang dikongsi dan sama bagi ketiga-tiganya
    - Sahkan butang "Set Semula" dan butang "Eksport CSV" membawa `wire:loading.attr="disabled"`
    - Sahkan mesej keadaan kosong dirender bagi jumlah sifar, julat terbalik dan id penapis yang tidak wujud
    - **Validates: Requirements 7.1, 7.6, 9.12, 9.13, 11.13**

- [x] 17. Checkpoint — pembetulan visual dan ujian tertunda siap
  - Ensure all tests pass, ask the user if questions arise.

## Di Luar Skop Spesifikasi Ini — Pepijat Bersebelahan Yang Diluluskan

Kumpulan 18 **bukan** bahagian spesifikasi `laporan-keseluruhan-ui-refresh`. Ia menyentuh
`app/Http/Controllers/LaporanController.php`, iaitu fail yang dilarang oleh Requirement 9
kriteria 8, dan ia memperbaiki halaman laporan lain yang disenaraikan dalam "Out of Scope".
Ia dijejaki di sini semata-mata untuk kemudahan kerana pengguna meluluskan pembetulan tersebut
semasa semakan spec ini. Requirement 9 kriteria 8 dan kriteria 10 kekal terpakai kepada
kumpulan 1 hingga 17: pengesahan `git diff` dalam `tests/Feature/Laporan/LaporanRegressionTest.php`
tidak boleh dilemahkan untuk menampung kumpulan 18.

- [x] 18. Pertanyaan `viewByDepartment` yang mudah alih
  - [x] 18.1 Tulis semula pertanyaan agregat bahagian dalam `app/Http/Controllers/LaporanController.php`
    - `viewByDepartment()` (sekitar baris 338-344) membina `selectRaw('distinct(ldap_bahagian) as bahagian, ( select count(id) from reports where bahagian_pengadu = bahagian and report_status_id <> 1 ) as jumlah')`. Merujuk alias senarai select (`bahagian`) di dalam WHERE subkueri ialah sambungan MySQL; SQLite menolaknya, jadi `GET /laporan/mengikut-bahagian` menghasilkan 500 di bawah sambungan ujian
    - Tulis semula secara mudah alih: ulang ungkapan `users.ldap_bahagian` di dalam subkueri, atau susun semula sebagai agregat berkumpulan / `leftJoin` ke atas `reports`
    - Kekalkan kontrak hasil yang sama seperti yang digunakan oleh `resources/views/laporan/show_by_department.blade.php`: satu baris per bahagian dengan medan `bahagian` dan `jumlah`, disusun menaik mengikut `bahagian`, penapis `department` pilihan dikekalkan, dan Draf (`report_status_id = 1`) dikecualikan
    - **Betulkan juga `laporanByDepartmentApi()` (sekitar baris 548)** yang membawa pertanyaan yang sama perkataan demi perkataan; kedua-dua kaedah hendaklah berkongsi satu pembina pertanyaan supaya ia tidak boleh menyimpang lagi
    - Tiada perubahan skema, laluan atau kebenaran; tandatangan kaedah dan struktur respons API dikekalkan
    - _Nota: pepijat di luar skop spec ini; rujuk bahagian "Di Luar Skop Spesifikasi Ini" di atas_

  - [x] 18.2 Tulis ujian ciri bahagian dalam `tests/Feature/Laporan/LaporanByDepartmentTest.php`
    - Sahkan `GET /laporan/mengikut-bahagian` menghasilkan 200 di bawah pemacu SQLite yang dikonfigurasi oleh `phpunit.xml`
    - Sahkan bilangan per-bahagian betul: semai aduan merentas beberapa `bahagian_pengadu` termasuk aduan Draf, kemudian sahkan `jumlah` setiap baris sama dengan bilangan aduan bukan Draf bahagian tersebut, dan bahagian tanpa aduan bukan Draf memaparkan 0
    - Sahkan penapis `department` menghadkan hasil kepada satu bahagian sahaja
    - Sahkan `GET` endpoint API bahagian menghasilkan struktur respons yang sama seperti sebelum penulisan semula
    - _Nota: pepijat di luar skop spec ini_

  - [x] 18.3 Buang kes khas MySQL dalam `tests/Feature/Laporan/SharedLayoutSmokeTest.php`
    - Ringkaskan `test_the_department_report_is_unaffected_apart_from_its_pre_existing_mysql_only_query` kepada pengesahan 200 biasa (atau buangnya dan biarkan laluan diuji oleh gelung biasa), buang pemalar `DEPARTMENT_REPORT` beserta kedua-dua panggilan `array_diff`, dan pulangkan `/laporan/mengikut-bahagian` kepada gelung penanda aktif sidebar
    - Kemas kini pengesahan `assertCount(6, $paths)` kepada tujuh laluan laporan
    - Jangan lemahkan mana-mana pengesahan lain dalam fail ini
    - _Nota: pepijat di luar skop spec ini; bergantung kepada tugasan 18.1_

- [x] 19. Checkpoint — pembetulan di luar skop siap
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tugasan dengan `*` ialah ujian properti dan ujian contoh tambahan yang boleh dilangkau untuk MVP yang lebih pantas. Tugasan 4.6, 13.1, 13.2, 16.1 dan 16.2 **tidak** ditanda pilihan: 16.1 dan 16.2 ialah dua ujian yang dahulunya pilihan dan kini diwajibkan oleh pengguna, 4.6 ialah satu-satunya perlindungan automatik bagi cabang MySQL `dateBucketExpr()`, manakala 13.1 dan 13.2 menguatkuasakan Requirement 9 kriteria 10 (suite sedia ada kekal lulus) dan Requirement 8 kriteria 13 (halaman pengguna `x-filter-bar` yang lain tidak terjejas).
- Properti dilaksanakan sebagai kaedah PHPUnit dengan gelung >=100 lelaran dan benih `fake()` tetap, bukan pustaka PBT, kerana Requirement 9 kriteria 11 dan Requirement 11 kriteria 19 melarang entri `composer.json` baharu. Setiap kaedah ditag dengan komen `// Feature: laporan-keseluruhan-ui-refresh, Property {n}: {teks}`.
- Setiap tugasan merujuk nombor kriteria penerimaan yang khusus untuk kebolehjejakan.
- Fail yang dibenarkan diubah ditetapkan oleh Requirement 9 kriteria 8. `resources/views/components/filter-field.blade.php`, `resources/views/dashboard.blade.php` dan `resources/views/livewire/dashboard/dashboard.blade.php` **tidak** boleh disentuh; itulah sebabnya tugasan 9.3 menyalin pembantu chrome carta daripada dashboard.
- Semakan yang tidak boleh diautomasikan dalam PHPUnit — susun atur responsif pada 375/575/768/992/1200/1920px, nisbah kontras, kelakuan tooltip dan pelukisan semula ECharts, degradasi dengan ECharts dinyahaktifkan, tempoh penunjuk memuat 300ms, pertindihan label paksi, dan sasaran sentuh 40px — disenaraikan dalam bahagian "Semakan yang tidak diautomasikan" `design.md` dan disemak oleh pelaksana semasa mengerjakan tugasan 1.1, 7.2, 9.2, 9.3, 12.1, 15.1 dan 15.2.
- **Tugasan 15 hingga 17 ditambah selepas semakan halaman langsung terhadap mockup.** Tugasan 15.1 membetulkan warna Kad_Statistik (Requirement 3 kriteria 14-19) dan tugasan 15.2 membetulkan kekemasan Label_Paksi_Tarikh serta Legenda_Carta_Status (Requirement 4 kriteria 10-14, Requirement 5 kriteria 13-16). Rujuk Keputusan Yang Telah Diputuskan 10 hingga 13 dalam `requirements.md`.
- **Tugasan 15.3 ditambah selepas semakan kedua Legenda_Carta_Status terhadap mockup.** Tugasan 15.2 dengan sengaja meninggalkan empat perkara berbeza daripada mockup kerana teks kriteria ketika itu melarang perubahan tersebut. Requirement 4 kini dipinda supaya mockup menang: kriteria 1 dihadkan kepada Cincin_Donat, kriteria 2 menetapkan "Jumlah Tiket", kriteria 3 dan 11 tidak lagi mengikat legenda kepada susunan segmen, dan kriteria 15 hingga 20 baharu menetapkan susunan menurun bilangan, tujuh baris termasuk sifar, Warna_Status per-Indeks_Status yang stabil, dan peratusan berkurungan. Rujuk Keputusan Yang Telah Diputuskan 15 dalam `requirements.md`.
- **Pustaka_Carta ialah ECharts 4.3.1, bukan 5** (`version:"4.3.1"` dalam `public/assets/libs/echarts/echarts.min.js`), ditemui semasa tugasan 15.2. Oleh itu `axisLabel.hideOverlap` yang diarahkan oleh tugasan 9.3 tidak pernah berfungsi; `interval: 'auto'` ialah satu-satunya perlindungan pertindihan Label_Paksi_Tarikh dan ia disahkan secara aritmetik jelas pada 15, 31 dan 62 baldi. Requirement 9 kriteria 16 melarang pilihan khusus ECharts 5 supaya perlindungan yang tidak berfungsi tidak dimasukkan semula; rujuk `design.md` seksyen 4.3 dan Keputusan Yang Telah Diputuskan 14.
- **Dua ujian tertunda kini diwajibkan dan dipindahkan.** Tugasan 9.5 menjadi tugasan 16.1 dan tugasan 12.3 menjadi tugasan 16.2; kedua-duanya tidak lagi bertanda `*` dan dijadualkan **selepas** tugasan 15 supaya ia mengesahkan markup akhir, bukan markup yang akan diubah. Entri asal 9.5 dan 12.3 digantikan dengan nota penunjuk dalam kumpulan 9 dan 12, dan dibuang daripada Task Dependency Graph supaya tiada tugasan berganda menulis fail ujian yang sama.
- **Tugasan 2.3 dan 3.3 kekal pilihan dan kekal tidak dilaksanakan** mengikut keputusan pengguna untuk melangkaunya. Kedua-duanya masih tersenarai dan masih berada dalam Task Dependency Graph (wave 2), jadi ia boleh dilaksanakan kemudian tanpa perancangan semula.
- **Kumpulan 18 berada di luar skop spesifikasi ini** dan dijejaki di sini untuk kemudahan sahaja; rujuk bahagian "Di Luar Skop Spesifikasi Ini" di atas. Ia menyentuh `app/Http/Controllers/LaporanController.php`, iaitu fail yang dilarang oleh Requirement 9 kriteria 8, jadi ia **tidak** boleh digabungkan dengan mana-mana tugasan kumpulan 1 hingga 17 dalam satu commit yang mengaku mematuhi Requirement 9.
- Corak pertanyaan mudah alih yang sama perlu disemak pada kaedah adik-beradik: `laporanByDepartmentApi()` membawa pertanyaan alias-dalam-subkueri yang **serupa perkataan demi perkataan** (dibetulkan oleh tugasan 18.1). Selain itu, `viewByStatus()`, `viewByCategory()`, `viewByRole()`, `laporanByStatusApi()`, `laporanByCategoryApi()`, `laporanByRiskApi()` dan `laporanByRoleyApi()` menggunakan fungsi MySQL `if(?, ..., true)` dalam `selectRaw` — itu sambungan MySQL yang **berbeza** dan bukan pepijat yang sama, jadi ia **tidak** termasuk dalam tugasan 18 dan dilaporkan di sini sahaja sebagai hutang teknikal yang diketahui.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1", "3.1"] },
    { "id": 1, "tasks": ["1.2", "2.2", "3.2"] },
    { "id": 2, "tasks": ["2.3", "3.3", "4.1", "7.1"] },
    { "id": 3, "tasks": ["4.2", "7.2", "8.1"] },
    { "id": 4, "tasks": ["4.3", "6.1", "8.3"] },
    { "id": 5, "tasks": ["4.4", "6.2"] },
    { "id": 6, "tasks": ["4.5", "4.6", "8.2"] },
    { "id": 7, "tasks": ["6.3", "9.1", "11.1"] },
    { "id": 8, "tasks": ["9.2", "11.2"] },
    { "id": 9, "tasks": ["9.3", "12.1"] },
    { "id": 10, "tasks": ["9.4", "11.3"] },
    { "id": 11, "tasks": ["12.2"] },
    { "id": 12, "tasks": ["13.1"] },
    { "id": 13, "tasks": ["13.2"] },
    { "id": 14, "tasks": ["15.1", "18.1"] },
    { "id": 15, "tasks": ["15.2", "18.2"] },
    { "id": 16, "tasks": ["15.3", "18.3"] },
    { "id": 17, "tasks": ["16.1", "16.2"] }
  ]
}
```
