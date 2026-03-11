#!/bin/sh
set -eu

cd /app

run_migrations="${RUN_DB_MIGRATIONS:-true}"
max_attempts="${MIGRATION_MAX_ATTEMPTS:-20}"
sleep_seconds="${MIGRATION_RETRY_SLEEP_SECONDS:-3}"

if [ "$run_migrations" = "true" ] || [ "$run_migrations" = "1" ] || [ "$run_migrations" = "yes" ]; then
  attempt=1
  while :; do
    echo "Running migrations (attempt ${attempt}/${max_attempts})..."
    if php artisan migrate --force; then
      echo "Migrations completed."
      break
    fi

    if [ "$attempt" -ge "$max_attempts" ]; then
      echo "Migrations failed after ${max_attempts} attempts."
      exit 1
    fi

    attempt=$((attempt + 1))
    echo "Migration failed, retrying in ${sleep_seconds}s..."
    sleep "$sleep_seconds"
  done
else
  echo "Skipping migrations (RUN_DB_MIGRATIONS=${run_migrations})."
fi

exec "$@"
