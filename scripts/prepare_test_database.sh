#!/usr/bin/env bash
# Crée la base *_test (suffixe Doctrine en APP_ENV=test) et aligne le schéma sur les entités SF6.
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ -f .env.local ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env.local
  set +a
  if grep -q '^DATABASE_URL=' .env.local 2>/dev/null; then
    grep '^DATABASE_URL=' .env.local > .env.test.local
    echo "DATABASE_URL copié vers .env.test.local (non versionné)."
  fi
fi

if [[ -z "${DATABASE_URL:-}" ]]; then
  echo "DATABASE_URL manquant (.env.local ou .env.test.local)." >&2
  exit 1
fi

eval "$(php -r '
$url = getenv("DATABASE_URL");
$parts = parse_url($url);
$user = $parts["user"] ?? "root";
$pass = $parts["pass"] ?? "";
$host = $parts["host"] ?? "127.0.0.1";
$port = $parts["port"] ?? "3306";
$db = trim($parts["path"] ?? "", "/");
$testDb = $db . "_test";
echo "DB_HOST=" . escapeshellarg($host);
echo " DB_PORT=" . escapeshellarg($port);
echo " DB_USER=" . escapeshellarg($user);
echo " DB_PASS=" . escapeshellarg($pass);
echo " TEST_DB=" . escapeshellarg($testDb);
')"

echo "Préparation de la base de test : ${TEST_DB}"

MYSQL_PWD="${DB_PASS}" mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" \
  -e "CREATE DATABASE IF NOT EXISTS \`${TEST_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

APP_ENV=test php bin/console doctrine:schema:update --force --no-interaction

echo "OK : ${TEST_DB} prête pour php bin/phpunit"
