# Idempotency Package – Implementation Summary

**Status:** Implemented (Layer 1 v1)

## Implemented

- `IdempotencyService` (`begin`, `complete`, `fail`) implementing `IdempotencyServiceInterface`
- Contracts: `IdempotencyStoreInterface`, `IdempotencyClockInterface`, `IdempotencyServiceInterface`
- Domain: `IdempotencyRecord`, `BeginDecision`, `IdempotencyPolicy`
- Value objects: `TenantId`, `OperationRef`, `ClientKey`, `RequestFingerprint`, `ResultEnvelope`
- Enums: `IdempotencyRecordStatus`, `BeginOutcome`
- Exceptions for missing/invalid keys, fingerprint conflict, tenant mismatch, expiry, wrong completion state
- `InMemoryIdempotencyStore`, `SystemClock`
- Unit tests (PHPUnit) including replay, conflict, in-progress, failed retry policy, tenant isolation, pending expiry

## Deferred

- Database / Redis adapters (Layer 3)
- HTTP `Idempotency-Key` handling (Layer 3)
- Distributed locks (Layer 2/3)

## Verification

```bash
vendor/bin/phpunit -c packages/Idempotency/phpunit.xml
```
