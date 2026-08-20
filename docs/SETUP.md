# Local Setup — Cedarside Holding Corp.

Get the whole system running locally.

## Prerequisites
- **PHP 8.2+** + **Composer**
- **Node 18+** + npm
- **PostgreSQL 16** (running on `127.0.0.1:5432`)

## 1. Databases
Create the app + test databases (psql or your GUI):
```sql
CREATE DATABASE furniture_monitoring;
CREATE DATABASE furniture_monitoring_test;
```

## 2. Backend (Laravel API — `backend/`)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```
Edit `.env` → set your Postgres credentials:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=furniture_monitoring
DB_USERNAME=postgres
DB_PASSWORD=your_password
ADMIN_EMAIL=admin@cedarside.local
ADMIN_PASSWORD=password
```
Migrate + seed roles/permissions and the admin:
```bash
php artisan migrate:fresh --seed
```
Run the API. **3D uploads need PHP's limits raised above 20 MB** (defaults are often 8 MB), so start it with overrides:
```bash
php -d upload_max_filesize=25M -d post_max_size=26M artisan serve   # http://127.0.0.1:8000
```
(Or set `upload_max_filesize` / `post_max_size` permanently in `php.ini`.)

## 3. Frontend (React SPA — `frontend/`)
```bash
cd frontend
npm install
npm run dev        # http://localhost:5173  (proxies /api → :8000)
```

## 4. Log in
Open http://localhost:5173 → **admin@cedarside.local / password** (the seeded admin sees every module).

Create staff/customer users under **Users**, or seed more via tinker; assign roles: `admin`, `production_manager`, `manufacturing_operative`, `logistics_coordinator`, `delivery_personnel`, `customer`, `qa_tester`.

## 5. Tests
```bash
# backend (uses furniture_monitoring_test; DB_PASSWORD comes from your .env)
cd backend && php artisan test

# frontend unit tests
cd frontend && npm run test
```

## Optional — real-time (Pusher)
Real-time broadcasts run on the `log` driver by default (the SPA polls). To enable live push, set the `PUSHER_*` keys in `.env` and `BROADCAST_CONNECTION=pusher`.

## Quality tooling
- Backend: `./vendor/bin/pint` (format), `./vendor/bin/phpstan analyse` (Larastan level 6)
- Frontend: `npm run lint`, `npm run build`
