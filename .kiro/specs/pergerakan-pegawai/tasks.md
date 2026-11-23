# Tasks — Modul Pergerakan Pegawai (draf, menunggu kelulusan)

Urutan ini direka untuk dikerjakan **satu persatu**: setiap task berakhir dengan ujian lulus,
arahan migration untuk dijalankan sendiri, dan senarai pendek apa yang perlu diuji di skrin.
Kerja ditinggalkan **tanpa commit** untuk pengujian anda dahulu.

---

- [x] **1. Skema, model dan kebenaran** — siap, menunggu ujian pengguna (belum commit)
  - Migration: `movement_units`, `monitored_officers`, `movement_types`, `movements`, `movement_holidays`
  - Model: `MovementUnit`, `MonitoredOfficer`, `Movement`, `Ref\MovementType`, `MovementHoliday` + hubungan
  - Migration kebenaran (5 kebenaran: `view`, `create`, `manageAny`, `manageLocked`, `manageRoster` + kaitan peranan + `forgetCachedPermissions()`)
  - `PermissionSeeder` (`firstOrCreate`) + `RoleSeeder` dikemas kini
  - `MovementUnitSeeder`, `MovementTypeSeeder`, didaftarkan dalam `DatabaseSeeder`
  - Factory untuk ujian
  - _Requirements: 1, 5.1, 6_
  - _Ujian: skema terbina, kebenaran wujud, pemetaan peranan betul_

- [x] **2. Pegawai Dipantau (roster)** — siap, menunggu ujian pengguna (belum commit)
  - `Pergerakan\RosterIndex` (senarai, tambah dari `users`, edit nama paparan/susunan, nyahaktif)
  - Laluan + page wrapper + seksyen menu "Pergerakan" dalam navbar
  - _Requirements: 1, 6.5, 6.6_
  - _Ujian: `MovementRosterTest`_

- [x] **3. Jenis pergerakan + cuti umum** — siap, menunggu ujian pengguna (belum commit)
  - `Pergerakan\MovementTypeIndex`: CRUD jenis (nama, warna, `is_leave`, aktif) dan senarai cuti umum
  - _Requirements: 5_
  - _Ujian: `MovementTypeTest`_

- [x] **4. Borang rekod pergerakan + kawalan edit/hapus** — siap, menunggu ujian pengguna (belum commit)
  - `Pergerakan\MovementForm` (modal) + `Actions\Movements\SaveMovement`
  - `MovementPolicy` (tempoh edit/hapus) didaftarkan dalam `AuthServiceProvider`
  - `Pergerakan\MyMovements` + halaman "Pergerakan Saya" sebagai tempat borang itu digunakan
  - Pengesahan julat tarikh/masa, amaran tindih cuti, kuatkuasa `pergerakan.manageAny`, `created_by`/`updated_by`, soft delete
  - _Requirements: 2, 2A, 8_
  - _Ujian: `MovementFormTest`, `MovementPolicyTest`_

- [x] **5. Jadual bulanan (grid)** — siap, menunggu ujian pengguna (belum commit)
  - `Support\MovementCalendar` + `Pergerakan\MovementSchedule` + view grid
  - Pemilih bulan/tahun/unit, tanda hujung minggu + cuti umum, lencana jenis, entri berbilang hari, beberapa entri sehari, klik → butiran/edit
  - Paparan agenda untuk telefon
  - _Requirements: 3_
  - _Ujian: `MovementCalendarTest`_

- [x] **6. Senarai dan carian** — siap, menunggu ujian pengguna (belum commit)
  - `Pergerakan\MovementIndex`: jadual + penapis (tempoh, pegawai, unit, jenis, saya sahaja) + carian + paginasi
  - _Requirements: 4_
  - _Ujian: `MovementListTest`_

- [x] **7. Cetak dan eksport CSV** — siap, menunggu ujian pengguna (belum commit)
  - View cetak (landskap, tanpa sidebar) pada laluan `pergerakan.print`
  - `Support\MovementCsvExporter` + `MovementSchedule::exportCsv()` sebagai **tindakan komponen**,
    bukan laluan — konvensyen repo yang dijaga oleh `ReportAllExportTest`
  - _Requirements: 7_
  - _Ujian: `MovementExportTest`_

- [x] **8. Widget ketersediaan pegawai pada dashboard** — siap, menunggu ujian pengguna (belum commit)
  - `Pergerakan\TodayWidget`: tiga jumlah (di pejabat / bercuti / bertugas luar), senarai nama
    pegawai available sahaja dengan indikator hijau, butang Lihat Jadual Bulanan;
    dipasang pada Dashboard Pengurusan dan Dashboard Pegawai (dipagar `pergerakan.view`)
  - Jadual bulanan menatal automatik ke lajur tarikh hari ini apabila dibuka
  - _Requirements: 3.13, 10_
  - _Ujian: `MovementWidgetTest`_

---

## Tidak termasuk

- Import data jadual 2026 dari Google Sheet — key-in manual sahaja (D8)
- Peringatan e-mel pergerakan harian/mingguan (D10)
- Aliran kelulusan dalam sistem (D5)
- ICS feed untuk langganan kalendar (tiada permintaan)
