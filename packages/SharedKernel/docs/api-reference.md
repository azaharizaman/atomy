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

### Money

Immutable monetary value with currency and precision.

**Namespace:** `Nexus\SharedKernel\ValueObjects\Money`

#### Factory Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `of(float\|int\|string $amount, string $currency)` | Create Money from numeric value | `Money` |
| `zero(string $currency)` | Create zero money in a currency | `Money` |

#### Instance Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `getAmount()` | Get amount as string with 4 decimal places | `string` |
| `toFloat()` | Get amount as float (use with caution) | `float` |
| `getCurrency()` | Get ISO 4217 currency code | `string` |
| `add(Money $other)` | Add another Money object | `Money` |
| `subtract(Money $other)` | Subtract another Money object | `Money` |
| `multiply(float\|int\|string $factor)` | Multiply by a factor | `Money` |
| `divide(float\|int\|string $divisor)` | Divide by a divisor | `Money` |
| `equals(Money $other)` | Check equality | `bool` |
| `greaterThan(Money $other)` | Check if greater than | `bool` |
| `lessThan(Money $other)` | Check if less than | `bool` |
| `isZero()` | Check if zero | `bool` |
| `isPositive()` | Check if positive | `bool` |
| `isNegative()` | Check if negative | `bool` |
| `abs()` | Get absolute value | `Money` |
| `negate()` | Negate the amount | `Money` |
| `format(int $decimals = 2)` | Format for display | `string` |

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

### LoggerInterface

**Namespace:** `Nexus\SharedKernel\Contracts\LoggerInterface`

PSR-3 compatible logging contract.

| Method | Description |
|--------|-------------|
| `emergency(string\|\Stringable $message, array $context = [])` | System is unusable |
| `alert(string\|\Stringable $message, array $context = [])` | Action must be taken immediately |
| `critical(string\|\Stringable $message, array $context = [])` | Critical conditions |
| `error(string\|\Stringable $message, array $context = [])` | Runtime errors |
| `warning(string\|\Stringable $message, array $context = [])` | Exceptional occurrences |
| `notice(string\|\Stringable $message, array $context = [])` | Normal but significant events |
| `info(string\|\Stringable $message, array $context = [])` | Interesting events |
| `debug(string\|\Stringable $message, array $context = [])` | Detailed debug information |
| `log(string $level, string\|\Stringable $message, array $context = [])` | Logs with arbitrary level |

## Exceptions

### InvalidValueException

**Namespace:** `Nexus\SharedKernel\Exceptions\InvalidValueException`

Thrown when a value object receives invalid data.

#### Factory Methods

| Method | Description |
|--------|-------------|
| `invalidFormat(string $expected, string $actual)` | Create for invalid format |
| `outOfRange(string $field, mixed $value, mixed $min, mixed $max)` | Create for out of range |
