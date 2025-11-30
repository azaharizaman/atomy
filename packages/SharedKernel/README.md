# Nexus SharedKernel

**Shared domain primitives, contracts, and value objects for Nexus packages.**

The SharedKernel package contains foundational elements that are shared across multiple Nexus packages, ensuring consistent domain modeling and reducing code duplication.

## Overview

This package provides:

- **Value Objects**: Immutable domain primitives (TenantId, etc.)
- **Contracts**: Common interfaces used across packages (ClockInterface, EventDispatcherInterface)
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

### Contracts (`src/Contracts/`)

| Interface | Description |
|-----------|-------------|
| `ClockInterface` | Provides current time for testability |
| `EventDispatcherInterface` | Event dispatching contract |

> **Note:** For logging, use PSR-3's `Psr\Log\LoggerInterface` directly. This package depends on `psr/log` for convenience.

> **Note:** For monetary values, use `Nexus\Finance\ValueObjects\Money` from the Finance package.

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

### Logging (PSR-3)

```php
use Psr\Log\LoggerInterface;

final readonly class MyService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    public function doSomething(): void
    {
        $this->logger->info('Operation completed');
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
- psr/log ^3.0

## License

MIT License - see LICENSE file for details.

---

**Part of the Nexus Package Monorepo**
