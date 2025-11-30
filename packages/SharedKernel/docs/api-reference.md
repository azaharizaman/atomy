# SharedKernel API Reference

This document describes the public API of the SharedKernel package.

## Value Objects

### TenantId

Strongly-typed tenant identifier using ULID format.

**Namespace:** `Nexus\SharedKernel\ValueObjects\TenantId`

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

**Namespace:** `Nexus\SharedKernel\Contracts\ClockInterface`

Provides current time for testability.

| Method | Description | Returns |
|--------|-------------|---------|
| `now()` | Get the current time | `DateTimeImmutable` |

### EventDispatcherInterface

**Namespace:** `Nexus\SharedKernel\Contracts\EventDispatcherInterface`

Dispatches domain events to the application layer.

| Method | Description | Returns |
|--------|-------------|---------|
| `dispatch(object $event)` | Dispatch an event to all listeners | `void` |

## Logging

For logging, use PSR-3's `Psr\Log\LoggerInterface` directly. This package depends on `psr/log` for convenience.

## Monetary Values

For monetary values, use `Nexus\Finance\ValueObjects\Money` from the Finance package.

## Exceptions

### InvalidValueException

**Namespace:** `Nexus\SharedKernel\Exceptions\InvalidValueException`

Thrown when a value object receives invalid data.

#### Factory Methods

| Method | Description |
|--------|-------------|
| `invalidFormat(string $expected, string $actual)` | Create for invalid format |
| `outOfRange(string $field, mixed $value, mixed $min, mixed $max)` | Create for out of range |
