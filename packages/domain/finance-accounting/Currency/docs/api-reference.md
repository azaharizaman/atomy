# API Reference: Currency

This document provides comprehensive documentation of all public interfaces, value objects, and exceptions in the Nexus Currency package.

---

## Interfaces

### CurrencyManagerInterface

**Location:** `src/Contracts/CurrencyManagerInterface.php`

**Purpose:** High-level currency management operations

**Methods:**

#### getCurrency()

```php
public function getCurrency(string $code): Currency;
```

**Description:** Retrieve currency metadata by ISO 4217 code

**Parameters:**
- `$code` (string) - 3-letter ISO 4217 currency code (e.g., 'USD', 'EUR')

**Returns:** `Currency` - Currency value object with metadata

**Throws:**
- `InvalidCurrencyCodeException` - If code format is invalid
- `CurrencyNotFoundException` - If currency doesn't exist

**Example:**
```php
$usd = $currencyManager->getCurrency('USD');
echo $usd->symbol; // "$"
```

---

#### validateCode()

```php
public function validateCode(string $code): void;
```

**Description:** Validate ISO 4217 currency code format and existence

**Parameters:**
- `$code` (string) - Currency code to validate

**Returns:** `void` - Throws exception if invalid

**Throws:**
- `InvalidCurrencyCodeException` - If code format is invalid (not 3-letter uppercase)
- `CurrencyNotFoundException` - If currency doesn't exist in repository

---

#### exists()

```php
public function exists(string $code): bool;
```

**Description:** Check if currency exists in repository

**Parameters:**
- `$code` (string) - Currency code to check

**Returns:** `bool` - True if exists, false otherwise

---

#### getDecimalPrecision()

```php
public function getDecimalPrecision(string $code): int;
```

**Description:** Get decimal places for currency per ISO 4217

**Parameters:**
- `$code` (string) - Currency code

**Returns:** `int` - Number of decimal places (0-4)

**Example:**
```php
$currencyManager->getDecimalPrecision('USD'); // 2
$currencyManager->getDecimalPrecision('JPY'); // 0
$currencyManager->getDecimalPrecision('BHD'); // 3
```

---

#### formatAmount()

```php
public function formatAmount(string $amount, string $code, bool $withSymbol = true): string;
```

**Description:** Format amount according to currency rules

**Parameters:**
- `$amount` (string) - Amount to format
- `$code` (string) - Currency code
- `$withSymbol` (bool) - Include currency symbol

**Returns:** `string` - Formatted amount

**Example:**
```php
$currencyManager->formatAmount('1234.567', 'USD'); // "$ 1,234.57"
$currencyManager->formatAmount('1234.567', 'JPY'); // "¥ 1,235"
```

---

### ExchangeRateProviderInterface

**Location:** `src/Contracts/ExchangeRateProviderInterface.php`

**Purpose:** Abstraction for external exchange rate APIs

**Methods:**

#### getExchangeRate()

```php
public function getExchangeRate(
    string $from,
    string $to,
    ?\DateTimeImmutable $asOf = null
): float;
```

**Description:** Fetch exchange rate from external provider

**Parameters:**
- `$from` (string) - Source currency code
- `$to` (string) - Target currency code
- `$asOf` (DateTimeImmutable|null) - Optional date for historical rates

**Returns:** `float` - Exchange rate

**Throws:**
- `ExchangeRateNotFoundException` - If rate not available
- `ExchangeRateProviderException` - If API call fails

---

#### supportsHistoricalRates()

```php
public function supportsHistoricalRates(): bool;
```

**Description:** Check if provider supports historical rate queries

**Returns:** `bool` - True if historical rates supported

---

### CurrencyRepositoryInterface

**Location:** `src/Contracts/CurrencyRepositoryInterface.php`

**Purpose:** Currency data persistence abstraction

**Methods:**

#### findByCode()

```php
public function findByCode(string $code): ?Currency;
```

**Description:** Find currency by ISO 4217 code

**Parameters:**
- `$code` (string) - Currency code

**Returns:** `Currency|null` - Currency or null if not found

---

#### getAll()

```php
public function getAll(): array;
```

**Description:** Get all active currencies

**Returns:** `array<string, Currency>` - Associative array keyed by currency code

---

#### exists()

```php
public function exists(string $code): bool;
```

**Description:** Check if currency exists

**Parameters:**
- `$code` (string) - Currency code

**Returns:** `bool` - True if exists

---

### RateStorageInterface

**Location:** `src/Contracts/RateStorageInterface.php`

**Purpose:** Exchange rate caching abstraction

**Methods:**

#### get()

```php
public function get(string $key): ?array;
```

**Description:** Retrieve cached rate data

**Parameters:**
- `$key` (string) - Cache key

**Returns:** `array|null` - Cached data or null if not found

---

#### set()

```php
public function set(string $key, array $value, int $ttl): void;
```

**Description:** Store rate data with TTL

**Parameters:**
- `$key` (string) - Cache key
- `$value` (array) - Rate data to cache
- `$ttl` (int) - Time-to-live in seconds

---

#### delete()

```php
public function delete(string $key): void;
```

**Description:** Remove cached rate data

**Parameters:**
- `$key` (string) - Cache key to delete

---

## Value Objects

### Currency

**Location:** `src/ValueObjects/Currency.php`

**Purpose:** Immutable ISO 4217 currency metadata

**Properties:**
- `$code` (string) - 3-letter ISO 4217 code
- `$name` (string) - Currency name in English
- `$symbol` (string) - Currency symbol
- `$decimalPlaces` (int) - Decimal precision (0-4)
- `$numericCode` (string) - 3-digit ISO numeric code

**Constructor:**
```php
public function __construct(
    public readonly string $code,
    public readonly string $name,
    public readonly string $symbol,
    public readonly int $decimalPlaces,
    public readonly string $numericCode
)
```

**Methods:**

#### isZeroDecimal()

```php
public function isZeroDecimal(): bool
```

**Returns:** True if currency uses 0 decimal places (JPY, KRW)

---

#### formatAmount()

```php
public function formatAmount(
    string $amount,
    bool $withSymbol = true,
    bool $withCode = false
): string
```

**Description:** Format amount according to currency rules

---

### CurrencyPair

**Location:** `src/ValueObjects/CurrencyPair.php`

**Purpose:** Immutable representation of currency pair (e.g., USD/EUR)

**Properties:**
- `$from` (string) - Source currency code
- `$to` (string) - Target currency code

**Constructor:**
```php
public function __construct(
    public readonly string $from,
    public readonly string $to
)
```

**Static Factories:**

#### fromString()

```php
public static function fromString(string $pair): self
```

**Description:** Create from string notation (e.g., "USD/EUR")

**Example:**
```php
$pair = CurrencyPair::fromString('USD/EUR');
```

---

**Methods:**

#### toString()

```php
public function toString(): string
```

**Returns:** String representation (e.g., "USD/EUR")

---

#### inverse()

```php
public function inverse(): self
```

**Returns:** Inverted pair (EUR/USD becomes USD/EUR)

---

## Exceptions

### InvalidCurrencyCodeException

**Location:** `src/Exceptions/InvalidCurrencyCodeException.php`

**Extends:** `\InvalidArgumentException`

**Purpose:** Thrown when currency code format is invalid

**Static Factory:**

```php
public static function forCode(string $code): self
```

**Example:**
```php
throw InvalidCurrencyCodeException::forCode('US'); // Invalid: only 2 letters
```

---

### CurrencyNotFoundException

**Location:** `src/Exceptions/CurrencyNotFoundException.php`

**Extends:** `\RuntimeException`

**Purpose:** Thrown when currency doesn't exist in repository

**Static Factory:**

```php
public static function forCode(string $code): self
```

**Example:**
```php
throw CurrencyNotFoundException::forCode('XXX');
```

---

### ExchangeRateNotFoundException

**Location:** `src/Exceptions/ExchangeRateNotFoundException.php`

**Extends:** `\RuntimeException`

**Purpose:** Thrown when exchange rate not available

**Static Factory:**

```php
public static function forPair(string $from, string $to, ?\DateTimeImmutable $asOf = null): self
```

**Example:**
```php
throw ExchangeRateNotFoundException::forPair('USD', 'EUR', new \DateTimeImmutable('2020-01-01'));
```

---

### ExchangeRateProviderException

**Location:** `src/Exceptions/ExchangeRateProviderException.php`

**Extends:** `\RuntimeException`

**Purpose:** Thrown when external API call fails

**Static Factory:**

```php
public static function apiFailure(string $provider, string $message, ?\Throwable $previous = null): self
```

**Example:**
```php
throw ExchangeRateProviderException::apiFailure('ECB', 'Rate limit exceeded');
```

---

### IncompatibleCurrencyException

**Location:** `src/Exceptions/IncompatibleCurrencyException.php`

**Extends:** `\LogicException`

**Purpose:** Thrown when currencies are incompatible for operation

**Static Factory:**

```php
public static function forOperation(string $from, string $to, string $operation): self
```

**Example:**
```php
throw IncompatibleCurrencyException::forOperation('USD', 'BTC', 'conversion');
```

---

## Usage Patterns

### Pattern 1: Currency Validation

```php
try {
    $currencyManager->validateCode($code);
    // Code is valid
} catch (InvalidCurrencyCodeException $e) {
    // Invalid format (not 3-letter uppercase)
} catch (CurrencyNotFoundException $e) {
    // Currency doesn't exist
}
```

### Pattern 2: Safe Currency Retrieval

```php
if ($currencyManager->exists($code)) {
    $currency = $currencyManager->getCurrency($code);
} else {
    // Handle missing currency
}
```

### Pattern 3: Exchange Rate with Fallback

```php
try {
    $rate = $exchangeRateService->getExchangeRate('USD', 'EUR');
} catch (ExchangeRateProviderException $e) {
    // Use fallback rate or retry
    Log::error('Exchange rate API failed', ['exception' => $e]);
    $rate = $this->getFallbackRate('USD', 'EUR');
}
```
