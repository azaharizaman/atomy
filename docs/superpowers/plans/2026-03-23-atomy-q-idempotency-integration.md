# Atomy-Q Idempotency Integration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship `nexus/laravel-idempotency-adapter` (Layer 3) plus Atomy-Q API and WEB wiring so Phase 1 mutating routes require `Idempotency-Key`, use `Nexus\Idempotency` for deduplication and replay, and match the approved integration spec.

**Architecture:** New Laravel adapter package under `adapters/Laravel/Idempotency/` implements `IdempotencyStoreInterface` (DB + migration), clock, middleware (`jwt.auth` → `tenant` → `idempotency`), fingerprint hashing, and request attributes for `IdempotencyRequest`. Atomy-Q adds `nexus/idempotency` + adapter dependencies, named routes, a small HTTP replay envelope helper, controller-level `complete`/`fail` (try/finally) using the canonical v1 envelope string, feature tests, axios header injection, and scheduler hook for cleanup.

**Tech stack:** PHP 8.3, Laravel 12, `nexus/idempotency` (L1), MySQL-compatible migration, PHPUnit, Vitest for WEB optional, Axios interceptors.

**Specs (read first):**
- `docs/superpowers/specs/2026-03-23-atomy-q-idempotency-integration-design.md` (approved)
- `docs/superpowers/specs/2026-03-22-idempotency-laravel-adapter-design.md` (adapter details)

---

## File map (expected)

| Path | Role |
|------|------|
| `adapters/Laravel/Idempotency/composer.json` | Package manifest, Laravel provider auto-discovery |
| `adapters/Laravel/Idempotency/config/nexus-idempotency.php` | Store, policy TTLs, header name |
| `adapters/Laravel/Idempotency/database/migrations/*_create_nexus_idempotency_records_table.php` | Table + unique `(tenant_id, operation_ref, client_key)` |
| `adapters/Laravel/Idempotency/src/Adapters/DatabaseIdempotencyStore.php` | L1 query + persist ports |
| `adapters/Laravel/Idempotency/src/Clock/LaravelIdempotencyClock.php` | UTC `DateTimeImmutable` |
| `adapters/Laravel/Idempotency/src/Http/IdempotencyMiddleware.php` | Header, tenant from `auth_tenant_id`, `OperationRef` from route name, `begin()`, replay/409 short-circuit |
| `adapters/Laravel/Idempotency/src/Http/IdempotencyRequest.php` | VOs bag for controllers |
| `adapters/Laravel/Idempotency/src/Http/Concerns/Idempotency.php` | Trait `getIdempotencyRequest()` |
| `adapters/Laravel/Idempotency/src/Support/RequestFingerprintFactory.php` | Canonical JSON + method + route path → `RequestFingerprint` (hash if needed to respect 512-byte VO limit) |
| `adapters/Laravel/Idempotency/src/Providers/IdempotencyAdapterServiceProvider.php` | Bindings |
| `adapters/Laravel/Idempotency/src/Console/Commands/IdempotencyCleanupCommand.php` | Expired row cleanup |
| `adapters/Laravel/Idempotency/src/Contracts/ReplayResponseFactoryInterface.php` | Turns stored v1 envelope string into `SymfonyResponse` (implemented in Atomy-Q) |
| `apps/atomy-q/API/composer.json` | Require `nexus/idempotency`, `nexus/laravel-idempotency-adapter` |
| `apps/atomy-q/API/bootstrap/app.php` or `AppServiceProvider` | Register `idempotency` middleware alias |
| `apps/atomy-q/API/routes/api.php` | `->name()` on Phase 1 routes; wrap groups with `idempotency` |
| `apps/atomy-q/API/app/Http/Idempotency/IdempotencyReplayResponseFactory.php` | Implements `ReplayResponseFactoryInterface` (v1 decode) — **single owner** for replay bytes → Laravel response |
| `apps/atomy-q/WEB/src/lib/api.ts` | Generate/preserve `Idempotency-Key` |
| `packages/Idempotency/IMPLEMENTATION_SUMMARY.md` | Note adapter dependency (per AGENTS.md if touched) |

---

### Task 1: Scaffold `nexus/laravel-idempotency-adapter` package

**Files:**
- Create: `adapters/Laravel/Idempotency/composer.json` (require `nexus/idempotency`, illuminate 11/12, psr/log)
- Create: `adapters/Laravel/Idempotency/phpunit.xml` (mirror other Laravel adapters)
- Modify: `apps/atomy-q/API/composer.json` — add path repo if missing (already has `adapters/Laravel/*`)

- [ ] **Step 1:** Copy structure from `adapters/Laravel/Tenant/` (composer layout, `src/`, `tests/`).
- [ ] **Step 2:** `composer validate` in adapter directory; from `apps/atomy-q/API` run `composer require nexus/laravel-idempotency-adapter:*@dev` (or add to require + `composer update`).
- [ ] **Step 3:** Commit: `feat(idempotency-adapter): scaffold composer package`

---

### Task 2: Migration + Eloquent model + `DatabaseIdempotencyStore`

**Files:**
- Create: `adapters/Laravel/Idempotency/database/migrations/2026_03_23_000001_create_nexus_idempotency_records_table.php`
- Create: `adapters/Laravel/Idempotency/src/Models/IdempotencyRecord.php`
- Create: `adapters/Laravel/Idempotency/src/Adapters/DatabaseIdempotencyStore.php`
- Test: `adapters/Laravel/Idempotency/tests/Unit/DatabaseIdempotencyStoreTest.php` (use L1 `InMemoryIdempotencyStore` patterns; integration test with SQLite if project allows)

- [ ] **Step 1:** Implement migration matching adapter spec + L1 `IdempotencyRecord` fields (status, fingerprint, attempt_token, result payload as string/JSON column).
- [ ] **Step 2:** Implement `claimPending` / `find` / `save` / `delete` per `packages/Idempotency` contracts — read `IdempotencyPersistInterface` + `IdempotencyQueryInterface` method signatures.
- [ ] **Step 3:** Unit tests for happy path + fingerprint conflict paths where store is involved.
- [ ] **Step 4:** Commit: `feat(idempotency-adapter): database store and migration`

---

### Task 3: Clock, policy factory, `IdempotencyService` registration

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Clock/LaravelIdempotencyClock.php`
- Create: `adapters/Laravel/Idempotency/src/Support/IdempotencyPolicyFactory.php`
- Create: `adapters/Laravel/Idempotency/config/nexus-idempotency.php`
- Create: `adapters/Laravel/Idempotency/src/Providers/IdempotencyAdapterServiceProvider.php`

- [ ] **Step 1:** Clock returns `CarbonImmutable::now('UTC')` → `DateTimeImmutable`.
- [ ] **Step 2:** Bind `IdempotencyService` as singleton with L1 `IdempotencyPolicy::default()` or config-driven factory.
- [ ] **Step 3:** `vendor:publish` tag for config (document in adapter README snippet).
- [ ] **Step 4:** Commit: `feat(idempotency-adapter): service provider and policy`

---

### Task 4: Fingerprint factory + middleware + HTTP types

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Support/RequestFingerprintFactory.php`
- Create: `adapters/Laravel/Idempotency/src/Http/IdempotencyRequest.php`
- Create: `adapters/Laravel/Idempotency/src/Http/Concerns/Idempotency.php`
- Create: `adapters/Laravel/Idempotency/src/Http/IdempotencyMiddleware.php`
- Modify: `adapters/Laravel/Idempotency/src/Providers/IdempotencyAdapterServiceProvider.php` — register middleware alias `idempotency`

**Behavior checklist (from spec):**
- Missing header → 400, `code`: `idempotency_key_required`
- Invalid `ClientKey` → 400, `idempotency_key_invalid`
- No route name → 500, `idempotency_operation_ref_missing`
- No `auth_tenant_id` → 500, `idempotency_tenant_missing`
- `BeginOutcome::Replay` → build Laravel response from stored envelope via **`ReplayResponseFactoryInterface`** injected into middleware (Atomy-Q registers the v1 decoder in Task 7 — do not implement decode in two places)
- `BeginOutcome::InProgress` → 409, `idempotency_in_progress`
- Map L1 `IdempotencyFingerprintConflictException` → 409, `idempotency_fingerprint_conflict`

- [ ] **Step 1:** Add `ReplayResponseFactoryInterface` + `fromString(string $payload): SymfonyResponse` in the adapter package.
- [ ] **Step 2:** Write middleware unit/integration tests with mocked `IdempotencyServiceInterface` and a stub `ReplayResponseFactoryInterface` where practical.
- [ ] **Step 3:** Commit: `feat(idempotency-adapter): middleware and fingerprint`

---

### Task 5: Cleanup command + adapter tests + package docs

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Console/Commands/IdempotencyCleanupCommand.php`
- Create: `adapters/Laravel/Idempotency/README.md` (install, publish, migrate, middleware order)
- Modify: `packages/Idempotency/IMPLEMENTATION_SUMMARY.md` — one paragraph linking adapter

- [ ] **Step 1:** Register command in provider; delete expired rows in chunks.
- [ ] **Step 2:** Run `./vendor/bin/phpunit` from adapter directory.
- [ ] **Step 3:** Commit: `feat(idempotency-adapter): cleanup command and docs`

---

### Task 6: Atomy-Q — replay envelope helper + `ReplayResponseFactoryInterface` binding

**Files:**
- Create: `apps/atomy-q/API/app/Http/Idempotency/IdempotencyReplayEnvelope.php` (static `encode(Response $response): string`, `maxBytes` check → throw domain exception with `code` **`idempotency_envelope_too_large`** mapped to spec §10)
- Create: `apps/atomy-q/API/app/Http/Idempotency/IdempotencyReplayResponseFactory.php` — implements `Nexus\Laravel\Idempotency\Contracts\ReplayResponseFactoryInterface` (decode v1 JSON → `Illuminate\Http\Response`)
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php` (or dedicated provider) — `ReplayResponseFactoryInterface` → `IdempotencyReplayResponseFactory`
- Modify: `apps/atomy-q/API/bootstrap/app.php` or exception handler — map envelope-too-large exception to 500 + `idempotency_envelope_too_large` if not caught earlier

- [ ] **Step 1:** Unit test: encode/decode round-trip preserves status + JSON body.
- [ ] **Step 2:** Commit: `feat(atomy-q-api): idempotency replay envelope helpers and factory binding`

---

### Task 7: Wire Atomy-Q API — dependency, middleware alias, scheduler, replay binding

**Files:**
- Modify: `apps/atomy-q/API/composer.json` — require `nexus/idempotency`, `nexus/laravel-idempotency-adapter`
- Modify: `apps/atomy-q/API/bootstrap/app.php` — alias e.g. `idempotency` → middleware class
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php` — register `ReplayResponseFactoryInterface` (depends on Task 6)
- Modify: `apps/atomy-q/API/routes/console.php` or `routes/console.php` / `bootstrap/app.php` — `Schedule::command('idempotency:cleanup')->daily()`

- [ ] **Step 1:** `composer update` in API; run migration on local DB.
- [ ] **Step 2:** Verify middleware `Replay` path uses the bound factory (manual smoke or integration test).
- [ ] **Step 3:** Commit: `feat(atomy-q-api): wire idempotency adapter`

---

### Task 8: Phase 1 route names + middleware group

**Files:**
- Modify: `apps/atomy-q/API/routes/api.php`

**Named routes (illustrative — align names with spec §12):**

| Route | Suggested name |
|-------|----------------|
| `POST rfqs` | `v1.rfqs.store` |
| `POST rfqs/bulk-action` | `v1.rfqs.bulk-action` |
| `POST rfqs/{rfqId}/invitations` | `v1.rfqs.invitations.store` |
| `POST rfqs/{rfqId}/invitations/{invId}/remind` | `v1.rfqs.invitations.remind` |
| `POST rfqs/{rfqId}/duplicate` | `v1.rfqs.duplicate` |
| `POST rfq-templates` | `v1.rfq-templates.store` |
| `POST rfq-templates/{id}/duplicate` | `v1.rfq-templates.duplicate` |
| `POST rfq-templates/{id}/apply` | `v1.rfq-templates.apply` |
| `POST projects` | `v1.projects.store` |
| `POST tasks` | `v1.tasks.store` |

- [ ] **Step 1:** Add `->name(...)` to each Phase 1 route.
- [ ] **Step 2:** Nest only those routes (or duplicate route definitions into) a group: `Route::middleware(['jwt.auth', 'tenant', 'idempotency'])->group(...)` — **do not** duplicate `jwt.auth`/`tenant` if already outer; prefer inner group adding **only** `idempotency` after existing middleware.

Example pattern:

```php
Route::middleware(['jwt.auth', 'tenant'])->group(function (): void {
    Route::middleware(['idempotency'])->group(function (): void {
        Route::post('rfqs', ...)->name('v1.rfqs.store');
        // ...
    });
    // ... unprotected-by-idempotency routes
});
```

- [ ] **Step 3:** Add feature test: `GET` or unauthenticated route unchanged; Phase 1 `POST` without header → 400 + `idempotency_key_required`.
- [ ] **Step 4:** Commit: `feat(atomy-q-api): phase 1 idempotent routes`

---

### Task 9: Controllers — complete / fail integration

**Files:**
- Modify: each Phase 1 controller action listed in spec (e.g. `RfqController@store`, `RfqController@bulkAction`, … `ProjectController@store`, `TaskController@store`, RFQ template controller methods)

**Pattern (per action):**

```php
$idemp = $this->getIdempotencyRequest($request);
// null only if middleware bug — assert or throw
try {
    // existing validation + logic
    $response = ...; // existing response()->json(...)
    $this->idempotencyService->complete(
        ...,
        new ResultEnvelope(IdempotencyReplayEnvelope::encode($response)),
    );
    return $response;
} catch (\Throwable $e) {
    $this->idempotencyService->fail(...);
    throw $e;
}
```

**Validation / early exits:** If validation fails **after** a first-execution reservation exists (same request), call **`fail()`** before returning **422** — `try/catch` does not run on non-throwing `return response()->json(..., 422)`.

- [ ] **Step 1:** Add `use Idempotency` trait from adapter; inject `IdempotencyServiceInterface`.
- [ ] **Step 2:** Refactor each Phase 1 handler; keep validation **before** `complete` on success only.
- [ ] **Step 3:** Feature test: same key + body → duplicate RFQ not created (mock or DB assertion).
- [ ] **Step 4:** Feature test: same key + altered body → 409 `idempotency_fingerprint_conflict`.
- [ ] **Step 5:** Feature test (optional): concurrent duplicate `POST` with same key → 409 `idempotency_in_progress` **if** reliable without flakiness (spec §14); if skipped, document why in a short comment in the test file.
- [ ] **Step 6:** Commit: `feat(atomy-q-api): idempotency complete/fail on phase 1 controllers`

---

### Task 10: WEB — `Idempotency-Key` on Phase 1 mutations

**Files:**
- Modify: `apps/atomy-q/WEB/src/lib/api.ts`
- Optional: `apps/atomy-q/WEB/src/lib/idempotency-key.ts` — `crypto.randomUUID()`, attach to config
- Test: `apps/atomy-q/WEB/src/lib/api.test.ts` or hook test — assert header set for `post` to `/rfqs`

**Implementation note:** Interceptor: for `POST` to paths matching Phase 1 prefix list, set `Idempotency-Key` if not set; preserve key on retried `config` (store on `config` object).

**Canonical transport (spec §13):** Phase 1 UI flows must use **`src/lib/api.ts`** so the interceptor applies. If any feature uses `generated/api/client` directly, add a thin wrapper that injects the same header or document that path as out-of-scope for Phase 1.

- [ ] **Step 1:** Implement interceptor; run `npm run test:unit` in `apps/atomy-q/WEB`.
- [ ] **Step 2:** Commit: `feat(atomy-q-web): send Idempotency-Key on phase 1 mutations`

---

### Task 11: OpenAPI / Scramble (spec §16)

**Files:**
- Modify: controller docblocks or Scramble config so Phase 1 operations document `Idempotency-Key` header and error `code` values.

- [ ] **Step 1:** Verify generated docs in dev.
- [ ] **Step 2:** Commit: `docs(atomy-q-api): document idempotency header in API docs`

---

### Task 12: Route list assertion (spec §7)

**Files:**
- Create: `apps/atomy-q/API/tests/Feature/Idempotency/Phase1RoutesNamedTest.php`

- [ ] **Step 1:** Assert each known Phase 1 route name resolves and middleware `idempotency` is applied (via `$route->gatherMiddleware()` or custom `Route::getRoutes()` filter).
- [ ] **Step 2:** Commit: `test(atomy-q-api): assert phase 1 idempotency routes are named`

---

## Verification commands (final)

From `apps/atomy-q/API`:

```bash
composer install
php artisan test --filter=Idempotency
```

From `adapters/Laravel/Idempotency`:

```bash
composer install
./vendor/bin/phpunit
```

From `apps/atomy-q/WEB`:

```bash
npm run test:unit
```

**Expected:** All green; manual smoke: create RFQ twice with same key returns same response without double insert.

---

## Execution handoff

Plan complete and saved to `docs/superpowers/plans/2026-03-23-atomy-q-idempotency-integration.md`.

**1. Subagent-driven (recommended)** — Dispatch a fresh subagent per task, review between tasks.

**2. Inline execution** — Execute tasks in this session using executing-plans with checkpoints.

**Which approach do you want?**
