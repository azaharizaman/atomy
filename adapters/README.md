# Nexus Adapters

This directory contains **framework-specific adapter packages** that provide concrete implementations of interfaces defined in atomic packages and orchestrators. Adapters are the **only layer** where framework code is allowed.

**Key Principle:** Adapters bridge the gap between pure PHP business logic (packages/orchestrators) and framework-specific infrastructure (Eloquent, Jobs, Controllers, Migrations).

---

## 🎯 What is an Adapter?

An adapter is a **framework-specific package** that:
- ✅ Implements repository interfaces using framework ORM (Eloquent, Doctrine)
- ✅ Contains database migrations and seeders
- ✅ Provides HTTP controllers and API resources
- ✅ Handles framework-specific jobs/queues
- ✅ Binds interfaces to implementations via service providers
- ❌ Does NOT contain business logic (that belongs in atomic packages)
- ❌ Does NOT define domain entities (those belong in atomic packages)
- ❌ Does NOT implement workflows (those belong in orchestrators)

**This is the ONLY place where `use Illuminate\...` or `use Symfony\...` is allowed.**

---

## 📋 Current Adapters

### Laravel/
Framework adapters for Laravel applications.

**Current Implementations:**
- Laravel adapter structure is established
- Individual domain adapters to be created as needed

**Planned Adapters:**
- `Laravel/Finance/` - Eloquent models and migrations for Finance package
- `Laravel/Inventory/` - Eloquent models and migrations for Inventory package
- `Laravel/Identity/` - Eloquent models and migrations for Identity package
- `Laravel/Sales/` - Eloquent models and migrations for Sales package

---

## 🏗️ Adapter Architecture

### Standard Folder Structure

```
adapters/Laravel/DomainName/
├── composer.json              # Requires: Nexus\DomainName, illuminate/database
├── LICENSE
├── README.md
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── .gitignore
├── src/
│   ├── Providers/             # Service Providers (Bind Interfaces → Eloquent)
│   │   └── DomainServiceProvider.php
│   │
│   ├── Models/                # Eloquent Models (extends Model)
│   │   ├── Entity.php
│   │   └── README.md
│   │
│   ├── Repositories/          # Concrete Repositories using Eloquent
│   │   ├── EloquentEntityRepository.php
│   │   └── README.md
│   │
│   ├── Database/
│   │   ├── Migrations/        # Database schema migrations
│   │   ├── Seeders/           # Database seeders
│   │   └── Factories/         # Model factories for testing
│   │
│   ├── Http/
│   │   ├── Controllers/       # API/Web controllers
│   │   ├── Requests/          # Form request validation
│   │   └── Resources/         # API Resources (JSON transformers)
│   │
│   ├── Jobs/                  # Laravel Queued Jobs
│   │   └── ProcessEntityJob.php
│   │
│   ├── Console/               # Artisan Commands
│   │   └── Commands/
│   │       └── EntityCommand.php
│   │
│   └── Exceptions/            # Laravel-specific exceptions
│       └── ModelNotFoundException.php
│
└── tests/                     # Integration tests requiring DB
    ├── Feature/
    └── Unit/
```

---

## 📐 Coding Guidelines

### ✅ DO

1. **Implement Package Interfaces**
   ```php
   namespace App\Adapters\Laravel\Finance\Repositories;
   
   use Nexus\Finance\Contracts\AccountRepositoryInterface;
   use App\Adapters\Laravel\Finance\Models\Account;
   
   final class EloquentAccountRepository implements AccountRepositoryInterface
   {
       public function findById(string $id): AccountInterface
       {
           $model = Account::findOrFail($id);
           return $this->toDomainEntity($model);
       }
       
       private function toDomainEntity(Account $model): AccountInterface
       {
           // Map Eloquent model to domain entity/VO
       }
   }
   ```

2. **Bind Interfaces in Service Provider**
   ```php
   namespace App\Adapters\Laravel\Finance\Providers;
   
   use Illuminate\Support\ServiceProvider;
   use Nexus\Finance\Contracts\AccountRepositoryInterface;
   use App\Adapters\Laravel\Finance\Repositories\EloquentAccountRepository;
   
   final class FinanceServiceProvider extends ServiceProvider
   {
       public function register(): void
       {
           $this->app->singleton(
               AccountRepositoryInterface::class,
               EloquentAccountRepository::class
           );
       }
   }
   ```

3. **Create Migrations for Package Entities**
   ```php
   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;
   
   return new class extends Migration
   {
       public function up(): void
       {
           Schema::create('accounts', function (Blueprint $table) {
               $table->string('id', 26)->primary(); // ULID
               $table->string('tenant_id', 26)->index();
               $table->string('code', 20)->unique();
               $table->string('name', 255);
               $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
               $table->decimal('balance', 15, 2)->default(0);
               $table->timestamps();
               
               $table->foreign('tenant_id')->references('id')->on('tenants');
           });
       }
   };
   ```

4. **Map Between Domain and Eloquent**
   ```php
   final class EloquentAccountRepository implements AccountRepositoryInterface
   {
       private function toDomainEntity(Account $model): AccountInterface
       {
           return new AccountEntity(
               id: $model->id,
               code: $model->code,
               name: $model->name,
               type: AccountType::from($model->type),
               balance: Money::fromCents($model->balance_cents, $model->currency)
           );
       }
       
       private function toEloquentModel(AccountInterface $account): Account
       {
           $model = Account::findOrNew($account->getId());
           $model->code = $account->getCode();
           $model->name = $account->getName();
           $model->type = $account->getType()->value;
           $model->balance_cents = $account->getBalance()->getAmountInCents();
           $model->currency = $account->getBalance()->getCurrency();
           return $model;
       }
   }
   ```

5. **Create API Resources for External Consumption**
   ```php
   namespace App\Adapters\Laravel\Finance\Http\Resources;
   
   use Illuminate\Http\Resources\Json\JsonResource;
   
   final class AccountResource extends JsonResource
   {
       public function toArray($request): array
       {
           return [
               'id' => $this->id,
               'code' => $this->code,
               'name' => $this->name,
               'type' => $this->type,
               'balance' => [
                   'amount' => $this->balance / 100,
                   'currency' => $this->currency
               ],
               'created_at' => $this->created_at->toIso8601String()
           ];
       }
   }
   ```

### ❌ DON'T

1. **Put Business Logic in Adapters**
   ```php
   // ❌ WRONG: Business logic in controller
   final class InvoiceController extends Controller
   {
       public function store(Request $request)
       {
           $invoice = new Invoice();
           $invoice->total = array_sum($request->line_items); // Business logic!
           $invoice->save();
       }
   }
   
   // ✅ CORRECT: Business logic in package, controller delegates
   final class InvoiceController extends Controller
   {
       public function __construct(
           private InvoiceManagerInterface $invoiceManager
       ) {}
       
       public function store(InvoiceRequest $request)
       {
           $invoice = $this->invoiceManager->create($request->validated());
           return new InvoiceResource($invoice);
       }
   }
   ```

2. **Define Domain Entities in Adapters**
   ```php
   // ❌ WRONG: Domain entity in adapter
   namespace App\Adapters\Laravel\Finance\Models;
   
   class Invoice extends Model // This is a persistence model, NOT a domain entity
   {
       public function calculateTotal(): float
       {
           return $this->line_items->sum('amount'); // Domain logic!
       }
   }
   
   // ✅ CORRECT: Eloquent model is just persistence
   namespace App\Adapters\Laravel\Finance\Models;
   
   class Invoice extends Model
   {
       protected $fillable = ['number', 'customer_id', 'total'];
       
       public function lineItems()
       {
           return $this->hasMany(InvoiceLineItem::class);
       }
   }
   
   // Domain entity lives in package
   namespace Nexus\Receivable\Domain\Entities;
   
   final class Invoice implements InvoiceInterface
   {
       public function calculateTotal(): Money
       {
           // Business logic here
       }
   }
   ```

3. **Access Other Adapters Directly**
   ```php
   // ❌ WRONG: Adapter coupling
   use App\Adapters\Laravel\Finance\Repositories\EloquentAccountRepository;
   
   final class InvoiceController extends Controller
   {
       public function __construct(
           private EloquentAccountRepository $accountRepo // Concrete adapter!
       ) {}
   }
   
   // ✅ CORRECT: Depend on package interface
   use Nexus\Finance\Contracts\AccountRepositoryInterface;
   
   final class InvoiceController extends Controller
   {
       public function __construct(
           private AccountRepositoryInterface $accountRepo // Package interface!
       ) {}
   }
   ```

---

## 🔍 Decision Matrix: Where Does This Code Belong?

| Question | Atomic Package | Orchestrator | Adapter |
|----------|---------------|--------------|---------|
| Does it use Eloquent/Doctrine? | ❌ No | ❌ No | ✅ Yes |
| Does it define business rules? | ✅ Yes | ✅ Yes | ❌ No |
| Does it have database migrations? | ❌ No | ❌ No | ✅ Yes |
| Does it contain HTTP controllers? | ❌ No | ❌ No | ✅ Yes |
| Does it implement package interfaces? | ❌ No | ❌ No | ✅ Yes |
| Can it run without a framework? | ✅ Yes | ✅ Yes | ❌ No |
| Can it be published to Packagist? | ✅ Yes | ✅ Yes | ❌ No |

---

## 🚀 Creating a New Adapter

### Step 1: Identify the Package

Determine which atomic package needs Laravel infrastructure.

**Example:** `Nexus\Finance` package needs Eloquent models and migrations.

### Step 2: Create Adapter Structure

```bash
mkdir -p adapters/Laravel/Finance
cd adapters/Laravel/Finance

# Initialize composer
composer init
# Name: nexus/laravel-finance-adapter
# Require: nexus/finance, illuminate/database, illuminate/support

# Create folder structure
mkdir -p src/{Providers,Models,Repositories,Database/{Migrations,Seeders,Factories},Http/{Controllers,Requests,Resources},Jobs,Console/Commands,Exceptions}
mkdir -p tests/{Feature,Unit}

# Create documentation
touch README.md
touch IMPLEMENTATION_SUMMARY.md
touch REQUIREMENTS.md
touch TEST_SUITE_SUMMARY.md
touch VALUATION_MATRIX.md
touch LICENSE
touch .gitignore
```

### Step 3: Create Eloquent Models

Map package entities to database tables:
```php
namespace App\Adapters\Laravel\Finance\Models;

use Illuminate\Database\Eloquent\Model;

final class Account extends Model
{
    protected $fillable = ['id', 'tenant_id', 'code', 'name', 'type', 'balance'];
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### Step 4: Create Repository Implementation

```php
namespace App\Adapters\Laravel\Finance\Repositories;

use Nexus\Finance\Contracts\AccountRepositoryInterface;
use App\Adapters\Laravel\Finance\Models\Account;

final class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function findById(string $id): AccountInterface
    {
        $model = Account::findOrFail($id);
        return $this->toDomainEntity($model);
    }
    
    public function save(AccountInterface $account): void
    {
        $model = $this->toEloquentModel($account);
        $model->save();
    }
    
    // Mapping methods...
}
```

### Step 5: Create Service Provider

```php
namespace App\Adapters\Laravel\Finance\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Finance\Contracts\AccountRepositoryInterface;
use App\Adapters\Laravel\Finance\Repositories\EloquentAccountRepository;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            AccountRepositoryInterface::class,
            EloquentAccountRepository::class
        );
    }
    
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
```

### Step 6: Create Migrations

```bash
php artisan make:migration create_accounts_table
```

### Step 7: Register in Application

Add to `config/app.php`:
```php
'providers' => [
    // ...
    App\Adapters\Laravel\Finance\Providers\FinanceServiceProvider::class,
],
```

---

## 🧪 Testing Adapters

### Feature Tests (Database Required)

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EloquentAccountRepositoryTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_find_by_id_returns_domain_entity(): void
    {
        // Arrange
        $model = Account::create([
            'id' => 'account-123',
            'tenant_id' => 'tenant-1',
            'code' => 'ACC001',
            'name' => 'Test Account',
            'type' => 'asset',
            'balance' => 1000
        ]);
        
        $repository = new EloquentAccountRepository();
        
        // Act
        $account = $repository->findById('account-123');
        
        // Assert
        $this->assertInstanceOf(AccountInterface::class, $account);
        $this->assertEquals('ACC001', $account->getCode());
    }
}
```

### Unit Tests (Mocking Eloquent)

```php
use PHPUnit\Framework\TestCase;

final class AccountControllerTest extends TestCase
{
    public function test_store_creates_account(): void
    {
        $manager = $this->createMock(AccountManagerInterface::class);
        $manager->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(AccountInterface::class));
        
        $controller = new AccountController($manager);
        
        // Test controller logic...
    }
}
```

---

## 📚 Dependency Direction

**CRITICAL RULE:** Dependencies flow in ONE direction only:

```
Adapters → depends on → Packages/Orchestrators
Packages/Orchestrators → NEVER depend on → Adapters
Applications → depend on → Adapters AND Packages/Orchestrators
```

**Example:**
- ✅ `adapters/Laravel/Finance` requires `nexus/finance` ✅
- ❌ `nexus/finance` CANNOT require `nexus/laravel-finance-adapter` ❌

---

## 📖 Key References

- **Architecture Guidelines:** `../ARCHITECTURE.md`
- **Coding Standards:** `../CODING_GUIDELINES.md`
- **Package Reference:** `../docs/NEXUS_PACKAGES_REFERENCE.md`
- **Refactoring Exercise:** `../REFACTOR_EXERCISE_12.md`
- **Atomic Packages:** `../packages/README.md`
- **Orchestrators:** `../orchestrators/README.md`

---

**Last Updated:** 2025-11-30  
**Maintained By:** Nexus Architecture Team  
**Current Adapters:** Laravel structure established  
**Planned Adapters:** Finance, Inventory, Identity, Sales, and more as needed
