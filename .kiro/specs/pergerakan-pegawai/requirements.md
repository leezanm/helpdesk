# Requirements — Modul Pergerakan Pegawai

## Pengenalan

Pemantauan pergerakan pegawai Unit Operasi dan Bantuan Teknikal, BPM kini dibuat dalam Google
Sheet ("Jadual Pergerakan Unit Operasi dan Bantuan Teknikal, BPM Tahun 2026"). Modul ini
memindahkan jadual tersebut ke dalam Sistem Meja Bantuan KKDW supaya data pergerakan berada
dalam satu sistem, ada kawalan akses, ada jejak audit, dan boleh dilihat/dicetak tanpa berkongsi
fail luar.

### Bentuk jadual sedia ada (sumber: Google Sheet)

- Satu helaian per bulan; baris = pegawai (BIL + NAMA), lajur = setiap hari dalam bulan.
- Lajur dikumpulkan ikut minggu (MINGGU PERTAMA … MINGGU KELIMA) dengan lajur
  **HUJUNG MINGGU** (Ahad/Sabtu) ditandakan berasingan.
- Setiap sel mengandungi teks bebas yang pada praktiknya ada 3–4 bahagian:
  **Tajuk** (Kerja Luar / Bengkel / Mesyuarat / Kursus / lain-lain), **Masa**, **Lokasi/Tempat**,
  dan kadangkala catatan tarikh penuh untuk aktiviti berbilang hari.
- Cuti direkod dalam sel yang sama sebagai teks: `CUTI REHAT`, `TEMUJANJI HOSPITAL`.
- Cuti umum (contoh `CUTI DEEPAVALI`) ditulis pada hari berkenaan untuk pegawai terlibat.
- Aktiviti berbilang hari ditulis sekali dengan rangkaian tarikh (contoh
  `18 – 19 November 2026`, `23-25 November 2026`, `2 - 6 November 2026`).
- Seorang pegawai boleh ada lebih daripada satu aktiviti pada hari yang sama
  (contoh `Mesyuarat JK KKSK … / Perbincangan Sistem Helpdesk 2.0 …`).

### Prinsip

- **Pemantauan terpilih**: bukan semua pengguna sistem dipantau. Hanya pegawai yang
  dimasukkan ke dalam senarai pemantauan (roster) muncul dalam jadual.
- **Tambahan, bukan ubahan**: modul baharu; aliran aduan sedia ada tidak diubah.
- Ikut corak kod sedia ada: Livewire 3 untuk skrin interaktif, logik dalam `app/Actions`,
  jadual rujukan dalam `app/Models/Ref`, kebenaran melalui spatie/laravel-permission.

## Keputusan

| # | Perkara | Keputusan | Status |
|---|---------|-----------|--------|
| D1 | Skop unit | Struktur data sokong banyak unit; UI mula dengan satu unit (Unit Operasi & Bantuan Teknikal, BPM) | Disahkan |
| D2 | Pegawai dipantau mesti ada akaun sistem | Ya — pilih daripada `users`; nama paparan boleh ditulis semula (En./Pn. + nama) | Disahkan |
| D3 | Siapa isi entri | Pegawai isi sendiri; `helpdesk.admin` dan `pengurusan` boleh isi untuk semua pegawai dalam roster | Disahkan |
| D4 | Siapa boleh lihat jadual | Pegawai dalam roster + `pengurusan` + `helpdesk.admin` + `superadmin`. Role `pengguna` biasa tidak nampak modul ini | Disahkan |
| D5 | Aliran kelulusan | Tiada. Aktiviti yang dimasukkan ke dalam jadual ialah aktiviti yang **sudah** diluluskan di luar sistem | Disahkan |
| D6 | Cuti pegawai | Direkod sebagai jenis pergerakan dalam jadual yang sama; bukan modul cuti, tiada integrasi HRMIS | Disahkan |
| D7 | Cuti umum | Jadual `movement_holidays` diurus admin; ditanda sekali dan dipaparkan untuk semua pegawai | Disahkan |
| D8 | Pindah data 2026 dari Google Sheet | Tiada import. Key-in manual sahaja; data lepas tidak dipindahkan | Disahkan |
| D9 | Eksport | Paparan sesuai cetak (cetak → PDF melalui pelayar) + muat turun CSV. Tiada pakej Composer baharu | Disahkan |
| D10 | Peringatan e-mel harian | Tidak perlu — keluar daripada skop | Disahkan |
| D11 | Entri bertindih | Dibenarkan (hari yang sama boleh ada beberapa entri); sistem hanya beri amaran jika bertindih dengan cuti | Disahkan |
| D12 | Tempoh edit/hapus sendiri | Pegawai boleh edit/hapus entri **sendiri** hanya selagi tarikh entri belum sampai (paling lewat sehari sebelum tarikh mula). Selepas itu hanya `helpdesk.admin` boleh edit/hapus | Disahkan |
| D13 | Cipta entri bertarikh lepas | Dibenarkan untuk melengkapkan rekod tertinggal, tetapi entri itu terus terkunci — edit/hapus seterusnya perlu `helpdesk.admin` | Disahkan |

## Requirements

### Requirement 1 — Senarai pegawai dipantau (roster)

**User Story:** Sebagai urus setia unit, saya mahu menetapkan pegawai mana yang dipantau,
supaya jadual hanya memaparkan pegawai berkaitan.

#### Acceptance Criteria

1. WHEN urus setia membuka halaman Pegawai Dipantau THEN sistem SHALL memaparkan senarai pegawai dalam roster mengikut susunan paparan (BIL), berserta unit, nama paparan dan status aktif.
2. WHEN urus setia menambah pegawai THEN sistem SHALL membenarkan pemilihan daripada pengguna sistem yang aktif (carian ikut nama/username) dan menyimpan nama paparan serta susunan.
3. WHEN pegawai yang sama ditambah dua kali untuk unit yang sama THEN sistem SHALL menolak dan memaparkan mesej ralat.
4. WHEN pegawai tidak lagi dipantau THEN sistem SHALL membenarkan penyahaktifan (bukan hapus) supaya entri pergerakan lepas kekal utuh dan kekal boleh dilihat dalam jadual bulan lepas.
5. WHEN susunan paparan diubah THEN jadual bulanan SHALL mengikut susunan yang sama.
6. WHERE pengguna tiada kebenaran `pergerakan.manageRoster` THEN halaman ini SHALL tidak boleh diakses dan pautannya tidak dipaparkan.

### Requirement 2 — Rekod pergerakan

**User Story:** Sebagai pegawai yang dipantau, saya mahu merekod pergerakan saya
(mesyuarat, kerja luar, kursus, cuti), supaya jadual unit sentiasa terkini.

#### Acceptance Criteria

1. WHEN pegawai mencipta entri THEN sistem SHALL menerima: jenis pergerakan, tajuk, tarikh mula, tarikh tamat, masa mula (pilihan), masa tamat (pilihan), **lokasi aktiviti (dalam pejabat / luar pejabat)**, tempat (pilihan) dan catatan (pilihan).
1a. WHEN jenis yang dipilih bukan cuti THEN lokasi aktiviti SHALL dipaparkan dengan nilai lalai "dalam pejabat"; WHEN jenis cuti dipilih THEN medan itu SHALL disembunyikan dan nilainya dikosongkan, kerana cuti tiada lokasi bertugas.
1b. WHERE lokasi aktiviti disimpan THEN ia SHALL berada pada ENTRI dan bukan pada jenis pergerakan, kerana jenis yang sama (contoh "Mesyuarat") boleh berlaku di dalam atau di luar pejabat.
2. WHEN tarikh tamat lebih awal daripada tarikh mula THEN sistem SHALL menolak penyimpanan dengan mesej pengesahan.
3. WHEN tarikh mula sama dengan tarikh tamat THEN entri SHALL dianggap entri satu hari.
4. WHEN tarikh tamat selepas tarikh mula THEN entri SHALL dianggap satu aktiviti berbilang hari dan dipaparkan merentasi semua hari dalam julat tersebut.
5. WHEN masa mula dan masa tamat diisi THEN masa tamat SHALL mesti selepas masa mula.
6. WHEN pegawai sudah ada entri lain pada hari yang sama THEN sistem SHALL tetap membenarkan entri baharu (beberapa entri sehari dibenarkan).
7. WHEN entri baharu bertindih dengan entri jenis cuti bagi pegawai yang sama THEN sistem SHALL memaparkan amaran tetapi tetap membenarkan penyimpanan selepas pengesahan pengguna.
8. WHEN pegawai menyimpan entri untuk dirinya sendiri THEN sistem SHALL membenarkan tanpa kebenaran tambahan selain `pergerakan.create`.
9. WHEN pengguna cuba menyimpan entri bagi pegawai lain THEN sistem SHALL menolak kecuali pengguna memiliki `pergerakan.manageAny`; kawalan ini SHALL dikuatkuasakan pada peringkat simpan (bukan hanya pada paparan borang).
10. WHEN entri disimpan THEN sistem SHALL merekod `created_by`; WHEN entri dikemas kini THEN sistem SHALL merekod `updated_by` dan masa kemas kini.
11. WHEN entri dihapus THEN sistem SHALL melakukan soft delete dan entri SHALL hilang daripada jadual.
12. WHERE pegawai bukan ahli roster aktif THEN pemilih pegawai SHALL tidak menawarkan nama tersebut untuk entri baharu.

### Requirement 2A — Tempoh dibenarkan untuk edit dan hapus (D12)

**User Story:** Sebagai pemilik sistem, saya mahu entri yang tarikhnya sudah sampai tidak lagi
boleh diubah oleh pegawai sendiri, supaya rekod pergerakan yang sudah berlaku tidak diubah
selepas fakta.

#### Acceptance Criteria

1. WHEN pegawai cuba edit atau hapus entri **miliknya sendiri** DAN tarikh mula entri masih pada masa hadapan (`start_date > hari ini`) THEN sistem SHALL membenarkan tindakan tersebut.
2. WHEN pegawai cuba edit atau hapus entri miliknya sendiri DAN tarikh mula entri ialah hari ini atau sudah lepas THEN sistem SHALL menolak dan memaklumkan bahawa entri sudah terkunci dan perlu dirujuk kepada pentadbir helpdesk.
3. WHEN pemegang `pergerakan.manageAny` (termasuk `pengurusan`) cuba edit atau hapus entri pegawai lain DAN tarikh mula masih pada masa hadapan THEN sistem SHALL membenarkan.
4. WHEN mana-mana entri yang tarikh mulanya ialah hari ini atau sudah lepas hendak diedit atau dihapus THEN sistem SHALL membenarkan hanya pemegang `pergerakan.manageLocked` (lalai: `helpdesk.admin` dan `superadmin`).
5. WHEN entri terkunci dipaparkan kepada pengguna yang tidak boleh mengubahnya THEN butang edit/hapus SHALL tidak dipaparkan, DAN percubaan langsung ke tindakan tersebut SHALL ditolak pada peringkat server.
6. WHERE entri berbilang hari sudah bermula (tarikh mula lepas tetapi tarikh tamat belum) THEN entri SHALL dianggap terkunci bagi tujuan peraturan ini.

### Requirement 3 — Jadual bulanan (paparan utama)

**User Story:** Sebagai pegawai atau pengurusan, saya mahu melihat jadual bulanan unit
seperti dalam Google Sheet, supaya saya tahu pergerakan semua pegawai dalam satu paparan.

#### Acceptance Criteria

1. WHEN jadual bulanan dibuka THEN sistem SHALL memaparkan grid: baris = pegawai roster aktif (ikut susunan), lajur = setiap hari dalam bulan dipilih.
2. WHEN grid dipaparkan THEN setiap lajur SHALL menunjukkan nombor/tarikh hari dan nama hari dalam Bahasa Melayu (AHAD, ISNIN, SELASA, RABU, KHAMIS, JUMAAT, SABTU).
3. WHEN hari itu Sabtu atau Ahad THEN lajur SHALL ditandakan sebagai hujung minggu secara visual.
4. WHEN tarikh itu cuti umum yang direkod THEN lajur SHALL ditandakan dan nama cuti dipaparkan.
5. WHEN satu sel mempunyai lebih daripada satu entri THEN semua entri SHALL dipaparkan dalam sel tersebut.
6. WHEN entri berbilang hari dipaparkan THEN ia SHALL kelihatan pada setiap hari dalam julat, dengan petunjuk bahawa ia aktiviti berbilang hari.
7. WHEN pengguna menukar bulan/tahun THEN grid SHALL dimuat semula untuk tempoh tersebut tanpa meninggalkan halaman.
8. WHEN entri mempunyai jenis pergerakan THEN warna/lencana jenis SHALL digunakan supaya jenis dapat dibezakan secara pandangan.
9. WHEN pengguna mengklik satu sel atau entri THEN butiran penuh (tajuk, masa, lokasi, catatan, dicatat oleh) SHALL dipaparkan.
10. WHEN pengguna memiliki kebenaran mengisi bagi entri itu THEN tindakan tambah/edit SHALL boleh dicapai terus dari sel tersebut.
10a. WHEN pengguna tanpa `pergerakan.manageAny` menambah entri dari sel mana-mana baris THEN borang SHALL terisi dengan **pegawai dirinya sendiri**, bukan pegawai baris itu; tarikh sel tetap dihormati.
10b. WHERE pengguna memiliki `pergerakan.manageAny` THEN borang SHALL terisi dengan pegawai baris yang diklik.
11. WHEN jadual dibuka pada telefon THEN sistem SHALL memaparkan bentuk alternatif yang mudah dibaca (contoh senarai harian/agenda) tanpa limpahan mendatar yang menyusahkan.
12. WHERE pengguna tiada kebenaran `pergerakan.view` THEN halaman SHALL tidak boleh diakses.
13. WHEN jadual bulan semasa dibuka THEN grid SHALL menatal secara automatik supaya lajur tarikh hari ini kelihatan, dan WHEN bulan lain dipilih THEN grid SHALL kembali ke 1 haribulan.

### Requirement 10 — Widget ketersediaan pegawai pada dashboard

**User Story:** Sebagai pengurusan, saya mahu tahu pegawai mana yang berada di pejabat dan boleh
diurus kerja pada hari semasa, tanpa membuka jadual penuh.

Widget ini menjawab **ketersediaan**, bukan senarai pergerakan: nama yang dipaparkan ialah nama
pegawai yang boleh dicari, manakala yang keluar dan bercuti hanya dikira sebagai nombor.

#### Acceptance Criteria

1. WHEN dashboard dibuka oleh pemegang `pergerakan.view` THEN satu widget SHALL memaparkan ketersediaan pegawai unit berkenaan pada **tarikh dan masa semasa**.
2. WHEN widget dipaparkan THEN ia SHALL memaparkan empat jumlah: **Tersedia**, **Tidak Tersedia** (aktiviti dalam pejabat), **Luar Pejabat**, dan **Bercuti**.
3. WHEN senarai nama dipaparkan THEN ia SHALL mengandungi **hanya** pegawai yang tersedia; nama pegawai dalam tiga kategori lain SHALL tidak disenaraikan.
4. WHEN pegawai tersedia dipaparkan THEN statusnya SHALL ditandakan dengan indikator hijau dan label "Tersedia".
4a. WHEN widget dipaparkan THEN ia SHALL menggunakan komponen visual dashboard yang sedia ada (`panel`, `kpi-strip`, `kpi`, `stat-icon--*`) supaya ia sekata dengan kad dashboard lain.
5. WHEN widget dipaparkan THEN ia SHALL menyediakan butang "Lihat Jadual Bulanan" ke jadual penuh.
6. WHEN seorang pegawai TIADA rekod yang sedang berlangsung pada masa semasa THEN dia SHALL dikira tersedia, walaupun dia mempunyai rekod lain pada hari yang sama di luar waktu itu.
6a. WHEN satu entri mempunyai masa mula THEN ia SHALL menduduki pegawai hanya dari masa mula hingga masa tamat; WHEN masa tamat kosong THEN ia SHALL menduduki pegawai hingga akhir hari.
6b. WHEN satu entri tiada masa, atau merupakan entri berbilang hari, THEN ia SHALL menduduki pegawai sepanjang hari.
6c. WHEN pegawai tersedia tetapi mempunyai program kemudian pada hari yang sama THEN widget SHALL memaparkan masa program itu sebagai had tingkap yang terbuka (contoh "hingga 15:00").
6d. WHERE peraturan masa dikenakan THEN ia SHALL juga terpakai kepada entri cuti, supaya cuti separuh hari yang direkod dengan masa tidak menandakan pegawai bercuti sepanjang hari.
6e. WHEN dashboard dibiarkan terbuka THEN widget SHALL menyegarkan statusnya secara berkala (setiap 60 saat) supaya status tidak kekal lapuk selepas satu program tamat.
7. WHEN pegawai mempunyai rekod berjenis cuti pada tarikh semasa THEN dia SHALL dikira bercuti, walaupun dia juga mempunyai rekod aktiviti lain pada hari yang sama.
8. WHEN pegawai mempunyai rekod aktiviti bukan cuti bertanda **luar pejabat** THEN dia SHALL dikira Luar Pejabat; WHEN rekodnya hanya aktiviti **dalam pejabat** THEN dia SHALL dikira Tidak Tersedia.
8a. WHERE keutamaan status diperlukan THEN ia SHALL cuti > luar pejabat > dalam pejabat.
9. WHEN entri berbilang hari merangkumi tarikh semasa THEN pegawai itu SHALL dikira tidak tersedia pada hari tersebut.
10. WHEN rekod pegawai hanya pada hari lain (semalam atau esok) THEN status hari ini SHALL tidak terjejas.
11. WHERE pegawai bukan ahli roster aktif THEN dia SHALL tidak dikira dalam mana-mana jumlah.
12. WHEN hari itu cuti umum THEN nama cuti SHALL dipaparkan pada widget.
13. WHERE pengguna tiada `pergerakan.view` (contoh role `pengguna`) THEN widget SHALL tidak dipaparkan sama sekali.

### Requirement 4 — Senarai dan carian

**User Story:** Sebagai pengurusan, saya mahu menapis pergerakan, supaya saya boleh menjawab
soalan seperti "siapa keluar minggu ini" atau "semua kursus bulan ini".

#### Acceptance Criteria

1. WHEN halaman senarai dibuka THEN sistem SHALL memaparkan entri dalam bentuk jadual (pegawai, jenis, tajuk, tarikh, masa, lokasi) dengan paginasi.
2. WHEN penapis digunakan THEN sistem SHALL menapis mengikut tempoh tarikh, pegawai, unit dan jenis pergerakan.
3. WHEN kata carian dimasukkan THEN sistem SHALL mencari pada tajuk, lokasi dan catatan.
4. WHEN tiada rekod sepadan THEN sistem SHALL memaparkan keadaan kosong yang jelas.

### Requirement 5 — Jenis pergerakan dan cuti umum (rujukan)

**User Story:** Sebagai admin sistem, saya mahu menyelenggara jenis pergerakan dan senarai
cuti umum, supaya jadual kekal betul tanpa perubahan kod.

#### Acceptance Criteria

1. WHEN sistem dipasang THEN jenis pergerakan asas SHALL disemai: Mesyuarat, Kerja Luar, Bengkel, Kursus/Latihan, Cuti Rehat, Cuti Sakit, Tugas Rasmi Luar Negara, Lain-lain.
2. WHEN admin menyelenggara jenis THEN sistem SHALL membenarkan tambah/edit nama, warna lencana, penanda "jenis cuti", dan aktif/tidak aktif.
3. WHEN satu jenis dinyahaktifkan THEN ia SHALL tidak lagi ditawarkan untuk entri baharu tetapi entri lama SHALL kekal memaparkan jenis tersebut.
4. WHEN admin merekod cuti umum THEN sistem SHALL menerima tarikh (atau julat tarikh) dan nama cuti, dan tarikh itu SHALL ditandakan dalam jadual bulanan.
4a. WHEN cuti umum dengan nama yang sama sudah direkod pada tarikh yang bertindih (dalam skop unit yang sama, atau mana-mana satu daripadanya meliputi semua unit) THEN sistem SHALL menolak penyimpanan dan menyatakan tarikh rekod yang sudah ada; perbandingan nama SHALL mengabaikan huruf besar/kecil dan ruang di hujung.
4b. WHERE dua cuti dengan nama berbeza jatuh pada tarikh yang sama THEN sistem SHALL membenarkannya.
5. WHERE pengguna tiada `pergerakan.manageRoster` THEN penyelenggaraan rujukan ini SHALL tidak boleh diakses.

### Requirement 6 — Kebenaran dan akses

**User Story:** Sebagai pemilik sistem, saya mahu akses modul ini mengikut peranan,
supaya data pergerakan tidak terbuka kepada semua warga kementerian.

#### Acceptance Criteria

1. WHEN modul dipasang THEN kebenaran baharu SHALL diwujudkan: `pergerakan.view`, `pergerakan.create`, `pergerakan.manageAny`, `pergerakan.manageLocked`, `pergerakan.manageRoster` (gaya nama `area.camelCaseAction` seperti kebenaran sedia ada), setiap satu dengan lajur `desc` Bahasa Melayu.
2. WHEN pangkalan data sedia ada dinaik taraf THEN satu migration SHALL mewujudkan kebenaran tersebut dan mengaitkannya kepada peranan yang betul, dan SHALL memanggil `forgetCachedPermissions()` pada akhirnya.
3. WHEN pemasangan baharu dijalankan THEN `PermissionSeeder` SHALL menggunakan `firstOrCreate` untuk kebenaran baharu ini supaya tiada ralat `PermissionAlreadyExists` semasa ujian/semaian.
4. WHEN peranan dipetakan THEN lalai SHALL: `jtk`/`pptm`/`ptm` → `pergerakan.view` + `pergerakan.create`; `pengurusan` → `pergerakan.view` + `pergerakan.create` + `pergerakan.manageAny`; `helpdesk.admin` → semua lima; `superadmin` → semua (automatik); `pengguna` → tiada.
4a. WHERE `pergerakan.manageLocked` diberi THEN ia SHALL terhad kepada `helpdesk.admin` dan `superadmin` sahaja — `pengurusan` boleh mengisi untuk pegawai lain tetapi tidak boleh mengubah entri yang sudah terkunci (Req 2A.4).
5. WHEN pengguna tiada sebarang kebenaran `pergerakan.*` THEN menu Pergerakan SHALL tidak dipaparkan dan laluannya SHALL ditolak (403).
6. WHEN laluan modul didaftarkan THEN ia SHALL berada dalam kumpulan middleware `auth`, `audit-trail`, `ensure-phone` yang sama seperti modul lain.

### Requirement 7 — Cetak dan eksport

**User Story:** Sebagai urus setia, saya mahu mencetak atau memuat turun jadual bulanan,
supaya ia boleh dihantar/dilampirkan seperti amalan sekarang.

#### Acceptance Criteria

1. WHEN pengguna memilih cetak THEN sistem SHALL membuka paparan jadual bulanan yang sesuai dicetak (landskap, tanpa menu/sidebar, dengan tajuk unit, bulan dan tahun).
2. WHEN pengguna memuat turun CSV THEN sistem SHALL menghasilkan fail mengandungi entri bulan dipilih (pegawai, jenis, tajuk, tarikh mula, tarikh tamat, masa, lokasi, catatan).
2a. WHEN eksport CSV dilaksanakan THEN ia SHALL menjadi tindakan komponen Livewire dan BUKAN laluan baharu, mengikut konvensyen eksport Laporan_Keseluruhan yang dikuatkuasakan oleh ujian sedia ada.
3. WHEN eksport dibuat THEN tiada pakej Composer baharu SHALL diperlukan.
4. WHERE pengguna tiada `pergerakan.view` THEN cetak/eksport SHALL ditolak.

### Requirement 8 — Jejak dan integriti data

**User Story:** Sebagai pemilik sistem, saya mahu tahu siapa mengubah apa,
supaya jadual boleh dipercayai.

#### Acceptance Criteria

1. WHEN entri dicipta/dikemas kini/dihapus THEN sistem SHALL menyimpan pengguna bertanggungjawab dan cap masa.
2. WHEN entri dipaparkan dalam butiran THEN "dicatat oleh" dan "dikemas kini oleh" SHALL dapat dilihat oleh pemegang `pergerakan.manageAny`.
3. WHEN pegawai roster dipadam daripada senarai pengguna sistem (soft delete) THEN jadual lepas SHALL kekal memaparkan nama paparan yang disimpan, tanpa ralat.

### Requirement 9 — Kualiti dan ujian

#### Acceptance Criteria

1. WHEN modul siap THEN ujian ciri (feature tests) SHALL meliputi: kebenaran/akses laluan, pengesahan borang (julat tarikh, masa), simpan untuk diri sendiri vs pegawai lain, **tempoh dibenarkan edit/hapus (Req 2A: masa hadapan dibenarkan, hari ini/lepas ditolak kecuali `pergerakan.manageLocked`)**, pembinaan grid bulanan (termasuk entri berbilang hari dan beberapa entri sehari), dan eksport CSV.
2. WHEN suite dijalankan THEN `vendor/bin/phpunit --display-all-issues` SHALL lulus sepenuhnya tanpa isu runner baharu.
3. WHEN ujian ditulis THEN ia SHALL menggunakan `Tests\FeatureTestCase` dan SQLite dalam memori seperti ujian sedia ada.
