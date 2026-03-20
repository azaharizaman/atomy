# Atomy-Q staging checklist

## API (Laravel)

1. **Environment:** Copy `apps/atomy-q/API/.env.example` → `.env`; run `php artisan key:generate`.
2. **Database:** Set `DATABASE_URL` or `DB_*`; run `php artisan migrate`.
3. **Auth:** Set `JWT_SECRET` (strong random, 32+ bytes).
4. **CORS:** Set `CORS_ALLOWED_ORIGINS` to the deployed WEB origin(s).
5. **Mail:** Use a real `MAIL_*` configuration so password-reset emails deliver (not `log` in production).
6. **Health:** Laravel 11+ exposes `GET /up` (no `/api/v1` prefix). Load balancer or k8s probes should hit `https://<api-host>/up`.

## WEB (Next.js)

1. **Environment:** Copy `apps/atomy-q/WEB/.env.example` → `.env.local`.
2. **API:** Set `NEXT_PUBLIC_API_URL` to the public API base including `/api/v1`.
3. **Mocks:** Ensure `NEXT_PUBLIC_USE_MOCKS` is **false** in staging/production.

## Smoke

- Login, RFQ list (pagination), RFQ overview, approval queue list + detail, forgot/reset password request.
