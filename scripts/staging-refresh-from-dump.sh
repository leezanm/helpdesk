#!/usr/bin/env bash
#
# Refresh the staging database from a CURRENT production dump, then bring its
# schema up to the current codebase.
#
# The production dump is from the pre-upgrade system: it holds the old 21
# tables, report statuses 1-6 with the old labels, trail statuses 1-8, and a
# `migrations` table with (almost) no history. Running `artisan migrate`
# straight after loading it would try to re-create tables that already exist,
# so this script first records the pre-existing migrations as applied, then
# migrates only what is genuinely missing:
#
#   2026_08_29  lesson-learn columns on report_trails
#   2026_08_30  notification_settings table
#   2026_09_01  _risk_levels table + reports.risk_level_id (backfilled to Tahap 1)
#   2014_10_11  divisions / departments tables (absent from the dump)
#
# ...and finally applies the new-flow reference data (statuses 7/8, trail
# statuses 9/10) and the notification recipient defaults.
#
# The staging database is a throwaway copy: this script DROPS and recreates it.
# It never touches production — it only talks to the staging-mysql container.
#
# Usage:
#   ./scripts/staging-refresh-from-dump.sh ~/Downloads/helpdesk_011026.sql
#   ./scripts/staging-refresh-from-dump.sh path/to/dump.sql.gz
set -euo pipefail

DUMP="${1:-}"
if [ -z "$DUMP" ]; then
  echo "Usage: $0 <path-to-dump.sql[.gz]>"
  exit 1
fi
if [ ! -f "$DUMP" ]; then
  echo "Dump not found: $DUMP"
  exit 1
fi

COMPOSE=(docker compose -f docker-compose.staging.yml)
DB=helpdesk_staging
MYSQL_PASS=staging_root

MYSQL_CID="$("${COMPOSE[@]}" ps -q staging-mysql)"
if [ -z "$MYSQL_CID" ]; then
  echo "staging-mysql container is not running. Start it first:"
  echo "  docker compose -f docker-compose.staging.yml up -d"
  exit 1
fi

mysql_exec() { docker exec -i "$MYSQL_CID" mysql -uroot -p"$MYSQL_PASS" "$@"; }

echo "==> 1/8 Recreating empty schema '$DB'"
mysql_exec -e "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> 2/8 Loading dump: $DUMP"
case "$DUMP" in
  *.gz) gzcat "$DUMP" ;;
  *)    cat "$DUMP" ;;
esac | docker exec -i "$MYSQL_CID" sh -c "exec mysql -uroot -p$MYSQL_PASS $DB"

echo "==> 3/8 Recording pre-existing migrations as already applied"
mysql_exec "$DB" <<'SQL'
-- The dump's schema already contains these tables/columns, so the migrations
-- that would create them must not run again. `divisions` and `departments`
-- are deliberately NOT listed: those tables are absent from the dump, so their
-- migrations should run and create them empty.
DELETE FROM `migrations` WHERE `migration` = '2014_10_11_000000_create_departments_table';

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m, 1 FROM (
  SELECT '2014_10_12_000000_create_users_table' AS m
  UNION ALL SELECT '2014_10_12_100000_create_password_resets_table'
  UNION ALL SELECT '2014_10_12_200000_add_two_factor_columns_to_users_table'
  UNION ALL SELECT '2019_08_19_000000_create_failed_jobs_table'
  UNION ALL SELECT '2021_02_22_060812_create_permission_tables'
  UNION ALL SELECT '2021_03_25_160004_create_audit_trails_table'
  UNION ALL SELECT '2021_03_25_160004_create_types_table'
  UNION ALL SELECT '2021_04_30_012129_create_officers_table'
  UNION ALL SELECT '2021_06_06_130325_create_report_statuses_table'
  UNION ALL SELECT '2021_06_06_130349_create_report_categories_table'
  UNION ALL SELECT '2021_06_06_130416_create_reports_table'
  UNION ALL SELECT '2021_06_06_130418_create_report_attachments_table'
  UNION ALL SELECT '2021_06_11_074512_create_report_trail_statuses_table'
  UNION ALL SELECT '2021_06_11_074513_create_report_trails_table'
  UNION ALL SELECT '2021_06_11_093608_create_jobs_table'
  UNION ALL SELECT '2021_06_14_185034_create_notifications_table'
  UNION ALL SELECT '2021_06_24_094934_create_user_assets_table'
) AS baseline
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migrations`.`migration` = baseline.m);
SQL

echo "==> 4/8 Running outstanding migrations"
"${COMPOSE[@]}" exec -T staging-app php artisan migrate --force

echo "==> 5/8 Applying new-flow reference data (statuses 7/8, trail 9/10)"
mysql_exec "$DB" < scripts/apply-flow-statuses.sql

echo "==> 6/8 Seeding notification recipient defaults"
"${COMPOSE[@]}" exec -T staging-app php artisan db:seed --class=NotificationSettingSeeder --force

# Demo data only. The schema migration parks every pre-existing aduan on
# Tahap 1; this re-evaluates those from the category + complaint wording so the
# demo shows a realistic spread across all five levels. Aduan an officer has
# already placed above Tahap 1 are never overwritten. Drop this step if you
# want the imported aduan left on the plain Tahap 1 default.
echo "==> 7/8 Suggesting risk levels for imported aduan (demo)"
"${COMPOSE[@]}" exec -T staging-app php artisan aduan:classify-risk --reclassify

# The imported data holds no Lesson Learn records: trail status 9 and the
# punca_masalah columns arrived with the new flow, so every imported trail has
# them empty. This documents the most recent completed aduan so the knowledge
# base is not blank during a demo. Every generated record is marked as sample
# data. Drop this step if you want the screen to reflect the import exactly.
echo "==> 8/8 Filling Lesson Learn for completed aduan (demo)"
"${COMPOSE[@]}" exec -T staging-app php artisan demo:seed-lesson-learn --count=60 --force

# Demo logins: the imported accounts carry production password hashes that
# nobody here knows, and no phone number (which the EnsurePhoneFilled gate
# blocks on). Reset the handful you plan to demo with:
#   docker compose -f docker-compose.staging.yml exec staging-app \
#     php artisan demo:reset-password akram.zaki mastura.mdzin --force

"${COMPOSE[@]}" exec -T staging-app php artisan optimize:clear

echo
echo "Done. Verification:"
mysql_exec "$DB" -e "
  SELECT (SELECT COUNT(*) FROM reports) AS aduan,
         (SELECT COUNT(*) FROM users) AS pengguna,
         (SELECT COUNT(*) FROM _risk_levels) AS tahap_risiko,
         (SELECT COUNT(*) FROM reports WHERE risk_level_id IS NULL) AS aduan_tanpa_risiko,
         (SELECT COUNT(*) FROM _report_statuses) AS status,
         (SELECT COUNT(*) FROM _report_trail_statuses) AS status_trail;"
mysql_exec "$DB" -e "
  SELECT r.level AS tahap, r.\`desc\` AS nama, COUNT(a.id) AS bil_aduan
    FROM _risk_levels r LEFT JOIN reports a ON a.risk_level_id = r.id
   GROUP BY r.level, r.\`desc\` ORDER BY r.level;"
echo "App: http://localhost:8088"
