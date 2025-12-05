# Common API Reference

This document describes the public API of the Common package.

## Value Objects

### TenantId

Strongly-typed tenant identifier using ULID format.

**Namespace:** `Nexus\Common\ValueObjects\TenantId`

#### Factory Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `generate()` | Generate a new TenantId with a random ULID | `TenantId` |
| `fromString(string $ulid)` | Create from existing ULID string | `TenantId` |

#### Instance Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `toString()` | Get the ULID value as string | `string` |
| `equals(TenantId $other)` | Check equality with another TenantId | `bool` |
| `__toString()` | Magic method for string conversion | `string` |

## Contracts

### ClockInterface

**Namespace:** `Nexus\Common\Contracts\ClockInterface`

Provides current time for testability.

| Method | Description | Returns |
|--------|-------------|---------|
| `now()` | Get the current time | `DateTimeImmutable` |

### UlidInterface

**Namespace:** `Nexus\Common\Contracts\UlidInterface`

Standardized ULID generation for entity identifiers.

| Method | Description | Returns |
|--------|-------------|---------|
| `generate()` | Generate a new ULID string | `string` |
| `isValid(string $ulid)` | Validate ULID format | `bool` |
| `getTimestamp(string $ulid)` | Extract timestamp from ULID | `DateTimeImmutable` |

## Event Dispatching

For event dispatching, use PSR-14's `Psr\EventDispatcher\EventDispatcherInterface` directly. 
This package depends on `psr/event-dispatcher` for PSR compliance.

```php
// Inject PSR-14 interface
public function __construct(
    private \Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
) {}
```

## Logging

For logging, use PSR-3's `Psr\Log\LoggerInterface` directly. This package depends on `psr/log` for convenience.

## Monetary Values

The `Money` value object is located in this package (`Nexus\Common\ValueObjects\Money`).

**Money vs Currency Package Boundary:**
- **`Money` (this package)**: Immutable monetary value with arithmetic, comparison, formatting
- **`Nexus\Currency` package**: Exchange rate management, cross-currency conversions

## Exceptions

### InvalidValueException

**Namespace:** `Nexus\Common\Exceptions\InvalidValueException`

Thrown when a value object receives invalid data.

#### Factory Methods

| Method | Description |
|--------|-------------|
| `invalidFormat(string $expected, string $actual)` | Create for invalid format |
| `outOfRange(string $field, mixed $value, mixed $min, mixed $max)` | Create for out of range |
