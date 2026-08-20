# Deployment

Two artifacts: the Laravel API and the static React build. **Deploy them on the same origin** behind one web server, with `/api` reverse-proxied to Laravel. This keeps the relative signed URLs (3D + proof files) and the SPA's `/api/v1` calls working without CORS.

```
            ┌────────────── your-domain.com ──────────────┐
  Browser → │  /            → static SPA (frontend/dist)   │
            │  /api/*       → Laravel (backend, PHP-FPM)   │
            └──────────────────────────────────────────────┘
                                   │
                          PostgreSQL · S3 · Pusher
```

## Backend (Laravel)
1. Host with PHP 8.2+ and PostgreSQL. Set production `.env`:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain.com`
   - DB credentials; `FILESYSTEM_DISK=s3` + `AWS_*` (private bucket) for 3D/proof files
   - `MAIL_MAILER=smtp` + SMTP creds; `QUEUE_CONNECTION=redis` (or database)
   - `BROADCAST_CONNECTION=pusher` + `PUSHER_*` for real-time
   - Raise PHP `upload_max_filesize` / `post_max_size` ≥ 25M
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force` then seed roles once: `php artisan db:seed --class=RolePermissionSeeder --force`
4. `php artisan storage:link`, `config:cache`, `route:cache`
5. Run a **queue worker** (`php artisan queue:work`) and the **scheduler** (cron: `* * * * * php artisan schedule:run`) — the scheduler runs the monthly `orders:archive`.

## Frontend (SPA)
```bash
cd frontend && npm ci && npm run build   # → frontend/dist
```
Serve `frontend/dist` as static files. Configure the web server to:
- serve the SPA with a history fallback (all non-`/api` routes → `index.html`)
- reverse-proxy `/api/*` (and the signed `/api/v1/*/file` routes) to Laravel

## Hosting note (from the quotation)
Hosting is the client's responsibility (quotation note #8). Any PHP+Postgres host (shared, VPS, or PaaS) plus a static host/CDN for the SPA works; the same-origin reverse-proxy setup above is the simplest.

## CI
GitHub Actions run on every push (`backend/.github/workflows/ci.yml`: Pint + Larastan + Pest/PHPUnit on Postgres; `frontend/.github/workflows/ci.yml`: lint + Vitest + build). Green CI is the deploy gate.
