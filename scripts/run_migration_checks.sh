#!/usr/bin/env bash
# Garde-fous migration SF3.4 → SF6 (CI ou local).
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Validation schéma Doctrine (env dev)"
php bin/console doctrine:schema:validate

echo "==> Lint conteneur et Twig"
php bin/console lint:container
php bin/console lint:twig templates/

echo "==> Contrôles statiques migration"
if grep -rq "@FOSUser/" templates/ 2>/dev/null; then
  echo "ERREUR : références @FOSUser/ encore présentes dans templates/" >&2
  exit 1
fi
if grep -rqE "->get\([^)]+,\s*\[\s*\]" src/ 2>/dev/null; then
  echo "AVERTISSEMENT : InputBag::get(..., []) détecté — incompatible Symfony 6" >&2
fi
if grep -rqE "->add\([^,]+,\s*'(date|datetime|choice)'" src/Admin/ 2>/dev/null; then
  echo "AVERTISSEMENT : types de formulaire en chaîne dans src/Admin/" >&2
fi

echo "==> Préparation base de test"
bash scripts/prepare_test_database.sh

echo "==> PHPUnit"
php bin/phpunit

echo "==> PHPStan (Admin + Controller, niveau migration)"
php vendor/bin/phpstan analyse --memory-limit=512M

echo "==> Smoke HTTP (script legacy)"
php smoke_test.php

echo "Tous les contrôles migration sont passés."
