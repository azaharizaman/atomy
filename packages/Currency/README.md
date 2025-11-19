# Nexus\Currency

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)

Framework-agnostic ISO 4217-compliant currency management and exchange rate engine for the Nexus ERP system.

## Overview

The **Nexus\Currency** package provides authoritative currency metadata and exchange rate management following ISO 4217 standards. It complements the existing `Nexus\Finance\ValueObjects\Money` implementation by providing validation, formatting, and exchange rate coordination without replacing core financial value objects.

## Key Features

- **ISO 4217 Compliance**: Full support for international currency standards
- **Decimal Precision Rules**: Correct handling of 0-decimal (JPY), 2-decimal (USD), and 3-decimal (BHD) currencies
- **Exchange Rate Management**: Pluggable provider architecture for external rate APIs
- **Intelligent Caching**: Configurable rate storage to minimize API calls
- **Framework-Agnostic**: Pure PHP with no Laravel dependencies
- **BCMath Compatible**: Designed to work with high-precision monetary calculations
- **Stateless Design**: All services are stateless and horizontally scalable

## Architecture

This package follows the **"Logic in Packages, Implementation in Applications"** pattern:

```
┌─────────────────────────────────────────────────────────────┐
│                    Nexus\Currency                           │
│  (ISO 4217 Authority & Exchange Rate Coordination)          │
└────────────────────┬────────────────────────────────────────┘
                     │ provides metadata
                     ↓
┌─────────────────────────────────────────────────────────────┐
│                    Nexus\Finance                            │
│         (Money & ExchangeRate Value Objects)                │
└────────────────────┬────────────────────────────────────────┘
                     │ used by
                     ↓
┌─────────────────────────────────────────────────────────────┐
│  Nexus\Accounting, Nexus\Payroll, Nexus\Procurement, etc.  │
└─────────────────────────────────────────────────────────────┘
```

### Integration with Nexus\Finance

The `Nexus\Currency` package **augments** (not replaces) existing financial components:

- **`Nexus\Finance\ValueObjects\Money`** remains the core monetary value object
- **`Nexus\Finance\ValueObjects\ExchangeRate`** remains the core rate value object
- **`Nexus\Currency`** provides:
  - Currency metadata (symbols, names, decimal rules)
  - Currency code validation
  - Exchange rate provider coordination
  - Formatting utilities

### What This Package Provides

- **`CurrencyManager`**: High-level currency operations (validation, formatting, metadata)
- **`ExchangeRateService`**: Exchange rate lookup with caching and conversion
- **Value Objects**: `Currency` (metadata), `CurrencyPair` (pair representation)
- **Contracts**: Repository, Provider, and Storage interfaces
- **Exceptions**: Domain-specific errors with static factories

### What the Application Must Implement

The consuming application (`Nexus\Atomy`) must provide:

1. **Currency Repository**: Database-backed implementation with ISO 4217 seed data
2. **Exchange Rate Provider**: Integration with external APIs (ECB, Fixer.io, etc.) using `Nexus\Connector`
3. **Rate Storage**: Redis/Database caching implementation
4. **Service Bindings**: IoC container bindings in service provider

## Installation

```bash
composer require nexus/currency:"*@dev"
```

## Requirements

- PHP 8.3 or higher
- BCMath extension (for precision calculations)
- PSR-3 Logger implementation (optional)

## Core Components

### 1. Currency Value Object

Immutable representation of ISO 4217 currency metadata:

```php
use Nexus\Currency\ValueObjects\Currency;

// Currency with full ISO 4217 data
$usd = new Currency(
    code: 'USD',
    name: 'US Dollar',
    symbol: '$',
    decimalPlaces: 2,
    numericCode: '840'
);

// Access metadata
echo $usd->getCode();          // "USD"
echo $usd->getSymbol();        // "$"
echo $usd->getDecimalPlaces(); // 2

// Format amounts
echo $usd->formatAmount('1234.56');              // "$ 1,234.56"
echo $usd->formatAmount('1234.56', false, true); // "1,234.56 USD"

// Check decimal type
$jpy = new Currency('JPY', 'Japanese Yen', '¥', 0, '392');
$jpy->isZeroDecimal(); // true
```

### 2. CurrencyPair Value Object

Represents a currency exchange pair:

```php
use Nexus\Currency\ValueObjects\CurrencyPair;

// Create pair
$pair = new CurrencyPair('USD', 'EUR');

// Or from string notation
$pair = CurrencyPair::fromString('USD/EUR');

// Access components
echo $pair->getFromCode(); // "USD"
echo $pair->getToCode();   // "EUR"
echo $pair->toString();    // "USD/EUR"

// Get inverse
$inverse = $pair->inverse(); // EUR/USD
```

### 3. CurrencyManager Service

High-level currency management:

```php
use Nexus\Currency\Services\CurrencyManager;

$manager = app(CurrencyManager::class);

// Get currency metadata
$usd = $manager->getCurrency('USD');

// Validate currency code
$manager->validateCode('USD'); // void (success)
$manager->validateCode('XXX'); // throws CurrencyNotFoundException

// Check existence
$manager->exists('EUR'); // true
$manager->exists('ZZZ'); // false

// Get decimal precision
$precision = $manager->getDecimalPrecision('JPY'); // 0

// Format amounts
$formatted = $manager->formatAmount('1234.5678', 'USD');
// "$ 1,234.57" (rounded to 2 decimals)

// Get all currencies
$all = $manager->getAllCurrencies();

// Search currencies
$results = $manager->searchCurrencies('dollar');
```

### 4. ExchangeRateService

Exchange rate lookup and conversion:

```php
use Nexus\Currency\Services\ExchangeRateService;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\Money;

$service = app(ExchangeRateService::class);

// Get current exchange rate
$pair = new CurrencyPair('USD', 'EUR');
$rate = $service->getRate($pair); // Returns Nexus\Finance\ValueObjects\ExchangeRate

// Get historical rate
$date = new DateTimeImmutable('2024-01-15');
$historicalRate = $service->getRate($pair, $date);

// Convert money
$usd = Money::of(100, 'USD');
$eur = $service->convert($usd, 'EUR'); // Money in EUR

// Get multiple rates (batch operation)
$pairs = [
    new CurrencyPair('USD', 'EUR'),
    new CurrencyPair('USD', 'GBP'),
    new CurrencyPair('USD', 'JPY'),
];
$rates = $service->getRates($pairs);

// Refresh specific rates (bypass cache)
$service->refreshRates($pairs);

// Clear all cached rates
$service->clearCache();

// Check provider capabilities
$service->supportsHistoricalRates(); // true/false
$service->getProviderName();         // "ECB" or "Fixer.io"
$service->isProviderAvailable();     // true/false
```

## Application Integration

### Step 1: Implement Currency Repository

Create a database-backed repository in `apps/Atomy`:

```php
namespace App\Repositories;

use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\ValueObjects\Currency;
use Illuminate\Support\Facades\DB;

class DbCurrencyRepository implements CurrencyRepositoryInterface
{
    public function findByCode(string $code): ?Currency
    {
        $row = DB::table('currencies')
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$row) {
            return null;
        }

        return new Currency(
            code: $row->code,
            name: $row->name,
            symbol: $row->symbol,
            decimalPlaces: $row->decimal_places,
            numericCode: $row->numeric_code
        );
    }

    public function getAll(): array
    {
        $rows = DB::table('currencies')
            ->where('is_active', true)
            ->get();

        $currencies = [];
        foreach ($rows as $row) {
            $currencies[$row->code] = new Currency(
                code: $row->code,
                name: $row->name,
                symbol: $row->symbol,
                decimalPlaces: $row->decimal_places,
                numericCode: $row->numeric_code
            );
        }

        return $currencies;
    }

    public function exists(string $code): bool
    {
        return DB::table('currencies')
            ->where('code', $code)
            ->where('is_active', true)
            ->exists();
    }

    // Implement other methods...
}
```

### Step 2: Create Database Migration

```php
// database/migrations/xxxx_create_currencies_table.php
Schema::create('currencies', function (Blueprint $table) {
    $table->string('code', 3)->primary();
    $table->string('name', 100);
    $table->string('symbol', 10);
    $table->tinyInteger('decimal_places')->default(2);
    $table->string('numeric_code', 3);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### Step 3: Seed ISO 4217 Data

```php
// database/seeders/CurrencySeeder.php
DB::table('currencies')->insert([
    ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'numeric_code' => '840'],
    ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'numeric_code' => '978'],
    ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2, 'numeric_code' => '826'],
    ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0, 'numeric_code' => '392'],
    ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimal_places' => 2, 'numeric_code' => '458'],
    ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2, 'numeric_code' => '702'],
    ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_places' => 2, 'numeric_code' => '156'],
    ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'symbol' => 'BD', 'decimal_places' => 3, 'numeric_code' => '048'],
    // Add more currencies as needed...
]);
```

### Step 4: Implement Exchange Rate Provider

Using `Nexus\Connector` for resilient API integration:

```php
namespace App\Services\ExchangeRates;

use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;
use Nexus\Connector\Services\ConnectorManager;
use DateTimeImmutable;

class EcbExchangeRateProvider implements ExchangeRateProviderInterface
{
    public function __construct(
        private readonly ConnectorManager $connector
    ) {}

    public function getRate(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): ExchangeRate
    {
        // Use Nexus\Connector with circuit breaker and retry logic
        $response = $this->connector->execute(
            connectionId: 'ecb-api',
            method: 'GET',
            endpoint: $asOf 
                ? "/history/{$asOf->format('Y-m-d')}" 
                : '/latest',
            params: [
                'base' => $pair->getFromCode(),
                'symbols' => $pair->getToCode(),
            ]
        );

        $data = json_decode($response->getBody(), true);

        if (!isset($data['rates'][$pair->getToCode()])) {
            throw ExchangeRateNotFoundException::forPair($pair, $asOf);
        }

        return ExchangeRate::create(
            fromCurrency: $pair->getFromCode(),
            toCurrency: $pair->getToCode(),
            rate: $data['rates'][$pair->getToCode()],
            effectiveDate: $asOf ?? new DateTimeImmutable()
        );
    }

    public function supportsHistoricalRates(): bool
    {
        return true;
    }

    public function getProviderName(): string
    {
        return 'European Central Bank';
    }

    public function isAvailable(): bool
    {
        return $this->connector->isHealthy('ecb-api');
    }

    // Implement getRates()...
}
```

### Step 5: Implement Rate Storage (Redis)

```php
namespace App\Services\ExchangeRates;

use Nexus\Currency\Contracts\RateStorageInterface;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;
use Illuminate\Support\Facades\Redis;
use DateTimeImmutable;

class RedisRateStorage implements RateStorageInterface
{
    private const PREFIX = 'exchange_rate:';

    public function get(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): ?ExchangeRate
    {
        $key = $this->buildKey($pair, $asOf);
        $data = Redis::get($key);

        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);

        return ExchangeRate::create(
            fromCurrency: $decoded['from'],
            toCurrency: $decoded['to'],
            rate: $decoded['rate'],
            effectiveDate: new DateTimeImmutable($decoded['date'])
        );
    }

    public function put(CurrencyPair $pair, ExchangeRate $rate, int $ttl = 3600): bool
    {
        $key = $this->buildKey($pair, $rate->getEffectiveDate());
        $data = json_encode([
            'from' => $rate->getFromCurrency(),
            'to' => $rate->getToCurrency(),
            'rate' => $rate->getRate(),
            'date' => $rate->getEffectiveDate()->format('Y-m-d H:i:s'),
        ]);

        return Redis::setex($key, $ttl, $data);
    }

    public function forget(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): bool
    {
        $key = $this->buildKey($pair, $asOf);
        return Redis::del($key) > 0;
    }

    public function flush(): bool
    {
        $keys = Redis::keys(self::PREFIX . '*');
        return count($keys) > 0 ? Redis::del($keys) > 0 : true;
    }

    public function has(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): bool
    {
        $key = $this->buildKey($pair, $asOf);
        return Redis::exists($key) > 0;
    }

    private function buildKey(CurrencyPair $pair, ?DateTimeImmutable $asOf): string
    {
        $dateStr = $asOf ? $asOf->format('Y-m-d') : 'current';
        return self::PREFIX . $pair->toString() . ':' . $dateStr;
    }
}
```

### Step 6: Bind Implementations in Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\Contracts\RateStorageInterface;
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Services\CurrencyManager;
use Nexus\Currency\Services\ExchangeRateService;
use App\Repositories\DbCurrencyRepository;
use App\Services\ExchangeRates\EcbExchangeRateProvider;
use App\Services\ExchangeRates\RedisRateStorage;

class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            CurrencyRepositoryInterface::class,
            DbCurrencyRepository::class
        );

        // Bind exchange rate provider
        $this->app->singleton(
            ExchangeRateProviderInterface::class,
            EcbExchangeRateProvider::class
        );

        // Bind rate storage
        $this->app->singleton(
            RateStorageInterface::class,
            RedisRateStorage::class
        );

        // Bind currency manager
        $this->app->singleton(
            CurrencyManagerInterface::class,
            CurrencyManager::class
        );

        // Bind exchange rate service
        $this->app->singleton(ExchangeRateService::class);
    }
}
```

## Integration with Nexus\Finance

### Enhancing Money Validation

Update `Nexus\Finance\ValueObjects\Money` to use `CurrencyManager`:

```php
// In Nexus\Finance\ValueObjects\Money
use Nexus\Currency\Contracts\CurrencyManagerInterface;

private function validateCurrency(string $currency): void
{
    // Get CurrencyManager from container (or inject if refactoring to service)
    $currencyManager = app(CurrencyManagerInterface::class);
    
    // Delegate validation to Currency package
    try {
        $currencyManager->validateCode($currency);
    } catch (\Nexus\Currency\Exceptions\InvalidCurrencyCodeException $e) {
        throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
    } catch (\Nexus\Currency\Exceptions\CurrencyNotFoundException $e) {
        throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
    }
}
```

### Currency-Aware Formatting

Add a formatting method to `Money` that uses currency metadata:

```php
// In Nexus\Finance\ValueObjects\Money
public function formatWithCurrency(CurrencyManagerInterface $currencyManager): string
{
    return $currencyManager->formatAmount(
        amount: $this->amount,
        currencyCode: $this->currency,
        includeSymbol: true,
        includeCode: false
    );
}
```

Usage:
```php
$money = Money::of(1234.5678, 'USD');
$currencyManager = app(CurrencyManagerInterface::class);

echo $money->formatWithCurrency($currencyManager); // "$ 1,234.57"

$jpy = Money::of(1234.5678, 'JPY');
echo $jpy->formatWithCurrency($currencyManager); // "¥ 1,235" (zero decimals)
```

## Decimal Precision Strategy

### The 4-Decimal Internal Standard

`Nexus\Finance\ValueObjects\Money` uses **4 decimal places** for all internal BCMath calculations:

```php
// In Money VO
private const PRECISION = 4;
```

**Why 4 decimals internally?**
- Prevents rounding errors in complex calculations (tax, interest, multi-step conversions)
- Allows accurate intermediate results for financial operations
- Industry standard for accounting systems

### Currency-Specific Display Precision

`Nexus\Currency` provides the **correct display precision** per ISO 4217:

```php
$usd = $currencyManager->getCurrency('USD');
$usd->getDecimalPlaces(); // 2

$jpy = $currencyManager->getCurrency('JPY');
$jpy->getDecimalPlaces(); // 0

$bhd = $currencyManager->getCurrency('BHD');
$bhd->getDecimalPlaces(); // 3 (Bahraini Dinar)
```

### Best Practice: Calculate at 4, Display per Currency

```php
// Internal calculation (4 decimals)
$subtotal = Money::of(100.00, 'USD');
$tax = $subtotal->multiply(0.06); // 6.0000
$total = $subtotal->add($tax);    // 106.0000

// Display with currency-specific precision (2 decimals for USD)
$formatted = $total->formatWithCurrency($currencyManager);
// Result: "$ 106.00"

// For JPY (0 decimals)
$jpyTotal = Money::of(1234.5678, 'JPY');
$formatted = $jpyTotal->formatWithCurrency($currencyManager);
// Result: "¥ 1,235" (rounded, no decimals)
```

## Error Handling

All exceptions provide static factory methods for contextual error messages:

```php
use Nexus\Currency\Exceptions\CurrencyNotFoundException;
use Nexus\Currency\Exceptions\InvalidCurrencyCodeException;
use Nexus\Currency\Exceptions\ExchangeRateNotFoundException;
use Nexus\Currency\Exceptions\ExchangeRateProviderException;

try {
    $currency = $manager->getCurrency('XXX');
} catch (CurrencyNotFoundException $e) {
    // "Currency with code 'XXX' not found. Ensure it exists in the currency repository."
}

try {
    $manager->validateCode('12');
} catch (InvalidCurrencyCodeException $e) {
    // "Currency code must be 3 characters, got 2: '12'"
}

try {
    $rate = $service->getRate(new CurrencyPair('USD', 'FAKE'));
} catch (ExchangeRateNotFoundException $e) {
    // "Exchange rate not found for currency pair USD/FAKE..."
}

try {
    $rate = $service->getRate(new CurrencyPair('USD', 'EUR'));
} catch (ExchangeRateProviderException $e) {
    // "Exchange rate provider 'ECB' API failed. Please try again later."
}
```

## Testing

### Unit Testing with In-Memory Repository

For package unit tests, create a simple in-memory implementation:

```php
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\ValueObjects\Currency;

class InMemoryCurrencyRepository implements CurrencyRepositoryInterface
{
    private array $currencies = [];

    public function __construct()
    {
        // Seed with common currencies for testing
        $this->currencies = [
            'USD' => new Currency('USD', 'US Dollar', '$', 2, '840'),
            'EUR' => new Currency('EUR', 'Euro', '€', 2, '978'),
            'JPY' => new Currency('JPY', 'Japanese Yen', '¥', 0, '392'),
            'MYR' => new Currency('MYR', 'Malaysian Ringgit', 'RM', 2, '458'),
        ];
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->currencies[$code] ?? null;
    }

    public function getAll(): array
    {
        return $this->currencies;
    }

    public function exists(string $code): bool
    {
        return isset($this->currencies[$code]);
    }

    // Implement other methods...
}
```

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Credits

Developed by the Nexus Development Team for the Nexus ERP System.

## Related Packages

- **[Nexus\Finance](../Finance/)** - Core financial value objects (Money, ExchangeRate)
- **[Nexus\Accounting](../Accounting/)** - Multi-currency financial statements
- **[Nexus\Connector](../Connector/)** - Resilient API integration with circuit breaker
- **[Nexus\Tenant](../Tenant/)** - Multi-tenancy with per-tenant base currency
