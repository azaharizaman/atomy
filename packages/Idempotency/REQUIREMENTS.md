# Nexus\Idempotency — Requirements

## Functional

1. **Composite identity:** Every idempotency scope is `(tenantId, operationRef, clientKey)`; client key alone is never sufficient.
2. **Fingerprint:** Non-empty opaque string (typically a hash of a canonical command payload). Same key + different fingerprint ⇒ **conflict** (`IdempotencyFingerprintConflictException`).
3. **Begin outcomes:** `FirstExecution`, `Replay` (completed + same fingerprint), `InProgress` (pending/in-flight + same fingerprint).
4. **Complete / fail:** Only for **open** records (`Pending` or `InProgress`) with matching fingerprint; transitions to `Completed` or `Failed`.
5. **Policy:** Configurable pending TTL, optional completed replay TTL, and whether failed keys may be retried.

## Non-functional

- **Layer 1 only:** No framework imports, no database.
- **Anti-overlap:** This package must not own outbox dispatch, event sourcing, messaging transport, audit timelines, sequencing, or policy evaluation—see design doc in `docs/plans/2026-03-21-nexus-idempotency-layer1-design.md`.

## Value object limits (v1)

| Field | Max length / size |
|--------|-------------------|
| `TenantId` | 128 chars |
| `OperationRef` | 128 chars |
| `ClientKey` | 256 chars |
| `RequestFingerprint` | 512 chars |
| `ResultEnvelope` payload | 65536 bytes |
