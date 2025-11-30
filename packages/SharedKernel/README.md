# Nexus SharedKernel

**Shared domain primitives, contracts, and value objects for Nexus packages.**

The SharedKernel package contains foundational elements that are shared across multiple Nexus packages, ensuring consistent domain modeling and reducing code duplication.

## Overview

This package provides:

- **Value Objects**: Immutable domain primitives (TenantId, Money, DateRange, etc.)
- **Contracts**: Common interfaces used across packages (ClockInterface, LoggerInterface, EventDispatcherInterface)
- **Exceptions**: Shared domain exceptions

## Installation

```bash
composer require nexus/shared-kernel
```

## Package Contents

### Value Objects (`src/ValueObjects/`)

| Value Object | Description |
|--------------|-------------|
| `TenantId` | Strongly-typed tenant identifier (ULID) |
| `Money` | Monetary value with currency and precision |

### Contracts (`src/Contracts/`)

| Interface | Description |
|-----------|-------------|
| `ClockInterface` | Provides current time for testability |
| `LoggerInterface` | PSR-3 compatible logging contract |
| `EventDispatcherInterface` | Event dispatching contract |

### Exceptions (`src/Exceptions/`)

| Exception | Description |
|-----------|-------------|
| `InvalidValueException` | Base exception for invalid value objects |

## Usage

### TenantId

```php
use Nexus\SharedKernel\ValueObjects\TenantId;

// Create a new TenantId
$tenantId = TenantId::generate();

// Create from existing ULID string
$tenantId = TenantId::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');

// Compare tenant IDs
$tenantId->equals($otherTenantId);

// Get string value
$tenantIdString = $tenantId->toString();
```

### Money

```php
use Nexus\SharedKernel\ValueObjects\Money;

// Create money
$money = Money::of(100.50, 'MYR');

// Operations
$total = $money->add(Money::of(50, 'MYR'));
$difference = $money->subtract(Money::of(25, 'MYR'));
$doubled = $money->multiply(2);

// Comparisons
$money->greaterThan($otherMoney);
$money->equals($otherMoney);
$money->isPositive();
$money->isZero();

// Format for display
echo $money->format(); // "100.50 MYR"
```

### ClockInterface

```php
use Nexus\SharedKernel\Contracts\ClockInterface;

final readonly class MyService
{
    public function __construct(
        private ClockInterface $clock
    ) {}
    
    public function isExpired(\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt < $this->clock->now();
    }
}
```

## Design Principles

1. **Immutability**: All value objects are immutable (`readonly`)
2. **Type Safety**: Strong typing with PHP 8.3+ features
3. **Validation**: Invalid states are impossible to create
4. **Framework Agnostic**: No framework dependencies

## Dependencies

- PHP ^8.3

## License

MIT License - see LICENSE file for details.

---

**Part of the Nexus Package Monorepo**
