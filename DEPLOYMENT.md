# Panduan Deploy ke Production — Sistem Meja Bantuan KKDW

Panduan langkah demi langkah untuk deploy versi baharu (Laravel 12 + Livewire 3)
ke pelayan production. Ikut turutan. Jangan langkau bahagian **Backup** dan
**Mod Penyelenggaraan**.

> Nota: perintah menganggap projek berada di `/var/www/helpdesk` dan pengguna
> web ialah `www-data`. Laraskan ikut pelayan sebenar.

---

## 0. Prasyarat pelayan (sekali sahaja)

Pastikan pelayan production ada:

- **PHP 8.3+** dengan sambungan: `pdo_mysql`, `mbstring`, `openssl`, `zip`,
  `gd`, `bcmath`, `ctype`, `fileinfo`, `tokenizer`, `xml`.
- **MySQL 8.0** (atau MariaDB setara).
- **Composer 2**.
- **Node.js 20+ & npm** — untuk `npm run build`. (Boleh build di mesin lain dan
  hantar folder `public/build` jika pelayan tiada Node.)
- **mysqldump** (atau `mariadb-dump`) — diperlukan oleh fungsi Backup sistem.
- Akses **Active Directory (LDAP)** dari pelayan (login guna AD).
- **Supervisor** (atau systemd) jika menggunakan queue (disyorkan).

---

## 1. Backup DULU (WAJIB — jangan langkau)

Sebelum sebarang perubahan, backup kod, DB, dan fail muat naik.

```bash
# Backup pangkalan data
mysqldump -u <db_user> -p <db_name> > ~/backup-helpdesk-db-$(date +%F-%H%M).sql

# Backup fail muat naik pengguna + .env sedia ada
tar czf ~/backup-helpdesk-storage-$(date +%F-%H%M).tar.gz \
    /var/www/helpdesk/storage/app/public \
    /var/www/helpdesk/.env
```

Simpan salinan backup ini di lokasi selamat (bukan atas pelayan yang sama).

---

## 2. Mod Penyelenggaraan (elak pengguna guna semasa deploy)

```bash
cd /var/www/helpdesk
php artisan down --render="errors::503" --retry=60
```

Pengguna akan nampak halaman "sedang diselenggara" sepanjang langkah 3–9.

---

## 3. Tarik kod terbaharu

```bash
cd /var/www/helpdesk
git fetch origin
git checkout upgrade/laravel-12      # atau branch yang telah di-merge ke main
git pull origin upgrade/laravel-12
```

> Jika alur kerja anda merge ke `main` dahulu, checkout `main` sebaliknya.

---

## 4. Kemas kini `.env` production

Buka `.env` production dan sahkan / tetapkan nilai berikut. **Jangan**
guna `.env.staging` atau `.env.example` terus — kekalkan rahsia production.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://helpdesk.rurallink.gov.my

# Pangkalan data production
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
# Jika DB guna SSL self-signed, backup mysqldump akan langkau SSL secara lalai
# (DB_DUMP_SKIP_SSL=true). Set MYSQL_ATTR_SSL_CA hanya jika perlu.

# Cookie selamat (HTTPS). Biarkan TIDAK diset supaya lalai = true.
# JANGAN set SESSION_SECURE_COOKIE=false di production.

# Emel (SMTP kementerian sebenar — bukan Mailtrap)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=helpdesk@rurallink.gov.my
MAIL_FROM_NAME="Sistem Meja Bantuan KKDW"

# Queue: 'database' + worker (disyorkan). Guna 'sync' jika tiada worker.
QUEUE_CONNECTION=database

# Tetapan LDAP / AD (ikut konfig sedia ada)
LDAP_HOST=...
LDAP_USERNAME=...
LDAP_PASSWORD=...
LDAP_BASE_DN=...
```

---

## 5. Pasang dependency + bina aset

```bash
cd /var/www/helpdesk

# PHP dependencies (tanpa dev, optimum untuk production)
composer install --no-dev --optimize-autoloader

# Aset frontend (CSS/JS). Jika pelayan tiada Node, build di tempat lain
# dan salin folder public/build ke pelayan.
npm ci
npm run build
```

---

## 6. Migrasi pangkalan data

```bash
php artisan migrate --force
```

Migrasi baharu yang akan dijalankan:
- `2026_08_29_000000_update_report_flow_statuses_and_lesson_learn` — tambah
  lajur lesson-learn pada `report_trails` (aliran aduan baharu).
- `2026_08_30_000000_create_notification_settings_table` — jadual tetapan
  notifikasi boleh-ubah.

---

## 7. Seed data rujukan (idempotent — selamat dijalankan pada DB sedia ada)

Seeder ini guna `firstOrCreate`/`syncPermissions`, jadi ia **tidak** menimpa
data sedia ada dan selamat dijalankan semula.

```bash
# Peranan + kebenaran (termasuk role 'penyelia' baharu)
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=PermissionSeeder --force

# Tetapan penerima notifikasi (default = kelakuan sedia ada)
php artisan db:seed --class=NotificationSettingSeeder --force
```

### 7a. Status aliran aduan baharu pada DB SEDIA ADA

Pangkalan data production sedia ada sudah ada status 1–6. Jangan reseed status
(elak perlanggaran id). Sebaliknya jalankan skrip idempotent ini **sekali**:

```bash
mysql -u <db_user> -p <db_name> < scripts/apply-flow-statuses.sql
```

Ia menamakan semula label status 2 & 3, dan menambah status 7 & 8 serta trail
status 9 & 10 **hanya jika belum wujud**. Nilai `report_status_id` sedia ada
tidak diubah (aduan "Selesai" kekal betul).

> DB baharu (fresh install) TIDAK perlu langkah 7a — status datang dari seeder.

---

## 8. Optimumkan cache untuk production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Selepas ini, sebarang perubahan `.env` perlu `php artisan config:cache`
> semula untuk berkuat kuasa.

---

## 9. Storage link (sekali sahaja / jika belum wujud)

```bash
php artisan storage:link
```

---

## 10. Queue worker (jika QUEUE_CONNECTION=database)

Notifikasi emel dihantar melalui queue. Worker mesti sentiasa berjalan.
Gunakan **Supervisor**:

Fail `/etc/supervisor/conf.d/helpdesk-worker.conf`:

```ini
[program:helpdesk-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/helpdesk/artisan queue:work --sleep=3 --tries=3 --backoff=15 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/helpdesk/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start helpdesk-worker:*
```

> Selepas SETIAP deploy, muat semula worker supaya ia guna kod baharu:
> `sudo supervisorctl restart helpdesk-worker:*`
>
> Alternatif tanpa worker: set `QUEUE_CONNECTION=sync` di `.env` (emel dihantar
> serta-merta semasa request; tiada worker diperlukan).

---

## 11. Kebenaran fail (jika perlu)

```bash
sudo chown -R www-data:www-data /var/www/helpdesk/storage /var/www/helpdesk/bootstrap/cache
sudo chmod -R 775 /var/www/helpdesk/storage /var/www/helpdesk/bootstrap/cache
```

---

## 12. Keluar dari mod penyelenggaraan

```bash
php artisan up
```

---

## 13. Semakan selepas deploy (smoke test)

Uji di `https://helpdesk.rurallink.gov.my`:

- [ ] Halaman login memuat (rupa baharu + QR code + background).
- [ ] Login guna akaun AD sebenar berjaya.
- [ ] Selepas login, pengguna mendarat di dashboard peribadi.
      - Pegawai (jtk/ptm/pptm) → Dashboard Pegawai.
      - Pengurusan/Helpdesk/Penyelia/Superadmin → boleh buka "Dashboard
        Pengurusan" (org-wide).
- [ ] Senarai aduan + penapis (bahagian/kategori/pegawai/status) berfungsi.
- [ ] Cipta aduan baharu → emel notifikasi diterima (semak inbox penerima).
- [ ] Log Sistem (audit trail) memuat tanpa ralat 500.
- [ ] Backup: `/backup` → "Backup Pangkalan Data" menghasilkan fail .zip.
- [ ] Peranan `penyelia` wujud (Pengurusan Peranan) dan boleh ditugaskan.
- [ ] Tetapan Notifikasi (`/tetapan/notifikasi`) boleh dibuka oleh superadmin.

Semak log jika ada isu:
```bash
tail -n 100 storage/logs/laravel.log
```

---

## 14. Tugasan pentadbir selepas deploy (dalam sistem)

Melalui UI, sebagai superadmin:

1. **Tugaskan role `penyelia`** kepada pegawai penyelia yang berkenaan
   (Pengurusan Pengguna).
2. **Semak Tetapan Notifikasi** (`/tetapan/notifikasi`) — sahkan penerima
   setiap peristiwa emel betul (peranan + emel tambahan).

---

## Rollback (jika deploy gagal)

```bash
cd /var/www/helpdesk
php artisan down

# Pulihkan kod ke versi sebelum
git checkout <tag_atau_commit_lama>
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Pulihkan pangkalan data dari backup langkah 1
mysql -u <db_user> -p <db_name> < ~/backup-helpdesk-db-<cap masa>.sql

php artisan config:cache route:cache view:cache
php artisan up
```

> Migrasi baharu hanya MENAMBAH lajur/jadual (tidak memadam data), jadi kod
> lama masih boleh berjalan dengan skema baharu. Rollback DB hanya perlu jika
> ada masalah data yang serius.

---

## Ringkasan pantas (untuk deploy seterusnya, DB sudah ada skema baharu)

```bash
php artisan down
git pull origin <branch>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache route:cache view:cache event:cache
sudo supervisorctl restart helpdesk-worker:*
php artisan up
```
