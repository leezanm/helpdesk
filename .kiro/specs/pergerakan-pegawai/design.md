# Design — Modul Pergerakan Pegawai

## Gambaran keseluruhan

Modul baharu `Pergerakan` dalam Sistem Meja Bantuan KKDW yang menggantikan Google Sheet
"Jadual Pergerakan Unit Operasi dan Bantuan Teknikal, BPM". Ia mengikut corak sedia ada:

- Skrin interaktif = **Livewire 3** (`app/Livewire/Pergerakan/*`)
- Logik simpan = **Action** (`app/Actions/Movements/*`)
- Jadual rujukan = `app/Models/Ref/*`
- Controller **nipis** (hanya render page wrapper yang mount komponen Livewire)
- Kebenaran = **spatie/laravel-permission**, dikuatkuasakan pada laluan (`can:`) dan sekali lagi
  dalam Action (corak sama seperti `aduan.representAny` dalam `ReportForm`)
- Tiada pakej Composer baharu

Model mental: **satu entri = satu aktiviti dengan julat tarikh**, bukan satu sel per hari.
Grid bulanan dibina dengan "menebarkan" (fan out) entri ke atas hari-hari dalam julatnya.
Ini lebih baik daripada menyimpan satu baris per hari kerana aktiviti berbilang hari dalam
sheet sekarang (`18 – 19 November`, `2 - 6 November`) kekal satu rekod yang boleh diedit sekali.

## Model data

### `movement_units` — unit/kumpulan jadual (D1)

| Lajur | Jenis | Nota |
|-------|-------|------|
| id | id | |
| name | string | contoh: "Unit Operasi dan Bantuan Teknikal" |
| short_name | string nullable | contoh: "UOBT, BPM" |
| is_active | boolean default true | |
| timestamps / softDeletes | | |

Satu baris disemai untuk unit semasa. Struktur ini membenarkan bahagian lain guna modul yang sama
nanti tanpa migration baharu; UI fasa 1 hanya menapis ikut unit (dropdown muncul bila >1 unit aktif).

### `monitored_officers` — roster pegawai dipantau (Req 1)

| Lajur | Jenis | Nota |
|-------|-------|------|
| id | id | |
| movement_unit_id | foreignId → movement_units | |
| user_id | foreignId → users | pegawai mesti pengguna sistem (D2) |
| display_name | string | snapshot nama paparan ("En. Syarul Emy bin Abu Samah") |
| sort_order | unsignedInteger default 0 | lajur BIL dalam sheet |
| is_active | boolean default true | nyahaktif, bukan hapus (Req 1.4) |
| timestamps / softDeletes | | |

Unik: `(movement_unit_id, user_id)` — menguatkuasakan Req 1.3 pada peringkat pangkalan data.
`display_name` disimpan supaya jadual lama kekal betul walaupun pengguna di-soft-delete (Req 8.3);
ia diisi lalai daripada `users.name` dan boleh ditulis semula untuk gelaran En./Pn.

### `movement_types` — jenis pergerakan (Req 5)

| Lajur | Jenis | Nota |
|-------|-------|------|
| id | id | |
| name | string | Mesyuarat, Kerja Luar, Bengkel, Kursus/Latihan, Cuti Rehat, … |
| color | string nullable | kelas/kod warna lencana untuk grid |
| is_leave | boolean default false | penanda jenis cuti → asas amaran Req 2.7 |
| is_active | boolean default true | |
| sort_order | unsignedInteger default 0 | |
| timestamps | | |

Model: `App\Models\Ref\MovementType` (sejajar `Ref\RiskLevel`, `Ref\ReportStatus`).

### `movements` — entri pergerakan (Req 2)

| Lajur | Jenis | Nota |
|-------|-------|------|
| id | id | |
| monitored_officer_id | foreignId → monitored_officers | pemilik entri |
| movement_type_id | foreignId → movement_types | |
| title | string | Tajuk |
| start_date | date | |
| end_date | date | sama dengan start_date untuk entri satu hari |
| start_time | time nullable | |
| end_time | time nullable | |
| location | string nullable | Lokasi/Tempat |
| remarks | text nullable | Catatan |
| created_by / updated_by | foreignId → users nullable | Req 8 |
| timestamps / softDeletes | | Req 2.11 |

Indeks: `(monitored_officer_id, start_date, end_date)` dan `(start_date, end_date)` untuk
pertanyaan grid bulanan.

### `movement_holidays` — cuti umum (D7, Req 5.4)

| Lajur | Jenis | Nota |
|-------|-------|------|
| id | id | |
| name | string | contoh "Deepavali" |
| start_date / end_date | date | julat untuk cuti berbilang hari |
| movement_unit_id | foreignId nullable | null = semua unit |
| timestamps | | |

Cuti umum ditanda sekali untuk semua pegawai (bukan satu entri per pegawai seperti sheet sekarang).

### Hubungan

```
MovementUnit 1─n MonitoredOfficer n─1 User
MonitoredOfficer 1─n Movement n─1 Ref\MovementType
MovementUnit 1─n MovementHoliday (nullable = global)
```

## Pertanyaan dan pembinaan grid

`App\Support\MovementCalendar` (kelas biasa, boleh diuji tanpa HTTP):

```php
new MovementCalendar(MovementUnit $unit, int $year, int $month)
    ->days()      // senarai hari: tarikh, nama hari BM, is_weekend, holiday?
    ->weeks()     // kumpulan minggu untuk tajuk "MINGGU PERTAMA" / "HUJUNG MINGGU"
    ->rows()      // satu baris per MonitoredOfficer aktif, ikut sort_order
                  // setiap baris: ['officer' => …, 'cells' => [ 'Y-m-d' => Movement[] ]]
```

Pertanyaan tunggal untuk keseluruhan bulan (elak N+1):

```php
Movement::query()
    ->with(['type', 'officer'])
    ->whereHas('officer', fn ($q) => $q->where('movement_unit_id', $unit->id))
    ->where('start_date', '<=', $monthEnd)
    ->where('end_date', '>=', $monthStart)   // julat bertindih dengan bulan
    ->get();
```

Entri kemudian ditebarkan: untuk setiap hari antara `max(start_date, monthStart)` dan
`min(end_date, monthEnd)` entri itu dimasukkan ke dalam sel hari tersebut, ditanda
`is_multi_day` supaya UI boleh tunjuk penyambung (contoh lencana "18–19 Nov").

Nama hari BM: peta statik (`['AHAD','ISNIN','SELASA','RABU','KHAMIS','JUMAAT','SABTU']`)
berdasarkan `Carbon::dayOfWeek` — tidak bergantung pada ext-intl walaupun `config/app.php`
sudah set `locale = ms`.

## Komponen Livewire dan skrin

| Komponen | Fail | Peranan |
|----------|------|---------|
| `Pergerakan\MovementCalendar` | `app/Livewire/Pergerakan/MovementCalendar.php` | Grid bulanan (Req 3): pemilih bulan/tahun/unit, buka modal entri |
| `Pergerakan\MovementForm` | `app/Livewire/Pergerakan/MovementForm.php` | Borang modal cipta/edit entri (Req 2) |
| `Pergerakan\MovementIndex` | `app/Livewire/Pergerakan/MovementIndex.php` | Senarai + penapis + carian + paginasi (Req 4) |
| `Pergerakan\RosterIndex` | `app/Livewire/Pergerakan/RosterIndex.php` | CRUD pegawai dipantau (Req 1) |
| `Pergerakan\MovementTypeIndex` | `app/Livewire/Pergerakan/MovementTypeIndex.php` | Jenis pergerakan + cuti umum (Req 5) |

View: `resources/views/livewire/pergerakan/*.blade.php`, page wrapper
`resources/views/pergerakan/*.blade.php`, paparan cetak
`resources/views/pergerakan/print.blade.php` (layout minimal, landskap, `@media print`).

Mudah alih (Req 3.11): grid desktop dalam pembalut `table-responsive`; di bawah breakpoint `md`
komponen memaparkan **paparan agenda** — senarai per hari (hari → pegawai → entri) menggunakan
data `MovementCalendar` yang sama, jadi tiada logik kedua.

## Action

`App\Actions\Movements\SaveMovement`

```php
public function handle(array $data, ?Movement $movement = null): Movement
```

Tanggungjawab:
1. **Kuatkuasa kebenaran**: jika `monitored_officer_id` bukan milik pengguna semasa, pengguna
   mesti ada `pergerakan.manageAny`, jika tidak `AuthorizationException` (Req 2.9 — semakan kedua,
   bukan hanya di UI; corak sama seperti `ReportForm::save()`).
   Untuk kemas kini, kebenaran disemak melalui `MovementPolicy::update` (Req 2A) supaya peraturan
   entri terkunci dikuatkuasakan di server, bukan hanya dengan menyembunyikan butang.
2. Pastikan pegawai sasaran ahli roster **aktif** (Req 2.12).
3. Set `created_by` / `updated_by`.
4. Simpan dalam transaksi.

Pengesahan julat tarikh/masa (Req 2.2, 2.5) tinggal dalam peraturan `rules()` komponen Livewire
(`end_date >= start_date` guna `after_or_equal`, `end_time` guna `after`), kerana di sanalah mesej
ralat per-medan dipaparkan. `SaveMovement` menyemak semula invarian julat sebagai jaring keselamatan.

Pengesanan tindih cuti (Req 2.7) ialah **amaran**, bukan pengesahan: komponen menanya pertindihan
dengan entri `is_leave`, memaparkan amaran, dan hanya menyimpan pada hantaran kedua
(`$confirmedOverlap = true`).

## Kawalan edit/hapus — `MovementPolicy` (Req 2A)

Satu tempat sahaja memutuskan "boleh ubah atau tidak", didaftarkan dalam `AuthServiceProvider`
bersama `ReportPolicy` yang sudah ada, dan digunakan oleh view (`@can`), komponen Livewire
(`$this->authorize(...)`) dan `SaveMovement`.

```php
public function update(User $user, Movement $movement): bool
{
    // Entri terkunci sebaik tarikh mula sampai: hari ini pun sudah terkunci.
    $locked = $movement->start_date->isToday() || $movement->start_date->isPast();

    if ($locked) {
        return $user->can('pergerakan.manageLocked');
    }

    return $movement->officer->user_id === $user->id
        ? $user->can('pergerakan.create')
        : $user->can('pergerakan.manageAny');
}

public function delete(User $user, Movement $movement): bool
{
    return $this->update($user, $movement);
}
```

- "Sehari sebelum tarikh" ialah had terakhir: `start_date > hari ini` sahaja yang terbuka.
- Entri berbilang hari yang sudah bermula dikira terkunci kerana `start_date` sudah lepas
  (Req 2A.6) — tiada syarat tambahan diperlukan.
- `start_date` dicast kepada `date` dalam model `Movement` supaya `isToday()`/`isPast()` sah,
  dan perbandingan dibuat pada peringkat tarikh (bukan masa) menggunakan zon waktu aplikasi.
- UI: butang edit/hapus hanya dipaparkan apabila polisi lulus; sel yang terkunci memaparkan
  lencana kunci kecil dengan tooltip "Rujuk pentadbir helpdesk untuk kemaskini".

## Laluan

Dalam kumpulan `['auth','audit-trail','ensure-phone']` yang ada dalam `routes/web.php`:

```php
Route::prefix('pergerakan')->as('pergerakan.')->group(function () {
    Route::get('/', [MovementController::class, 'calendar'])->name('calendar')
        ->middleware('can:pergerakan.view');
    Route::get('/senarai', [MovementController::class, 'index'])->name('index')
        ->middleware('can:pergerakan.view');
    Route::get('/cetak', [MovementController::class, 'print'])->name('print')
        ->middleware('can:pergerakan.view');
    Route::get('/eksport.csv', [MovementController::class, 'exportCsv'])->name('export-csv')
        ->middleware('can:pergerakan.view');
    Route::get('/pegawai-dipantau', [MovementController::class, 'roster'])->name('roster')
        ->middleware('can:pergerakan.manageRoster');
    Route::get('/jenis', [MovementController::class, 'types'])->name('types')
        ->middleware('can:pergerakan.manageRoster');
});
```

`App\Http\Controllers\MovementController` nipis: `calendar/index/roster/types` hanya `return view(...)`.
`print` memulangkan view cetak yang menerima bulan/tahun/unit melalui query string yang disahkan.
`exportCsv` guna `response()->streamDownload()` dengan `fputcsv` (tiada pakej baharu, D9/Req 7.3).

## Kebenaran (Req 6)

Empat kebenaran baharu, gaya `area.camelCaseAction`, dengan lajur `desc` Bahasa Melayu:

| Nama | desc |
|------|------|
| `pergerakan.view` | Lihat jadual pergerakan |
| `pergerakan.create` | Catat pergerakan sendiri |
| `pergerakan.manageAny` | Catat pergerakan semua pegawai |
| `pergerakan.manageLocked` | Kemaskini pergerakan yang sudah terkunci |
| `pergerakan.manageRoster` | Selenggara pegawai dipantau & jenis pergerakan |

Pemetaan peranan lalai:

| Peranan | view | create | manageAny | manageLocked | manageRoster |
|---------|:----:|:------:|:---------:|:------------:|:------------:|
| `jtk`, `pptm`, `ptm` | ✓ | ✓ | | | |
| `pengurusan` | ✓ | ✓ | ✓ | | |
| `helpdesk.admin` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `superadmin` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `pengguna` | | | | | |

`manageAny` (isi/ubah untuk pegawai lain) sengaja dipisahkan daripada `manageLocked`
(ubah entri yang tarikhnya sudah sampai). `pengurusan` mendapat yang pertama tetapi bukan yang
kedua, menepati keputusan bahawa hanya pentadbir helpdesk boleh mengubah rekod yang sudah berlaku.
Pemisahan ini mengikut corak `aduan.representAny` yang juga tidak menggunakan semula
`aduan.adminTask`.

Tiga perangkap yang dikendalikan (sudah pernah berlaku dalam repo ini):

1. `database/seeders/PermissionSeeder.php` guna `Permission::create()` dan **tidak** idempoten →
   empat kebenaran baharu guna `firstOrCreate` (Req 6.3).
2. Pangkalan data sedia ada perlu **migration** untuk mewujudkan kebenaran + mengaitkannya kepada
   peranan (semai semula bukan pilihan).
3. Migration itu juga berjalan pada pemasangan baharu **sebelum** seeder → sebab itu `firstOrCreate`.
   Migration tamat dengan `app(PermissionRegistrar::class)->forgetCachedPermissions()`.

`RoleSeeder` dikemas kini guna `syncPermissions` seperti sedia ada. Satu migration berasingan
menambah kebenaran ke peranan untuk DB sedia ada.

## Navigasi

Seksyen baharu dalam `resources/views/layouts/navbar.blade.php`, selepas seksyen Aduan,
mengikut corak `@canany`:

```
Pergerakan                          @canany(['pergerakan.view','pergerakan.create'])
  ├── Jadual Bulanan                @can('pergerakan.view')
  ├── Senarai Pergerakan            @can('pergerakan.view')
  └── (Penyelenggaraan)
      ├── Pegawai Dipantau          @can('pergerakan.manageRoster')
      └── Jenis Pergerakan          @can('pergerakan.manageRoster')
```

"Pergerakan Saya" tidak jadi halaman berasingan: pengguna menambah entri sendiri terus dari
grid bulanan atau senarai, dan penapis "pegawai = saya" tersedia dalam senarai.

## Semaian (seeders)

- `MovementUnitSeeder` — satu unit: "Unit Operasi dan Bantuan Teknikal" (BPM).
- `MovementTypeSeeder` — Mesyuarat, Kerja Luar, Bengkel, Kursus/Latihan, Perbincangan,
  Cuti Rehat (`is_leave`), Cuti Sakit (`is_leave`), Cuti Tanpa Rekod (`is_leave`), Lain-lain.
- Roster **tidak** disemai dengan nama sebenar: urus setia memasukkannya melalui UI (D8),
  jadi tiada nama pegawai ditanam dalam kod/repo.

Kedua-dua seeder guna `firstOrCreate` dan didaftarkan dalam `DatabaseSeeder`.

## Ujian (Req 9)

`tests/Feature/Pergerakan/`:

| Fail | Liputan |
|------|---------|
| `MovementAccessTest.php` | setiap laluan: 403 tanpa kebenaran, 200 dengan kebenaran; menu tidak dipaparkan |
| `MovementFormTest.php` | pengesahan julat tarikh/masa, simpan diri sendiri, simpan untuk pegawai lain ditolak tanpa `manageAny`, diterima dengan `manageAny`, pegawai roster tidak aktif ditolak |
| `MovementPolicyTest.php` | tempoh edit/hapus: entri masa hadapan milik sendiri ✓, entri hari ini/lepas ✗, `pengurusan` pada entri orang lain masa hadapan ✓ tetapi entri terkunci ✗, `helpdesk.admin` pada entri terkunci ✓, entri berbilang hari yang sudah bermula terkunci |
| `MovementCalendarTest.php` | grid: susunan baris, bilangan hari, penandaan hujung minggu/cuti umum, entri berbilang hari muncul pada setiap hari, beberapa entri dalam satu sel |
| `MovementRosterTest.php` | tambah/edit/nyahaktif, pendua ditolak, susunan diikut |
| `MovementExportTest.php` | muat turun CSV: header, baris, kebenaran |

Factory baharu: `MovementUnitFactory`, `MonitoredOfficerFactory`, `MovementFactory`.
Semua ujian guna `Tests\FeatureTestCase` (SQLite dalam memori; ia sudah semai
`PermissionSeeder` + `RoleSeeder`), dan jenis pergerakan disemai dalam ujian yang memerlukannya.

## Keputusan reka bentuk dan alternatif

| Keputusan | Alternatif ditolak | Sebab |
|-----------|--------------------|-------|
| Satu rekod dengan `start_date`/`end_date` | Satu rekod per hari | Aktiviti berbilang hari dalam sheet ialah satu aktiviti; satu rekod = satu kali edit/hapus, dan grid mudah ditebarkan semasa paparan |
| `monitored_officers` berasingan daripada `users` | Tanda `is_monitored` pada `users` | Pemantauan terpilih + susunan BIL + nama paparan + keahlian unit tidak sesuai dimasukkan ke dalam jadual `users` yang dikongsi seluruh sistem |
| `display_name` snapshot | Selalu baca `users.name` | Jadual bulan lepas kekal betul walaupun pengguna dipadam/bertukar nama (Req 8.3) |
| Cuti sebagai `movement_type` | Modul cuti berasingan / integrasi HRMIS | Sheet sekarang pun merekod cuti dalam sel yang sama; integrasi HRMIS di luar skop (D6) |
| Cuti umum dalam jadual sendiri | Entri pergerakan per pegawai | Satu rekod untuk semua pegawai; jadual menandakan lajur sekali (D7) |
| CSV + cetak pelayar | dompdf / maatwebsite-excel | Tiada pakej baharu untuk diselenggara; cukup untuk keperluan hantar/lampir (D9) |
| Tiada aliran kelulusan | Draf → hantar → sah | Aktiviti hanya dimasukkan ke dalam jadual selepas ia diluluskan di luar sistem, jadi kelulusan dalam sistem hanya menduplikasi proses (D5) |
| Kunci berasaskan `start_date > hari ini` + `MovementPolicy` | Medan `is_locked` / kerja berjadual yang mengunci entri | Tiada keadaan tersimpan untuk menjadi lapuk; peraturan dikira daripada tarikh, satu tempat sahaja, mudah diuji (D12) |
| `manageLocked` berasingan daripada `manageAny` | Guna semula `manageAny` untuk kedua-duanya | `pengurusan` perlu boleh mengisi untuk pegawai lain tanpa kuasa mengubah rekod yang sudah berlaku |

## Luar skop

Disahkan **tidak** diperlukan:

- Peringatan e-mel / notifikasi harian pergerakan (D10)
- Import data Google Sheet 2026 — key-in manual sahaja (D8)
- Aliran kelulusan dalam sistem: aktiviti dimasukkan selepas diluluskan di luar sistem (D5)

Tidak dirancang buat masa ini:

- Integrasi kalendar (ICS feed / Outlook / Google Calendar)
- Widget "Pergerakan Hari Ini" pada dashboard (calon fasa 2 yang murah)
