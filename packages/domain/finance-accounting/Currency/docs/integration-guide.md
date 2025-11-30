# Integration Guide: Nexus\Currency

This guide demonstrates how to integrate the `Nexus\Currency` package into your application layer (Laravel, Symfony, or custom frameworks).

---

## Table of Contents
- [Laravel Integration](#laravel-integration)
- [Symfony Integration](#symfony-integration)
- [Custom Framework Integration](#custom-framework-integration)
- [Testing Your Integration](#testing-your-integration)

---

## Laravel Integration

### Step 1: Database Migrations

Create migrations for currency and exchange rate storage:

```php
<?php
// database/migrations/2024_01_01_000001_create_currencies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->char('code', 3)->primary(); // ISO 4217 code
            $table->string('name');
            $table->unsignedTinyInteger('decimal_places'); // 0-4
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
```

```php
<?php
// database/migrations/2024_01_01_000002_create_exchange_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('from_currency', 3);
            $table->char('to_currency', 3);
            $table->decimal('rate', 18, 6); // High precision
            $table->date('effective_date');
            $table->string('source')->nullable(); // Provider name
            $table->timestamps();
            
            $table->foreign('from_currency')
                  ->references('code')
                  ->on('currencies')
                  ->onDelete('cascade');
                  
            $table->foreign('to_currency')
                  ->references('code')
                  ->on('currencies')
                  ->onDelete('cascade');
            
            $table->unique(['from_currency', 'to_currency', 'effective_date']);
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
```

### Step 2: Eloquent Models

```php
<?php
// app/Models/Currency.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'decimal_places',
        'is_active',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
    ];
}
```

```php
<?php
// app/Models/ExchangeRate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
    ];

    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }
}
```

### Step 3: Repository Implementations

```php
<?php
// app/Repositories/CurrencyRepository.php

namespace App\Repositories;

use App\Models\Currency as CurrencyModel;
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\Exceptions\CurrencyNotFoundException;
use Nexus\Currency\ValueObjects\Currency;

final class CurrencyRepository implements CurrencyRepositoryInterface
{
    public function findByCode(string $code): Currency
    {
        $model = CurrencyModel::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$model) {
            throw CurrencyNotFoundException::forCode($code);
        }

        return new Currency(
            code: $model->code,
            decimalPlaces: $model->decimal_places
        );
    }

    public function findAll(): array
    {
        return CurrencyModel::where('is_active', true)
            ->get()
            ->map(fn($model) => new Currency(
                code: $model->code,
                decimalPlaces: $model->decimal_places
            ))
            ->all();
    }

    public function exists(string $code): bool
    {
        return CurrencyModel::where('code', $code)
            ->where('is_active', true)
            ->exists();
    }
}
```

```php
<?php
// app/Services/DatabaseRateStorage.php

namespace App\Services;

use App\Models\ExchangeRate;
use Nexus\Currency\Contracts\RateStorageInterface;
use Nexus\Currency\ValueObjects\CurrencyPair;

final class DatabaseRateStorage implements RateStorageInterface
{
    public function store(CurrencyPair $pair, string $rate, \DateTimeImmutable $effectiveDate): void
    {
        ExchangeRate::updateOrCreate(
            [
                'from_currency' => $pair->getFrom()->getCode(),
                'to_currency' => $pair->getTo()->getCode(),
                'effective_date' => $effectiveDate->format('Y-m-d'),
            ],
            [
                'rate' => $rate,
                'source' => 'system',
            ]
        );
    }

    public function retrieve(CurrencyPair $pair, \DateTimeImmutable $date): ?string
    {
        $rate = ExchangeRate::where('from_currency', $pair->getFrom()->getCode())
            ->where('to_currency', $pair->getTo()->getCode())
            ->where('effective_date', '<=', $date->format('Y-m-d'))
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate?->rate;
    }

    public function retrieveLatest(CurrencyPair $pair): ?string
    {
        $rate = ExchangeRate::where('from_currency', $pair->getFrom()->getCode())
            ->where('to_currency', $pair->getTo()->getCode())
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate?->rate;
    }
}
```

### Step 4: Service Provider

```php
<?php
// app/Providers/CurrencyServiceProvider.php

namespace App\Providers;

use App\Repositories\CurrencyRepository;
use App\Services\DatabaseRateStorage;
use App\Services\ExternalRateProvider; // Your implementation
use Illuminate\Support\ServiceProvider;
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\Contracts\RateStorageInterface;
use Nexus\Currency\Services\CurrencyManager;
use Psr\Log\LoggerInterface;

final class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            CurrencyRepositoryInterface::class,
            CurrencyRepository::class
        );

        // Bind rate storage
        $this->app->singleton(
            RateStorageInterface::class,
            DatabaseRateStorage::class
        );

        // Bind rate provider (implement your own or use mock)
        $this->app->singleton(
            ExchangeRateProviderInterface::class,
            ExternalRateProvider::class
        );

        // Bind currency manager
        $this->app->singleton(
            CurrencyManagerInterface::class,
            function ($app) {
                return new CurrencyManager(
                    currencyRepository: $app->make(CurrencyRepositoryInterface::class),
                    rateProvider: $app->make(ExchangeRateProviderInterface::class),
                    rateStorage: $app->make(RateStorageInterface::class),
                    logger: $app->make(LoggerInterface::class)
                );
            }
        );
    }

    public function boot(): void
    {
        // Seed default currencies
        $this->seedDefaultCurrencies();
    }

    private function seedDefaultCurrencies(): void
    {
        if (app()->runningInConsole()) {
            // Seed will run via database seeder
        }
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\CurrencyServiceProvider::class,
],
```

### Step 5: Database Seeder

```php
<?php
// database/seeders/CurrencySeeder.php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'decimal_places' => 2],
            ['code' => 'USD', 'name' => 'US Dollar', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'decimal_places' => 2],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'decimal_places' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'decimal_places' => 0],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'decimal_places' => 3],
            ['code' => 'BTC', 'name' => 'Bitcoin', 'decimal_places' => 4], // Not ISO 4217, but example
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency + ['is_active' => true]
            );
        }
    }
}
```

### Step 6: Usage in Laravel Controllers

```php
<?php
// app/Http/Controllers/InvoiceController.php

namespace App\Http\Controllers;

use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Exceptions\InvalidCurrencyException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly CurrencyManagerInterface $currencyManager
    ) {}

    public function create(Request $request)
    {
        // Validate currency code
        try {
            $currency = $this->currencyManager->getCurrency($request->input('currency'));
        } catch (InvalidCurrencyException $e) {
            return back()->withErrors(['currency' => 'Invalid currency code']);
        }

        // Format amount with correct decimal places
        $formattedAmount = $this->currencyManager->formatAmount(
            amount: $request->input('amount'),
            currencyCode: $currency->getCode()
        );

        // Convert to base currency (MYR)
        $amountInMYR = $this->currencyManager->convert(
            amount: $request->input('amount'),
            fromCurrency: $currency->getCode(),
            toCurrency: 'MYR',
            effectiveDate: now()
        );

        // Store invoice...
    }
}
```

---

## Symfony Integration

### Step 1: Doctrine Entities

```php
<?php
// src/Entity/Currency.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'currencies')]
class Currency
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 3)]
    private string $code;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'smallint')]
    private int $decimalPlaces;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    public function __construct(string $code, string $name, int $decimalPlaces)
    {
        $this->code = $code;
        $this->name = $name;
        $this->decimalPlaces = $decimalPlaces;
    }

    // Getters...
}
```

```php
<?php
// src/Entity/ExchangeRate.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'exchange_rates')]
#[ORM\UniqueConstraint(columns: ['from_currency', 'to_currency', 'effective_date'])]
class ExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 3)]
    private string $fromCurrency;

    #[ORM\Column(type: 'string', length: 3)]
    private string $toCurrency;

    #[ORM\Column(type: 'decimal', precision: 18, scale: 6)]
    private string $rate;

    #[ORM\Column(type: 'date')]
    private \DateTimeImmutable $effectiveDate;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $source = null;

    // Constructor and getters...
}
```

### Step 2: Repository Implementation

```php
<?php
// src/Repository/DoctrineCurrencyRepository.php

namespace App\Repository;

use App\Entity\Currency as CurrencyEntity;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\Exceptions\CurrencyNotFoundException;
use Nexus\Currency\ValueObjects\Currency;

final readonly class DoctrineCurrencyRepository implements CurrencyRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function findByCode(string $code): Currency
    {
        $entity = $this->entityManager
            ->getRepository(CurrencyEntity::class)
            ->findOneBy(['code' => $code, 'isActive' => true]);

        if (!$entity) {
            throw CurrencyNotFoundException::forCode($code);
        }

        return new Currency(
            code: $entity->getCode(),
            decimalPlaces: $entity->getDecimalPlaces()
        );
    }

    public function findAll(): array
    {
        $entities = $this->entityManager
            ->getRepository(CurrencyEntity::class)
            ->findBy(['isActive' => true]);

        return array_map(
            fn($entity) => new Currency(
                code: $entity->getCode(),
                decimalPlaces: $entity->getDecimalPlaces()
            ),
            $entities
        );
    }

    public function exists(string $code): bool
    {
        return $this->entityManager
            ->getRepository(CurrencyEntity::class)
            ->count(['code' => $code, 'isActive' => true]) > 0;
    }
}
```

### Step 3: Service Configuration

```yaml
# config/services.yaml

services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Currency Repository
    Nexus\Currency\Contracts\CurrencyRepositoryInterface:
        class: App\Repository\DoctrineCurrencyRepository

    # Rate Storage
    Nexus\Currency\Contracts\RateStorageInterface:
        class: App\Service\DoctrineRateStorage

    # Rate Provider (your implementation)
    Nexus\Currency\Contracts\ExchangeRateProviderInterface:
        class: App\Service\ExternalRateProvider

    # Currency Manager
    Nexus\Currency\Contracts\CurrencyManagerInterface:
        class: Nexus\Currency\Services\CurrencyManager
        arguments:
            $currencyRepository: '@Nexus\Currency\Contracts\CurrencyRepositoryInterface'
            $rateProvider: '@Nexus\Currency\Contracts\ExchangeRateProviderInterface'
            $rateStorage: '@Nexus\Currency\Contracts\RateStorageInterface'
            $logger: '@logger'
```

### Step 4: Usage in Symfony Controller

```php
<?php
// src/Controller/InvoiceController.php

namespace App\Controller;

use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly CurrencyManagerInterface $currencyManager
    ) {}

    #[Route('/invoice/create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $currencyCode = $request->request->get('currency');
        
        // Validate currency
        if (!$this->currencyManager->isValidCurrency($currencyCode)) {
            return $this->json(['error' => 'Invalid currency'], 400);
        }

        $currency = $this->currencyManager->getCurrency($currencyCode);
        
        // Format amount
        $formattedAmount = $this->currencyManager->formatAmount(
            amount: $request->request->get('amount'),
            currencyCode: $currency->getCode()
        );

        // Convert to EUR
        $amountInEUR = $this->currencyManager->convert(
            amount: $request->request->get('amount'),
            fromCurrency: $currency->getCode(),
            toCurrency: 'EUR',
            effectiveDate: new \DateTimeImmutable()
        );

        // Process invoice...
        
        return $this->json(['success' => true]);
    }
}
```

---

## Custom Framework Integration

For frameworks without built-in dependency injection:

```php
<?php
// bootstrap.php

use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\Contracts\RateStorageInterface;
use Nexus\Currency\Services\CurrencyManager;

// Manual dependency wiring
$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'user', 'pass');

$currencyRepository = new \App\Repository\PDOCurrencyRepository($pdo);
$rateStorage = new \App\Service\PDORateStorage($pdo);
$rateProvider = new \App\Service\ManualRateProvider();
$logger = new \Monolog\Logger('currency');

$currencyManager = new CurrencyManager(
    currencyRepository: $currencyRepository,
    rateProvider: $rateProvider,
    rateStorage: $rateStorage,
    logger: $logger
);

// Use in application
$currency = $currencyManager->getCurrency('USD');
```

---

## Testing Your Integration

### Laravel Test Example

```php
<?php
// tests/Feature/CurrencyIntegrationTest.php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Tests\TestCase;

class CurrencyIntegrationTest extends TestCase
{
    private CurrencyManagerInterface $currencyManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->currencyManager = app(CurrencyManagerInterface::class);
        
        // Seed test data
        Currency::factory()->create(['code' => 'MYR', 'decimal_places' => 2]);
        Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);
        
        ExchangeRate::factory()->create([
            'from_currency' => 'USD',
            'to_currency' => 'MYR',
            'rate' => '4.50',
            'effective_date' => now()->toDateString(),
        ]);
    }

    public function test_can_retrieve_currency(): void
    {
        $currency = $this->currencyManager->getCurrency('MYR');
        
        $this->assertEquals('MYR', $currency->getCode());
        $this->assertEquals(2, $currency->getDecimalPlaces());
    }

    public function test_can_validate_currency(): void
    {
        $this->assertTrue($this->currencyManager->isValidCurrency('MYR'));
        $this->assertFalse($this->currencyManager->isValidCurrency('XXX'));
    }

    public function test_can_format_amount(): void
    {
        $formatted = $this->currencyManager->formatAmount(
            amount: '1234.567',
            currencyCode: 'MYR'
        );
        
        $this->assertEquals('1234.57', $formatted);
    }

    public function test_can_convert_currency(): void
    {
        $converted = $this->currencyManager->convert(
            amount: '100.00',
            fromCurrency: 'USD',
            toCurrency: 'MYR',
            effectiveDate: now()
        );
        
        $this->assertEquals('450.00', $converted);
    }

    public function test_throws_exception_for_invalid_currency(): void
    {
        $this->expectException(\Nexus\Currency\Exceptions\CurrencyNotFoundException::class);
        
        $this->currencyManager->getCurrency('INVALID');
    }
}
```

### Symfony Test Example

```php
<?php
// tests/Integration/CurrencyServiceTest.php

namespace App\Tests\Integration;

use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CurrencyServiceTest extends KernelTestCase
{
    private CurrencyManagerInterface $currencyManager;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->currencyManager = static::getContainer()
            ->get(CurrencyManagerInterface::class);
    }

    public function testCurrencyValidation(): void
    {
        $this->assertTrue($this->currencyManager->isValidCurrency('EUR'));
        $this->assertFalse($this->currencyManager->isValidCurrency('INVALID'));
    }

    public function testAmountFormatting(): void
    {
        $formatted = $this->currencyManager->formatAmount('1234.567', 'EUR');
        
        $this->assertEquals('1234.57', $formatted);
    }

    public function testCurrencyConversion(): void
    {
        // Assuming exchange rate exists
        $converted = $this->currencyManager->convert(
            amount: '100.00',
            fromCurrency: 'EUR',
            toCurrency: 'USD',
            effectiveDate: new \DateTimeImmutable()
        );
        
        $this->assertIsString($converted);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $converted);
    }
}
```

---

## Best Practices

### 1. **Always Validate Currency Codes**
```php
if (!$currencyManager->isValidCurrency($code)) {
    throw new \InvalidArgumentException("Invalid currency: {$code}");
}
```

### 2. **Use BCMath for Precision**
The package uses BCMath internally, ensure it's enabled in your PHP installation.

### 3. **Cache Exchange Rates**
Implement caching in your `RateStorageInterface` to reduce external API calls.

### 4. **Handle Missing Exchange Rates Gracefully**
```php
try {
    $converted = $currencyManager->convert(...);
} catch (ExchangeRateNotFoundException $e) {
    // Fallback: use manual rate or display error
}
```

### 5. **Seed Currencies at Deployment**
Always seed your supported currencies during application setup.

### 6. **Log Currency Operations**
The package logs to PSR-3 logger - configure appropriately for production.

---

## Troubleshooting

### Issue: "Currency not found"
**Solution:** Ensure currencies are seeded in your database and marked as active.

### Issue: "Exchange rate not available"
**Solution:** Check your `ExchangeRateProviderInterface` implementation is correctly fetching rates.

### Issue: "BCMath extension not found"
**Solution:** Install BCMath: `sudo apt-get install php8.3-bcmath` (Ubuntu/Debian)

### Issue: Decimal precision errors
**Solution:** Verify your database schema uses sufficient precision (e.g., `DECIMAL(18, 6)`).

---

## Additional Resources

- [Getting Started Guide](getting-started.md)
- [API Reference](api-reference.md)
- [Code Examples](examples/)
- [Package README](../README.md)
