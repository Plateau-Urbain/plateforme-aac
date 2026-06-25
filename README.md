# Plateau Urbain

Application web Symfony pour la gestion d'occupations temporaires, de candidatures et d'administration back-office (Sonata Admin).

## Prérequis

- PHP **≥ 8.1** (recommandé 8.2 ou 8.3)
- [Composer](https://getcomposer.org/) 2
- MySQL ou MariaDB
- Extensions PHP requises : voir `composer.json` (`ctype`, `iconv`, `pdo_mysql`, `intl`, `mbstring`, …)
- [Less](http://lesscss.org/) (`lessc`) pour compiler les feuilles de style — ex. `brew install lessc` sur macOS

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

## Assets front (CSS, images, JS)

Les sources sont dans `assets/` (Less, CSS, images, JS). Elles sont exposées sous `/bundles/app/` via `public/bundles/app/` (généré, non versionné).

| Répertoire | Rôle |
|------------|------|
| `assets/less/main.less` | Source Less principale |
| `assets/css/` | CSS compilés et librairies |
| `assets/images/` | Images statiques du thème |
| `public/images/` | Images legacy (logos, etc.) |
| `public/uploads/` | Fichiers déposés par l'application (Vich Uploader) |

Après modification des styles ou ajout d'images statiques :

```bash
make          # compile main.less → main.css, puis reconstruit public/bundles/app/
make css      # compilation Less uniquement
make bundles  # reconstruction de public/bundles/app/ uniquement
```

Équivalent manuel :

```bash
lessc assets/less/main.less assets/css/main.css
python3 scripts/rebuild_bundle_app_assets.py
```

`composer install` relance aussi la reconstruction des bundles via les scripts post-install.

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
