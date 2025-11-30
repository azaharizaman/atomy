# Getting Started with Nexus Finance

**Package:** `Nexus\Finance`  
**Version:** 1.0  
**PHP Required:** 8.3+

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Core Concepts](#core-concepts)
4. [Configuration](#configuration)
5. [Basic Usage](#basic-usage)
6. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required

- **PHP 8.3+** with extensions:
  - `ext-json`
  - `ext-bcmath` (for precise decimal calculations)
- **Composer** for dependency management
- **Database** (PostgreSQL, MySQL, SQLite)
- **Cache** (Redis, Memcached, or file-based)

### Required Nexus Packages

```bash
composer require nexus/tenant:"*@dev"      # Multi-tenancy context
composer require nexus/period:"*@dev"      # Fiscal period management
```

### Optional Nexus Packages

```bash
composer require nexus/audit-logger:"*@dev"  # Audit trail
composer require nexus/monitoring:"*@dev"    # Telemetry tracking
composer require nexus/event-stream:"*@dev"  # Event sourcing for GL
```

---

## Installation

### Step 1: Install Package

```bash
composer require nexus/finance:"*@dev"
```

### Step 2: Database Setup

**Create migration files** in your application:

```php
// database/migrations/2025_01_01_000001_create_accounts_table.php
public function up()
{
    Schema::create('accounts', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->ulid('tenant_id')->index();
        $table->string('code', 20)->unique();
        $table->string('name');
        $table->string('type'); // Asset, Liability, Equity, Revenue, Expense
        $table->ulid('parent_id')->nullable()->index();
        $table->string('currency', 3)->default('MYR');
        $table->boolean('is_active')->default(true);
        $table->json('metadata')->nullable();
        $table->timestamps();
        $table->softDeletes();
        
        $table->unique(['tenant_id', 'code']);
    });
}
```

```php
// database/migrations/2025_01_01_000002_create_journal_entries_table.php
public function up()
{
    Schema::create('journal_entries', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->ulid('tenant_id')->index();
        $table->string('number', 50)->unique();
        $table->date('entry_date')->index();
        $table->ulid('period_id')->index();
        $table->text('description');
        $table->string('status', 20)->default('Draft'); // Draft, Posted, Reversed
        $table->string('reference')->nullable();
        $table->ulid('posted_by')->nullable();
        $table->timestamp('posted_at')->nullable();
        $table->ulid('reversed_by')->nullable();
        $table->timestamp('reversed_at')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
        $table->softDeletes();
        
        $table->index(['tenant_id', 'entry_date']);
        $table->index(['tenant_id', 'status']);
    });
}
```

```php
// database/migrations/2025_01_01_000003_create_journal_entry_lines_table.php
public function up()
{
    Schema::create('journal_entry_lines', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->ulid('journal_entry_id')->index();
        $table->ulid('account_id')->index();
        $table->decimal('debit', 19, 4)->default(0);
        $table->decimal('credit', 19, 4)->default(0);
        $table->string('currency', 3)->default('MYR');
        $table->decimal('exchange_rate', 12, 6)->default(1);
        $table->text('description')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
        
        $table->foreign('journal_entry_id')
            ->references('id')->on('journal_entries')
            ->onDelete('cascade');
        $table->foreign('account_id')
            ->references('id')->on('accounts')
            ->onDelete('restrict');
    });
}
```

Run migrations:
```bash
php artisan migrate
```

### Step 3: Create Eloquent Models

```php
// app/Models/Finance/Account.php
namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Nexus\Finance\Contracts\AccountInterface;
use Nexus\Finance\Enums\AccountType;
use Nexus\Finance\ValueObjects\AccountCode;

class Account extends Model implements AccountInterface
{
    protected $fillable = [
        'code', 'name', 'type', 'parent_id', 'currency', 'is_active', 'metadata'
    ];
    
    protected $casts = [
        'type' => AccountType::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getCode(): AccountCode
    {
        return new AccountCode($this->code);
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getType(): AccountType
    {
        return $this->type;
    }
    
    public function getCurrency(): string
    {
        return $this->currency;
    }
    
    public function isActive(): bool
    {
        return $this->is_active;
    }
    
    // Relationships
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }
    
    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
```

### Step 4: Create Repository Implementations

```php
// app/Repositories/Finance/EloquentAccountRepository.php
namespace App\Repositories\Finance;

use App\Models\Finance\Account;
use Nexus\Finance\Contracts\{AccountInterface, AccountRepositoryInterface};
use Nexus\Finance\ValueObjects\AccountCode;
use Nexus\Finance\Enums\AccountType;
use Nexus\Finance\Exceptions\AccountNotFoundException;

final readonly class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    public function findById(string $id): AccountInterface
    {
        $account = Account::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->findOrFail($id);
            
        return $account;
    }
    
    public function findByCode(AccountCode $code): ?AccountInterface
    {
        return Account::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('code', $code->value)
            ->first();
    }
    
    public function findByType(AccountType $type): array
    {
        return Account::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('type', $type->value)
            ->where('is_active', true)
            ->get()
            ->all();
    }
    
    public function save(AccountInterface $account): AccountInterface
    {
        $model = $account instanceof Account ? $account : $this->toModel($account);
        $model->tenant_id = $this->tenantContext->getCurrentTenantId();
        $model->save();
        
        return $model;
    }
    
    public function delete(string $id): void
    {
        $account = $this->findById($id);
        $account->delete();
    }
}
```

### Step 5: Register Service Provider

```php
// app/Providers/FinanceServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Finance\Contracts\{
    AccountRepositoryInterface,
    JournalEntryRepositoryInterface,
    FinanceManagerInterface
};
use App\Repositories\Finance\{
    EloquentAccountRepository,
    EloquentJournalEntryRepository
};
use Nexus\Finance\Services\FinanceManager;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(AccountRepositoryInterface::class, EloquentAccountRepository::class);
        $this->app->singleton(JournalEntryRepositoryInterface::class, EloquentJournalEntryRepository::class);
        
        // Bind finance manager
        $this->app->singleton(FinanceManagerInterface::class, FinanceManager::class);
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\FinanceServiceProvider::class,
],
```

---

## Core Concepts

### 1. Chart of Accounts (COA)

The **Chart of Accounts** is the foundational structure of the GL system. It defines all accounts used for recording financial transactions.

**Account Hierarchy:**
```
1000 - Assets
  1100 - Current Assets
    1110 - Cash
    1120 - Accounts Receivable
  1200 - Fixed Assets
    1210 - Equipment
    1220 - Accumulated Depreciation

2000 - Liabilities
  2100 - Current Liabilities
    2110 - Accounts Payable
    2120 - Accrued Expenses

3000 - Equity
  3100 - Share Capital
  3200 - Retained Earnings

4000 - Revenue
  4100 - Sales Revenue
  4200 - Service Revenue

5000 - Expenses
  5100 - Cost of Goods Sold
  5200 - Operating Expenses
```

**Account Types (Enum):**
- `Asset` - Resources owned (Cash, AR, Inventory, Equipment)
- `Liability` - Obligations owed (AP, Loans, Accrued Expenses)
- `Equity` - Owner's interest (Capital, Retained Earnings)
- `Revenue` - Income earned (Sales, Service Revenue)
- `Expense` - Costs incurred (COGS, Salaries, Rent)

### 2. Double-Entry Bookkeeping

Every transaction affects **at least two accounts** - one debit and one credit.

**Fundamental Equation:**
```
Assets = Liabilities + Equity
Debit = Credit (always balanced)
```

**Debit/Credit Rules:**

| Account Type | Debit | Credit |
|--------------|-------|--------|
| **Asset** | Increase | Decrease |
| **Liability** | Decrease | Increase |
| **Equity** | Decrease | Increase |
| **Revenue** | Decrease | Increase |
| **Expense** | Increase | Decrease |

**Example Transaction:**
```
Customer pays $1,000 for services:
  Debit:  Cash (Asset)          $1,000
  Credit: Service Revenue        $1,000
```

### 3. Journal Entries

A **Journal Entry** is a record of a financial transaction.

**Components:**
- **Number** - Unique identifier (e.g., JE-2025-001)
- **Date** - Transaction date
- **Description** - What the transaction is for
- **Lines** - Individual account debits/credits
- **Status** - Draft, Posted, or Reversed

**Journal Entry Lifecycle:**
1. **Draft** - Created but not yet recorded in ledger
2. **Posted** - Recorded and affects account balances
3. **Reversed** - Undone with reverse entry (audit trail preserved)

### 4. Multi-Currency Support

The Finance package supports multi-currency transactions with automatic conversion.

**Exchange Rate Handling:**
- Each journal entry line can have its own currency
- Exchange rate is recorded at transaction time
- Base currency balances calculated automatically

**Example:**
```
Receive $1,000 USD payment (exchange rate: 4.75 MYR/USD):
  Debit:  Cash USD               $1,000 USD (@ 4.75 = 4,750 MYR)
  Credit: Accounts Receivable    4,750 MYR
```

### 5. Period Locking

**Fiscal periods** can be closed to prevent backdated entries.

**Period Validation:**
- Journal entries must be in an open period
- Closed periods reject new postings
- Supports period-end close process

### 6. Trial Balance

A **Trial Balance** verifies that total debits equal total credits.

**Format:**
```
Account Code | Account Name           | Debit    | Credit
--------------------------------------------------------
1110         | Cash                   | 50,000   | 0
1120         | Accounts Receivable    | 30,000   | 0
2110         | Accounts Payable       | 0        | 15,000
4100         | Sales Revenue          | 0        | 65,000
                                     --------   --------
                                      80,000     80,000
```

---

## Configuration

### Configure Base Currency

```php
// config/finance.php
return [
    'base_currency' => env('FINANCE_BASE_CURRENCY', 'MYR'),
    'decimal_places' => 4,
    'require_balanced_entries' => true,
    'allow_negative_balances' => false, // For asset accounts
];
```

### Configure Audit Logging (Optional)

```php
// In FinanceServiceProvider
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;

$this->app->extend(FinanceManagerInterface::class, function ($manager, $app) {
    if ($app->bound(AuditLogManagerInterface::class)) {
        return new AuditableFinanceManager(
            $manager,
            $app->make(AuditLogManagerInterface::class)
        );
    }
    return $manager;
});
```

---

## Basic Usage

### Example 1: Create Chart of Accounts

```php
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\Enums\AccountType;
use Nexus\Finance\ValueObjects\AccountCode;

$financeManager = app(FinanceManagerInterface::class);

// Create parent account
$assetsParent = $financeManager->createAccount(
    code: new AccountCode('1000'),
    name: 'Assets',
    type: AccountType::Asset
);

// Create child accounts
$cash = $financeManager->createAccount(
    code: new AccountCode('1110'),
    name: 'Cash',
    type: AccountType::Asset,
    parentId: $assetsParent->getId()
);

$accountsReceivable = $financeManager->createAccount(
    code: new AccountCode('1120'),
    name: 'Accounts Receivable',
    type: AccountType::Asset,
    parentId: $assetsParent->getId()
);
```

### Example 2: Post Simple Journal Entry

```php
use Nexus\Finance\ValueObjects\{JournalEntryNumber, Money};

// Create journal entry
$journalEntry = new JournalEntry([
    'number' => new JournalEntryNumber('JE-2025-001'),
    'entry_date' => now(),
    'description' => 'Customer payment received',
    'lines' => [
        [
            'account_id' => $cash->getId(),
            'debit' => new Money(1000, 'MYR'),
            'credit' => new Money(0, 'MYR'),
        ],
        [
            'account_id' => $accountsReceivable->getId(),
            'debit' => new Money(0, 'MYR'),
            'credit' => new Money(1000, 'MYR'),
        ],
    ],
]);

// Post to ledger
$financeManager->postJournalEntry($journalEntry);
```

### Example 3: Get Account Balance

```php
$balance = $financeManager->getAccountBalance($cash->getId());

echo "Cash Balance: " . $balance->format(); // "MYR 1,000.00"
```

---

## Troubleshooting

### Issue 1: Unbalanced Journal Entry Error

**Problem:** `UnbalancedJournalEntryException` thrown when posting.

**Cause:** Total debits ≠ total credits.

**Solution:**
```php
// Check journal entry balance before posting
$totalDebit = array_sum(array_column($lines, 'debit'));
$totalCredit = array_sum(array_column($lines, 'credit'));

if ($totalDebit !== $totalCredit) {
    throw new \Exception("Entry is unbalanced: Debit {$totalDebit} vs Credit {$totalCredit}");
}
```

### Issue 2: Cannot Delete Account with Transactions

**Problem:** `AccountHasTransactionsException` thrown.

**Cause:** Account has posted journal entries.

**Solution:** Deactivate instead of deleting:
```php
$account->is_active = false;
$account->save();
```

### Issue 3: Period is Closed

**Problem:** Cannot post journal entry to closed period.

**Cause:** Attempting to post to a locked fiscal period.

**Solution:** Post to current open period or reopen the period:
```php
// Check if period is open first
$period = Period::where('start_date', '<=', $entryDate)
    ->where('end_date', '>=', $entryDate)
    ->first();

if ($period && !$period->is_open) {
    throw new PeriodClosedException("Period {$period->name} is closed");
}
```

---

## Next Steps

- **[API Reference](api-reference.md)** - Complete interface documentation
- **[Integration Guide](integration-guide.md)** - Framework integration examples
- **[Examples](examples/)** - Working code samples

---

**Last Updated:** 2025-11-25  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Finance Team
