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

Composer and npm dependencies are installed and the Vite production build succeeds. Docker/MySQL could not be started in the local environment, so migration and feature coverage currently runs on SQLite; MySQL compatibility still needs a real service check. See [`docs/DEVELOPMENT_PROGRESS.md`](docs/DEVELOPMENT_PROGRESS.md) for per-screen status and test evidence.

## Product implementation status

Initial implementation is active: authentication and onboarding flows, workspace membership/invites, a real-data Home summary, Checklist tasks, and the Perjalanan milestone timeline are in progress. Eighteen Screen IDs have implementation evidence; none has completed the full desktop/mobile and Stitch visual verification gate. Most other modules are not implemented yet. See [`docs/DEVELOPMENT_PROGRESS.md`](docs/DEVELOPMENT_PROGRESS.md) before treating any screen as complete.
