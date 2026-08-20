# Deployment — Laravel Cloud (backend) + Vercel (frontend)

The app is **cross-origin ready**: the SPA on Vercel calls the Laravel API on Laravel Cloud directly. Auth is bearer-token (no cookies), CORS is enabled on the API, and the signed 3D/proof-file URLs are host-agnostic (relative signatures) and prefixed with the API origin client-side. Every `git push` to `main` auto-deploys both.

```
   Browser ──► your-app.vercel.app (React SPA, static)
                     │  VITE_API_URL
                     ▼
              your-app.laravel.cloud (Laravel API)  ──►  managed PostgreSQL + object storage
```

Deploy the **backend first** (you need its URL for the frontend), then the frontend, then point CORS back at the frontend URL.

---

## 1. Backend → Laravel Cloud
1. **Create the app**: laravel.cloud → New Application → connect GitHub `John-CarloPaz/FurnitureManagementSystem`.
2. **Monorepo path**: set the application root/path to **`backend`** (the Laravel app lives in the subfolder).
3. **Database**: provision a **PostgreSQL** database in the same environment; Laravel Cloud wires the `DB_*` env vars for you.
4. **Environment variables** (dashboard):
   ```
   APP_NAME="Cedarside Holding Corp."
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://<your-app>.laravel.cloud
   APP_KEY=            # generate (dashboard button or `php artisan key:generate --show`)
   CORS_ALLOWED_ORIGINS=https://<your-frontend>.vercel.app   # set after step 2 below
   FILESYSTEM_DISK=s3      # + bucket creds (see Storage) — for 3D + proof files
   MAIL_MAILER=smtp        # + SMTP creds (order/status emails)
   BROADCAST_CONNECTION=pusher   # + PUSHER_* (optional live updates)
   ADMIN_EMAIL=admin@cedarside.local
   ADMIN_PASSWORD=<a strong password>
   ```
5. **Deploy command** (runs on every deploy): `php artisan migrate --force`
6. **First deploy only** — seed roles + admin (Laravel Cloud console / one-off command):
   `php artisan db:seed --class=RolePermissionSeeder --force`
7. **Enable** the **queue worker** (notifications) and the **scheduler** (monthly `orders:archive`) — both are toggles in Laravel Cloud.
8. **PHP limits**: raise `upload_max_filesize` / `post_max_size` to **≥ 25M** (3D uploads up to 20 MB).
9. **Storage**: 3D + proof files must live on durable storage. Attach an S3-compatible bucket (or Laravel Cloud object storage) and set `FILESYSTEM_DISK` + the bucket env. The signed URLs keep working (they point at the API, which streams from the disk).

Copy the resulting API URL (e.g. `https://cedarside.laravel.cloud`).

## 2. Frontend → Vercel
1. **New Project** → import the same GitHub repo.
2. **Root Directory** = **`frontend`**. Framework preset auto-detects **Vite** (also pinned in `vercel.json`).
3. **Environment variable**:
   ```
   VITE_API_URL=https://<your-app>.laravel.cloud   # the backend URL from step 1, no trailing slash
   ```
4. **Deploy.** Vercel builds `npm run build` → serves `dist` with SPA history fallback (from `vercel.json`).
5. Copy the Vercel URL (e.g. `https://cedarside.vercel.app`).

## 3. Close the loop (CORS)
Back on Laravel Cloud, set `CORS_ALLOWED_ORIGINS=https://<your-frontend>.vercel.app` and redeploy the backend. (Comma-separate to allow more than one origin, e.g. a custom domain.)

## Auto-deploy
Both platforms watch `main`. **Every `git push` redeploys** — Vercel rebuilds the SPA; Laravel Cloud rebuilds the API and runs `migrate --force`. Use a branch + PR if you want preview deploys before prod.

## Verify (post-deploy)
- `https://<api>/api/v1/health` → `{"status":"healthy"}`
- Log in on the Vercel URL as the seeded admin
- Open a product → the 3D model loads (confirms CORS + signed-file prefixing)
- Walk the [UAT checklist](./UAT-CHECKLIST.md)

## Local dev is unchanged
No `VITE_API_URL` locally → the Vite dev server proxies `/api` to `http://127.0.0.1:8000` and file URLs stay relative. See [SETUP.md](./SETUP.md).
