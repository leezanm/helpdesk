# Requirements Document

## Introduction

Spesifikasi ini merangkumi penambahbaikan UI/UX bagi halaman **Laporan Keseluruhan** dalam
Sistem Meja Bantuan KKDW. Konsep reka bentuk ialah *Modern Government Enterprise Dashboard*:
kemas, profesional, minimalis tetapi mempunyai hierarki visual yang jelas, selaras dengan
identiti visual sistem yang telah ditetapkan dalam spec `.kiro/specs/ui-refresh`
("Periwinkle Butter", token CSS dalam `resources/css/app.css`).

Skop teknikal dikekalkan **UI/UX sahaja**, dengan **satu pengecualian yang diluluskan oleh
pengguna**: fungsi eksport (Requirement 11) memerlukan kod pelayan baharu. Halaman ini dipacu
oleh komponen Livewire `App\Livewire\Laporan\ReportAll`
(view `resources/views/livewire/laporan/report-all.blade.php`, pembalut halaman
`resources/views/laporan/show_by_all.blade.php`). Semua logik perniagaan, skema pangkalan data,
endpoint API dan kebenaran akses yang lain dikekalkan tanpa perubahan.

Skop juga merangkumi penambahbaikan **hierarki visual sidebar** (Requirement 10). Sidebar
dikongsi oleh setiap halaman sistem, jadi skop sidebar dihadkan kepada struktur dan hierarki
visual sahaja: **palet warna sidebar dan nilai token Tema_Sistem dikekalkan tanpa perubahan**.

Keadaan sedia ada yang telah disemak dan menjadi asas spesifikasi ini:

- Penapis sedia ada: Dari Tarikh, Hingga Tarikh, Bahagian, Status Tindakan, Pegawai
  Bertanggungjawab, Tahap Risiko, Rating / Maklum Balas — semuanya reaktif
  (`wire:model.live`) dan disimpan dalam URL melalui atribut `#[Url]`.
- Jalur ringkasan status sedia ada menjana jubin secara dinamik daripada
  `App\Support\ReportStatuses::COUNTED` (7 status: Baru, Aduan Disemak, Dalam Tindakan,
  Selesai, KIV, Selesai Tindakan, Tindakan Lanjut Diperlukan) ditambah satu jubin Jumlah.
- Jadual aduan menggunakan penomboran Livewire 20 rekod per halaman. **Tiada** fungsi
  susunan lajur (sorting) dan **tiada** fungsi eksport pada halaman ini.
- Pustaka carta yang telah tersedia dan digunakan oleh dashboard ialah **Apache ECharts
  versi 4.3.1** (`public/assets/libs/echarts/echarts.min.js`, rentetan `version:"4.3.1"` dalam
  fail tersebut, dimuatkan dalam `<head>` `resources/views/layouts/app.blade.php`). ApexCharts
  turut dimuatkan dalam `layouts/script.blade.php` tetapi hanya digunakan oleh skrin lama. Tiada
  dependency baharu diperlukan. Versi 4.3.1 ialah kekangan sebenar: pilihan yang hanya wujud
  dalam ECharts 5 — termasuk `axisLabel.hideOverlap`, `series.labelLayout` dan pemendekan teks
  per-token dalam legenda — diabaikan tanpa ralat oleh versi 4.3.1, jadi pilihan tersebut tidak
  boleh dijadikan perlindungan bagi mana-mana kriteria penerimaan. Rujuk Keputusan Yang Telah
  Diputuskan 14.
- Corak carta reaktif yang telah terbukti: komponen Livewire menulis JSON ke dalam satu
  pemegang data tersembunyi dalam view, dan skrip carta dalam `@section('script')` halaman
  membaca pemegang data tersebut serta melukis semula pada setiap render (rujuk
  `resources/views/dashboard.blade.php` dan `livewire/dashboard/dashboard.blade.php`).
- Sidebar sedia ada ialah `resources/views/layouts/navbar.blade.php` yang disertakan oleh
  `resources/views/layouts/app.blade.php` di dalam `<aside class="app-sidebar">`, bersama blok
  jenama `.app-sidebar__brand` (`images/logo.png` + teks "KKDW Helpdesk"). Gaya sidebar berada
  dalam `resources/css/app.css` (`.app-sidebar`, `.app-sidebar__brand`, `.app-sidebar__nav`,
  `.app-sidebar__title`, `.app-nav-link`, `.app-nav-link.active`, `.app-nav-sub`). Latar sidebar
  **putih** (`--sidebar-bg: #ffffff`), teks `--sidebar-fg`, item aktif menggunakan latar
  `--c-primary-050`, teks `--sidebar-fg-active` dan rel kiri 3px berwarna `--c-butter` melalui
  `.app-nav-link.active::before`. Kumpulan submenu dibuka/ditutup oleh Alpine
  (`x-data="{ o: ... }"`, `x-show`, `x-collapse`) dan item aktif ditentukan daripada
  `request()->path()` melalui `str_starts_with`/`str_contains`.
- **Tiada** pakej eksport sisi pelayan dalam projek: `composer.json` tiada
  `maatwebsite/excel`, `barryvdh/laravel-dompdf` atau pustaka Excel/PDF lain
  (`barryvdh/laravel-debugbar` ialah pakej debug, bukan eksport), dan `package.json` hanya
  mengandungi Vite dan Axios. Satu-satunya corak muat turun sedia ada ialah
  `response()->download()` dalam `App\Http\Controllers\BackupController`. DataTables Buttons,
  JSZip dan pdfmake dimuatkan dalam `resources/views/layouts/script.blade.php`, tetapi
  pustaka tersebut hanya mengeksport baris yang ada dalam DOM DataTable, sedangkan
  Jadual_Aduan ialah jadual Livewire bernombor, jadi pustaka tersebut tidak dapat mengeksport
  keseluruhan set hasil yang ditapis.

## Glossary

- **Laporan_Keseluruhan**: Halaman laporan gabungan pada laluan `laporan.view-by-all`, dirender oleh komponen Livewire `App\Livewire\Laporan\ReportAll`.
- **Pengguna_Pengurusan**: Pengguna yang lulus pemeriksaan kebenaran `access-main-dashboard`, iaitu satu-satunya kumpulan yang boleh mengakses Laporan_Keseluruhan.
- **Kepala_Halaman**: Blok tajuk di bahagian atas Laporan_Keseluruhan yang memuatkan tajuk, subtitle, breadcrumb dan ringkasan tempoh.
- **Penapis_Laporan**: Kad penapis yang memuatkan tujuh kawalan penapis sedia ada bagi Laporan_Keseluruhan.
- **Set_Penapis_Semasa**: Gabungan nilai tujuh penapis yang sedang aktif pada Penapis_Laporan.
- **Kad_Statistik**: Set lapan kad ringkasan (tujuh status dalam `ReportStatuses::COUNTED` serta satu kad Jumlah) pada Laporan_Keseluruhan.
- **Nada_Pastel**: Rawatan warna Kad_Statistik yang dituntut oleh mockup, iaitu empat lapisan dalam satu hue yang sama: isian latar sebagai tint cair berkepekatan rendah, label status dalam nada gelap yang lembut, bilangan dalam nada gelap yang tegas, dan sempadan garis halus 1px dalam nada pertengahan hue tersebut, ditambah satu ikon kecil berwarna. Nada_Pastel ialah teks gelap atas latar cair, bertentangan dengan teks putih atas isian penuh.
- **Hue_Status**: Keluarga warna satu status, diterbitkan daripada token `bg-*` dalam `_report_statuses.class` status berkenaan, iaitu satu-satunya sumber warna per-status bagi Kad_Statistik dan badge status Jadual_Aduan.
- **Token_Nada**: Pasangan token CSS baharu dalam blok `:root` `resources/css/app.css` yang menakrifkan nilai tint dan nilai nada tegas bagi setiap Hue_Status. Token_Nada ialah token **baharu**; ia tidak menggantikan atau mengubah nilai mana-mana token sedia ada.
- **Carta_Status**: Carta donat yang memaparkan agihan aduan mengikut Status Tindakan bagi Set_Penapis_Semasa.
- **Cincin_Donat**: Gelang segmen Carta_Status yang dilukis oleh Pustaka_Carta, iaitu bahagian grafik Carta_Status tanpa Legenda_Carta_Status dan tanpa nilai tengah donat.
- **Legenda_Carta_Status**: Senarai legenda Carta_Status yang menyenaraikan status Aduan_Dikira beserta titik warna, nama status, bilangan dan peratusan, dirender sebagai senarai HTML di luar kanvas Pustaka_Carta.
- **Indeks_Status**: Kedudukan satu status dalam senarai `App\Support\ReportStatuses::COUNTED` yang disusun menaik mengikut `_report_statuses.id`, iaitu nilai 0 hingga 6 yang tidak bergantung kepada Set_Penapis_Semasa.
- **Warna_Status**: Warna satu status dalam Carta_Status, iaitu warna Palet_Carta pada Indeks_Status status tersebut, digunakan oleh segmen Cincin_Donat status tersebut dan oleh titik warna baris Legenda_Carta_Status status tersebut.
- **Carta_Tren**: Carta bar yang memaparkan bilangan aduan mengikut tarikh bagi Set_Penapis_Semasa.
- **Label_Paksi_Tarikh**: Label paksi mendatar Carta_Tren bagi satu baldi masa, iaitu paparan rentetan label yang dijana oleh pelayan seperti dalam Requirement 5 kriteria 5.
- **Palet_Carta**: Senarai warna segmen Carta_Status yang disalin daripada `resources/views/dashboard.blade.php`, iaitu keluarga periwinkle, mentega dan pastel sejuk yang digunakan oleh carta seluruh sistem.
- **Jadual_Aduan**: Jadual senarai aduan bernombor (paginated) pada Laporan_Keseluruhan.
- **Tema_Sistem**: Token CSS reka bentuk dalam `resources/css/app.css` (contoh `--c-primary`, `--c-bg`, `--c-success`, `--c-warning`, `--c-danger`, `--c-text`, `--c-text-muted`) yang mengawal identiti visual seluruh sistem.
- **Pustaka_Carta**: Apache ECharts versi **4.3.1**, pustaka carta yang telah tersedia dalam projek (`public/assets/libs/echarts/echarts.min.js`) dan dimuatkan secara global oleh `resources/views/layouts/app.blade.php`. Pilihan konfigurasi yang diperkenalkan dalam ECharts 5 tidak wujud dalam versi 4.3.1 dan diabaikan tanpa ralat oleh versi tersebut.
- **Aduan_Dikira**: Aduan yang `report_status_id` berada dalam `App\Support\ReportStatuses::COUNTED`, iaitu semua aduan yang telah dihantar (Draf dikecualikan).
- **Julat_Tarikh_Tidak_Sah**: Keadaan Set_Penapis_Semasa apabila nilai Dari Tarikh lebih lewat daripada nilai Hingga Tarikh.
- **Sidebar_Sistem**: Navigasi sisi bersama yang dirender oleh `resources/views/layouts/navbar.blade.php` di dalam elemen `<aside class="app-sidebar">` bagi `resources/views/layouts/app.blade.php`, bersama blok jenama `.app-sidebar__brand`, dan digunakan oleh setiap halaman yang mewarisi layout `layouts.app`.
- **Item_Menu_Utama**: Pautan `.app-nav-link` yang berada terus di dalam `.app-sidebar__nav`, termasuk pencetus kumpulan submenu yang mempunyai ikon Boxicons dan ikon chevron.
- **Item_Submenu**: Pautan `.app-nav-link` yang berada di dalam senarai `.app-nav-sub` bagi satu Item_Menu_Utama.
- **Penanda_Aktif**: Konvensyen keadaan aktif sedia ada bagi Sidebar_Sistem, iaitu kelas `active` pada `.app-nav-link` yang menghasilkan latar `--c-primary-050`, teks `--sidebar-fg-active` dan rel kiri 3px berwarna `--c-butter` melalui `.app-nav-link.active::before`.
- **Eksport_CSV**: Tindakan muat turun fail CSV bagi set hasil Laporan_Keseluruhan yang sedang ditapis, dicetuskan oleh butang "Eksport CSV" pada kepala Penapis_Laporan.
- **Fail_Eksport**: Fail CSV yang dihasilkan oleh Eksport_CSV.

## Requirements

### Requirement 1: Kepala halaman yang kemas dan bermaklumat

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu kepala halaman yang kemas dan ringkas, supaya saya segera tahu laporan yang dipaparkan dan tempoh yang terlibat tanpa ruang kosong yang membazir.

#### Acceptance Criteria

1. WHEN Laporan_Keseluruhan dimuatkan, THE Kepala_Halaman SHALL memaparkan tajuk dengan teks tepat "Laporan Keseluruhan" sebagai satu-satunya tajuk utama halaman.
2. WHEN Laporan_Keseluruhan dimuatkan, THE Kepala_Halaman SHALL memaparkan subtitle dengan teks tepat "Ringkasan dan analisis keseluruhan aduan/helpdesk dalam sistem." pada saiz fon yang lebih kecil daripada saiz fon tajuk.
3. WHEN Laporan_Keseluruhan dimuatkan, THE Kepala_Halaman SHALL memaparkan breadcrumb dengan tepat tiga aras mengikut susunan "Laman Utama", "Laporan" dan "Keseluruhan", dengan aras "Laman Utama" sebagai pautan ke laluan `dashboard.index` dan aras terakhir "Keseluruhan" dipaparkan sebagai teks bukan pautan.
4. WHEN Laporan_Keseluruhan dirender, termasuk setiap render semula selepas Set_Penapis_Semasa berubah, THE Kepala_Halaman SHALL memaparkan ringkasan tempoh yang mengandungi nilai Dari Tarikh dan Hingga Tarikh Set_Penapis_Semasa dalam format tarikh panjang Bahasa Melayu yang dijana oleh pembantu sedia ada `convertDateFormat($tarikh, 'LL')` (contoh susunan "1 Januari 2026"), dengan kedua-dua tarikh dipisahkan oleh perkataan "hingga".
5. IF penapis Bahagian mempunyai nilai, THEN THE Kepala_Halaman SHALL memaparkan nama Bahagian tersebut dalam ringkasan tempoh yang sama, dan SHALL memendekkan nama Bahagian yang melebihi 60 aksara kepada 60 aksara diikuti penanda pemendekan.
6. THE Kepala_Halaman SHALL memaparkan teks "Laporan Keseluruhan" tepat satu kali dalam kandungan Laporan_Keseluruhan, dan jarak menegak antara bahagian bawah Kepala_Halaman dengan bahagian atas Penapis_Laporan SHALL tidak melebihi 24px pada lebar viewport 992px dan ke atas.
7. IF penapis Bahagian tiada nilai, THEN THE Kepala_Halaman SHALL memaparkan ringkasan tempoh dengan penanda liputan "Semua Bahagian" bersama julat tarikh.
8. IF Dari Tarikh atau Hingga Tarikh dalam Set_Penapis_Semasa kosong, THEN THE Kepala_Halaman SHALL memaparkan ringkasan tempoh menggunakan nilai lalai yang ditetapkan oleh `mount()` komponen `ReportAll`, iaitu hari pertama bulan semasa bagi Dari Tarikh dan tarikh hari ini bagi Hingga Tarikh, tanpa memaparkan ringkasan tempoh yang kosong.
9. WHILE Set_Penapis_Semasa berada dalam keadaan Julat_Tarikh_Tidak_Sah, THE Kepala_Halaman SHALL memaparkan ringkasan tempoh bersama mesej amaran Bahasa Melayu yang menyatakan julat tarikh tidak sah, SHALL mengekalkan nilai kedua-dua tarikh yang dimasukkan tanpa mengubahnya, dan Laporan_Keseluruhan SHALL diteruskan render seperti dalam Requirement 9 kriteria 12.

### Requirement 2: Kad penapis yang tersusun dengan fungsi dikekalkan

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu semua penapis tersusun dalam satu kad yang seragam, supaya saya boleh menapis laporan dengan cepat dan yakin.

#### Acceptance Criteria

1. WHEN Laporan_Keseluruhan dimuatkan tanpa parameter penapis dalam URL, THE Penapis_Laporan SHALL memaparkan tujuh kawalan penapis mengikut susunan Dari Tarikh, Hingga Tarikh, Bahagian, Status Tindakan, Pegawai Bertanggungjawab, Tahap Risiko dan Rating / Maklum Balas, dengan Dari Tarikh ditetapkan kepada hari pertama bulan semasa, Hingga Tarikh ditetapkan kepada tarikh hari ini, dan lima kawalan yang lain berada pada pilihan lalai bernilai kosong masing-masing, iaitu "Semua Bahagian", "Semua Status", "Semua Pegawai", "Semua Tahap Risiko" dan "Semua Rating".
2. THE Penapis_Laporan SHALL memaparkan label teks setiap kawalan penapis di atas kawalan tersebut, kekal kelihatan sepenuhnya tanpa pemotongan teks pada semua lebar viewport dari 375px hingga 1920px, dan tidak bergantung kepada teks placeholder atau tooltip untuk mengenal pasti kawalan.
3. THE Penapis_Laporan SHALL menyusun tujuh kawalan penapis dalam grid Bootstrap dengan nilai gutter mendatar dan menegak yang sama bagi semua kawalan, ketinggian setiap kawalan input yang sama, dan tepi bawah semua kawalan pada baris grid yang sama sejajar pada paksi mendatar yang sama.
4. THE Penapis_Laporan SHALL memaparkan setiap kawalan input dengan sempadan dan bulatan sudut yang diambil daripada token `--c-border` dan `--radius-sm` Tema_Sistem.
5. WHEN Pengguna_Pengurusan menukar nilai mana-mana satu daripada tujuh penapis, THE Laporan_Keseluruhan SHALL mengemas kini Kad_Statistik, Carta_Status, Carta_Tren dan Jadual_Aduan mengikut Set_Penapis_Semasa dalam masa tidak melebihi 3 saat, dan kembali ke halaman pertama Jadual_Aduan.
6. WHEN Pengguna_Pengurusan menekan butang "Set Semula" pada kepala Penapis_Laporan, THE Penapis_Laporan SHALL menetapkan semula penapis Bahagian, Status Tindakan, Pegawai Bertanggungjawab, Tahap Risiko dan Rating kepada pilihan lalai bernilai kosong masing-masing, menetapkan Dari Tarikh kepada hari pertama bulan semasa, menetapkan Hingga Tarikh kepada tarikh hari ini, dan kembali ke halaman pertama Jadual_Aduan, iaitu kelakuan `resetFilters()` sedia ada.
7. WHEN Set_Penapis_Semasa berubah, THE Laporan_Keseluruhan SHALL mengekalkan nilai setiap penapis dalam parameter query URL seperti kelakuan atribut `#[Url]` sedia ada, dengan Dari Tarikh dan Hingga Tarikh dalam format tarikh YYYY-MM-DD dan penapis yang berada pada pilihan lalai diwakili sebagai nilai kosong.
8. WHEN Laporan_Keseluruhan dimuatkan daripada URL yang mengandungi parameter penapis, THE Penapis_Laporan SHALL memaparkan nilai penapis daripada URL tersebut bagi setiap parameter yang nilainya terdapat dalam senarai pilihan kawalan berkenaan.
9. IF satu parameter penapis dalam URL mengandungi nilai yang tiada dalam senarai pilihan kawalan berkenaan, atau nilai tarikh yang bukan dalam format YYYY-MM-DD, THEN THE Penapis_Laporan SHALL memaparkan kawalan tersebut pada nilai lalainya seperti dalam kriteria 1 dan meneruskan paparan laporan menggunakan Set_Penapis_Semasa yang terhasil.
10. WHILE Set_Penapis_Semasa berada dalam keadaan Julat_Tarikh_Tidak_Sah, THE Penapis_Laporan SHALL memaparkan penanda amaran sebaris pada kedua-dua kawalan tarikh yang menyatakan julat tarikh tidak sah, SHALL mengekalkan kedua-dua nilai tarikh yang dimasukkan tanpa mengosongkan atau menukar kawalan, dan SHALL membenarkan kemas kini Kad_Statistik, Carta_Status, Carta_Tren dan Jadual_Aduan diteruskan seperti dalam Requirement 9 kriteria 12.
11. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 575px dan ke bawah, THE Penapis_Laporan SHALL menyusun tujuh kawalan penapis dalam satu lajur selebar penuh bekas kad tanpa limpahan mendatar pada badan halaman.
12. THE Penapis_Laporan SHALL memaparkan tepat dua butang pada kepala kad penapis, iaitu butang "Set Semula" yang disediakan oleh atribut `show-reset` komponen `x-filter-bar` dan butang "Eksport CSV" yang diletakkan dalam slot `$action` komponen tersebut, disusun pada satu baris mendatar dengan butang "Set Semula" di sebelah kiri butang "Eksport CSV", tanpa butang penjanaan laporan kerana kesemua tujuh penapis telah reaktif (`wire:model.live`).

### Requirement 3: Kad statistik ringkasan yang moden

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu kad statistik yang mudah dibaca sekilas pandang, supaya saya dapat menilai taburan status aduan dengan pantas.

#### Acceptance Criteria

1. WHEN Laporan_Keseluruhan dirender, THE Kad_Statistik SHALL memaparkan tepat tujuh kad status, satu bagi setiap status dalam `ReportStatuses::COUNTED` (Baru, Aduan Disemak, Dalam Tindakan, Selesai, KIV, Selesai Tindakan dan Tindakan Lanjut Diperlukan), disusun menaik mengikut `_report_statuses.id` seperti kelakuan sedia ada, dan setiap kad memaparkan nama status tersebut sebagai label.
2. WHEN Laporan_Keseluruhan dirender, THE Kad_Statistik SHALL memaparkan satu kad "Jumlah" sebagai kad kelapan yang memaparkan bilangan keseluruhan Aduan_Dikira bagi Set_Penapis_Semasa.
3. THE Kad_Statistik SHALL mengambil setiap bilangan daripada hasil pertanyaan pangkalan data komponen `ReportAll` bagi Set_Penapis_Semasa, tanpa sebarang nilai bilangan yang ditulis secara tetap dalam view.
4. THE Kad_Statistik SHALL memaparkan setiap bilangan dalam fon paparan token `--font-display` pada saiz sekurang-kurangnya 1.5rem, dan memaparkan label kad pada saiz tidak melebihi 0.875rem.
5. THE Kad_Statistik SHALL memaparkan satu ikon Bootstrap Icons pada setiap kad status, dengan ikon yang berbeza bagi setiap tujuh status dan ikon yang sama bagi status yang sama pada setiap render.
6. THE Kad_Statistik SHALL menentukan Hue_Status setiap kad status daripada kelas badge status (`_report_statuses.class`) status berkenaan, supaya warna kad dan warna badge status dalam Jadual_Aduan berasal daripada sumber data yang sama, tanpa pemetaan warna per-status yang ditulis secara tetap dalam view.
7. THE Kad_Statistik SHALL memaparkan kad "Jumlah" dengan gradien berasaskan token `--c-primary` Tema_Sistem.
8. WHEN warna teks bilangan dan warna teks label setiap kad dinilai terhadap warna latar kad tersebut, THE Kad_Statistik SHALL mencapai nisbah kontras sekurang-kurangnya 4.5:1 bagi kedua-dua pasangan, termasuk kad "Jumlah".
9. IF satu status tiada Aduan_Dikira bagi Set_Penapis_Semasa, THEN THE Kad_Statistik SHALL memaparkan nilai 0 bagi kad status tersebut dan mengekalkan kad tersebut kelihatan.
10. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 1200px dan ke atas, THE Kad_Statistik SHALL menyusun kelapan kad dalam dua baris atau kurang, tanpa limpahan mendatar pada badan halaman dan tanpa pemotongan teks label atau bilangan.
11. THE Kad_Statistik SHALL memaparkan bilangan kad "Jumlah" yang sama dengan hasil tambah bilangan tujuh kad status bagi Set_Penapis_Semasa yang sama.
12. THE Kad_Statistik SHALL memaparkan setiap bilangan sebagai nombor bulat bukan negatif dengan pemisah ribuan bagi nilai 1,000 dan ke atas, dan menyokong paparan sehingga sekurang-kurangnya 7 angka tanpa pemotongan.
13. IF Pengguna_Pengurusan mengklik mana-mana kad dalam Kad_Statistik, THEN THE Laporan_Keseluruhan SHALL mengekalkan Set_Penapis_Semasa dan kandungan halaman tanpa perubahan.
14. THE Kad_Statistik SHALL memaparkan isian latar setiap kad status sebagai tint Nada_Pastel bagi Hue_Status kad tersebut, iaitu warna dengan nilai lightness HSL sekurang-kurangnya 88% dan nilai saturation HSL tidak melebihi 65%, dan bukan isian penuh Hue_Status tersebut.
15. THE Kad_Statistik SHALL memaparkan bilangan setiap kad status dalam nada tegas Hue_Status kad tersebut, iaitu warna dengan nilai lightness HSL tidak melebihi 45%, dan memaparkan label setiap kad status dalam nada gelap dengan nilai lightness HSL tidak melebihi 55%, supaya kedua-dua teks ialah teks gelap atas latar cair dan bukan teks putih atas isian penuh.
16. THE Kad_Statistik SHALL memaparkan sempadan setiap kad status sebagai garis selebar 1px dalam Hue_Status kad tersebut, pada nilai lightness HSL antara nilai lightness isian latar kad dan nilai lightness bilangan kad.
17. THE Kad_Statistik SHALL memaparkan ikon setiap kad status dalam nada tegas Hue_Status yang sama seperti bilangan kad tersebut, pada saiz fon tidak melebihi 1.25rem.
18. WHEN Laporan_Keseluruhan dirender dalam penyemak imbas yang memuatkan helaian gaya utiliti Bootstrap, THE Kad_Statistik SHALL memaparkan nilai dikira (computed value) latar, teks dan sempadan setiap kad status seperti yang dituntut oleh kriteria 14 hingga 16, tanpa nilai tersebut diganti oleh pengisytiharan `!important` mana-mana kelas utiliti warna Bootstrap.
19. THE Kad_Statistik SHALL mengambil setiap nilai tint dan setiap nilai nada tegas daripada Token_Nada dalam blok `:root` `resources/css/app.css`, dan Token_Nada tersebut SHALL berada dalam keluarga warna Palet_Carta yang sama, supaya tiada kad status memaparkan hue yang bercanggah dengan warna segmen Carta_Status.

### Requirement 4: Carta agihan status tindakan

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu melihat agihan status aduan dalam bentuk carta donat, supaya saya faham nisbah setiap status dengan cepat.

#### Acceptance Criteria

1. WHEN Laporan_Keseluruhan dirender, THE Cincin_Donat SHALL memaparkan satu segmen bagi setiap status dalam `ReportStatuses::COUNTED` yang mempunyai sekurang-kurangnya satu Aduan_Dikira bagi Set_Penapis_Semasa, iaitu maksimum tujuh segmen, disusun menaik mengikut `_report_statuses.id`, dan tanpa segmen bagi status yang bilangannya 0; kriteria ini mengawal Cincin_Donat sahaja, manakala kandungan, susunan dan warna Legenda_Carta_Status ditetapkan oleh kriteria 3 dan kriteria 15 hingga 20.
2. WHEN Laporan_Keseluruhan dirender, THE Carta_Status SHALL memaparkan di tengah-tengah Cincin_Donat bilangan keseluruhan Aduan_Dikira bagi Set_Penapis_Semasa sebagai nombor bulat dengan pemisah ribuan pada baris pertama dan label dengan teks tepat "Jumlah Tiket" pada baris kedua di bawah bilangan tersebut, dan bilangan tersebut SHALL sama dengan nilai kad "Jumlah" dalam Kad_Statistik.
3. THE Legenda_Carta_Status SHALL memaparkan bagi setiap baris legenda satu titik warna, nama status, bilangan Aduan_Dikira status tersebut sebagai nombor bulat, serta peratusan bilangan tersebut daripada jumlah keseluruhan Aduan_Dikira yang dikira oleh pelayan dan dibundarkan kepada satu tempat perpuluhan.
4. THE Carta_Status SHALL dilukis menggunakan Pustaka_Carta di dalam bekas carta yang mempunyai ketinggian tetap 300px pada semua lebar viewport 992px dan ke atas.
5. WHEN Set_Penapis_Semasa berubah, THE Carta_Status SHALL dilukis semula menggunakan data baharu daripada komponen `ReportAll` dalam masa 2 saat selepas render Livewire selesai, tanpa muat semula penuh halaman, dan tanpa mengekalkan mana-mana segmen atau baris legenda daripada Set_Penapis_Semasa sebelumnya.
6. IF bilangan keseluruhan Aduan_Dikira bagi Set_Penapis_Semasa ialah 0, THEN THE Carta_Status SHALL memaparkan ikon dan mesej keadaan kosong "Tiada data untuk tempoh ini" dalam bekas carta berketinggian sama, tanpa melukis segmen donat, tanpa nilai tengah dan tanpa legenda.
7. THE Carta_Status SHALL menyalin tetapan carta donat sedia ada dalam `resources/views/dashboard.blade.php` tanpa mengubah fail tersebut, iaitu Palet_Carta yang sama mengikut susunan yang sama, fon dan warna teks yang sama, ikon bulat bagi penanda legenda, serta tooltip dengan pencetus setiap item; kedudukan dan susun atur Legenda_Carta_Status dikecualikan daripada salinan ini dan ditetapkan oleh kriteria 10 hingga 20, dan pemetaan warna Palet_Carta kepada segmen dikecualikan daripada salinan ini dan ditetapkan oleh kriteria 18.
8. WHEN Pengguna_Pengurusan menuding satu segmen donat, THE Carta_Status SHALL memaparkan tooltip yang mengandungi nama status, bilangan aduan segmen tersebut dan peratusannya daripada jumlah keseluruhan yang dibundarkan kepada satu tempat perpuluhan.
9. IF Pustaka_Carta tidak tersedia pada penyemak imbas, THEN THE Carta_Status SHALL mengekalkan mesej Bahasa Melayu dalam bekas carta yang menyatakan carta tidak dapat dipaparkan, dan Kepala_Halaman, Penapis_Laporan, Kad_Statistik serta Jadual_Aduan SHALL kekal dipaparkan seperti biasa.
10. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 992px dan ke atas, THE Legenda_Carta_Status SHALL dipaparkan sebagai senarai menegak di sebelah donat, iaitu donat pada satu sisi dan senarai legenda pada sisi yang lain dalam satu baris mendatar yang sama, dan bukan sebagai jalur legenda mendatar di bawah donat.
11. THE Legenda_Carta_Status SHALL memaparkan setiap status yang disenaraikan oleh kriteria 16 sebagai tepat satu baris legenda, dengan nama status dijajarkan ke kiri baris, dan bilangan beserta peratusan dijajarkan ke kanan baris pada tepi kanan yang sama bagi kesemua baris.
12. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 991px dan ke bawah, THE Legenda_Carta_Status SHALL disusun di bawah donat dan SHALL kekal sebagai senarai menegak satu baris bagi setiap status seperti dalam kriteria 11.
13. THE Legenda_Carta_Status SHALL memaparkan nama status penuh pada lebar viewport 1200px dan ke atas, dan IF lebar yang tersedia bagi baris legenda tidak mencukupi untuk nama status penuh, THEN THE Legenda_Carta_Status SHALL memendekkan nama status tersebut dengan penanda pemendekan pada satu baris teks dan menyediakan nama status penuh sebagai tooltip pada baris tersebut, tanpa membenarkan baris legenda melimpah keluar daripada bekas kad.
14. THE Carta_Status SHALL mengekalkan ketinggian bekas `.chart-box` seperti yang ditetapkan oleh kriteria 4, iaitu susun atur Legenda_Carta_Status dalam kriteria 10 hingga 20 tidak menambah pengisytiharan ketinggian `.chart-box` yang kedua dan tidak mengubah ketinggian bekas carta.
15. THE Legenda_Carta_Status SHALL menyusun baris legenda menurun mengikut bilangan Aduan_Dikira, iaitu status dengan bilangan tertinggi pada baris pertama, dan SHALL menyusun dua status atau lebih yang mempunyai bilangan yang sama menaik mengikut `_report_statuses.id` antara status tersebut, supaya susunan baris legenda bagi satu Set_Penapis_Semasa adalah tunggal dan sama pada setiap render.
16. WHILE bilangan keseluruhan Aduan_Dikira bagi Set_Penapis_Semasa adalah sekurang-kurangnya 1, THE Legenda_Carta_Status SHALL menyenaraikan tepat tujuh baris legenda, iaitu satu baris bagi setiap status dalam `ReportStatuses::COUNTED`, termasuk status yang bilangannya 0 yang dipaparkan dengan bilangan 0 dan peratusan 0.0.
17. THE Legenda_Carta_Status SHALL memaparkan titik warna setiap baris legenda dalam Warna_Status status baris tersebut, iaitu warna yang sama seperti segmen Cincin_Donat status tersebut, walaupun susunan baris legenda dalam kriteria 15 berbeza daripada susunan segmen Cincin_Donat dalam kriteria 1.
18. THE Carta_Status SHALL menentukan Warna_Status setiap status daripada Indeks_Status status tersebut, iaitu warna Palet_Carta pada kedudukan Indeks_Status, tanpa bergantung kepada kedudukan segmen status tersebut dalam Cincin_Donat dan tanpa bergantung kepada kedudukan baris status tersebut dalam Legenda_Carta_Status.
19. WHEN Set_Penapis_Semasa berubah, THE Carta_Status SHALL memaparkan Warna_Status setiap status sama seperti Warna_Status status tersebut sebelum perubahan, pada segmen Cincin_Donat status tersebut dan pada titik warna baris Legenda_Carta_Status status tersebut.
20. THE Legenda_Carta_Status SHALL memaparkan peratusan setiap baris legenda sebagai nilai peratusan yang dikira oleh pelayan seperti dalam kriteria 3, dirender dengan tepat satu tempat perpuluhan, satu aksara peratus, dan dibalut oleh satu pasangan tanda kurungan buka dan tutup, iaitu bentuk "(38.5%)" dan bukan "38.5%".

### Requirement 5: Carta tren aduan mengikut tarikh

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu melihat tren aduan sepanjang tempoh yang dipilih, supaya saya dapat mengenal pasti lonjakan bilangan aduan.

#### Acceptance Criteria

1. WHEN Laporan_Keseluruhan dirender, THE Carta_Tren SHALL memaparkan satu bar bagi setiap selang masa dalam julat tarikh Set_Penapis_Semasa, dengan nilai bar ialah bilangan Aduan_Dikira yang `submitted_at` jatuh dalam selang tersebut, disusun menaik mengikut masa dari selang terawal ke selang terakhir.
2. THE Carta_Tren SHALL mengehadkan paksi tarikh kepada julat Dari Tarikh hingga Hingga Tarikh dalam Set_Penapis_Semasa secara inklusif kedua-dua hujung, tanpa memaparkan mana-mana selang di luar julat tersebut.
3. WHERE julat tarikh Set_Penapis_Semasa meliputi 62 hari atau kurang (dikira secara inklusif kedua-dua hujung), THE Carta_Tren SHALL mengumpulkan data mengikut hari dan memaparkan satu bar bagi setiap hari kalendar dalam julat, termasuk hari tanpa aduan dengan nilai 0.
4. WHERE julat tarikh Set_Penapis_Semasa meliputi lebih daripada 62 hari (dikira secara inklusif kedua-dua hujung), THE Carta_Tren SHALL mengumpulkan data mengikut bulan dan memaparkan satu bar bagi setiap bulan kalendar yang bertindan dengan julat, termasuk bulan tanpa aduan dengan nilai 0.
5. THE Carta_Tren SHALL memaparkan label paksi tarikh dalam Bahasa Melayu, iaitu hari dan singkatan bulan bagi pengumpulan harian (contoh "03 Mac") serta singkatan bulan dan tahun bagi pengumpulan bulanan (contoh "Mac 2025"), dan memaparkan label paksi nilai sebagai integer bukan negatif bermula dari 0.
6. WHEN Pengguna_Pengurusan menuding atau mengetuk satu bar, THE Carta_Tren SHALL memaparkan tooltip yang mengandungi label selang masa bar tersebut dan bilangan aduan bar tersebut sebagai integer.
7. THE Carta_Tren SHALL dilukis menggunakan Pustaka_Carta.
8. WHEN Set_Penapis_Semasa berubah, THE Carta_Tren SHALL dilukis semula sepenuhnya menggunakan data baharu daripada komponen `ReportAll` dalam tempoh tidak melebihi 2 saat selepas render Livewire selesai, tanpa mengekalkan bar daripada Set_Penapis_Semasa sebelumnya.
9. IF tiada Aduan_Dikira bagi Set_Penapis_Semasa, THEN THE Carta_Tren SHALL menyembunyikan kawasan carta, memaparkan mesej keadaan kosong "Tiada data untuk tempoh ini", dan mengekalkan ketinggian bekas carta yang sama seperti ketika carta dipaparkan.
10. THE Laporan_Keseluruhan SHALL menghantar data Carta_Status dan Carta_Tren ke penyemak imbas melalui pemegang data JSON dalam view Livewire, mengikut corak atribut `data-*` dengan `@json(...)` yang digunakan oleh `livewire/dashboard/dashboard.blade.php`.
11. WHILE Set_Penapis_Semasa berada dalam keadaan Julat_Tarikh_Tidak_Sah, THE Carta_Tren SHALL memaparkan mesej keadaan kosong "Tiada data untuk tempoh ini" tanpa memaparkan mana-mana bar.
12. IF data Carta_Tren dalam pemegang data JSON tidak tersedia atau tidak boleh dihuraikan oleh skrip carta, THEN THE Carta_Tren SHALL mengekalkan mesej Bahasa Melayu dalam bekas carta yang menyatakan carta tidak dapat dipaparkan, mengekalkan ketinggian bekas carta, dan Kad_Statistik serta Jadual_Aduan SHALL kekal dipaparkan seperti biasa.
13. WHEN Carta_Tren dilukis pada sebarang bilangan baldi, termasuk 15, 31 dan 62 baldi, THE Carta_Tren SHALL memaparkan Label_Paksi_Tarikh supaya tiada dua Label_Paksi_Tarikh bertindih atau bersambung menjadi satu rentetan, dan SHALL melangkau sebahagian Label_Paksi_Tarikh pada selang tetap apabila lebar paksi tidak mencukupi untuk memaparkan kesemua label tanpa pertindihan.
14. WHILE Carta_Tren mengumpulkan data mengikut hari, THE Carta_Tren SHALL memaparkan setiap Label_Paksi_Tarikh dalam dua baris, iaitu nombor hari pada baris pertama dan singkatan bulan pada baris kedua.
15. WHILE Carta_Tren mengumpulkan data mengikut bulan, THE Carta_Tren SHALL memaparkan setiap Label_Paksi_Tarikh dalam dua baris, iaitu singkatan bulan pada baris pertama dan tahun pada baris kedua.
16. THE Laporan_Keseluruhan SHALL mengekalkan format rentetan label yang dijana oleh pelayan seperti dalam kriteria 5 tanpa perubahan, iaitu paparan dua baris dalam kriteria 14 dan 15 dihasilkan semasa label dilukis pada paksi dan bukan dengan mengubah nilai label dalam pemegang data JSON.

### Requirement 6: Jadual senarai aduan yang profesional

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu jadual senarai aduan yang lapang dan mudah dibaca, supaya saya boleh menyemak aduan satu demi satu tanpa keletihan membaca.

#### Acceptance Criteria

1. WHEN Laporan_Keseluruhan dirender, THE Jadual_Aduan SHALL memaparkan tepat sepuluh lajur dalam susunan kiri ke kanan: Bil., Tiket, Subjek, Bahagian, Kategori, Risiko, Tarikh, Status, Rating dan Tindakan, dengan lajur pertama berlabel "Bil." menggantikan label "#" sedia ada.
2. WHEN Laporan_Keseluruhan dirender, THE Jadual_Aduan SHALL memaparkan nilai lajur Bil. sebagai nombor berturutan merentas halaman penomboran seperti kelakuan sedia ada, bermula daripada 1 pada halaman pertama dan daripada 21 pada halaman kedua.
3. THE Jadual_Aduan SHALL memaparkan nilai lajur Tarikh sebagai tarikh `submitted_at` dalam format tarikh panjang Bahasa Melayu melalui pembantu sedia ada `convertDateFormat($tarikh, 'LL')`, pada satu baris teks tanpa pemisahan baris.
4. THE Jadual_Aduan SHALL memaparkan latar belakang kepala jadual menggunakan token `--c-primary-050` Tema_Sistem, dan teks kepala jadual menggunakan token `--c-text` Tema_Sistem.
5. THE Jadual_Aduan SHALL memaparkan nombor tiket dengan berat fon sekurang-kurangnya 600, manakala teks sel lain pada baris yang sama tidak melebihi berat fon 400.
6. THE Jadual_Aduan SHALL memaparkan Status setiap aduan sebagai badge yang menggunakan kelas daripada `_report_statuses.class`.
7. THE Jadual_Aduan SHALL memaparkan Tahap Risiko setiap aduan sebagai badge yang menggunakan kelas daripada `_risk_levels.class`.
8. WHEN penuding tetikus Pengguna_Pengurusan berada di atas satu baris Jadual_Aduan, THE Jadual_Aduan SHALL menukar warna latar baris tersebut kepada tint daripada token `--c-primary-050` Tema_Sistem dan mengembalikan warna latar asal apabila penuding keluar daripada baris tersebut.
9. THE Jadual_Aduan SHALL menjajarkan kandungan lajur Bil. dan lajur Rating ke tengah sel pada setiap baris, dan menjajarkan kandungan lajur Tindakan ke kanan sel pada setiap baris.
10. THE Jadual_Aduan SHALL mengekalkan penomboran 20 rekod per halaman, susunan `submitted_at` menurun, beserta pautan penomboran Livewire sedia ada.
11. THE Jadual_Aduan SHALL mengekalkan butang tindakan "Lihat" yang memaut ke laluan `aduan.show` bagi setiap aduan.
12. IF panjang teks Subjek melebihi 40 aksara, THEN THE Jadual_Aduan SHALL memaparkan 40 aksara pertama diikuti penanda pemotongan seperti kelakuan sedia ada, dan menyediakan teks Subjek penuh sebagai tooltip pada sel tersebut.
13. IF nilai Bahagian, Kategori, Tahap Risiko atau Rating bagi satu aduan kosong, THEN THE Jadual_Aduan SHALL memaparkan satu penanda tiada nilai dalam sel berkenaan menggunakan warna teks token `--c-text-muted` Tema_Sistem, dan bukan sel kosong.
14. IF tiada aduan sepadan dengan Set_Penapis_Semasa, THEN THE Jadual_Aduan SHALL memaparkan satu baris keadaan kosong yang merentasi kesemua sepuluh lajur dengan satu ikon Bootstrap Icons dan mesej "Tiada aduan sepadan dengan penapis.", serta tidak memaparkan pautan penomboran.
15. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 575px dan ke bawah, THE Jadual_Aduan SHALL membenarkan penatalan mendatar dalam bekas jadual sahaja, tanpa menyebabkan penatalan mendatar pada badan halaman.

### Requirement 7: Keadaan memuat dan maklum balas visual

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu tahu apabila laporan sedang dimuatkan, supaya saya tidak tertanya-tanya sama ada sistem sedang memproses permintaan saya.

#### Acceptance Criteria

1. WHILE komponen `ReportAll` memproses permintaan yang dicetuskan oleh penukaran nilai penapis, butang "Set Semula" atau pautan penomboran Jadual_Aduan, THE Laporan_Keseluruhan SHALL memaparkan penunjuk keadaan memuat yang kelihatan serentak pada ketiga-tiga bahagian Kad_Statistik, bahagian Carta_Status bersama Carta_Tren, dan Jadual_Aduan, dalam masa tidak melebihi 300 milisaat selepas permintaan dihantar.
2. WHILE penunjuk keadaan memuat dipaparkan, THE Penapis_Laporan SHALL mengekalkan nilai yang dipaparkan bagi kesemua tujuh kawalan penapis sama seperti nilai Set_Penapis_Semasa sebelum permintaan, tanpa mengosongkan atau menetapkan semula mana-mana kawalan.
3. WHILE penunjuk keadaan memuat dipaparkan, THE Laporan_Keseluruhan SHALL mengekalkan nilai Kad_Statistik dan baris Jadual_Aduan daripada hasil terakhir yang berjaya dipaparkan, tanpa memaparkan nilai kosong atau mesej keadaan kosong.
4. WHEN permintaan selesai dan paparan baharu dirender, THE Laporan_Keseluruhan SHALL menyembunyikan kesemua penunjuk keadaan memuat dalam masa tidak melebihi 300 milisaat selepas render selesai.
5. THE Laporan_Keseluruhan SHALL mengekalkan ketinggian bekas Carta_Status dan bekas Carta_Tren pada nilai yang sama sebelum, semasa dan selepas keadaan memuat, pada setiap lebar viewport dari 375px hingga 1920px.
6. WHILE penunjuk keadaan memuat dipaparkan, THE Penapis_Laporan SHALL menyahaktifkan butang "Set Semula" dan butang "Eksport CSV" supaya klik tambahan pada butang tersebut tidak menghantar permintaan baharu.
7. THE Laporan_Keseluruhan SHALL mengekalkan pengendalian ralat lalai Livewire sedia ada bagi permintaan yang gagal, tanpa menambah lapisan pengendalian ralat, tamat masa (timeout) atau mesej kegagalan baharu.

### Requirement 8: Konsistensi reka bentuk dan responsif

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu halaman ini kelihatan sebahagian daripada sistem yang sama dan boleh digunakan pada telefon, supaya pengalaman penggunaan kekal selesa pada semua peranti.

#### Acceptance Criteria

1. THE Laporan_Keseluruhan SHALL mengambil semua warna, fon, bulatan sudut dan bayang daripada token Tema_Sistem dalam `resources/css/app.css`, iaitu warna daripada token `--c-*`, fon daripada `--font` atau `--font-display`, bulatan sudut daripada `--radius`, `--radius-sm` atau `--radius-pill`, dan bayang daripada `--shadow-sm`, `--shadow` atau `--shadow-lg`.
2. THE Laporan_Keseluruhan SHALL merujuk setiap nilai warna, bulatan sudut dan bayang dalam view, komponen Blade dan kelas CSS baharu halaman ini melalui rujukan token `var(--token)` Tema_Sistem, tanpa memperkenalkan nilai warna, bulatan sudut atau bayang literal baharu yang belum ditakrifkan sebagai token dalam `resources/css/app.css`.
3. THE Laporan_Keseluruhan SHALL menggunakan komponen Blade sedia ada `x-filter-bar` bagi bekas Penapis_Laporan dan `x-filter-field` bagi kesemua tujuh kawalan penapis, tanpa mengubah fail `resources/views/components/filter-field.blade.php`, dan dengan perubahan pada `resources/views/components/filter-bar.blade.php` dihadkan kepada satu prop pilihan baharu yang bernilai lalai kosong beserta atribut `wire:loading.attr="disabled"` dan `wire:target` pada butang "Set Semula" apabila prop tersebut diberi nilai.
4. IF gaya CSS baharu diperlukan, THEN THE Laporan_Keseluruhan SHALL menambah gaya tersebut dalam `resources/css/app.css` sebagai kelas komponen yang boleh diguna semula, dan SHALL menggunakan atribut `style` sebaris hanya bagi nilai yang dijana secara dinamik daripada data komponen `ReportAll`.
5. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 1200px dan ke atas, THE Laporan_Keseluruhan SHALL menyusun Carta_Status dan Carta_Tren dalam satu baris dua lajur yang sama lebar, tanpa limpahan mendatar pada badan halaman.
6. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 992px hingga 1199px, THE Laporan_Keseluruhan SHALL menyusun Kad_Statistik dalam baris yang mengandungi maksimum empat kad, menyusun Carta_Status dan Carta_Tren dalam satu baris dua lajur yang sama lebar, dan memaparkan kesemua kad serta carta tanpa pertindihan kotak sempadan antara elemen dan tanpa limpahan mendatar pada badan halaman.
7. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 576px hingga 991px, THE Laporan_Keseluruhan SHALL menyusun Carta_Status dan Carta_Tren secara menegak dalam satu lajur, setiap carta mengisi 100% lebar kad induknya dengan ketinggian bekas carta sekurang-kurangnya 260px.
8. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 575px dan ke bawah, THE Laporan_Keseluruhan SHALL menyusun setiap kawalan Penapis_Laporan satu per baris pada 100% lebar kad, dan menyusun Kad_Statistik dalam baris yang mengandungi maksimum dua kad.
9. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 575px dan ke bawah, THE Laporan_Keseluruhan SHALL memaparkan setiap kawalan penapis, butang dan pautan tindakan dengan ketinggian sasaran sentuh sekurang-kurangnya 40px.
10. WHEN Laporan_Keseluruhan dipaparkan pada lebar viewport 375px, THE Laporan_Keseluruhan SHALL memaparkan semua kandungan tanpa limpahan mendatar pada badan halaman, iaitu lebar tatal badan halaman tidak melebihi 375px.
11. THE Laporan_Keseluruhan SHALL memaparkan teks badan pada saiz sekurang-kurangnya 0.8125rem dan mencapai nisbah kontras sekurang-kurangnya 4.5:1 antara teks dan latar belakangnya pada setiap lebar viewport dari 375px hingga 1920px.
12. THE Laporan_Keseluruhan SHALL mengekalkan teks antara muka dalam Bahasa Melayu bagi semua label penapis, tajuk kad, tajuk lajur jadual, teks butang dan mesej keadaan kosong.
13. WHEN mana-mana halaman lain yang menggunakan komponen `x-filter-bar` dirender tanpa memberi nilai kepada prop pilihan baharu yang dibenarkan oleh kriteria 3, THE komponen `x-filter-bar` SHALL menghasilkan markup, kelas CSS, susunan elemen dan kelakuan butang "Set Semula" yang sama seperti sebelum penambahbaikan, tanpa atribut `wire:loading`, `wire:target` atau `disabled` tambahan, iaitu kesembilan view pengguna komponen yang lain kekal tidak terjejas: `livewire/dashboard/dashboard.blade.php`, `livewire/dashboard/officer-dashboard.blade.php`, `livewire/dashboard/user-dashboard.blade.php`, `livewire/aduan/report-index.blade.php`, `livewire/aduan/lesson-learn.blade.php`, `livewire/audit-trail/audit-trail-index.blade.php`, `livewire/category/category-index.blade.php`, `livewire/role/role-index.blade.php` dan `livewire/user/user-index.blade.php`.
14. WHERE satu nada warna yang dituntut oleh Requirement 3 kriteria 14 hingga 19 belum mempunyai token, THE Laporan_Keseluruhan SHALL menakrifkan nada tersebut sebagai Token_Nada **baharu** dalam blok `:root` `resources/css/app.css` dan merujuknya melalui `var(--token)`, iaitu penambahan token baharu dibenarkan manakala pengubahan nilai token sedia ada kekal dilarang oleh Requirement 9 kriteria 14.
15. THE Laporan_Keseluruhan SHALL memaparkan kelas nada Kad_Statistik melalui nama kelas yang tidak bertindan dengan nama kelas utiliti warna Bootstrap, supaya nilai yang dirender tidak boleh diganti oleh pengisytiharan `!important` helaian gaya Bootstrap seperti yang dituntut oleh Requirement 3 kriteria 18.

### Requirement 9: Pengekalan kelakuan sedia ada

**User Story:** Sebagai pemilik sistem, saya mahu kepastian bahawa penambahbaikan ini tidak mengubah tingkah laku sistem, supaya laporan kekal boleh dipercayai dan modul lain tidak terjejas.

#### Acceptance Criteria

1. THE Laporan_Keseluruhan SHALL mengekalkan kedua-dua lapisan pemeriksaan kebenaran sedia ada, iaitu pengadang `can:access-main-dashboard` pada kumpulan laluan `laporan.*` dalam `routes/web.php` dan panggilan `authorize('access-main-dashboard')` dalam `mount()` komponen `ReportAll`.
2. IF pengguna tanpa kebenaran `access-main-dashboard` memohon laluan `laporan.view-by-all`, THEN THE Laporan_Keseluruhan SHALL menolak permintaan tersebut seperti kelakuan sedia ada, tanpa merender Kepala_Halaman, Penapis_Laporan, Kad_Statistik, Carta_Status, Carta_Tren atau Jadual_Aduan, dan tanpa memasukkan sebarang data Aduan_Dikira dalam respons.
3. THE Laporan_Keseluruhan SHALL mengekalkan semantik pertanyaan asas sedia ada, iaitu hanya Aduan_Dikira disertakan (status Draf dikecualikan), `submitted_at` ditapis dari permulaan hari Dari Tarikh hingga penghujung hari Hingga Tarikh, penapis Bahagian, Status Tindakan, Tahap Risiko dan Rating dipadankan secara tepat (exact match), dan penapis Pegawai Bertanggungjawab dipadankan dengan jejak tugasan terkini yang mempunyai pegawai ditugaskan.
4. THE Laporan_Keseluruhan SHALL mengekalkan skema pangkalan data sedia ada, iaitu sifar fail migrasi baharu ditambah dan sifar fail migrasi sedia ada diubah.
5. THE Laporan_Keseluruhan SHALL mengekalkan laluan dalam `routes/api.php` dan kaedah API dalam `LaporanController` tanpa perubahan pada laluan, tandatangan kaedah atau struktur respons.
6. THE Laporan_Keseluruhan SHALL mengambil setiap bilangan Kad_Statistik, setiap segmen Carta_Status dan setiap titik data Carta_Tren daripada data yang dihantar oleh kaedah `render()` komponen `ReportAll`, tanpa sebarang bilangan, label status atau titik data yang ditulis secara tetap (hardcoded) dalam view atau skrip carta.
7. WHEN bilangan Aduan_Dikira bagi satu status berubah dalam pangkalan data dan Laporan_Keseluruhan dirender semula dengan Set_Penapis_Semasa yang sama, THE Laporan_Keseluruhan SHALL memaparkan bilangan baharu tersebut pada kad status berkenaan, pada kad "Jumlah", pada Carta_Status dan pada Carta_Tren.
8. THE Laporan_Keseluruhan SHALL mengehadkan perubahan fail kepada `resources/views/laporan/show_by_all.blade.php`, `resources/views/livewire/laporan/report-all.blade.php`, `app/Livewire/Laporan/ReportAll.php`, `resources/views/layouts/navbar.blade.php`, `resources/css/app.css`, `resources/views/components/filter-bar.blade.php` (terhad kepada suntingan kecil yang dibenarkan oleh Requirement 8 kriteria 3), beserta fail komponen Blade baharu yang khusus untuk halaman ini, satu fail kelas eksport baharu dalam `app/Support/`, dan fail ujian baharu di bawah `tests/Feature/Livewire/Laporan/`, `tests/Feature/Laporan/` serta `tests/Unit/Support/`, tanpa perubahan pada fail lain dalam `app/`, `routes/` atau `database/`, dan tanpa perubahan pada komponen Blade bersama sedia ada selain `filter-bar.blade.php` atau pada view dashboard yang dirujuk oleh Requirement 4 dan Requirement 8.
9. WHEN gaya CSS bersama dalam `resources/css/app.css` diubah, THE Laporan_Keseluruhan SHALL menambah gaya baharu sebagai kelas baharu tanpa membuang atau menukar nilai pengisytiharan kelas sedia ada yang digunakan oleh halaman lain, supaya susun atur dan warna dashboard, senarai aduan serta halaman laporan lain kekal tidak berubah, dengan satu pengecualian yang diluluskan pengguna iaitu pengisytiharan warna `.app-sidebar__title` seperti dinyatakan dalam Requirement 10 kriteria 18.
10. WHEN penambahbaikan selesai, THE Laporan_Keseluruhan SHALL mengekalkan suite ujian sedia ada dalam keadaan lulus dengan sifar ujian gagal, sifar ujian dibuang dan sifar pengesahan (assertion) ujian sedia ada dilemahkan.
11. THE Laporan_Keseluruhan SHALL menggunakan Pustaka_Carta yang telah dimuatkan oleh `resources/views/layouts/app.blade.php`, tanpa menambah entri dependency baharu dalam `composer.json` atau `package.json` dan tanpa menambah fail pustaka baharu dalam `public/assets/libs`, termasuk bagi Eksport_CSV.
12. WHILE Set_Penapis_Semasa berada dalam keadaan Julat_Tarikh_Tidak_Sah, THE Laporan_Keseluruhan SHALL merender halaman tanpa ralat pelayan, memaparkan nilai 0 pada kesemua lapan Kad_Statistik, dan memaparkan keadaan kosong pada Carta_Status, Carta_Tren dan Jadual_Aduan.
13. IF URL mengandungi nilai parameter penapis yang tiada rekod sepadan (contohnya id Status Tindakan, id Tahap Risiko atau id Pegawai Bertanggungjawab yang tidak wujud), THEN THE Laporan_Keseluruhan SHALL merender halaman tanpa ralat pelayan dan memaparkan keadaan kosong pada Carta_Status, Carta_Tren dan Jadual_Aduan.
14. THE Laporan_Keseluruhan SHALL mengekalkan nilai setiap token warna sedia ada dalam blok `:root` `resources/css/app.css` tanpa perubahan, termasuk `--sidebar-bg`, `--sidebar-fg`, `--sidebar-fg-active`, `--sidebar-w`, `--c-primary`, `--c-primary-050`, `--c-butter`, `--c-bg`, `--c-surface`, `--c-border`, `--c-text`, `--c-text-muted`, `--c-success`, `--c-warning`, `--c-danger` dan `--c-info`; menambah Token_Nada baharu pada blok `:root` dibenarkan seperti dalam Requirement 8 kriteria 14, manakala membuang token sedia ada atau mengubah nilainya kekal dilarang.
15. THE Laporan_Keseluruhan SHALL mengekalkan kesemua laluan sedia ada dalam `routes/web.php` dan `routes/api.php` tanpa perubahan pada URI, nama laluan, kaedah pengawal atau middleware, dan Eksport_CSV SHALL dilaksanakan sebagai tindakan komponen `ReportAll` tanpa menambah laluan baharu.
16. THE Laporan_Keseluruhan SHALL melaksanakan setiap kelakuan Carta_Status dan Carta_Tren menggunakan pilihan konfigurasi yang disokong oleh Pustaka_Carta versi 4.3.1 sahaja, iaitu `axisLabel.hideOverlap`, `series.labelLayout` dan pilihan lain yang diperkenalkan dalam ECharts 5 SHALL dikecualikan daripada skrip carta halaman ini, supaya setiap perlindungan yang dituntut oleh Requirement 4 dan Requirement 5 berkesan pada versi pustaka yang sebenarnya dimuatkan.

### Requirement 10: Hierarki visual sidebar dengan warna dikekalkan

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu sidebar yang lebih jelas membezakan menu utama daripada submenu dan menunjukkan item semasa dengan tegas, supaya saya tahu kedudukan saya dalam sistem tanpa menukar rupa warna sistem yang telah diluluskan.

#### Acceptance Criteria

1. THE Sidebar_Sistem SHALL mengekalkan nilai palet warna sedia ada, iaitu latar `--sidebar-bg`, teks `--sidebar-fg`, teks item aktif `--sidebar-fg-active`, latar item aktif `--c-primary-050`, rel item aktif `--c-butter` dan sempadan `--c-border`, tanpa memperkenalkan nilai warna baharu dan tanpa menukar latar sidebar kepada warna gelap, dengan satu pengecualian yang diluluskan pengguna seperti dinyatakan dalam kriteria 18.
2. THE Sidebar_Sistem SHALL mengekalkan blok jenama `.app-sidebar__brand` tanpa perubahan, iaitu imej `images/logo.png`, teks "KKDW Helpdesk", pautan ke `url('/dashboard')` serta gaya latar dan bayang imej logo sedia ada.
3. THE Sidebar_Sistem SHALL membezakan Item_Menu_Utama daripada Item_Submenu melalui sekurang-kurangnya tiga isyarat visual, iaitu saiz fon, berat fon dan inden kiri, dengan Item_Menu_Utama pada saiz fon dan berat fon yang lebih besar daripada Item_Submenu, dan Item_Submenu pada inden kiri yang lebih besar daripada Item_Menu_Utama.
4. THE Sidebar_Sistem SHALL mengekalkan garis panduan menegak `.app-nav-sub::before` bagi setiap senarai submenu menggunakan token `--c-border`, sebagai pengikat visual antara Item_Submenu dalam satu kumpulan yang sama.
5. THE Sidebar_Sistem SHALL memaparkan setiap Item_Menu_Utama dengan satu ikon Boxicons seperti markup sedia ada, dan memaparkan Item_Submenu tanpa ikon Boxicons.
6. WHEN laluan `request()->path()` sepadan dengan satu Item_Menu_Utama, THE Sidebar_Sistem SHALL memaparkan Penanda_Aktif pada item tersebut menggunakan kelas `active` sedia ada, tanpa memperkenalkan mekanisme keadaan aktif baharu.
7. WHEN laluan `request()->path()` sepadan dengan satu Item_Submenu, THE Sidebar_Sistem SHALL memaparkan Penanda_Aktif pada Item_Submenu tersebut menggunakan kelas `active` sedia ada yang dikira daripada `request()->path()`, dengan rel aktif dijajarkan pada inden Item_Submenu supaya rel tersebut tidak bertindih dengan garis panduan `.app-nav-sub::before`.
8. WHILE satu Item_Submenu memaparkan Penanda_Aktif, THE Sidebar_Sistem SHALL memaparkan Item_Menu_Utama bagi kumpulan submenu tersebut dalam keadaan terbuka, iaitu nilai awal `o` bagi `x-data` kumpulan berkenaan adalah `true`, dengan syarat laluan setiap kumpulan membandingkan `request()->path()` terhadap prefix laluan sebenar bagi kumpulan tersebut.
9. THE Sidebar_Sistem SHALL mengekalkan tajuk bahagian `.app-sidebar__title` sedia ada ("Laman Utama", "Peg Teknikal", "Pentadbir Helpdesk", "Pentadbiran", "Tetapan", "Manual") dengan teks yang sama dan pada kedudukan yang sama dalam susunan menu.
10. THE Sidebar_Sistem SHALL mengekalkan struktur menu sedia ada tanpa menambah, membuang atau menamakan semula mana-mana Item_Menu_Utama atau Item_Submenu, dan tanpa menukar susunan item.
11. THE Sidebar_Sistem SHALL mengekalkan `href` setiap Item_Menu_Utama dan Item_Submenu kepada laluan atau URL yang sama seperti markup sedia ada.
12. THE Sidebar_Sistem SHALL mengekalkan setiap pengadang keterlihatan sedia ada tanpa perubahan, iaitu arahan `@can`, `@canany` dan `@hasrole` beserta senarai kebenaran yang sama bagi setiap item, supaya setiap peranan melihat set item menu yang sama seperti sebelum penambahbaikan.
13. WHEN Pengguna_Pengurusan mengklik satu Item_Menu_Utama yang merupakan pencetus kumpulan submenu, THE Sidebar_Sistem SHALL membuka atau menutup senarai submenu tersebut menggunakan `x-show` dan `x-collapse` sedia ada serta memutarkan ikon chevron sebanyak 180 darjah, iaitu kelakuan sedia ada.
14. WHEN mana-mana halaman lain yang mewarisi layout `layouts.app` dirender, THE Sidebar_Sistem SHALL mengekalkan lebar sidebar `--sidebar-w`, jidar kandungan utama `.app-main`, serta kedudukan dan jarak menegak setiap item menu dan tajuk bahagian seperti sebelum penambahbaikan, dengan perbezaan terhad kepada pembezaan Item_Menu_Utama/Item_Submenu dan Penanda_Aktif yang dinyatakan dalam kriteria 3 hingga 7, warna tajuk bahagian yang dinyatakan dalam kriteria 18, dan pembetulan markup serta syarat laluan yang dinyatakan dalam kriteria 19 dan 20.
15. WHEN Laporan_Keseluruhan atau mana-mana halaman lain dipaparkan pada lebar viewport 991px dan ke bawah, THE Sidebar_Sistem SHALL mengekalkan kelakuan drawer sedia ada, iaitu sidebar tersembunyi melalui `transform: translateX(-100%)` dan dipaparkan apabila `.app-shell` mempunyai kelas `sidebar-open`.
16. THE Sidebar_Sistem SHALL mencapai nisbah kontras sekurang-kurangnya 4.5:1 antara teks setiap Item_Menu_Utama, Item_Submenu dan tajuk bahagian dengan latar belakangnya, dalam keadaan biasa, keadaan tuding dan keadaan aktif.
17. THE Sidebar_Sistem SHALL memaparkan setiap Item_Menu_Utama dan Item_Submenu dengan ketinggian sasaran sentuh sekurang-kurangnya 40px pada lebar viewport 575px dan ke bawah.
18. THE Sidebar_Sistem SHALL memaparkan warna teks `.app-sidebar__title` melalui token sedia ada `var(--c-text-muted)` dan bukan nilai literal `#9ba1ba`, iaitu satu-satunya pengisytiharan warna sidebar yang diubah oleh penambahbaikan ini; perubahan ini telah diluluskan oleh pengguna, ia memperbaiki nisbah kontras tajuk bahagian daripada kira-kira 2.6:1 kepada kira-kira 5.0:1 seperti dituntut oleh kriteria 16, dan ia mengubah rupa tajuk bahagian sidebar pada **setiap** halaman yang mewarisi layout `layouts.app` secara sengaja.
19. THE Sidebar_Sistem SHALL merender markup `resources/views/layouts/navbar.blade.php` dalam keadaan sah secara struktur, iaitu setiap elemen `<div>` yang dibuka dalam blok `@can('view.report')` ditutup dalam blok yang sama, tanpa menambah, membuang atau menamakan semula mana-mana Item_Menu_Utama atau Item_Submenu seperti dituntut oleh kriteria 10.
20. WHEN `request()->path()` bermula dengan `laporan`, THE Sidebar_Sistem SHALL memaparkan kumpulan submenu Laporan dalam keadaan terbuka dan memaparkan Penanda_Aktif pada Item_Submenu "Laporan Aduan", menggantikan syarat `str_starts_with($path, 'report')` sedia ada yang tidak pernah sepadan dengan laluan laporan yang sebenar.

### Requirement 11: Eksport laporan ke fail CSV

**User Story:** Sebagai Pengguna_Pengurusan, saya mahu mengeksport senarai aduan yang telah ditapis ke dalam satu fail yang boleh dibuka dalam aplikasi hamparan, supaya saya boleh menyediakan laporan luaran tanpa menyalin data secara manual.

Requirement ini ialah **satu-satunya pengecualian** kepada kekangan "UI/UX sahaja" bagi spesifikasi ini, dan pengecualian tersebut telah diluluskan oleh pengguna. Pendekatan yang dipilih ialah muat turun CSV yang distrim daripada satu tindakan komponen `ReportAll` menggunakan `response()->streamDownload()`, kerana projek tiada pakej eksport sisi pelayan dan pendekatan ini tidak memerlukan pakej, laluan atau pustaka hadapan baharu.

#### Acceptance Criteria

1. THE Penapis_Laporan SHALL memaparkan satu butang bertanda "Eksport CSV" dengan satu ikon Bootstrap Icons dalam slot `$action` komponen `x-filter-bar`, tanpa mengubah fail komponen `x-filter-bar`.
2. WHEN Pengguna_Pengurusan menekan butang "Eksport CSV", THE Laporan_Keseluruhan SHALL menghasilkan satu Fail_Eksport berformat CSV yang dihantar kepada penyemak imbas sebagai muat turun fail, tanpa meninggalkan halaman Laporan_Keseluruhan.
3. WHEN Eksport_CSV dijalankan, THE Fail_Eksport SHALL mengandungi satu baris data bagi setiap Aduan_Dikira yang sepadan dengan Set_Penapis_Semasa merentas kesemua halaman penomboran, bukan hanya baris pada halaman Jadual_Aduan yang sedang dipaparkan.
4. WHEN Eksport_CSV dijalankan, THE Laporan_Keseluruhan SHALL menggunakan semantik pertanyaan yang sama seperti Jadual_Aduan seperti dinyatakan dalam Requirement 9 kriteria 3, termasuk pengecualian status Draf, julat `submitted_at`, kesemua tujuh penapis dan susunan `submitted_at` menurun, supaya setiap baris Fail_Eksport sepadan dengan baris yang akan dipaparkan oleh Jadual_Aduan bagi Set_Penapis_Semasa yang sama.
5. THE Fail_Eksport SHALL memaparkan satu baris kepala sebagai baris pertama dengan tepat sembilan lajur dalam Bahasa Melayu mengikut susunan "Bil.", "Tiket", "Subjek", "Bahagian", "Kategori", "Risiko", "Tarikh", "Status" dan "Rating", iaitu sembilan lajur data Jadual_Aduan tanpa lajur "Tindakan".
6. THE Fail_Eksport SHALL memaparkan nilai lajur Subjek sebagai teks Subjek penuh tanpa pemendekan, nilai lajur Tarikh dalam format tarikh panjang Bahasa Melayu melalui pembantu sedia ada `convertDateFormat($tarikh, 'LL')`, nilai lajur Rating sebagai integer dari 1 hingga 5, dan nilai lajur Bil. sebagai nombor berturutan bermula dari 1 bagi keseluruhan set hasil.
7. IF nilai Bahagian, Kategori, Risiko atau Rating bagi satu aduan kosong, THEN THE Fail_Eksport SHALL memaparkan satu aksara sengkang "-" dalam lajur berkenaan.
8. THE Fail_Eksport SHALL dikodkan dalam UTF-8 dengan penanda BOM UTF-8 pada permulaan fail, menggunakan koma sebagai pemisah medan dan petikan berganda sebagai pembalut medan, supaya aksara Bahasa Melayu dan medan yang mengandungi koma dipaparkan dengan betul dalam aplikasi hamparan.
9. WHEN Eksport_CSV dijalankan, THE Laporan_Keseluruhan SHALL menamakan Fail_Eksport mengikut format `laporan-keseluruhan-{dateFrom}-hingga-{dateTo}.csv`, dengan `{dateFrom}` dan `{dateTo}` ialah nilai Dari Tarikh dan Hingga Tarikh Set_Penapis_Semasa dalam format YYYY-MM-DD.
10. WHEN Eksport_CSV dijalankan, THE Laporan_Keseluruhan SHALL menghantar pengepala respons `Content-Type: text/csv; charset=UTF-8` beserta pengepala `Content-Disposition` jenis `attachment` yang mengandungi nama fail seperti dalam kriteria 9.
11. THE Laporan_Keseluruhan SHALL melaksanakan pemeriksaan kebenaran `access-main-dashboard` dalam tindakan Eksport_CSV, selain pengadang laluan sedia ada yang dinyatakan dalam Requirement 9 kriteria 1.
12. IF pengguna tanpa kebenaran `access-main-dashboard` mencetuskan tindakan Eksport_CSV, THEN THE Laporan_Keseluruhan SHALL menolak permintaan tersebut tanpa menghasilkan Fail_Eksport dan tanpa memasukkan sebarang data Aduan_Dikira dalam respons.
13. WHILE Eksport_CSV sedang diproses, THE Penapis_Laporan SHALL memaparkan penunjuk keadaan memuat pada butang "Eksport CSV" dalam masa tidak melebihi 300 milisaat selepas permintaan dihantar, dan menyahaktifkan butang tersebut supaya klik tambahan tidak menghantar permintaan eksport baharu.
14. WHEN Eksport_CSV selesai, THE Penapis_Laporan SHALL mengaktifkan semula butang "Eksport CSV" dan menyembunyikan penunjuk keadaan memuat dalam masa tidak melebihi 300 milisaat selepas respons diterima.
15. WHEN Eksport_CSV dijalankan, THE Laporan_Keseluruhan SHALL mengekalkan Set_Penapis_Semasa, nombor halaman Jadual_Aduan, Kad_Statistik, Carta_Status dan Carta_Tren tanpa perubahan.
16. IF tiada Aduan_Dikira sepadan dengan Set_Penapis_Semasa, THEN THE Laporan_Keseluruhan SHALL membatalkan muat turun dan memaparkan mesej Bahasa Melayu "Tiada data untuk dieksport." pada halaman, tanpa menghasilkan Fail_Eksport.
17. WHILE Set_Penapis_Semasa berada dalam keadaan Julat_Tarikh_Tidak_Sah, THE Laporan_Keseluruhan SHALL mengendalikan tindakan Eksport_CSV tanpa ralat pelayan dan memaparkan mesej "Tiada data untuk dieksport." seperti dalam kriteria 16, selaras dengan nilai 0 dan keadaan kosong yang dinyatakan dalam Requirement 9 kriteria 12.
18. WHEN set hasil Eksport_CSV mengandungi 10,000 baris atau lebih, THE Laporan_Keseluruhan SHALL menstrim baris ke output dalam kelompok tidak melebihi 500 rekod pada satu masa, tanpa memuatkan keseluruhan set hasil ke dalam memori sebagai satu koleksi.
19. THE Laporan_Keseluruhan SHALL melaksanakan Eksport_CSV menggunakan fungsi terbina Laravel dan PHP sahaja, iaitu `response()->streamDownload()` beserta `fputcsv`, tanpa menambah pakej `composer.json` atau `package.json` baharu dan tanpa bergantung kepada DataTables Buttons, JSZip atau pdfmake.

## Out of Scope

- Format eksport selain CSV. Eksport XLSX, PDF dan cetakan terus tidak termasuk dalam skop; hanya CSV seperti Requirement 11. Rujuk Keputusan Terbuka 3.
- Eksport bagi halaman laporan lain selain Laporan_Keseluruhan.
- Fungsi susunan lajur (sorting) pada Jadual_Aduan. Halaman ini **tiada** fungsi sorting pada masa ini; susunan kekal `submitted_at` menurun.
- Perubahan pada halaman laporan lain (`show_by_status`, `show_by_category`, `show_by_risk`, `show_by_rating`, `show_by_role`, `show_by_user`, `show_by_department`) dan pada dashboard.
- Perubahan pada palet warna sidebar, struktur menu, item menu, susunan menu, laluan menu atau kebenaran yang mengawal keterlihatan item menu, kecuali warna `.app-sidebar__title` yang diluluskan dalam Requirement 10 kriteria 18. Skop sidebar terhad kepada hierarki visual seperti Requirement 10, ditambah pembetulan markup dan syarat laluan dalam Requirement 10 kriteria 19 dan 20.
- Perubahan **nilai** token Tema_Sistem sedia ada dalam blok `:root` `resources/css/app.css`, dan pembuangan mana-mana token sedia ada. Penambahan Token_Nada **baharu** pada blok `:root` berada **dalam** skop seperti Requirement 8 kriteria 14; rujuk Keputusan Yang Telah Diputuskan 12.
- Perubahan logik perniagaan, skema pangkalan data, endpoint API, peranan dan kebenaran, kecuali kod pelayan baharu yang diperlukan oleh Eksport_CSV (Requirement 11).
- Penggantian Bootstrap 5 atau penambahan pustaka UI/carta baharu.
- Mod gelap (dark mode).
- Lapisan pengesahan (validation) atau pengendalian kegagalan/tamat masa baharu bagi permintaan `ReportAll`. Rujuk Keputusan Yang Telah Diputuskan 5.

## Keputusan Yang Telah Diputuskan

1. **Sidebar: warna dikekalkan, reka bentuk struktur diterapkan.** Cadangan asal menyebut sidebar *dark navy* `#172554`. Pengguna memutuskan supaya palet warna sidebar sedia ada (latar putih `--sidebar-bg`, pil periwinkle `--c-primary-050` bagi item aktif, rel mentega `--c-butter`) **dikekalkan**, manakala penambahbaikan reka bentuk sidebar yang dicadangkan diterapkan pada bahagian struktur dan hierarki visual sahaja. Keputusan ini dikodkan dalam Requirement 10, dan sidebar kini berada dalam skop spesifikasi ini.
2. **Nilai token warna dikekalkan.** Palet yang dicadangkan (Primary `#5145CD`, Background `#F5F7FC`, Success `#16A36A`, Warning `#F5A623`, Danger `#E5485B`, Text `#172554`/`#64748B`) telah dinilai terhadap token sedia ada (Primary `#5a68cf`, Background `#f7f8fe`, Success `#3b9b86`, Warning `#c08f2b`, Danger `#d4636f`, Text `#1c2138`/`#6a7089`). Kedua-dua palet cukup hampir, dan menukar nilai token akan menukar rupa **seluruh** sistem. Pengguna memutuskan supaya token sedia ada **dikekalkan**. Keputusan ini dikodkan dalam Requirement 8 kriteria 1 dan 2 serta Requirement 9 kriteria 14.
3. **Eksport diperlukan.** Pengguna memutuskan bahawa fungsi eksport ialah keperluan sebenar, bukan pilihan. Oleh sebab projek tiada pakej eksport sisi pelayan dan eksport sisi pelanggan (DataTables Buttons/JSZip/pdfmake) hanya meliputi baris DOM pada halaman semasa, pendekatan yang dipilih ialah muat turun CSV yang distrim daripada tindakan Livewire menggunakan `response()->streamDownload()`. Keputusan ini dikodkan dalam Requirement 11, dan merupakan satu-satunya pengecualian kepada kekangan "UI/UX sahaja" bagi spesifikasi ini.
4. **Tiada butang "Jana Laporan".** Pengguna memutuskan butang tersebut tidak diperlukan kerana kesemua tujuh penapis telah reaktif (`wire:model.live`) dan laporan dikemas kini secara automatik. Kepala Penapis_Laporan mengandungi butang "Set Semula" dan butang "Eksport CSV" sahaja, seperti Requirement 2 kriteria 12.
5. **Kelakuan apabila julat tarikh tidak sah.** Dua kelakuan bercanggah dipertimbangkan bagi keadaan Julat_Tarikh_Tidak_Sah: (i) halang kemas kini dengan ralat pengesahan sebaris dan kekalkan hasil terakhir yang berjaya, atau (ii) teruskan render dengan nilai 0 dan keadaan kosong beserta amaran pada Kepala_Halaman. Spesifikasi ini memilih **(ii)**, kerana komponen `ReportAll` sedia ada tiada lapisan pengesahan dan pilihan (i) memerlukan logik perniagaan baharu. Pilihan (ii) diterapkan secara konsisten dalam Requirement 1 kriteria 9, Requirement 2 kriteria 10, Requirement 5 kriteria 11, Requirement 9 kriteria 12 dan Requirement 11 kriteria 17.
6. **Fail ujian baharu dibenarkan.** Pengguna meluluskan perluasan senarai fail Requirement 9 kriteria 8 supaya fail ujian baharu boleh ditambah di bawah `tests/Feature/Livewire/Laporan/`, `tests/Feature/Laporan/` dan `tests/Unit/Support/`. Tanpa kelulusan ini, Requirement 9 kriteria 10 (suite ujian kekal lulus) dan properti ketepatan dalam `design.md` tidak boleh dibuktikan.
7. **Warna tajuk bahagian sidebar diubah.** Pengguna meluluskan penukaran pengisytiharan `.app-sidebar__title` daripada nilai literal `#9ba1ba` kepada token sedia ada `var(--c-text-muted)`, dan menerima bahawa rupa tajuk bahagian sidebar berubah pada setiap halaman sistem. Keputusan ini dikodkan dalam Requirement 10 kriteria 18, dengan pengecualian yang jelas dalam Requirement 9 kriteria 9, Requirement 10 kriteria 1 dan Requirement 10 kriteria 14.
8. **Pembetulan pepijat `navbar.blade.php` dibenarkan.** Pengguna meluluskan dua pembetulan: menutup elemen `<div x-data>` dalam blok `@can('view.report')` yang kini tidak ditutup, dan menggantikan syarat buka kumpulan Laporan `str_starts_with($path, 'report')` dengan prefix laluan sebenar `laporan`. Kedua-duanya dikodkan dalam Requirement 10 kriteria 19 dan 20, dan tidak mengubah struktur, teks, susunan atau `href` mana-mana item menu.
9. **Suntingan kecil pada `x-filter-bar` dibenarkan.** Pengguna meluluskan satu suntingan kecil pada `resources/views/components/filter-bar.blade.php` supaya butang "Set Semula" boleh dinyahaktifkan dengan atribut `disabled` sebenar melalui `wire:loading.attr="disabled"`, menggantikan penyelesaian `pointer-events: none`. Suntingan dihadkan kepada satu prop pilihan yang bernilai lalai kosong, jadi kesembilan halaman lain yang menggunakan komponen ini kekal tidak berubah. Keputusan ini dikodkan dalam Requirement 8 kriteria 3 dan 13, dan dalam senarai fail Requirement 9 kriteria 8.
10. **Kad statistik: Nada_Pastel, bukan isian penuh.** Semakan halaman langsung terhadap mockup menunjukkan Kad_Statistik dirender sebagai blok warna penuh (sian, indigo, kuning, hijau, merah) dengan teks terang, bukan Nada_Pastel yang dituntut oleh mockup dan oleh Requirement 3 kriteria 6. Pengguna menyatakan warna tersebut bercanggah dengan Tema_Sistem dan dengan Palet_Carta. Requirement 3 kriteria 6 kini dihadkan kepada penerbitan Hue_Status sahaja, dan kriteria 14 hingga 19 baharu menetapkan Nada_Pastel dalam had HSL yang boleh diukur, menetapkan teks gelap atas latar cair, dan menuntut nilai dikira yang tidak boleh diganti oleh utiliti warna Bootstrap. Peraturan sedia ada dikekalkan: hue per-kad diterbitkan daripada `_report_statuses.class` dan tiada pemetaan warna per-status dalam view (kriteria 6), dan nisbah kontras sekurang-kurangnya 4.5:1 bagi bilangan dan label setiap kad termasuk kad "Jumlah" (kriteria 8).
11. **Label dan legenda carta dikemaskan.** Semakan halaman langsung menunjukkan dua kecacatan: Label_Paksi_Tarikh Carta_Tren bertindan sehingga bersambung menjadi satu rentetan pada julat 15 hari, dan Legenda_Carta_Status membalut sebagai jalur mendatar yang bersemak di bawah donat. Mockup menuntut label paksi dua baris dan legenda sebagai senarai menegak di sebelah donat. Keputusan ini dikodkan dalam Requirement 4 kriteria 10 hingga 14 dan Requirement 5 kriteria 13 hingga 16. Peraturan sedia ada dikekalkan: peratusan dikira di pelayan (Requirement 4 kriteria 3), format rentetan label pelayan tidak berubah kerana ujian sedia ada mengesahkannya (Requirement 5 kriteria 5 dan 16), dan ketinggian tetap `.chart-box` diisytiharkan sekali sahaja (Requirement 4 kriteria 4 dan 14, Requirement 7 kriteria 5).
12. **Token_Nada baharu dibenarkan, nilai token sedia ada kekal.** Nada_Pastel memerlukan pasangan nilai tint dan nada tegas bagi setiap Hue_Status. Pengguna meluluskan penambahan Token_Nada **baharu** pada blok `:root` `resources/css/app.css`, kerana Requirement 8 kriteria 2 melarang nilai warna literal baharu dan satu-satunya cara memenuhi kedua-dua kekangan ialah menakrifkan nada baharu sebagai token. Mengubah **nilai** mana-mana token sedia ada kekal dilarang oleh Requirement 9 kriteria 14, kerana itu akan mengubah rupa seluruh sistem.
13. **Nama kelas nada tidak boleh bertindan dengan utiliti Bootstrap.** Kelas nada kad statistik sedia ada ialah `.status-tile.bg-info`, `.bg-primary`, `.bg-warning`, `.bg-success`, `.bg-danger` dan `.bg-secondary`. `resources/css/app.css` sudah menetapkan nilai pastel bagi nada tersebut melalui pemboleh ubah `--tile-bg`/`--tile-fg`/`--tile-bd`, tetapi helaian gaya Bootstrap 5.3.3 (dimuatkan pada baris 17 `resources/views/layouts/app.blade.php`, sebelum `app.css` pada baris 25) mengisytiharkan `background-color` kelas utiliti `bg-*` dengan `!important`, jadi isian penuh Bootstrap menang ke atas tint `app.css` tanpa mengira susunan fail. Pengguna meluluskan peralihan kepada nama kelas nada berskop yang tidak bertindan dengan utiliti Bootstrap. Keputusan ini dikodkan dalam Requirement 3 kriteria 18 dan Requirement 8 kriteria 15.
14. **Pustaka_Carta ialah ECharts 4.3.1, bukan 5.** Tugasan 15.2 mendapati fail pustaka yang dibundalkan (`public/assets/libs/echarts/echarts.min.js`) mengandungi `version:"4.3.1"`. Oleh itu `axisLabel.hideOverlap`, yang dahulunya didokumenkan dalam `design.md` dan tugasan 9.3 sebagai perlindungan terhadap pertindihan Label_Paksi_Tarikh, **tidak pernah berkesan**: pilihan tersebut hanya wujud dari ECharts 5 ke atas dan diabaikan tanpa ralat oleh versi 4.3.1. Perlindungan yang sebenar ialah `interval: 'auto'` pada `axisLabel` bagi setiap bilangan baldi, yang disahkan secara aritmetik jelas pada 15, 31 dan 62 baldi semasa tugasan 15.2. Versi pustaka kini direkodkan dalam Glossary (Pustaka_Carta) dan dalam bahagian keadaan sedia ada, dan Requirement 9 kriteria 16 melarang pilihan khusus ECharts 5 supaya perlindungan yang tidak berfungsi tidak dimasukkan semula. Menaik taraf pustaka kepada ECharts 5 berada **di luar skop** spesifikasi ini kerana Requirement 9 kriteria 11 melarang penambahan fail pustaka baharu dalam `public/assets/libs`, dan pustaka tersebut dikongsi oleh dashboard.
15. **Susunan Legenda_Carta_Status dipisahkan daripada susunan Cincin_Donat.** Semakan halaman langsung kedua terhadap mockup mendapati empat perbezaan pada Carta_Status: baris legenda disusun menaik mengikut id status dan bukan menurun mengikut bilangan, status berbilangan 0 tiada dalam legenda, label tengah donat berbunyi "Jumlah" dan bukan "Jumlah Tiket", dan peratusan dirender tanpa tanda kurungan. Pengguna memutuskan mockup menang pada kesemua empat. Pelaksana tugasan 15.2 membangkitkan satu kekangan yang nyata: warna segmen ketika itu diambil daripada Palet_Carta mengikut **kedudukan** segmen, jadi menyusun semula Cincin_Donat akan menukar warna satu status setiap kali bilangannya berubah kedudukan. Oleh itu kedua-dua susunan dipisahkan: Cincin_Donat kekal menaik mengikut `_report_statuses.id` (Requirement 4 kriteria 1) supaya setiap status kekal pada Indeks_Status yang sama, manakala Legenda_Carta_Status disusun menurun mengikut bilangan (kriteria 15). Supaya pemisahan itu tidak memecahkan kaitan visual, Warna_Status kini diterbitkan daripada Indeks_Status dan bukan daripada kedudukan segmen (kriteria 18), titik legenda mesti sepadan dengan segmen status yang sama (kriteria 17), dan warna satu status mesti kekal apabila penapis berubah (kriteria 19). Peraturan sedia ada dikekalkan: peratusan dikira di pelayan kepada satu tempat perpuluhan (kriteria 3 dan 20), jumlah tengah sama dengan kad "Jumlah" (kriteria 2), keadaan kosong pada jumlah sifar tanpa legenda (kriteria 6), dan ketinggian tetap `.chart-box` diisytiharkan sekali sahaja (kriteria 4 dan 14).

## Keputusan Terbuka (perlu pengesahan pengguna)

1. **Mockup — sebahagian diselesaikan.** Spesifikasi asal dibina sepenuhnya daripada arahan reka bentuk bertulis kerana tiada fail imej mockup diterima pada masa itu. Pengguna telah **menyemak halaman langsung terhadap mockup** selepas tugasan 1 hingga 14 mendarat, dan dua kecacatan visual dikenal pasti: warna Kad_Statistik (Keputusan Yang Telah Diputuskan 10) dan kekemasan label serta legenda carta (Keputusan Yang Telah Diputuskan 11). Selepas tugasan 15 mendarat, pengguna menyemak Legenda_Carta_Status langsung terhadap mockup sekali lagi dan empat perbezaan lanjut dikenal pasti (Keputusan Yang Telah Diputuskan 15). Kesemuanya kini dikodkan sebagai kriteria penerimaan. Butiran susun atur mockup yang lain belum disemak satu per satu, jadi semakan lanjut masih mungkin memunculkan pembetulan tambahan.
2. **Pengumpulan Carta_Tren.** Ambang 62 hari dalam Requirement 5 kriteria 3 dan 4 dipilih supaya julat lalai (bulan semasa) dan julat dua bulan kekal harian, manakala julat panjang tidak menghasilkan ratusan bar yang bertindih. Ambang ini boleh dilaraskan jika pengguna mahu granulariti berbeza.
3. **Format eksport: CSV tanpa pakej, atau XLSX dengan pakej baharu.** Requirement 11 menetapkan CSV yang distrim tanpa pakej baharu, kerana itu pendekatan dengan bahagian bergerak paling sedikit bagi aplikasi Livewire + Laravel ini dan fail CSV boleh dibuka terus dalam Excel. Jika pengguna memerlukan fail XLSX sebenar (dengan pemformatan sel, lebar lajur atau berbilang helaian) atau PDF, satu pakej baharu seperti `maatwebsite/excel` atau `barryvdh/laravel-dompdf` perlu ditambah dalam `composer.json`, dan Requirement 9 kriteria 11 serta Requirement 11 kriteria 19 perlu disemak semula. Pilihan: (a) kekalkan CSV tanpa pakej baharu (disyorkan), atau (b) tambah pakej eksport dan luaskan Requirement 11 kepada XLSX/PDF.
