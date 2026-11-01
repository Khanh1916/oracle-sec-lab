#!/usr/bin/env bash
# =============================================================
# OracleSecLab - web container entrypoint
# Waits for Oracle, imports schema + seed on first run, then
# generates bcrypt password hashes. Idempotent: existing data
# (and student progress) is preserved across restarts.
# =============================================================
set -e

DB_HOST=${DB_HOST:-oracle}
DB_PORT=${DB_PORT:-1521}
DB_SERVICE=${DB_SERVICE:-FREEPDB1}
DB_USER=${DB_USER:-dbs401_user}
DB_PASS=${DB_PASS:-dbs401_pass}

APP_DIR=/var/www/html/dbs401-oracle-app
CONN="${DB_USER}/${DB_PASS}@//${DB_HOST}:${DB_PORT}/${DB_SERVICE}"

echo "[entrypoint] Waiting for Oracle at ${DB_HOST}:${DB_PORT}/${DB_SERVICE} ..."
until echo "SELECT 1 FROM DUAL;" | sqlplus -s -L "$CONN" >/dev/null 2>&1; do
    echo "[entrypoint] ... database not ready yet, retrying in 5s"
    sleep 5
done
echo "[entrypoint] Database reachable."

# Does the schema already exist? (USERS table owned by the app user)
EXISTS=$(echo "SET HEADING OFF FEEDBACK OFF PAGESIZE 0;
SELECT COUNT(*) FROM USER_TABLES WHERE TABLE_NAME='USERS';" \
    | sqlplus -s -L "$CONN" | tr -d '[:space:]')

if [ "$EXISTS" = "0" ]; then
    echo "[entrypoint] First run: importing schema.sql ..."
    sqlplus -s -L "$CONN" @"${APP_DIR}/database/schema.sql"
    echo "[entrypoint] Importing seed.sql ..."
    sqlplus -s -L "$CONN" @"${APP_DIR}/database/seed.sql"
    echo "[entrypoint] Generating bcrypt password hashes ..."
    php "${APP_DIR}/database/init_passwords.php"
    echo "[entrypoint] Database initialization complete."
else
    echo "[entrypoint] Schema already present (USERS table found) - skipping import."
fi

# Per-instance flags (no-op unless RANDOMIZE_FLAGS=1). Prints shell `export`
# lines for the flag values that live in PHP source; eval them so Apache - and
# therefore store.php / admin.php via getenv() - sees this instance's flags.
FLAG_ENV="$(php "${APP_DIR}/database/apply_dynamic_flags.php" || true)"
if [ -n "$FLAG_ENV" ]; then
    eval "$FLAG_ENV"
    echo "[entrypoint] Applied per-instance flag values."
fi

echo "[entrypoint] Starting Apache (app :80, partner manifest :8081) ..."
exec apache2-foreground
