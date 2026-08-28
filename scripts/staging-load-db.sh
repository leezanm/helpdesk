#!/usr/bin/env bash
#
# Loads the pre-upgrade production dump into the staging MySQL container.
# The staging DB is a throwaway copy — safe to drop and reload anytime.
#
# Usage:
#   ./scripts/staging-load-db.sh [path-to-dump.sql.gz]
#
# Defaults to the most recent pre-upgrade backup.
set -euo pipefail

DUMP="${1:-$HOME/helpdesk-backups/pre-upgrade-20260828/helpdesk-db.sql.gz}"
CONTAINER="$(docker compose -f docker-compose.staging.yml ps -q staging-mysql)"

if [ -z "$CONTAINER" ]; then
  echo "staging-mysql container is not running. Start it first:"
  echo "  docker compose -f docker-compose.staging.yml up -d"
  exit 1
fi

if [ ! -f "$DUMP" ]; then
  echo "Dump not found: $DUMP"
  exit 1
fi

echo "Loading $DUMP into staging database 'helpdesk_staging'..."
gzcat "$DUMP" | docker exec -i "$CONTAINER" sh -c 'exec mysql -uroot -pstaging_root helpdesk_staging'
echo "Done. Staging database loaded."
