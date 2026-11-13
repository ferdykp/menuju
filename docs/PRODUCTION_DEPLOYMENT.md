# Production deployment preparation

## Release status

MENUJU is **not cleared for public production use yet**. The application build and SQLite-backed feature suite pass, but the production target is MySQL 8.4 and has not been exercised against MySQL. Google OAuth is still in Testing mode, the 77-screen acceptance inventory is not fully verified, and this repository does not define a provider-specific deploy/rollback pipeline. This guide prepares a single-server release; it does not replace the staging gates below.

## Production environment

Use `.env.production.example` as a key list, not as a file to deploy. Supply real values through the server's secret manager or protected environment configuration. At minimum:

- Set `APP_ENV=production`, `APP_DEBUG=false`, a generated `APP_KEY`, and the public HTTPS `APP_URL`.
- Use a dedicated MySQL 8.4 database/user with only the permissions required by the app. Do not use MySQL root.
- Keep `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, and `SESSION_SECURE_COOKIE=true` behind HTTPS.
- Configure a real SMTP sender and validate SPF/DKIM/DMARC and delivery of registration verification and password-reset messages.
- Configure the exact Google OAuth redirect URI for the production domain, then publish the consent screen so sign-in is not restricted to test users.
- Keep `FILESYSTEM_DISK=local` only when the server has persistent storage. Uploaded invitation images are stored on the private local disk and must survive releases and be included in backups. For multiple app replicas, use shared object storage and Redis-backed cache/session/queue services before scaling out.
- Leave `MENUJU_DEMO_PASSWORD` unset. The demo seeder rejects production, and production releases must never run `db:seed`.

Never commit `.env`, OAuth secrets, SMTP credentials, database credentials, private invitation uploads, or generated production keys.

Before activating a release, run `php artisan menuju:production-check` after loading the production environment and building the config cache. It verifies that required app, database, session, SMTP, and Google OAuth settings are present and aligned. It never prints secret values and does not send mail or contact Google; provider-side validation is part of staging.

## Public domain and service setup

1. Point the chosen domain's DNS A/AAAA records to the production server. Enable automatic certificate renewal and redirect HTTP to HTTPS at the web server or hosting platform.
2. Set `APP_URL` to the canonical HTTPS origin and forward the original host and HTTPS scheme through the reverse proxy. Confirm generated email links and OAuth callbacks use that exact origin.
3. In Google Cloud, configure the OAuth consent screen and add `https://<domain>/auth/google/callback` as an authorized redirect URI for the web client. Store its client ID and secret in the server's secret manager.
4. In the SMTP provider, verify the sender domain, publish the provider's SPF and DKIM records (and a DMARC policy), then set SMTP host, port, username, password, and a verified `MAIL_FROM_ADDRESS` in the server secret manager.
5. Send a registration verification email and password reset from staging; confirm delivery, links, and SPF/DKIM/DMARC results. Complete Google sign-in from a non-test account after OAuth publication.

## Single-server release sequence

Build from a reviewed release commit on the server or CI runner. Point the web server document root at Laravel's `public/` directory and run PHP-FPM behind a TLS-terminating reverse proxy. Do not expose the project root or use `php artisan serve` as the public web server.

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan storage:link
php artisan optimize
php artisan menuju:production-check
php artisan migrate --force
```

Run the preflight before migrations and stop the release if any check fails. It validates configuration only; it does not send an email or contact Google. Run migrations only after a verified database backup. Make `storage/` and `bootstrap/cache/` writable by the PHP process. Persist `storage/app/private` (uploaded invitation images), `storage/framework`, and logs across releases. If uploads can exceed the web server defaults, set request body limits to support the 8 MB per-image application limit and a multi-image gallery upload.

`/up` is the Laravel liveness endpoint. `/api/health` is the readiness endpoint and returns success only when the configured database responds. Configure the platform monitor to check both process health and readiness, and send application logs to a monitored destination.

## Required staging gates before public launch

1. Provision a staging environment with the same PHP extensions, MySQL 8.4 version/collation, web server, HTTPS proxy, and storage behavior planned for production.
2. Apply the complete migration set to an empty MySQL database. Run the full PHP and JavaScript suites against the release commit, then exercise signup + email verification, login/logout, password reset, partner invitation, workspace isolation, financial corrections, image upload/restore, publish/unpublish, and public RSVP on the staging domain.
3. Verify a fresh production build, readiness checks, HTTPS redirect, secure session cookie, OAuth callback, SMTP delivery, file permissions, log collection, and clean error responses with debug disabled.
4. Back up the database and private uploads, restore them into a separate staging copy, and document rollback steps. Confirm monitoring alerts on failed health checks and application errors.
5. Finish the product decisions recorded in `docs/01_PRODUCT_PRD.md` and the outstanding Screen IDs in `docs/DEVELOPMENT_PROGRESS.md`. Keep the launch private until those decisions and acceptance checks are signed off.

## Current known release blockers

- MySQL 8.4 migration and feature verification are pending; current tests use in-memory SQLite.
- Google OAuth consent remains in Testing mode and allows only configured test users.
- Full visual/accessibility/product acceptance is incomplete (tracker: 0/77 fully verified).
- Product decisions that affect financial permissions, event multiplicity, savings reconciliation, RSVP changes, and invitation image privacy remain recorded as needing validation in the PRD.
- A production domain/server, SMTP sender, OAuth production credentials, durable upload target, backup destination, and monitoring destination still need to be selected and configured.
