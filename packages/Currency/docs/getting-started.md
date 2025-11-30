# Getting Started with Nexus Currency

## Prerequisites

- PHP 8.3 or higher
- Composer
- BCMath extension
- PSR-3 compatible logger (optional)

## Installation

```bash
composer require nexus/currency:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Multi-currency financial transactions
- ✅ ISO 4217-compliant currency validation
- ✅ Exchange rate management with external API integration
- ✅ Currency conversion with BCMath precision
- ✅ Historical exchange rate queries
- ✅ Currency metadata (symbols, names, decimal rules)

Do NOT use this package for:
- ❌ Monetary value storage (use `Nexus\Finance\ValueObjects\Money`)
- ❌ Cryptocurrency (not ISO 4217 compliant)
- ❌ Single-currency applications (overkill)

## Core Concepts

### Concept 1: ISO 4217 Compliance

The package strictly enforces ISO 4217 international currency standards:

- **3-letter alphabetic codes**: USD, EUR, GBP, MYR, JPY
- **3-digit numeric codes**: 840 (USD), 978 (EUR), 392 (JPY)
- **Decimal precision**: 0-4 decimal places per currency
  - **0 decimals**: JPY (Japanese Yen), KRW (Korean Won)
  - **2 decimals**: USD, EUR, MYR (most currencies)
  - **3 decimals**: BHD (Bahraini Dinar), KWD (Kuwaiti Dinar)
  - **4 decimals**: CLF (Chilean Unidad de Fomento)

**Example:**
```php
$usd = $currencyManager->getCurrency('USD');
echo $usd->decimalPlaces; // 2

$jpy = $currencyManager->getCurrency('JPY');
echo $jpy->decimalPlaces; // 0

$bhd = $currencyManager->getCurrency('BHD');
echo $bhd->decimalPlaces; // 3
```

### Concept 2: Non-Breaking Augmentation

This package **complements** the existing `Nexus\Finance` package without replacing core value objects:

- **`Nexus\Finance\ValueObjects\Money`** → Still used for monetary values
- **`Nexus\Finance\ValueObjects\ExchangeRate`** → Still used for rates
- **`Nexus\Currency`** → Provides metadata and validation

**Why?** Ensures zero breaking changes to existing code while adding currency capabilities.

### Concept 3: Pluggable Exchange Rate Providers

The package defines `ExchangeRateProviderInterface` but doesn't include concrete providers. Your application implements the provider using external APIs:

**Supported Provider Examples:**
- European Central Bank (ECB) - Free, official rates
- Fixer.io - Commercial API
- Open Exchange Rates - Commercial API
- Central bank APIs - Country-specific

**Integration Pattern:**
```php
use Nexus\Connector\Contracts\ConnectorManagerInterface;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;

class EcbExchangeRateProvider implements ExchangeRateProviderInterface
{
    public function __construct(
        private readonly ConnectorManagerInterface $connector
    ) {}
    
    public function getExchangeRate(
        string $from,
        string $to,
        ?\DateTimeImmutable $asOf = null
    ): float {
        // Use Nexus\Connector for API calls with circuit breaker
        $connection = $this->connector->getConnection('ecb');
        $response = $connection->request('GET', '/latest', [
            'base' => $from,
            'symbols' => $to,
        ]);
        
        return $response['rates'][$to];
    }
}
```

### Concept 4: Intelligent Rate Caching

Exchange rates are cached to minimize external API calls:

- **Current rates**: 1 hour TTL (rates change frequently)
- **Historical rates**: 24 hour TTL (rates don't change after the day)

**Cache Storage:**
```php
use Nexus\Currency\Contracts\RateStorageInterface;

interface RateStorageInterface
{
    public function get(string $key): ?array;
    public function set(string $key, array $value, int $ttl): void;
    public function delete(string $key): void;
}

// Application implements with Redis, Database, or File
```

---

## Basic Configuration

### Step 1: Implement Currency Repository

Create a database-backed repository:

```php
// app/Repositories/CurrencyRepository.php
namespace App\Repositories;

use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\ValueObjects\Currency;
use Illuminate\Support\Facades\DB;

final readonly class CurrencyRepository implements CurrencyRepositoryInterface
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
        $rows = DB::table('currencies')->where('is_active', true)->get();
        
        return $rows->mapWithKeys(fn($row) => [
            $row->code => new Currency(
                code: $row->code,
                name: $row->name,
                symbol: $row->symbol,
                decimalPlaces: $row->decimal_places,
                numericCode: $row->numeric_code
            )
        ])->toArray();
    }
    
    public function exists(string $code): bool
    {
        return DB::table('currencies')
            ->where('code', $code)
            ->where('is_active', true)
            ->exists();
    }
}
```

### Step 2: Implement Exchange Rate Provider

```php
// app/Providers/EcbExchangeRateProvider.php
namespace App\Providers;

use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Connector\Contracts\ConnectorManagerInterface;

final readonly class EcbExchangeRateProvider implements ExchangeRateProviderInterface
{
    public function __construct(
        private ConnectorManagerInterface $connector
    ) {}
    
    public function getExchangeRate(
        string $from,
        string $to,
        ?\DateTimeImmutable $asOf = null
    ): float {
        $connection = $this->connector->getConnection('ecb');
        
        $endpoint = $asOf 
            ? "/history/{$asOf->format('Y-m-d')}" 
            : '/latest';
            
        $response = $connection->request('GET', $endpoint, [
            'query' => ['base' => $from, 'symbols' => $to],
        ]);
        
        return (float)$response['rates'][$to];
    }
    
    public function supportsHistoricalRates(): bool
    {
        return true;
    }
}
```

### Step 3: Implement Rate Storage

```php
// app/Storage/RedisRateStorage.php
namespace App\Storage;

use Nexus\Currency\Contracts\RateStorageInterface;
use Illuminate\Support\Facades\Redis;

final readonly class RedisRateStorage implements RateStorageInterface
{
    public function get(string $key): ?array
    {
        $value = Redis::get($key);
        return $value ? json_decode($value, true) : null;
    }
    
    public function set(string $key, array $value, int $ttl): void
    {
        Redis::setex($key, $ttl, json_encode($value));
    }
    
    public function delete(string $key): void
    {
        Redis::del($key);
    }
}
```

### Step 4: Bind Interfaces in Service Provider

```php
// app/Providers/CurrencyServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Currency\Contracts\{
    CurrencyRepositoryInterface,
    CurrencyManagerInterface,
    ExchangeRateProviderInterface,
    RateStorageInterface
};
use Nexus\Currency\Services\{CurrencyManager, ExchangeRateService};
use App\Repositories\CurrencyRepository;
use App\Providers\EcbExchangeRateProvider;
use App\Storage\RedisRateStorage;

class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            CurrencyRepositoryInterface::class,
            CurrencyRepository::class
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
        
        // Bind managers (services auto-bind via constructor)
        $this->app->singleton(
            CurrencyManagerInterface::class,
            CurrencyManager::class
        );
    }
}
```

### Step 5: Create Database Migration

```php
// database/migrations/xxxx_create_currencies_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->tinyInteger('decimal_places')->default(2);
            $table->string('numeric_code', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });
    }
};
```

### Step 6: Seed ISO 4217 Currency Data

```php
// database/seeders/CurrencySeeder.php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'numeric_code' => '840'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'numeric_code' => '978'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2, 'numeric_code' => '826'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0, 'numeric_code' => '392'],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimal_places' => 2, 'numeric_code' => '458'],
            ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'symbol' => 'BD', 'decimal_places' => 3, 'numeric_code' => '048'],
            // ... add all 180+ ISO 4217 currencies
        ];
        
        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                $currency + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
```

---

## Your First Integration

Complete working example:

```php
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Services\ExchangeRateService;
use Nexus\Finance\ValueObjects\Money;

// Inject services via DI
public function __construct(
    private readonly CurrencyManagerInterface $currencyManager,
    private readonly ExchangeRateService $exchangeRateService
) {}

public function processMultiCurrencyPayment(
    string $amount,
    string $fromCurrency,
    string $toCurrency
): array {
    // 1. Validate currencies
    $this->currencyManager->validateCode($fromCurrency);
    $this->currencyManager->validateCode($toCurrency);
    
    // 2. Get currency metadata
    $fromMeta = $this->currencyManager->getCurrency($fromCurrency);
    $toMeta = $this->currencyManager->getCurrency($toCurrency);
    
    // 3. Convert amount
    $fromMoney = Money::of($amount, $fromCurrency);
    $toMoney = $this->exchangeRateService->convert($fromMoney, $toCurrency);
    
    // 4. Format for display
    $formattedFrom = $fromMeta->formatAmount($amount);
    $formattedTo = $toMeta->formatAmount($toMoney->getAmount());
    
    return [
        'from' => $formattedFrom,
        'to' => $formattedTo,
        'rate' => $this->exchangeRateService->getRate(
            new CurrencyPair($fromCurrency, $toCurrency)
        )->getRate(),
    ];
}
```

**Output:**
```php
[
    'from' => '$ 100.00',
    'to' => '€ 85.00',
    'rate' => 0.85,
]
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples

## Troubleshooting

### Common Issues

**Issue 1: Currency not found**
- **Cause:** Currency not seeded in database or is_active = false
- **Solution:** Run `php artisan db:seed --class=CurrencySeeder`

**Issue 2: Exchange rate API failure**
- **Cause:** External API down, rate limit exceeded, or invalid credentials
- **Solution:** Check `Nexus\Connector` circuit breaker status, verify API credentials

**Issue 3: BCMath precision errors**
- **Cause:** BCMath extension not installed
- **Solution:** Install BCMath: `sudo apt install php8.3-bcmath` (Ubuntu) or enable in php.ini

**Issue 4: Cached rate is stale**
- **Cause:** Cache TTL not expired but rate changed
- **Solution:** Call `$exchangeRateService->clearCache()` to force refresh

**Issue 5: Invalid currency code exception**
- **Cause:** Using non-ISO 4217 codes (e.g., crypto, obsolete currencies)
- **Solution:** Only use current ISO 4217 codes (3-letter uppercase alphabetic)
