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
