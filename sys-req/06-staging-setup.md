# 06 — Staging Environment Setup (task 1.3)

The framework and MySQL upgrades must be validated on a **staging** environment that mirrors the
target runtime — never against the production `helpdesk` database. This is infrastructure you
provision; the steps below are the reference.

## Target runtime

- PHP **8.3** (production pin), tooling may run 8.4/8.5
- MySQL **8.0** (or MariaDB 10.11+ if that is your standard)
- Composer 2.x, Node 20+ (for Vite in later phases)

## Option A — Local containers (recommended for iteration)

Use Laravel Sail (already a dev dependency) or a plain docker-compose with:
- `php:8.3` app container
- `mysql:8.0` container with a **separate** database name (e.g. `helpdesk_staging`)
- Load the production dump into staging to test against real-shaped data:

```bash
# create the staging DB, then:
gzcat ~/helpdesk-backups/pre-upgrade-20260828/helpdesk-db.sql.gz \
  | mysql -h 127.0.0.1 -u root -p helpdesk_staging
```

Point a staging `.env` (APP_ENV=staging, its own DB creds) at `helpdesk_staging`.

## Option B — Separate staging server

Provision a VM/host matching production, deploy the `upgrade/laravel-12` branch, and restore the
dump into an isolated database. Keep it network-isolated from production.

## MySQL 8 pre-check (feeds task 2.3)

After loading the dump into MySQL 8, run the app's laporan/dashboard aggregate queries and watch
for `ONLY_FULL_GROUP_BY` errors. The known hot spot is `LaporanController::viewByStatus` (and the
other `viewBy*` methods) which use `GROUP BY report_status_id` while selecting non-grouped columns.

```sql
-- confirm the strict mode that will bite:
SELECT @@sql_mode;  -- expect ONLY_FULL_GROUP_BY present by default in MySQL 8
```

## Test database

Automated tests use in-memory SQLite (configured in `phpunit.xml`) and never touch any MySQL
database. No staging DB is required to run the suite:

```bash
vendor/bin/phpunit
```

## Restore procedure (if a staging run goes wrong)

```bash
# DB
gzcat ~/helpdesk-backups/pre-upgrade-20260828/helpdesk-db.sql.gz | mysql -u root -p helpdesk_staging
# uploads
tar -xzf ~/helpdesk-backups/pre-upgrade-20260828/storage-app-public.tar.gz -C storage/app
```
