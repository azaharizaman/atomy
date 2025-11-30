# Getting Started with Nexus CashManagement

## Prerequisites

- PHP 8.3 or higher
- Composer
- Required Nexus packages: Finance, Receivable, Payable, Period, Currency, Sequencing, Import, Setting, Workflow
- Database (MySQL 8.0+ or PostgreSQL 13+)
- Understanding of bank reconciliation processes

## Installation

```bash
composer require nexus/cash-management:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Bank account management with multi-currency support
- ✅ Automated bank statement import from CSV files
- ✅ Automatic reconciliation with AI assistance
- ✅ Cash flow forecasting with multiple scenarios
- ✅ SOX-compliant bank reconciliation workflows
- ✅ GL integration for unmatched transactions
- ✅ Liquidity management and cash position tracking

Do NOT use this package for:
- ❌ General ledger posting (use `Nexus\Finance` instead)
- ❌ Customer invoicing (use `Nexus\Receivable` instead)
- ❌ Vendor bill management (use `Nexus\Payable` instead)
- ❌ Payment processing (use payment gateway integrations)

---

## Core Concepts

### Concept 1: Framework-Agnostic Architecture

The `CashManagement` package defines **contracts (interfaces)** only. Your application implements these contracts using your chosen framework (Laravel, Symfony, etc.).

**Package Provides:**
- Interfaces for all operations
- Value Objects for domain data
- Enums for type safety
- Exceptions for domain errors

**Your Application Provides:**
- Eloquent/Doctrine models implementing interfaces
- Repository implementations
- Database migrations
- Event listeners
- Service provider bindings

### Concept 2: Separation of Concerns

```
┌──────────────────────────────────────────────────────────┐
│ Nexus\Import                                             │
│ Parses CSV → Emits StatementLineDTO[]                    │
└──────────────────────────────────────────────────────────┘
                            ▼
┌──────────────────────────────────────────────────────────┐
│ Nexus\CashManagement                                     │
│ Consumes DTOs → Creates BankStatement → Matches          │
└──────────────────────────────────────────────────────────┘
                            ▼
┌──────────────────────────────────────────────────────────┐
│ Nexus\Finance                                            │
│ Posts Journal Entries to GL                              │
└──────────────────────────────────────────────────────────┘
```

**Key Principle:** CashManagement does NOT post to GL. It creates `PendingAdjustment` entities that require manual GL posting for SOX compliance.

### Concept 3: Automatic vs Manual Reconciliation

**Automatic Reconciliation:**
- High-confidence matches (amount + date within tolerance)
- Automatically creates PaymentApplication (for deposits)
- Automatically matches Payment records (for withdrawals)

**Manual Reconciliation:**
- Low-confidence or unmatched transactions
- Creates `PendingAdjustment` entity
- User reviews and approves GL posting
- AI suggests GL account (with model version tracking)

### Concept 4: Reversal Workflows

**When User Rejects Pending Adjustment:**

1. **Immediate Action:** Reverse PaymentApplication (if exists)
   - Invoice marked as unpaid
   - Audit trail created

2. **Workflow Trigger:** Initiate GL reversal workflow
   - Requires approval (SOX compliance)
   - Documented reason
   - Approval hierarchy

### Concept 5: Multi-Tenant Isolation

All operations are tenant-scoped:
- Bank accounts belong to a tenant
- Statements belong to a tenant
- Reconciliations scoped by tenant
- Forecasts scoped by tenant

**Tenant context** is injected via `TenantContextInterface` from `Nexus\Tenant`.

---

## Basic Configuration

### Step 1: Implement Required Interfaces

Your application must implement these core interfaces:

#### Bank Account Interface

```php
namespace App\Models\CashManagement;

use Illuminate\Database\Eloquent\Model;
use Nexus\CashManagement\Contracts\BankAccountInterface;
use Nexus\CashManagement\Enums\BankAccountType;
use Nexus\CashManagement\Enums\BankAccountStatus;

class BankAccount extends Model implements BankAccountInterface
{
    protected $fillable = [
        'tenant_id', 'account_code', 'gl_account_id', 'account_number',
        'bank_name', 'bank_code', 'account_type', 'status', 'currency',
        'current_balance', 'csv_import_config'
    ];

    protected $casts = [
        'account_type' => BankAccountType::class,
        'status' => BankAccountStatus::class,
        'current_balance' => 'decimal:4',
        'csv_import_config' => 'array',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getAccountCode(): string
    {
        return $this->account_code;
    }

    public function getGlAccountId(): string
    {
        return $this->gl_account_id;
    }

    public function getAccountNumber(): string
    {
        return $this->account_number;
    }

    public function getBankName(): string
    {
        return $this->bank_name;
    }

    public function getBankCode(): string
    {
        return $this->bank_code;
    }

    public function getAccountType(): BankAccountType
    {
        return $this->account_type;
    }

    public function getStatus(): BankAccountStatus
    {
        return $this->status;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCurrentBalance(): string
    {
        return $this->current_balance;
    }

    public function getCSVImportConfig(): ?array
    {
        return $this->csv_import_config;
    }
}
```

#### Repository Implementation

```php
namespace App\Repositories\CashManagement;

use Nexus\CashManagement\Contracts\BankAccountRepositoryInterface;
use Nexus\CashManagement\Contracts\BankAccountInterface;
use App\Models\CashManagement\BankAccount;

final readonly class EloquentBankAccountRepository implements BankAccountRepositoryInterface
{
    public function __construct(
        private BankAccount $model
    ) {}

    public function findById(string $id): BankAccountInterface
    {
        return $this->model->findOrFail($id);
    }

    public function findByTenant(string $tenantId): array
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->all();
    }

    public function save(BankAccountInterface $bankAccount): void
    {
        $bankAccount->save();
    }

    public function delete(string $id): void
    {
        $this->model->findOrFail($id)->delete();
    }
}
```

---

### Step 2: Create Database Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('account_code', 50)->unique();
            $table->string('gl_account_id', 26);
            $table->string('account_number', 100);
            $table->string('bank_name', 255);
            $table->string('bank_code', 50);
            $table->string('branch_code', 50)->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->string('iban', 34)->nullable();
            $table->enum('account_type', ['checking', 'savings', 'credit_card', 'money_market', 'line_of_credit']);
            $table->enum('status', ['active', 'inactive', 'closed', 'suspended'])->default('active');
            $table->string('currency', 3)->default('MYR');
            $table->decimal('current_balance', 19, 4)->default(0);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->json('csv_import_config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
```

**Note:** See the full database schema in `IMPLEMENTATION_SUMMARY.md` for all tables (bank_statements, bank_transactions, reconciliations, pending_adjustments, etc.).

---

### Step 3: Bind Interfaces in Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\CashManagement\Contracts\BankAccountRepositoryInterface;
use Nexus\CashManagement\Contracts\CashManagementManagerInterface;
use App\Repositories\CashManagement\EloquentBankAccountRepository;
use App\Services\CashManagement\CashManagementManager;

class CashManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            BankAccountRepositoryInterface::class,
            EloquentBankAccountRepository::class
        );

        // ... bind other repositories (BankStatement, BankTransaction, etc.)

        // Bind manager
        $this->app->singleton(
            CashManagementManagerInterface::class,
            CashManagementManager::class
        );
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\CashManagementServiceProvider::class,
],
```

---

### Step 4: Configure CSV Import

Set up CSV column mapping for each bank account:

```php
$csvImportConfig = [
    'date_column' => 'Transaction Date',
    'description_column' => 'Description',
    'debit_column' => 'Debit',
    'credit_column' => 'Credit',
    'balance_column' => 'Balance',
    'reference_column' => 'Reference', // Optional
];

$bankAccount = $cashManager->createBankAccount(
    tenantId: $tenantId,
    accountCode: '1000-01',
    glAccountId: $cashAccountGlId,
    accountNumber: '1234567890',
    bankName: 'Maybank',
    bankCode: 'MBB',
    accountType: BankAccountType::CHECKING,
    currency: 'MYR',
    csvImportConfig: $csvImportConfig
);
```

---

## Your First Integration

### Example 1: Import Bank Statement

```php
use Nexus\CashManagement\Contracts\CashManagementManagerInterface;
use Nexus\Import\Contracts\ImportManagerInterface;

class BankStatementImportController
{
    public function __construct(
        private readonly ImportManagerInterface $importManager,
        private readonly CashManagementManagerInterface $cashManager
    ) {}

    public function import(Request $request)
    {
        // Step 1: Parse CSV via Nexus\Import
        $bankAccountId = $request->input('bank_account_id');
        $bankAccount = $this->bankAccountRepo->findById($bankAccountId);
        $csvConfig = $bankAccount->getCSVImportConfig();

        $result = $this->importManager->importFile(
            filePath: $request->file('csv_file')->path(),
            importType: 'bank_statement',
            config: $csvConfig
        );

        // Step 2: Create bank statement
        $statement = $this->cashManager->importBankStatement(
            bankAccountId: $bankAccountId,
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            transactions: $result->getData(),
            importedBy: auth()->id()
        );

        // Step 3: Auto-reconcile
        $reconciliationResult = $this->cashManager->reconcileStatement($statement->getId());

        return response()->json([
            'statement_id' => $statement->getId(),
            'matched' => $reconciliationResult->getMatchedCount(),
            'unmatched' => $reconciliationResult->getUnmatchedCount(),
        ]);
    }
}
```

### Example 2: Review Pending Adjustments

```php
use Nexus\CashManagement\Contracts\PendingAdjustmentRepositoryInterface;

class PendingAdjustmentController
{
    public function __construct(
        private readonly PendingAdjustmentRepositoryInterface $repository,
        private readonly CashManagementManagerInterface $cashManager
    ) {}

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $pendingAdjustments = $this->repository->findByTenant($tenantId);

        return view('cash-management.pending-adjustments', [
            'adjustments' => $pendingAdjustments,
        ]);
    }

    public function approve(Request $request, string $id)
    {
        $validated = $request->validate([
            'gl_account' => 'required|exists:gl_accounts,account_code',
        ]);

        $journalEntryId = $this->cashManager->postPendingAdjustment(
            pendingAdjustmentId: $id,
            glAccount: $validated['gl_account'],
            postedBy: auth()->id()
        );

        return redirect()->back()->with('success', 'Adjustment posted to GL');
    }

    public function reject(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $this->cashManager->rejectPendingAdjustment(
            pendingAdjustmentId: $id,
            reason: $validated['reason'],
            rejectedBy: auth()->id()
        );

        return redirect()->back()->with('success', 'Adjustment rejected and reversal initiated');
    }
}
```

### Example 3: Generate Cash Flow Forecast

```php
use Nexus\CashManagement\Contracts\CashFlowForecastInterface;
use Nexus\CashManagement\ValueObjects\ScenarioParametersVO;
use Nexus\CashManagement\Enums\ForecastScenarioType;

class CashFlowForecastController
{
    public function __construct(
        private readonly CashFlowForecastInterface $cashFlowForecast
    ) {}

    public function generate(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $parameters = ScenarioParametersVO::fromScenarioType(
            scenarioType: ForecastScenarioType::BASELINE,
            horizonDays: 90
        );

        $forecast = $this->cashFlowForecast->forecast($tenantId, $parameters);

        if ($forecast->hasNegativeBalance()) {
            // Alert finance team: Liquidity risk detected
            event(new LiquidityRiskDetected($tenantId, $forecast));
        }

        return view('cash-management.forecast', [
            'forecast' => $forecast,
            'min_balance' => $forecast->getMinBalance(),
            'max_balance' => $forecast->getMaxBalance(),
            'daily_balances' => $forecast->getDailyBalances(),
        ]);
    }
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples
- Review `IMPLEMENTATION_SUMMARY.md` for architecture details
- Check `REQUIREMENTS.md` for complete feature list

---

## Troubleshooting

### Common Issues

**Issue 1: Interface not bound**
- **Error:** `Target interface [Nexus\CashManagement\Contracts\BankAccountRepositoryInterface] is not instantiable.`
- **Cause:** Service provider not registered or interface not bound
- **Solution:** Ensure `CashManagementServiceProvider` is registered in `config/app.php` and all interfaces are bound in `register()` method

**Issue 2: Duplicate statement detected**
- **Error:** `DuplicateStatementException`
- **Cause:** Statement with same hash already exists
- **Solution:** This is intentional - the system prevents duplicate imports. User should verify if statement was already imported.

**Issue 3: Partial overlap detected**
- **Error:** `PartialOverlapException`
- **Cause:** New statement period overlaps with existing statement
- **Solution:** User must submit corrected file with non-overlapping date range. System rejects partial overlaps to prevent data corruption.

**Issue 4: Tenant context missing**
- **Error:** `Call to a member function getCurrentTenantId() on null`
- **Cause:** Tenant middleware not active
- **Solution:** Ensure `Nexus\Tenant` package is installed and tenant middleware is applied to routes

**Issue 5: GL account validation fails**
- **Error:** GL account does not exist
- **Cause:** Invalid GL account code provided
- **Solution:** Ensure GL account exists in `Nexus\Finance` chart of accounts before posting pending adjustment
