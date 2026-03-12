# Atomy-Q API (Laravel)

This is the backend API for the Atomy‑Q Quote Comparison & Procurement platform. It exposes the `/api/v1` REST endpoints used by the WEB app and includes JWT authentication, multi‑tenant scoping, and stubbed controllers for the full endpoint surface.

## Requirements
- PHP 8.3+
- Composer
- PostgreSQL (recommended for local dev; tests can use SQLite in memory)
- Redis (optional for caching/queues in local dev)

## Setup
1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Create your environment file**
   ```bash
   cp .env.example .env
   ```
   If you do not have an `.env.example`, create `.env` with the variables below.

3. **Generate app key**
   ```bash
   php artisan key:generate
   ```

4. **Run migrations and seed data**
   ```bash
   php artisan migrate --seed
   ```

5. **Run the API**
   ```bash
   php artisan serve
   ```
   The API will be available at `http://localhost:8000/api/v1`.

## Required Environment Variables

### App
- `APP_ENV` (e.g. `local`)
- `APP_KEY` (set by `php artisan key:generate`)
- `APP_URL` (e.g. `http://localhost:8000`)

### Database
- `DB_CONNECTION` (e.g. `pgsql`)
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

### JWT Auth
- `JWT_SECRET` (required; must be non-empty)
- `JWT_TTL` (minutes)
- `JWT_REFRESH_TTL` (minutes)
- `JWT_ALGO` (e.g. `HS256`)
- `JWT_ISSUER` (e.g. `atomy-q`)

### Optional (App Config)
- `REDIS_URL`
- `FILESYSTEM_DISK` (set to `s3` for MinIO)
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BUCKET`
- `AWS_URL` (MinIO base URL)
- `AWS_ENDPOINT` (MinIO endpoint URL)
- `AWS_USE_PATH_STYLE_ENDPOINTS` (set to `true` for MinIO)
- `ATOMY_DEFAULT_PER_PAGE`
- `ATOMY_MAX_PER_PAGE`
- `ATOMY_MAX_UPLOAD_MB`
- `ATOMY_MFA_DEVICE_TRUST_DAYS`
- `ATOMY_APPROVAL_SLA_HOURS`
- `ATOMY_APPROVAL_MAX_SNOOZE_HOURS`
- `ATOMY_MIN_VENDORS_FINAL`
- `ATOMY_MIN_VENDORS_PREVIEW`
- `ATOMY_CONFIDENCE_THRESHOLD`

## Example `.env`
```env
APP_ENV=local
APP_URL=http://localhost:8000
APP_KEY=base64:replace-me

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=atomy_dev
DB_USERNAME=postgres
DB_PASSWORD=secret

JWT_SECRET=base64:change-me
JWT_TTL=60
JWT_REFRESH_TTL=120
JWT_ALGO=HS256
JWT_ISSUER=atomy-q

REDIS_URL=redis://localhost:6379/0

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=http://localhost:9000
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINTS=true
```
