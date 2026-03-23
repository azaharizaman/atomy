# Idempotency Package – Implementation Summary

**Status:** Implemented (Layer 1 v1)

## Implemented

- `IdempotencyService` (`begin`, `complete`, `fail`) implementing `IdempotencyServiceInterface`
- Contracts: `IdempotencyServiceInterface`, `IdempotencyQueryInterface`, `IdempotencyPersistInterface`, composite `IdempotencyStoreInterface`, `IdempotencyClockInterface`
- Domain: `IdempotencyRecord` (includes per-attempt `AttemptToken`), `BeginDecision`, `IdempotencyPolicy`, `ClaimPendingResult`
- Value objects: `TenantId`, `OperationRef`, `ClientKey`, `RequestFingerprint`, `AttemptToken`, `ResultEnvelope`
- Enums: `IdempotencyRecordStatus`, `BeginOutcome`
- Exceptions for invalid keys, fingerprint conflict, tenant mismatch (generic message), failed-retry disallowed, expiry, wrong completion state
- `InMemoryIdempotencyStore` (JSON-encoded tuple keys; avoids pipe-delimiter collisions) and `SystemClock` (UTC)
- Internal `BoundedStringValidator` for shared bounded-string rules on VOs
- Unit tests (PHPUnit) including replay, conflict, in-progress, failed retry policy, fingerprint-before-delete on failed retry, stale attempt token, tenant isolation, pending expiry

## Deferred

- Redis-backed store (optional Layer 3)
- Distributed locks (Layer 2/3)

## Layer 3

- **`nexus/laravel-idempotency-adapter`** (`adapters/Laravel/Idempotency/`): `DatabaseIdempotencyStore`, `IdempotencyMiddleware`, cleanup command, `ReplayResponseFactoryInterface`. Consumed by Atomy-Q API with `ReplayResponseFactoryInterface` bound to `App\Http\Idempotency\IdempotencyReplayResponseFactory`.

## Verification

```bash
vendor/bin/phpunit -c packages/Idempotency/phpunit.xml
```

## Traceability

When changing behavior, update this file and the **Duplication check source** row in `VALUATION_MATRIX.md` so reviewers can map the package to `NEXUS_PACKAGES_REFERENCE.md` without duplicating work.
