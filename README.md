# Plateau Urbain

Application web Symfony pour la gestion d'occupations temporaires, de candidatures et d'administration back-office (Sonata Admin).

## Prérequis

- PHP **≥ 8.1** (recommandé 8.2 ou 8.3)
- [Composer](https://getcomposer.org/) 2
- MySQL ou MariaDB
- Extensions PHP requises : voir `composer.json` (`ctype`, `iconv`, `pdo_mysql`, `intl`, `mbstring`, …)

## Installation

```bash
composer install
cp .env.example .env.local
# Adapter DATABASE_URL, APP_SECRET, MAILER_DSN, etc. dans .env.local
php bin/console doctrine:schema:validate
```

Démarrer le serveur de développement :

```bash
symfony server:start
# ou nginx + php-fpm avec la racine web pointant vers public/
```

Les assets front legacy sont servis sous `/bundles/app/`. Ils sont régénérés automatiquement après `composer install` ; pour forcer une reconstruction :

```bash
python3 scripts/rebuild_bundle_app_assets.py
```

## Tests

```bash
./vendor/bin/phpunit
php smoke_test.php
```

La base de test peut être préparée avec `scripts/prepare_test_database.sh` (voir `.env.test`).

## Configuration

| Variable | Description |
|----------|-------------|
| `APP_SECRET` | Clé secrète Symfony |
| `DATABASE_URL` | Connexion Doctrine (MySQL/MariaDB) |
| `MAILER_DSN` | Transport d'e-mails (ex. Brevo) |
| `MAIL_CONFIRMATION_FROM` / `MAIL_CONFIRMATION_TO` | Expéditeur et destinataire des notifications |
| `APP_BASE_URL` | Domaine public de l'application |
| `TRUSTED_PROXIES` | (prod) Adresses du reverse proxy |

Copier [`.env.example`](.env.example) vers `.env.local` et renseigner les valeurs. Ne jamais committer de secrets.

## Licence

Distribué sous **GNU Affero General Public License v3** (AGPL-3.0).

Voir le fichier [LICENSE](LICENSE).
