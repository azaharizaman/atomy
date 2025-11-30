# Integration Guide: Budget

This guide shows how to integrate the Budget package into your application.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/budget:"*@dev"
```

### Step 2: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('period_id', 26)->index();
            $table->string('department_id', 26)->index();
            $table->string('account_id', 26)->index();
            $table->string('parent_budget_id', 26)->nullable()->index();
            $table->string('budget_type', 50);
            $table->string('status', 50);
            $table->decimal('allocated_amount', 19, 4);
            $table->decimal('committed_amount', 19, 4)->default(0);
            $table->decimal('actual_amount', 19, 4)->default(0);
            $table->string('base_currency', 3);
            $table->string('presentation_currency', 3);
            $table->decimal('exchange_rate', 19, 8);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
```

### Step 3: Create Eloquent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\BudgetStatus;

class Budget extends Model implements BudgetInterface
{
    protected $fillable = [
        'tenant_id', 'period_id', 'department_id', 'account_id',
        'parent_budget_id', 'budget_type', 'status', 'allocated_amount',
        'committed_amount', 'actual_amount', 'base_currency',
        'presentation_currency', 'exchange_rate'
    ];
    
    protected $casts = [
        'budget_type' => BudgetType::class,
        'status' => BudgetStatus::class,
        'allocated_amount' => 'decimal:4',
        'committed_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:8',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    // Implement other interface methods...
}
```

### Step 4: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetInterface;
use App\Models\Budget;

final readonly class EloquentBudgetRepository implements BudgetRepositoryInterface
{
    public function findById(string $id): BudgetInterface
    {
        return Budget::findOrFail($id);
    }
    
    public function save(BudgetInterface $budget): void
    {
        $budget->save();
    }
    
    public function findByDepartment(string $departmentId, string $periodId): array
    {
        return Budget::where('department_id', $departmentId)
            ->where('period_id', $periodId)
            ->get()
            ->all();
    }
    
    public function findDescendants(string $budgetId): array
    {
        // Recursive CTE implementation
        $results = \DB::select("
            WITH RECURSIVE budget_tree AS (
                SELECT * FROM budgets WHERE id = ?
                UNION ALL
                SELECT b.* FROM budgets b
                INNER JOIN budget_tree bt ON b.parent_budget_id = bt.id
            )
            SELECT * FROM budget_tree WHERE id != ?
        ", [$budgetId, $budgetId]);
        
        return array_map(fn($row) => Budget::hydrate([$row])->first(), $results);
    }
    
    // Implement other methods...
}
```

### Step 5: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetTransactionRepositoryInterface;
use Nexus\Budget\Services\BudgetManager;
use App\Repositories\EloquentBudgetRepository;
use App\Repositories\EloquentBudgetTransactionRepository;

class BudgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            BudgetRepositoryInterface::class,
            EloquentBudgetRepository::class
        );
        
        $this->app->singleton(
            BudgetTransactionRepositoryInterface::class,
            EloquentBudgetTransactionRepository::class
        );
        
        // Bind manager
        $this->app->singleton(
            BudgetManagerInterface::class,
            BudgetManager::class
        );
    }
}
```

### Step 6: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Enums\BudgetType;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetManagerInterface $budgetManager
    ) {}
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_id' => 'required|string',
            'department_id' => 'required|string',
            'account_id' => 'required|string',
            'budget_type' => 'required|string',
            'allocated_amount' => 'required|numeric',
        ]);
        
        $budget = $this->budgetManager->createBudget(
            tenantId: $request->user()->tenant_id,
            periodId: $validated['period_id'],
            departmentId: $validated['department_id'],
            accountId: $validated['account_id'],
            budgetType: BudgetType::from($validated['budget_type']),
            allocatedAmount: $validated['allocated_amount'],
            baseCurrency: 'MYR',
            presentationCurrency: 'USD',
            exchangeRate: 4.50
        );
        
        return response()->json($budget);
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/budget:"*@dev"
```

### Step 2: Create Doctrine Entity

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\BudgetStatus;

#[ORM\Entity]
#[ORM\Table(name: 'budgets')]
class Budget implements BudgetInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;
    
    #[ORM\Column(type: 'string', enumType: BudgetType::class)]
    private BudgetType $budgetType;
    
    #[ORM\Column(type: 'string', enumType: BudgetStatus::class)]
    private BudgetStatus $status;
    
    #[ORM\Column(type: 'decimal', precision: 19, scale: 4)]
    private float $allocatedAmount;
    
    // Implement interface methods...
}
```

### Step 3: Configure Services

`config/services.yaml`:

```yaml
services:
    # Repository binding
    Nexus\Budget\Contracts\BudgetRepositoryInterface:
        class: App\Repository\BudgetRepository
        
    # Manager binding
    Nexus\Budget\Contracts\BudgetManagerInterface:
        class: Nexus\Budget\Services\BudgetManager
        arguments:
            $budgetRepository: '@Nexus\Budget\Contracts\BudgetRepositoryInterface'
            $transactionRepository: '@Nexus\Budget\Contracts\BudgetTransactionRepositoryInterface'
```

---

## Common Patterns

### Pattern 1: Event-Driven Budget Control

Listen to Procurement events to automatically commit budget:

```php
use Nexus\Procurement\Events\PurchaseOrderApprovedEvent;
use Nexus\Budget\Contracts\BudgetManagerInterface;

class BudgetCommitmentListener
{
    public function __construct(
        private readonly BudgetManagerInterface $budgetManager
    ) {}
    
    public function handle(PurchaseOrderApprovedEvent $event): void
    {
        $this->budgetManager->commitAmount(
            budgetId: $event->budgetId,
            amount: $event->totalAmount,
            sourceDocumentId: $event->poId,
            sourceDocumentType: 'purchase_order',
            description: "PO {$event->poNumber} approved"
        );
    }
}
```

### Pattern 2: Multi-Tenancy

All repositories should automatically scope by tenant:

```php
public function findAll(): array
{
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    return Budget::where('tenant_id', $tenantId)->get()->all();
}
```

---

## Testing

### Unit Testing Package Logic

```php
use Nexus\Budget\Services\BudgetManager;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use PHPUnit\Framework\TestCase;

class BudgetManagerTest extends TestCase
{
    public function test_commit_amount_reduces_available(): void
    {
        $repository = $this->createMock(BudgetRepositoryInterface::class);
        $manager = new BudgetManager($repository);
        
        $repository->expects($this->once())
            ->method('save');
        
        $manager->commitAmount('budget-123', 1000.00, 'PO-001', 'purchase_order', 'Test');
        
        $this->assertTrue(true);
    }
}
```
