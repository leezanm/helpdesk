# 06 — Staging Environment (task 1.3) — LIVE

A Docker-based staging environment has been built and is running. It mirrors the upgrade target
runtime (**PHP 8.3 + MySQL 8.0**) with a full copy of production data, and is completely isolated
from the production `helpdesk` database.

## What was created

| File | Purpose |
|------|---------|
| `docker-compose.staging.yml` | Staging stack: `staging-app` (PHP 8.3) + `staging-mysql` (MySQL 8.0), own network/volume/ports |
| `docker/staging/Dockerfile` | PHP 8.3 image with pdo_mysql, ldap, gd, zip extensions + Composer |
| `.env.staging` | Staging config (git-ignored). Points at staging DB, mail=log, LDAP disabled, APP_DEBUG=true |
| `scripts/staging-load-db.sh` | Loads the pre-upgrade dump into the staging DB |

Isolation choices:
- Separate database name: **helpdesk_staging**
- Non-default host ports: app on **8088**, MySQL on **33061** (avoids clashing with prod 3306 / Sail)
- Its own Docker volume `staging_mysql_data` — safe to wipe and rebuild
- No real mail or LDAP credentials (staging can't email users or hit the real AD)

## How to use it

```bash
# Start (build first time)
docker compose -f docker-compose.staging.yml up -d --build

# First-time setup: generate app key + load a data copy
docker compose -f docker-compose.staging.yml exec staging-app php artisan key:generate --env=staging
docker compose -f docker-compose.staging.yml up -d --force-recreate staging-app   # reload env
./scripts/staging-load-db.sh

# App:  http://localhost:8088
# MySQL: localhost:33061  (user root / pass staging_root, db helpdesk_staging)

# Stop (keep data)
docker compose -f docker-compose.staging.yml stop
# Tear down completely (wipe data)
docker compose -f docker-compose.staging.yml down -v
```

Note: `.env.staging` is loaded into the container via `env_file`. After changing `APP_KEY` or other
vars, recreate the app container (`up -d --force-recreate staging-app`) — a plain `restart` does not
reload `env_file`.

## Verified working

- PHP **8.3.26**, MySQL **8.0**, extensions pdo_mysql/ldap/gd/zip present.
- Data copy loaded: **21 tables, 318 users, 2161 reports**.
- App responds: `/` → 302 redirect to `/login`, `/login` → 200.

## MySQL 8 `ONLY_FULL_GROUP_BY` check (feeds task 2.3) — RESULT

`ONLY_FULL_GROUP_BY` is **active** in the staging MySQL 8 (confirmed via `@@sql_mode`).

The laporan/dashboard aggregate queries flagged during code review were tested against real
MySQL 8 with production-shaped data:

| Query pattern | Location | Result on MySQL 8 |
|---------------|----------|-------------------|
| `leftJoinSub` status counts | `viewByStatus` | PASS |
| `select name,username + count`, group by both | `viewByUser` | PASS |
| `distinct(ldap_bahagian) as bahagian`, group by alias | `viewByDepartment` | PASS |

**Finding:** these queries are compliant — every non-aggregate selected column appears in the
GROUP BY. The MySQL-8 strict-mode risk flagged earlier does **not** materialize for the current
laporan queries. Task 2.3 should still re-run the full laporan/dashboard suite after the framework
upgrade (query builder behavior can shift between Laravel majors), but no rewrites appear necessary
based on this baseline test.

---

## Task 7 — Livewire introduced (Laravel 12)

Livewire 3.8.6 installed and wired into the existing Blade layout:
- `@vite(['resources/css/app.css','resources/js/app.js'])` + `@livewireStyles` in `<head>`
- `@livewireScripts` before `</body>` (after jQuery/theme scripts)

Verified coexistence (rendered HTML of a real authenticated page contains all layers):
Livewire component + `wire:snapshot` hydration, `@livewireScripts`, Vite `/build/` assets,
Alpine (`x-data`), the Bootstrap theme CSS, and jQuery — all present together.

A permanent minimal Livewire smoke test lives at `tests/Feature/Livewire/HealthCheckTest.php`
(proves `wire:click` server reactivity). The throwaway route/page wrapper were removed.

### Staging server change

The app container now runs `php artisan serve` (was `php -S ... -t public`). The plain
built-in server treated Livewire paths like `/livewire/livewire.js` as missing static files
(404). `artisan serve` routes non-file paths through `index.php` while still serving real
static assets, so Livewire endpoints, Vite `/build/` assets, and the theme's `/assets/**`
all serve correctly.

### Task 7.3 — audit-trail behavior under Livewire (documented decision)

Livewire's `livewire/update` XHR endpoint runs through only the **`web`** middleware group,
NOT the app's `audit-trail` route middleware. Consequence:
- **Page loads** (GET, in the `auth`+`audit-trail` group) are still audited — unchanged.
- **In-component interactions** (Livewire updates) are NOT audited — no audit row per click/keystroke.

This is the intended default and is sensible (avoids flooding the audit trail). If a specific
Livewire action needs auditing later (e.g. "report status changed"), log it explicitly inside
that action/component rather than blanket-logging every update.

---

## Tahap Risiko Aduan + runbook demo

### Ciri yang ditambah

Setiap aduan kini mempunyai **tahap risiko** (1 Sangat Rendah hingga 5 Sangat Tinggi), ditetapkan
oleh pegawai helpdesk. Tahap disimpan dalam jadual rujukan sendiri, `_risk_levels`, mengikut
konvensyen `_report_statuses` — nama, kriteria dan warna badge boleh dikemaskini dari UI tanpa
perubahan kod.

| Perkara | Lokasi |
|---|---|
| Jadual rujukan | `_risk_levels` (migration `2026_09_01_000000`) |
| Kolum pada aduan | `reports.risk_level_id`, nullable (migration `2026_09_01_000001`) |
| Model | `App\Models\Ref\RiskLevel`, relasi `Report::riskLevel()` |
| Penetapan oleh helpdesk | `App\Livewire\Aduan\ReportShow::setRiskLevel()` |
| Skrin selenggara | `/aras-risiko` (`manage.category`), `App\Livewire\RiskLevel\RiskLevelIndex` |
| Laporan | `/laporan/mengikut-tahap-risiko` + `POST /api/laporan/by-risk` |
| Penapis | Senarai aduan dan Laporan Keseluruhan |

Peraturan penting:

- **Wajib sebelum diagih.** `saveAdminTask()` menolak tindakan 1 (Majukan) dan 2 (Penugasan Semula)
  selagi tahap risiko belum **disimpan**. Memilih dalam dropdown tanpa menyimpan tidak dikira.
- **Setiap perubahan direkod** dalam kronologi aduan sebagai trail status **11**
  ("Tahap Risiko Ditetapkan").
- **Hanya tahap aktif boleh dipilih.** Tahap yang dinyahaktifkan kekal pada aduan yang sudah
  menggunakannya.
- Aduan sedia ada diisi Tahap 1 oleh migration; aduan baharu bermula tanpa tahap supaya helpdesk
  terpaksa menilainya.

### Memuat semula staging daripada dump produksi

```bash
docker compose -f docker-compose.staging.yml up -d
./scripts/staging-refresh-from-dump.sh ~/Downloads/helpdesk_011026.sql
```

Dump produksi (Navicat, skema `helpdesk`) **tidak boleh** sekadar dimuat lalu `artisan migrate`:
jadual `migrations` di dalamnya hanya ada satu baris sejarah, jadi migration lama akan cuba mencipta
semula jadual yang sudah ada. Skrip itu menguruskan urutan betul dalam 8 langkah: cipta semula
skema, muat dump, rekod migration lama sebagai sudah dijalankan, jalankan migration yang tertunggak,
pakai `scripts/apply-flow-statuses.sql` (status 7/8 dan trail 9/10), semai tetapan notifikasi,
cadangkan tahap risiko, dan isi Lesson Learn. Ia menjatuhkan serta mencipta semula
`helpdesk_staging` setiap kali, dan tidak pernah menyentuh produksi.

### Arahan demo

Kesemuanya enggan berjalan apabila `APP_ENV=production`.

```bash
# Kata laluan akaun sedia ada untuk demo (isi tel_no yang kosong juga)
php artisan demo:reset-password nazemah ummulatifah akram.zaki zatun yusliana azwani norliyana.farhan

# Cadangkan tahap risiko daripada kategori + isi aduan
php artisan aduan:classify-risk --dry-run
php artisan aduan:classify-risk --reclassify

# 10 aduan dummy merentas seluruh aliran bagi satu bulan
php artisan demo:seed-aduan --month=2026-11

# Isi pangkalan pengetahuan Lesson Learn bagi aduan yang sudah selesai
php artisan demo:seed-lesson-learn --count=60
```

Nota tentang data yang diimport:

- **Tiada akaun yang mempunyai `tel_no`.** Gerbang `EnsurePhoneFilled` akan mengurung setiap login
  di dashboard, jadi `demo:reset-password` mengisi nombor placeholder.
- **Tiada rekod Lesson Learn.** Trail status 9 dan kolum `punca_masalah` datang dengan aliran
  baharu; kesemua 16,903 trail yang diimport kosong. `demo:seed-lesson-learn` menjananya dengan
  penanda `[Rekod contoh untuk tujuan demo]`.
- **Peranan `penyelia` telah dibuang** daripada kod (tiada dalam `flow baru.md` dan tiada dalam
  data produksi). Langkah "Tutup & Tanda Selesai" dibenarkan untuk pengadu sendiri, `helpdesk.admin`
  dan `superadmin`.
- Dua daripada 10 aduan demo dibiarkan "Baru" tanpa tahap risiko, khusus untuk menunjukkan peraturan
  wajib itu secara langsung.

### Agihan pengelas risiko pada data produksi (4,024 aduan)

| Tahap | | Bilangan | Peratus |
|---|---|---|---|
| 1 | Sangat Rendah | 812 | 20.2% |
| 2 | Rendah | 1,738 | 43.2% |
| 3 | Sederhana | 1,342 | 33.3% |
| 4 | Tinggi | 131 | 3.3% |
| 5 | Sangat Tinggi | 1 | 0.03% |

`App\Support\RiskClassifier` ialah heuristik kata kunci, bukan pertimbangan. Padanan pada keterangan
aduan dihadkan kepada perkataan yang tidak mungkin tersalah tafsir (virus, ransomware, crowdstrike,
"server down"); selebihnya subjek sahaja, kerana permohonan mesyuarat mengandungi frasa seperti
"secara dalam talian" dan justifikasi "kebocoran maklumat" yang sebelum ini menaikkan aduan rutin
beberapa tahap. Pegawai helpdesk boleh mengubah mana-mana tahap, dan pengelas tidak pernah menulis
ganti tahap di atas default.
