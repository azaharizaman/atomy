# Getting Started with Nexus\Sequencing

Nexus\Sequencing delivers framework-agnostic, atomic sequence generation for ERP workloads. This guide walks through prerequisites, installation, configuration, and your first integration so you can begin issuing safe identifiers (invoice numbers, PO codes, tickets, etc.) within minutes.

## Prerequisites

- PHP 8.3 or higher with `ext-json`
- Composer 2.6+
- A consuming application capable of providing repository implementations (Laravel, Symfony, Slim, custom framework)
- Access to persistent storage for counters, reservations, and audits (relational DB recommended)

## Installation

```bash
composer require nexus/sequencing:"*@dev"
```

## When to Use This Package

Use Nexus\Sequencing whenever you need:

- ✅ Deterministic, monotonic identifiers with configurable patterns
- ✅ Counter reset rules (daily, monthly, yearly, never) without race conditions
- ✅ Gap management, number reservations, pattern versioning, or bulk generation
- ✅ Framework-agnostic logic that can be wired into any application layer

Do **not** use this package for:

- ❌ One-off UUID/ULID generation (use `symfony/uid` or native `random_bytes`)
- ❌ Database auto-increment columns (those are tied to a single storage technology)
- ❌ Event sourcing sequence numbers (use `Nexus\EventStream` instead)

## Core Concepts

### Sequences
Each sequence is identified by a name (e.g., `invoice_number`) and optional scope (`tenant_123`). A sequence stores the active pattern, counter state, reset policy, gap policy, overflow behavior, and auditing metadata.

### Patterns and Variables
Patterns combine literal text with variables. Built-ins such as `{YEAR}`, `{MONTH}`, `{DAY}`, `{COUNTER}` and padded variants (`{COUNTER:5}`) are always available. Domain-specific variables (e.g., `{DEPARTMENT}`, `{BRANCH}`) are provided through `contextVariables` at generation time.

### Reset Periods
Reset policies control when counters revert to their seed value. Supported options include `ResetPeriod::NEVER`, `::DAILY`, `::MONTHLY`, `::YEARLY`, and `::INTERVAL` (custom count-based resets). Reset logic lives entirely in your repository implementation so you can leverage DB-native scheduling.

### Gap Policies and Overflow Behavior
Gap policies (`GapPolicy::ALLOW_GAPS`, `::FILL_GAPS`, `::REPORT_ONLY`) determine whether to recycle voided numbers. Overflow behaviors (`OverflowBehavior::THROW_EXCEPTION`, `::SWITCH_PATTERN`, `::EXTEND_PADDING`) define how the system reacts when counters approach their maximum.

### Reservations and Bulk Generation
`ReservationService` temporarily holds numbers (with TTL) for workflows that need to allocate identifiers in advance, while `BulkGeneratorService` issues batches atomically to minimize locking overhead.

## Basic Configuration Steps

1. **Implement Repository Contracts**: Provide concrete implementations for `SequenceRepositoryInterface`, `CounterRepositoryInterface`, `ReservationRepositoryInterface`, `GapRepositoryInterface`, and `SequenceAuditInterface`. These implementations persist state in your chosen storage.
2. **Bind Interfaces in Your Container**: Register your implementations in the DI container so services (SequenceManager, GapManager, etc.) can resolve them.
3. **Seed Sequence Definitions**: Create migration/seeder logic that stores initial sequence definitions (name, pattern, reset policy, etc.).
4. **Wire Services**: Inject `SequenceManager`, `BulkGeneratorService`, `ReservationService`, or other services where you need to issue numbers.

## First Integration Example

```php
<?php

declare(strict_types=1);

use Nexus\Sequencing\Services\SequenceManager;
use Nexus\Sequencing\ValueObjects\GapPolicy;
use Nexus\Sequencing\ValueObjects\OverflowBehavior;
use Nexus\Sequencing\ValueObjects\ResetPeriod;

final readonly class InvoiceNumberService
{
	public function __construct(
		private SequenceManager $sequenceManager,
	) {}

	public function generateForTenant(string $tenantId, string $department): string
	{
		return $this->sequenceManager->generate(
			sequenceName: 'invoice_number',
			scopeIdentifier: $tenantId,
			contextVariables: [
				'DEPARTMENT' => $department,
			],
			options: [
				'gap_policy' => GapPolicy::allowGaps(),
				'overflow_behavior' => OverflowBehavior::throwException(),
				'reset_period' => ResetPeriod::yearly(),
			],
		);
	}
}
```

## Preview Mode

Preview the next identifier without incrementing the counter. Useful for displaying upcoming numbers in UI flows:

```php
$nextInvoice = $this->sequenceManager->preview('invoice_number', 'tenant_123');
```

## Next Steps

1. Read the [API Reference](api-reference.md) for every interface, service, value object, enum, and exception.
2. Follow the [Integration Guide](integration-guide.md) for Laravel/Symfony wiring patterns.
3. Review the [Examples](examples/) directory for runnable snippets (basic & advanced).
4. Consult `IMPLEMENTATION_SUMMARY.md` and `REQUIREMENTS.md` for project-level status and requirement coverage.

## Troubleshooting

| Issue | Cause | Resolution |
| --- | --- | --- |
| `SequenceNotFoundException` | Sequence definition missing for the provided `sequenceName` + `scopeIdentifier`. | Seed the sequence definition or ensure repositories return the active configuration. |
| `SequenceExhaustedException` | Counter reached its maximum value/padding. | Configure `OverflowBehavior::switchPattern()` or increase padding. |
| Numbers duplicated under load | Repository implementation not using pessimistic locking. | Ensure `CounterRepositoryInterface::increment()` uses `SELECT ... FOR UPDATE` (or equivalent) within a transaction. |
| Reserved numbers never released | TTL expiration job not running. | Run scheduled task that calls `ReservationRepositoryInterface::releaseExpiredReservations()`. |

Still blocked? Review `docs/api-reference.md` for method-level contracts or open a discussion in the Nexus architecture channel.
