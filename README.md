# MENUJU

MENUJU is a new wedding-planning product for couples. Product scope and design references live in [`docs/`](docs/01_PRODUCT_PRD.md), [`references/`](references/readme_start_here.md), and the warm editorial token guide at [`references/stitch/warm_editorial_utility/DESIGN.md`](references/stitch/warm_editorial_utility/DESIGN.md).

## Stack

- Laravel 13 / PHP 8.3+
- Vue 3 + JavaScript + Vue Router
- Tailwind CSS 4 + Vite
- MySQL 8.4
- Font Awesome

The Laravel application serves the Vue entry document and will own authenticated product APIs. Frontend source stays in `resources/js`; domain code and authorization stay in Laravel. Private resources must always be scoped to an authorized workspace.

## Local development

Prerequisites: PHP 8.3+, Composer, Node.js 22+, npm, and Docker Compose.

```sh
docker compose up -d mysql
cp .env.example .env
composer install
php artisan key:generate
npm install
php artisan migrate
npm run dev
```

Open `http://localhost:8000` in a second terminal after running `php artisan serve`. Local MySQL is exposed on port 3306 with database `menuju`, user `root`, and password `local`. Set a private `APP_KEY` and real credentials per environment; never commit `.env`.

Dependency installation and build are not yet verified in this environment because Packagist DNS was unavailable during project creation. See [`docs/DEVELOPMENT_PROGRESS.md`](docs/DEVELOPMENT_PROGRESS.md) for the actual status by screen.

## Product implementation status

The application is at its initial foundation stage. The current welcome view is a foundation placeholder, not a completed auth/onboarding flow. All 77 Screen IDs remain unimplemented and unverified until their functional and visual acceptance work is recorded in the tracker.
