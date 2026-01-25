#!/usr/bin/env bash
# Runs the test suite against an in-memory SQLite database to prevent accidental
# use of live or shared databases during local test runs.
# Unsets DB_* env vars and forces APP_ENV=testing with sqlite :memory:.
# Run with: ./scripts/run-tests-safe.sh

if [ -n "${DB_CONNECTION-}" ] || [ -n "${DB_DATABASE-}" ] || [ -n "${DB_HOST-}" ] || [ -n "${DB_USERNAME-}" ] || [ -n "${DB_PASSWORD-}" ]; then
  if [ -z "${ALLOW_DB_ENV-}" ]; then
    echo "Error: DB_* environment variables detected; refusing to run tests." >&2
    echo "Set ALLOW_DB_ENV=1 to bypass this safety check." >&2
    exit 1
  fi
  echo "Warning: DB_* environment variables detected; overriding for safe test run." >&2
  echo "DB_CONNECTION=${DB_CONNECTION-<unset>}" >&2
  echo "DB_DATABASE=${DB_DATABASE-<unset>}" >&2
  echo "DB_HOST=${DB_HOST-<unset>}" >&2
  echo "DB_USERNAME=${DB_USERNAME-<unset>}" >&2
  if [ -n "${DB_PASSWORD-}" ]; then
    echo "DB_PASSWORD=<set>" >&2
  else
    echo "DB_PASSWORD=<unset>" >&2
  fi
fi
set -euo pipefail

unset DB_CONNECTION DB_DATABASE DB_HOST DB_USERNAME DB_PASSWORD

export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

php artisan test "$@"
