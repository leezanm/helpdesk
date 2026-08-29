<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

# Sistem Meja Bantuan KKDW (Helpdesk)

Complaint / report management ("Sistem Aduan") for KKDW — Kementerian Kemajuan Desa dan Wilayah.

## Stack

- **PHP** 8.3+
- **Laravel** 12
- **Livewire** 3 (interactive UI) + **Alpine.js**
- **Bootstrap 5** admin theme (served from `public/assets`)
- **Vite** (asset build; replaced Laravel Mix)
- **MySQL** 8.0
- **Auth:** Laravel Fortify (login by `username`)
- **Authorization:** spatie/laravel-permission 6
- **LDAP / Active Directory:** directorytree/ldaprecord-laravel 3 (replaced the abandoned adldap2)
- **Backups:** spatie/laravel-backup 9

## Architecture

The interactive screens are **Livewire components** (`app/Livewire/**`), with business logic
extracted into **Actions** (`app/Actions/**`) and view helpers in `app/Support`. Controllers are
thin: they render page wrappers that mount the relevant Livewire component. Reference and analytics
screens (dashboard / laporan) remain server-rendered Blade fed by JSON API endpoints for charts.

Converted to Livewire: Kategori, Status, Pengguna (users + assets), Peranan (roles, read-only),
the full Aduan (report) flow (index / create / edit / show + trail + admin tasks + feedback),
and the header notification bell. Dashboard / laporan charts intentionally remain on the existing
ApexCharts/ECharts + API approach.

## Local development

Requirements: PHP 8.3+, Composer 2, Node 20+, MySQL 8 (or MariaDB 10.11+).

```bash
composer install
npm install
cp .env.example .env      # then set DB + APP_KEY
php artisan key:generate
php artisan migrate --seed
npm run dev               # Vite dev server (or: npm run build)
php artisan serve
```

## Staging (Docker)

A self-contained staging environment (PHP 8.3 + MySQL 8.0), isolated from production, is defined in
`docker-compose.staging.yml`. See `sys-req/06-staging-setup.md` for full usage. Quick start:

```bash
docker compose -f docker-compose.staging.yml up -d --build
docker compose -f docker-compose.staging.yml exec staging-app php artisan key:generate --env=staging
docker compose -f docker-compose.staging.yml up -d --force-recreate staging-app
./scripts/staging-load-db.sh    # load a data copy from a backup
# App at http://localhost:8088
```

## Testing

Automated tests use in-memory SQLite (see `phpunit.xml`) and never touch any MySQL database.

```bash
vendor/bin/phpunit
```

Feature tests live in `tests/Feature` (baseline + Livewire component tests). `tests/FeatureTestCase`
seeds permissions/roles/statuses because the app's observers depend on them.

## Upgrade documentation

The full analysis and phased upgrade plan (Laravel 8 → 12, Blade → Livewire) is documented in:

- `sys-req/` — analysis, target architecture, migration plan, risks, staging setup
- `.kiro/specs/laravel-livewire-upgrade/` — the executable spec (requirements / design / tasks)

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
