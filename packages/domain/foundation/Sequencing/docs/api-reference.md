# API Reference: Nexus\\Sequencing

This reference documents the public contracts exposed by Nexus\\Sequencing so consuming applications can safely implement repositories, wire services, and extend behaviors while staying framework agnostic.

## Interfaces

### `SequenceInterface`

Represents an immutable sequence definition.

| Method | Description |
| --- | --- |
| `getName(): string` | Unique identifier for the sequence (e.g., `invoice_number`). |
| `getScopeIdentifier(): ?string` | Optional scope/tenant key. |
| `getPattern(): string` | Active pattern with variables. |
| `getGapPolicy(): string` | Serialized gap policy value consumed by `GapPolicy`. |
| `getOverflowBehavior(): string` | Serialized overflow rule consumed by `OverflowBehavior`. |
| `getResetPeriod(): string` | Serialized reset period consumed by `ResetPeriod`. |
| `getStepSize(): int` | Counter increment (defaults to `1`). |
| `isLocked(): bool` | Indicates whether generation is paused. |
| `getMetadata(): array` | Arbitrary metadata for the consuming application. |

### `SequenceRepositoryInterface`

Persistence boundary for sequence definitions and lock status.

- `findByNameAndScope(string $sequenceName, ?string $scopeIdentifier = null): SequenceInterface`
- `lock(SequenceInterface $sequence): void`
- `unlock(SequenceInterface $sequence): void`
- `save(SequenceInterface $sequence): void`

Implementations must enforce tenant isolation and optimistic locking when updating definitions.

### `CounterRepositoryInterface`

Encapsulates counter state management.

| Method | Notes |
| --- | --- |
| `getCurrentValue(SequenceInterface $sequence): int` | Non-locking read of the counter. |
| `getCurrentValueWithLock(SequenceInterface $sequence): int` | Pessimistic read (`SELECT ... FOR UPDATE`). |
| `increment(SequenceInterface $sequence, int $step): int` | Atomic increment returning the new value. |
| `setCounterValue(SequenceInterface $sequence, int $value): void` | Manual override used by admins/tooling. |
| `needsReset(SequenceInterface $sequence): bool` | Determine if reset policy is due. |
| `reset(SequenceInterface $sequence): void` | Reset counter back to its start value. |

Repositories must wrap `getCurrentValueWithLock()`/`increment()` in the same transaction to guarantee atomicity.

### `ReservationRepositoryInterface`

Controls number reservations used for batch workflows.

- `reserve(SequenceInterface $sequence, int $count, int $ttlMinutes): array`
- `release(SequenceInterface $sequence, array $numbers): void`
- `releaseExpiredReservations(): int`

### `GapRepositoryInterface`

Records gaps (voided/cancelled numbers) and enables reclaiming when gap policies allow.

- `recordGap(SequenceInterface $sequence, string $number): void`
- `getNextGap(SequenceInterface $sequence): ?string`
- `markGapFilled(SequenceInterface $sequence, string $number): void`
- `reportGaps(SequenceInterface $sequence): array`

### `PatternVersionRepositoryInterface`

Stores and retrieves effective-dated pattern versions.

- `findActivePattern(string $sequenceName, ?string $scopeId, \\DateTimeImmutable $effectiveDate): ?array`
- `savePatternVersion(array $payload): void`
- `archivePattern(string $patternId): void`

### `SequenceAuditInterface`

Abstraction for audit logging (recommended to delegate to `Nexus\\AuditLogger`).

| Method | Description |
| --- | --- |
| `logNumberGenerated(SequenceInterface $sequence, string $number, array $context): void` | Called after every successful generation. |
| `logGapReclaimed(SequenceInterface $sequence, string $number): void` | Captures when a gap is filled. |
| `logCounterReset(SequenceInterface $sequence, int $oldValue, int $newValue, string $reason): void` | Captures automatic or manual resets. |
| `logCounterOverridden(SequenceInterface $sequence, int $oldValue, int $newValue, ?string $actor): void` | Records overrides initiated by administrators. |
| `logLockStatusChanged(SequenceInterface $sequence, bool $locked, ?string $actor): void` | Tracks lock/unlock operations. |

## Services

### `SequenceManager`

Primary façade for issuing numbers.

- `generate(string $sequenceName, ?string $scopeIdentifier = null, array $contextVariables = []): string`
  - Fills gaps when allowed, resets counters when due, increments atomically, builds final string via `PatternParser`, and writes audit logs.
- `preview(...)`
  - Returns the next number without consuming the counter (uses `getCurrentValue()` + step).
- `overrideCounter(...)`
  - Sets counter to an explicit value, validating via `InvalidCounterValueException` when the new value would regress.
- `lock(...)` / `unlock(...)`
  - Delegates to repository and logs the state change.
- `composeChild(string $parentNumber, string $childPattern, int $childCounter = 1): string`
  - Appends secondary identifiers (useful for shipment lots, credit notes tied to invoices, etc.).

### `BulkGeneratorService`

Generates multiple numbers within a single database lock.

- `generateBulk(string $sequenceName, int $count, ?string $scopeIdentifier = null, array $contextVariables = []): array`
  - Loops across `SequenceManager::generate()` while ensuring the counter is locked once to reduce contention.

### `ReservationService`

- `reserve(string $sequenceName, int $count, int $ttlMinutes, ?string $scopeIdentifier = null): array`
- `release(string $sequenceName, array $numbers, ?string $scopeIdentifier = null): void`
- `releaseExpired(string $sequenceName, ?string $scopeIdentifier = null): int`

Number reservations back order-processing flows, asynchronous picking, or offline batch jobs.

### `GapManager`

- `getGapReport(string $sequenceName, ?string $scopeIdentifier = null): array`
- `reclaimGap(string $sequenceName, string $number, ?string $scopeIdentifier = null): void`
- `recordGap(string $sequenceName, string $number, ?string $scopeIdentifier = null): void`

### `PatternParser`

Parses pattern strings: `parse(string $pattern, int $counterValue, array $contextVariables = []): string`. Throws `InvalidPatternException` when encountering unknown tokens.

### `PatternVersionManager` & `PatternMigrationService`

Helpers for staged pattern changes. `PatternVersionManager` determines which pattern should be active for a timestamp; `PatternMigrationService` copies counters, audits, and metadata to new patterns.

### `SequenceValidationService`

`validate(string $sequenceName, string $candidateNumber, ?string $scopeIdentifier = null): bool` — ensures supplied numbers match the registered pattern.

### `ExhaustionMonitor`

Calculates how close a sequence is to exhaustion (based on padding and reset policies) so applications can alert before failures.

### `SequenceMetricsService`

Produces `SequenceMetrics` snapshots that aggregate generation counts, gaps filled, reservations active, and timestamps of last operations.

## Value Objects

| Class | Description |
| --- | --- |
| `ResetPeriod` | Factory helpers (`never()`, `daily()`, `monthly()`, `yearly()`, `interval(int $count)`), plus `toString()`/`isDue()` for repository logic. |
| `OverflowBehavior` | Models exhaustion strategies (throw, switch pattern, extend padding). |
| `GapPolicy` | Encapsulates configuration for gap handling; exposes helpers such as `allowsFilling()` and `shouldReportOnly()`. |
| `PatternVariable` | Represents an individual token (name, padding length) inside a pattern string. |
| `SequenceMetrics` | Immutable DTO summarizing generated counts, gaps filled, reservations active, and timestamps for reporting dashboards. |

## Exceptions

- `InvalidPatternException`
- `PatternCollisionException`
- `SequenceNotFoundException`
- `SequenceLockedException`
- `SequenceExhaustedException`
- `InvalidCounterValueException`
- `InvalidResetPeriodException`
- `InvalidGapPolicyException`
- `InvalidOverflowBehaviorException`
- `CounterOverflowException`
- `ReservationExpiredException`

These exceptions surface actionable feedback to consuming systems and should be mapped to application-specific responses (e.g., HTTP 409 when a sequence is locked).

## Usage Patterns

### Pattern Parsing
```php
$number = $patternParser->parse(
    pattern: 'INV-{YEAR}-{COUNTER:5}',
    counterValue: 42,
    contextVariables: ['DEPARTMENT' => 'SALES']
);
// => INV-2025-00042
```

### Counter Reset Handling
Repositories typically perform the following per request:
1. `needsReset()` → `true`
2. `reset()` and audit the event
3. `getCurrentValueWithLock()`
4. `increment()` returning the new value used by `PatternParser`

### Auditing
`SequenceAuditInterface` implementations should write to `Nexus\\AuditLogger` or a PSR-3 logger. Always capture sequence name, scope, number, actor, and metadata for SOX/GDPR-compliant trails.

### Extending Reset or Overflow Policies
Add new strategy identifiers to your application, persist them via repositories, and interpret them inside `ResetPeriod::fromString()` or `OverflowBehavior::fromString()` when instantiating value objects.

Refer to the [Integration Guide](integration-guide.md) for framework-specific bindings and to `docs/examples` for runnable snippets that exercise these APIs.
