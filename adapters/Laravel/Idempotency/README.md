# nexus/laravel-idempotency-adapter

Layer 3 Laravel adapter for [`nexus/idempotency`](../../packages/Idempotency): database store, middleware (`idempotency`), cleanup command, fingerprint helper, and `ReplayResponseFactoryInterface` hook for HTTP replay.

## Install (monorepo / path repo)

Require from the consuming Laravel app:

```json
"nexus/laravel-idempotency-adapter": "*@dev"
```

The package auto-registers `Nexus\Laravel\Idempotency\Providers\IdempotencyAdapterServiceProvider`.

## Bind replay responses

Register an implementation of `Nexus\Laravel\Idempotency\Contracts\ReplayResponseFactoryInterface` (Atomy-Q uses `App\Http\Idempotency\IdempotencyReplayResponseFactory`).

## Middleware order

Use **`jwt.auth` → `tenant` → `idempotency`** on routes that require `Idempotency-Key`.

## Migrations & config

Migrations load from the package. Optional publish:

```bash
php artisan vendor:publish --tag=nexus-idempotency-config
```

## Tests (adapter package)

From `adapters/Laravel/Idempotency`:

```bash
../../../apps/atomy-q/API/vendor/bin/phpunit -c phpunit.xml
```

(Uses the Atomy-Q API `vendor/autoload.php` in this monorepo.)
