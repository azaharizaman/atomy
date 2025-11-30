# Integration Guide: CashManagement

This guide shows how to integrate the CashManagement package into your application with Laravel and Symfony examples.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/cash-management:"*@dev"
```

### Step 2: Create Database Migrations

Create all required tables for cash management:

```php
<?php
// database/migrations/2024_11_24_000001_create_bank_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('account_code', 50)->unique();
            $table->string('gl_account_id', 26');
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
            $table->foreign('gl_account_id')->references('id')->on('gl_accounts');
        });

        Schema::create('bank_statements', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('bank_account_id', 26);
            $table->string('statement_number', 100)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('statement_hash', 64)->unique();
            $table->decimal('opening_balance', 19, 4);
            $table->decimal('closing_balance', 19, 4);
            $table->decimal('total_debit', 19, 4)->default(0);
            $table->decimal('total_credit', 19, 4)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->timestamp('imported_at');
            $table->string('imported_by', 26);
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
            $table->index(['tenant_id']);
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('bank_statement_id', 26);
            $table->date('transaction_date');
            $table->text('description');
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer', 'fee', 'interest', 'check', 'atm', 'direct_debit', 'direct_credit', 'reversal', 'other']);
            $table->decimal('amount', 19, 4);
            $table->decimal('balance', 19, 4)->nullable();
            $table->string('reference', 255)->nullable();
            // V2 Multi-Currency (nullable)
            $table->string('transaction_currency', 3)->nullable();
            $table->decimal('exchange_rate', 19, 6)->nullable();
            $table->decimal('functional_amount', 19, 4)->nullable();
            $table->string('reconciliation_id', 26)->nullable();
            $table->timestamps();

            $table->foreign('bank_statement_id')->references('id')->on('bank_statements');
            $table->index(['transaction_date']);
        });

        Schema::create('reconciliations', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('bank_transaction_id', 26);
            $table->string('matched_entity_type', 50);
            $table->string('matched_entity_id', 26);
            $table->enum('status', ['pending', 'matched', 'variance_review', 'reconciled', 'unmatched', 'rejected']);
            $table->enum('matching_confidence', ['high', 'medium', 'low', 'manual']);
            $table->string('ai_model_version', 20)->nullable();
            $table->decimal('amount_variance', 19, 4)->default(0);
            $table->timestamp('reconciled_at')->nullable();
            $table->string('reconciled_by', 26)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('bank_transaction_id')->references('id')->on('bank_transactions');
            $table->index(['matched_entity_type', 'matched_entity_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('pending_adjustments', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('bank_transaction_id', 26);
            $table->string('suggested_gl_account', 50)->nullable();
            $table->string('gl_account', 50)->nullable();
            $table->decimal('amount', 19, 4);
            $table->text('description');
            $table->string('ai_model_version', 20)->nullable();
            $table->timestamp('correction_recorded_at')->nullable();
            $table->string('workflow_instance_id', 26)->nullable();
            $table->string('journal_entry_id', 26)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('posted_by', 26)->nullable();
            $table->timestamps();

            $table->foreign('bank_transaction_id')->references('id')->on('bank_transactions');
            $table->index(['posted_at']);
        });

        Schema::create('cash_forecast_scenarios', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('bank_account_id', 26)->nullable();
            $table->enum('scenario_type', ['optimistic', 'baseline', 'pessimistic', 'custom']);
            $table->json('parameters');
            $table->json('forecast_data');
            $table->decimal('min_balance', 19, 4)->nullable();
            $table->decimal('max_balance', 19, 4)->nullable();
            $table->boolean('has_negative')->default(false);
            $table->string('generated_by', 26);
            $table->timestamp('generated_at');

            $table->index(['generated_at']);
        });

        Schema::create('reconciliation_reversals', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('original_reconciliation_id', 26);
            $table->string('payment_application_id', 26)->nullable();
            $table->text('reversal_reason');
            $table->string('finance_workflow_id', 26)->nullable();
            $table->string('reversed_by', 26);
            $table->timestamp('reversed_at');

            $table->foreign('original_reconciliation_id')->references('id')->on('reconciliations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_reversals');
        Schema::dropIfExists('cash_forecast_scenarios');
        Schema::dropIfExists('pending_adjustments');
        Schema::dropIfExists('reconciliations');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_statements');
        Schema::dropIfExists('bank_accounts');
    }
};
```

### Step 3: Create Eloquent Models

```php
<?php
// app/Models/CashManagement/BankAccount.php

namespace App\Models\CashManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\CashManagement\Contracts\BankAccountInterface;
use Nexus\CashManagement\Enums\BankAccountType;
use Nexus\CashManagement\Enums\BankAccountStatus;

class BankAccount extends Model implements BankAccountInterface
{
    use HasUlids;

    protected $fillable = [
        'tenant_id', 'account_code', 'gl_account_id', 'account_number',
        'bank_name', 'bank_code', 'branch_code', 'swift_code', 'iban',
        'account_type', 'status', 'currency', 'current_balance',
        'last_reconciled_at', 'csv_import_config', 'notes'
    ];

    protected $casts = [
        'account_type' => BankAccountType::class,
        'status' => BankAccountStatus::class,
        'current_balance' => 'decimal:4',
        'last_reconciled_at' => 'datetime',
        'csv_import_config' => 'array',
    ];

    // Implement interface methods
    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenant_id; }
    public function getAccountCode(): string { return $this->account_code; }
    public function getGlAccountId(): string { return $this->gl_account_id; }
    public function getAccountNumber(): string { return $this->account_number; }
    public function getBankName(): string { return $this->bank_name; }
    public function getBankCode(): string { return $this->bank_code; }
    public function getAccountType(): BankAccountType { return $this->account_type; }
    public function getStatus(): BankAccountStatus { return $this->status; }
    public function getCurrency(): string { return $this->currency; }
    public function getCurrentBalance(): string { return $this->current_balance; }
    public function getCSVImportConfig(): ?array { return $this->csv_import_config; }

    // Relationships
    public function statements()
    {
        return $this->hasMany(BankStatement::class);
    }

    public function glAccount()
    {
        return $this->belongsTo(\App\Models\Finance\GlAccount::class, 'gl_account_id');
    }
}
```

Similarly, create models for:
- `BankStatement`
- `BankTransaction`
- `Reconciliation`
- `PendingAdjustment`
- `CashForecastScenario`
- `ReconciliationReversal`

### Step 4: Create Repository Implementations

```php
<?php
// app/Repositories/CashManagement/EloquentBankAccountRepository.php

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

    public function findByAccountCode(string $accountCode): ?BankAccountInterface
    {
        return $this->model->where('account_code', $accountCode)->first();
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

Create similar repositories for all interfaces.

### Step 5: Implement Core Services

```php
<?php
// app/Services/CashManagement/CashManagementManager.php

namespace App\Services\CashManagement;

use Nexus\CashManagement\Contracts\CashManagementManagerInterface;
use Nexus\CashManagement\Contracts\BankAccountInterface;
use Nexus\CashManagement\Contracts\BankAccountRepositoryInterface;
use Nexus\CashManagement\Contracts\BankStatementInterface;
use Nexus\CashManagement\Contracts\ReconciliationEngineInterface;
use Nexus\CashManagement\Enums\BankAccountType;
use Nexus\CashManagement\Enums\BankAccountStatus;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use App\Models\CashManagement\BankAccount;
use App\Models\CashManagement\BankStatement;

final readonly class CashManagementManager implements CashManagementManagerInterface
{
    public function __construct(
        private BankAccountRepositoryInterface $bankAccountRepository,
        private ReconciliationEngineInterface $reconciliationEngine,
        private SequencingManagerInterface $sequencing
    ) {}

    public function createBankAccount(
        string $tenantId,
        string $accountCode,
        string $glAccountId,
        string $accountNumber,
        string $bankName,
        string $bankCode,
        BankAccountType $accountType,
        string $currency,
        ?array $csvImportConfig = null
    ): BankAccountInterface {
        $bankAccount = new BankAccount([
            'tenant_id' => $tenantId,
            'account_code' => $accountCode,
            'gl_account_id' => $glAccountId,
            'account_number' => $accountNumber,
            'bank_name' => $bankName,
            'bank_code' => $bankCode,
            'account_type' => $accountType,
            'currency' => $currency,
            'csv_import_config' => $csvImportConfig,
            'status' => BankAccountStatus::ACTIVE,
        ]);

        $this->bankAccountRepository->save($bankAccount);

        return $bankAccount;
    }

    public function importBankStatement(
        string $bankAccountId,
        string $startDate,
        string $endDate,
        array $transactions,
        string $importedBy
    ): BankStatementInterface {
        // Validate no duplicates/overlaps
        // Create statement
        // Create transactions
        // Return statement
    }

    // ... implement other methods
}
```

### Step 6: Create Service Provider

```php
<?php
// app/Providers/CashManagementServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\CashManagement\Contracts\BankAccountRepositoryInterface;
use Nexus\CashManagement\Contracts\BankStatementRepositoryInterface;
use Nexus\CashManagement\Contracts\BankTransactionRepositoryInterface;
use Nexus\CashManagement\Contracts\ReconciliationRepositoryInterface;
use Nexus\CashManagement\Contracts\PendingAdjustmentRepositoryInterface;
use Nexus\CashManagement\Contracts\CashManagementManagerInterface;
use Nexus\CashManagement\Contracts\ReconciliationEngineInterface;
use Nexus\CashManagement\Contracts\DuplicationDetectorInterface;
use Nexus\CashManagement\Contracts\ReversalHandlerInterface;
use Nexus\CashManagement\Contracts\CashFlowForecastInterface;
use App\Repositories\CashManagement\EloquentBankAccountRepository;
use App\Services\CashManagement\CashManagementManager;
use App\Services\CashManagement\ReconciliationEngine;
use App\Services\CashManagement\DuplicationDetector;
use App\Services\CashManagement\ReversalHandler;
use App\Services\CashManagement\CashFlowForecast;

class CashManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            BankAccountRepositoryInterface::class,
            EloquentBankAccountRepository::class
        );

        $this->app->singleton(
            BankStatementRepositoryInterface::class,
            EloquentBankStatementRepository::class
        );

        $this->app->singleton(
            BankTransactionRepositoryInterface::class,
            EloquentBankTransactionRepository::class
        );

        $this->app->singleton(
            ReconciliationRepositoryInterface::class,
            EloquentReconciliationRepository::class
        );

        $this->app->singleton(
            PendingAdjustmentRepositoryInterface::class,
            EloquentPendingAdjustmentRepository::class
        );

        // Bind services
        $this->app->singleton(
            CashManagementManagerInterface::class,
            CashManagementManager::class
        );

        $this->app->singleton(
            ReconciliationEngineInterface::class,
            ReconciliationEngine::class
        );

        $this->app->singleton(
            DuplicationDetectorInterface::class,
            DuplicationDetector::class
        );

        $this->app->singleton(
            ReversalHandlerInterface::class,
            ReversalHandler::class
        );

        $this->app->singleton(
            CashFlowForecastInterface::class,
            CashFlowForecast::class
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

### Step 7: Create Controllers

```php
<?php
// app/Http/Controllers/CashManagement/BankStatementController.php

namespace App\Http\Controllers\CashManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nexus\CashManagement\Contracts\CashManagementManagerInterface;
use Nexus\Import\Contracts\ImportManagerInterface;

class BankStatementController extends Controller
{
    public function __construct(
        private readonly CashManagementManagerInterface $cashManager,
        private readonly ImportManagerInterface $importManager
    ) {}

    public function import(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'csv_file' => 'required|file|mimes:csv,txt',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            // Parse CSV
            $result = $this->importManager->importFile(
                filePath: $validated['csv_file']->path(),
                importType: 'bank_statement',
                config: $this->getBankAccountConfig($validated['bank_account_id'])
            );

            // Create statement
            $statement = $this->cashManager->importBankStatement(
                bankAccountId: $validated['bank_account_id'],
                startDate: $validated['start_date'],
                endDate: $validated['end_date'],
                transactions: $result->getData(),
                importedBy: auth()->id()
            );

            // Auto-reconcile
            $reconciliationResult = $this->cashManager->reconcileStatement($statement->getId());

            return response()->json([
                'success' => true,
                'statement_id' => $statement->getId(),
                'matched' => $reconciliationResult->getMatchedCount(),
                'unmatched' => $reconciliationResult->getUnmatchedCount(),
            ]);

        } catch (\Nexus\CashManagement\Exceptions\DuplicateStatementException $e) {
            return response()->json([
                'error' => 'Duplicate statement',
                'existing_id' => $e->getExistingStatementId(),
            ], 422);
        }
    }
}
```

### Step 8: Create API Routes

```php
<?php
// routes/api.php

use App\Http\Controllers\CashManagement\BankAccountController;
use App\Http\Controllers\CashManagement\BankStatementController;
use App\Http\Controllers\CashManagement\PendingAdjustmentController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Bank Accounts
    Route::get('/bank-accounts', [BankAccountController::class, 'index']);
    Route::post('/bank-accounts', [BankAccountController::class, 'store']);
    Route::get('/bank-accounts/{id}', [BankAccountController::class, 'show']);
    Route::patch('/bank-accounts/{id}', [BankAccountController::class, 'update']);

    // Bank Statements
    Route::post('/bank-statements/import', [BankStatementController::class, 'import']);
    Route::get('/bank-statements', [BankStatementController::class, 'index']);
    Route::get('/bank-statements/{id}', [BankStatementController::class, 'show']);

    // Pending Adjustments
    Route::get('/pending-adjustments', [PendingAdjustmentController::class, 'index']);
    Route::post('/pending-adjustments/{id}/approve', [PendingAdjustmentController::class, 'approve']);
    Route::post('/pending-adjustments/{id}/reject', [PendingAdjustmentController::class, 'reject']);
});
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/cash-management:"*@dev"
```

### Step 2: Create Doctrine Entities

```php
<?php
// src/Entity/CashManagement/BankAccount.php

namespace App\Entity\CashManagement;

use Doctrine\ORM\Mapping as ORM;
use Nexus\CashManagement\Contracts\BankAccountInterface;
use Nexus\CashManagement\Enums\BankAccountType;
use Nexus\CashManagement\Enums\BankAccountStatus;

#[ORM\Entity]
#[ORM\Table(name: 'bank_accounts')]
class BankAccount implements BankAccountInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $accountCode;

    #[ORM\Column(type: 'string', length: 26)]
    private string $glAccountId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $accountNumber;

    #[ORM\Column(type: 'string', length: 255)]
    private string $bankName;

    #[ORM\Column(type: 'string', length: 50)]
    private string $bankCode;

    #[ORM\Column(type: 'string', enumType: BankAccountType::class)]
    private BankAccountType $accountType;

    #[ORM\Column(type: 'string', enumType: BankAccountStatus::class)]
    private BankAccountStatus $status;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'decimal', precision: 19, scale: 4)]
    private string $currentBalance;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $csvImportConfig = null;

    // Implement interface methods
    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    // ... other getters
}
```

### Step 3: Create Repositories

```php
<?php
// src/Repository/CashManagement/BankAccountRepository.php

namespace App\Repository\CashManagement;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\CashManagement\Contracts\BankAccountRepositoryInterface;
use Nexus\CashManagement\Contracts\BankAccountInterface;
use App\Entity\CashManagement\BankAccount;

class BankAccountRepository extends ServiceEntityRepository implements BankAccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    public function findById(string $id): BankAccountInterface
    {
        return $this->find($id) ?? throw new \RuntimeException("Bank account not found: {$id}");
    }

    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId, 'status' => 'active']);
    }

    public function save(BankAccountInterface $bankAccount): void
    {
        $this->getEntityManager()->persist($bankAccount);
        $this->getEntityManager()->flush();
    }

    public function delete(string $id): void
    {
        $bankAccount = $this->find($id);
        if ($bankAccount) {
            $this->getEntityManager()->remove($bankAccount);
            $this->getEntityManager()->flush();
        }
    }
}
```

### Step 4: Configure Services

```yaml
# config/services.yaml

services:
    # Repositories
    Nexus\CashManagement\Contracts\BankAccountRepositoryInterface:
        class: App\Repository\CashManagement\BankAccountRepository
        arguments:
            $registry: '@doctrine'

    # Services
    Nexus\CashManagement\Contracts\CashManagementManagerInterface:
        class: App\Service\CashManagement\CashManagementManager
        arguments:
            $bankAccountRepository: '@Nexus\CashManagement\Contracts\BankAccountRepositoryInterface'
            $reconciliationEngine: '@Nexus\CashManagement\Contracts\ReconciliationEngineInterface'
            $sequencing: '@Nexus\Sequencing\Contracts\SequencingManagerInterface'
```

### Step 5: Create Controllers

```php
<?php
// src/Controller/CashManagement/BankStatementController.php

namespace App\Controller\CashManagement;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\CashManagement\Contracts\CashManagementManagerInterface;

#[Route('/api/cash-management')]
class BankStatementController extends AbstractController
{
    public function __construct(
        private readonly CashManagementManagerInterface $cashManager
    ) {}

    #[Route('/statements/import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $statement = $this->cashManager->importBankStatement(
                bankAccountId: $data['bank_account_id'],
                startDate: $data['start_date'],
                endDate: $data['end_date'],
                transactions: $data['transactions'],
                importedBy: $this->getUser()->getId()
            );

            $result = $this->cashManager->reconcileStatement($statement->getId());

            return $this->json([
                'success' => true,
                'statement_id' => $statement->getId(),
                'matched' => $result->getMatchedCount(),
                'unmatched' => $result->getUnmatchedCount(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

---

## Common Patterns

### Pattern 1: Dependency Injection

Always inject interfaces, never concrete classes:

```php
// ✅ CORRECT
public function __construct(
    private readonly CashManagementManagerInterface $cashManager
) {}

// ❌ WRONG
public function __construct(
    private readonly CashManagementManager $cashManager  // Concrete class!
) {}
```

### Pattern 2: Multi-Tenancy

All operations are automatically scoped by tenant:

```php
// Repository scopes by tenant
public function findAll(): array
{
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    return $this->model
        ->where('tenant_id', $tenantId)
        ->get();
}
```

### Pattern 3: Exception Handling

```php
use Nexus\CashManagement\Exceptions\DuplicateStatementException;
use Nexus\CashManagement\Exceptions\PartialOverlapException;

try {
    $statement = $this->cashManager->importBankStatement(...);
} catch (DuplicateStatementException $e) {
    return response()->json([
        'error' => 'Duplicate statement',
        'existing_id' => $e->getExistingStatementId(),
    ], 422);
} catch (PartialOverlapException $e) {
    return response()->json([
        'error' => 'Statement period overlaps existing statement',
        'overlapping_statements' => $e->getOverlappingStatements(),
    ], 422);
}
```

---

## Troubleshooting

### Issue: Interface not bound

**Laravel Error:**
```
Target interface [Nexus\CashManagement\Contracts\BankAccountRepositoryInterface] is not instantiable.
```

**Solution:**
```php
$this->app->singleton(
    BankAccountRepositoryInterface::class,
    EloquentBankAccountRepository::class
);
```

**Symfony Error:**
```
Cannot autowire service "App\Controller\BankStatementController": argument "$cashManager" references interface "Nexus\CashManagement\Contracts\CashManagementManagerInterface" but no such service exists.
```

**Solution:** Add to `services.yaml`:
```yaml
Nexus\CashManagement\Contracts\CashManagementManagerInterface:
    class: App\Service\CashManagement\CashManagementManager
```

---

## Performance Optimization

### Database Indexes

```php
$table->index(['tenant_id', 'status']);
$table->index(['bank_account_id']);
$table->index(['transaction_date']);
$table->index(['statement_hash']);
```

### Caching

```php
public function findById(string $id): BankAccountInterface
{
    return Cache::remember(
        "bank_account.{$id}",
        3600,
        fn() => $this->model->findOrFail($id)
    );
}
```

---

## Testing

### Unit Testing (Laravel)

```php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashManagementManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_bank_account(): void
    {
        $manager = app(CashManagementManagerInterface::class);

        $account = $manager->createBankAccount(
            tenantId: 'tenant_123',
            accountCode: '1000-01',
            glAccountId: 'gl_123',
            accountNumber: '1234567890',
            bankName: 'Maybank',
            bankCode: 'MBB',
            accountType: BankAccountType::CHECKING,
            currency: 'MYR'
        );

        $this->assertDatabaseHas('bank_accounts', [
            'account_code' => '1000-01',
            'bank_name' => 'Maybank',
        ]);
    }
}
```
