# Integration Guide: Workflow

This guide shows how to integrate the Workflow package into your application.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/workflow:"*@dev"
```

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
        // Workflow Definitions
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('name')->index();
            $table->string('description')->nullable();
            $table->json('states');
            $table->json('transitions');
            $table->json('config')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'name']);
        });

        // Workflow Instances
        Schema::create('workflows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->foreignUlid('definition_id')
                ->constrained('workflow_definitions')
                ->cascadeOnDelete();
            $table->string('subject_type');
            $table->string('subject_id');
            $table->string('current_state');
            $table->json('data')->nullable();
            $table->boolean('locked')->default(false);
            $table->string('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['subject_type', 'subject_id']);
            $table->index(['tenant_id', 'current_state']);
        });

        // Workflow Tasks
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->foreignUlid('workflow_id')
                ->constrained('workflows')
                ->cascadeOnDelete();
            $table->string('state_name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('assigned_user_id', 26)->nullable()->index();
            $table->string('assigned_role')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->string('action')->nullable();
            $table->string('completed_by', 26)->nullable();
            $table->text('comment')->nullable();
            $table->string('priority')->default('medium');
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Workflow History
        Schema::create('workflow_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->foreignUlid('workflow_id')
                ->constrained('workflows')
                ->cascadeOnDelete();
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('transition_name')->nullable();
            $table->string('actor_id', 26)->nullable();
            $table->text('comment')->nullable();
            $table->json('data_snapshot')->nullable();
            $table->timestamps();
            
            $table->index(['workflow_id', 'created_at']);
        });

        // Delegations
        Schema::create('workflow_delegations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('delegator_id', 26)->index();
            $table->string('delegatee_id', 26)->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Timers
        Schema::create('workflow_timers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->foreignUlid('workflow_id')
                ->constrained('workflows')
                ->cascadeOnDelete();
            $table->string('timer_type');
            $table->string('transition_name')->nullable();
            $table->timestamp('due_at')->index();
            $table->boolean('fired')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_timers');
        Schema::dropIfExists('workflow_delegations');
        Schema::dropIfExists('workflow_history');
        Schema::dropIfExists('workflow_tasks');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('workflow_definitions');
    }
};
```

### Step 3: Create Eloquent Models

#### Workflow Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Workflow\Contracts\WorkflowInterface;

class Workflow extends Model implements WorkflowInterface
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'definition_id',
        'subject_type',
        'subject_id',
        'current_state',
        'data',
        'locked',
        'locked_by',
        'locked_at',
        'completed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'locked' => 'boolean',
        'locked_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function definition()
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function tasks()
    {
        return $this->hasMany(WorkflowTask::class);
    }

    public function history()
    {
        return $this->hasMany(WorkflowHistory::class)->orderBy('created_at');
    }

    // Interface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getDefinitionId(): string
    {
        return $this->definition_id;
    }

    public function getCurrentState(): string
    {
        return $this->current_state;
    }

    public function getSubjectType(): string
    {
        return $this->subject_type;
    }

    public function getSubjectId(): string
    {
        return $this->subject_id;
    }

    public function getData(): array
    {
        return $this->data ?? [];
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getDataValue(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function isLocked(): bool
    {
        return $this->locked ?? false;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    // Scopes
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeForSubject($query, string $type, string $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }
}
```

#### WorkflowTask Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Workflow\Contracts\TaskInterface;

class WorkflowTask extends Model implements TaskInterface
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'workflow_id',
        'state_name',
        'title',
        'description',
        'assigned_user_id',
        'assigned_role',
        'status',
        'action',
        'completed_by',
        'comment',
        'priority',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    // Interface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkflowId(): string
    {
        return $this->workflow_id;
    }

    public function getStateName(): string
    {
        return $this->state_name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAssignedUserId(): ?string
    {
        return $this->assigned_user_id;
    }

    public function getAssignedRole(): ?string
    {
        return $this->assigned_role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getDueAt(): ?\DateTimeInterface
    {
        return $this->due_at;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_at', '<', now())
            ->where('status', 'pending');
    }
}
```

### Step 4: Create Repository Implementations

```php
<?php

namespace App\Repositories;

use Nexus\Workflow\Contracts\WorkflowRepositoryInterface;
use Nexus\Workflow\Contracts\WorkflowInterface;
use Nexus\Workflow\Exceptions\WorkflowNotFoundException;
use App\Models\Workflow;

final readonly class EloquentWorkflowRepository implements WorkflowRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {}

    public function findById(string $id): WorkflowInterface
    {
        $workflow = Workflow::forTenant($this->tenantId)->find($id);
        
        if (!$workflow) {
            throw WorkflowNotFoundException::forId($id);
        }
        
        return $workflow;
    }

    public function findBySubject(string $subjectType, string $subjectId): array
    {
        return Workflow::forTenant($this->tenantId)
            ->forSubject($subjectType, $subjectId)
            ->get()
            ->all();
    }

    public function save(WorkflowInterface $workflow): void
    {
        $workflow->save();
    }

    public function lock(string $id): void
    {
        Workflow::forTenant($this->tenantId)
            ->where('id', $id)
            ->update([
                'locked' => true,
                'locked_by' => auth()->id(),
                'locked_at' => now(),
            ]);
    }

    public function unlock(string $id): void
    {
        Workflow::forTenant($this->tenantId)
            ->where('id', $id)
            ->update([
                'locked' => false,
                'locked_by' => null,
                'locked_at' => null,
            ]);
    }
}
```

### Step 5: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Workflow\Contracts\{
    WorkflowRepositoryInterface,
    DefinitionRepositoryInterface,
    TaskRepositoryInterface,
    HistoryRepositoryInterface,
    ConditionEvaluatorInterface
};
use Nexus\Workflow\Core\StateEngine;
use Nexus\Workflow\Services\{
    WorkflowManager,
    TaskManager,
    InboxService,
    EscalationService,
    SlaService,
    DelegationService
};
use App\Repositories\{
    EloquentWorkflowRepository,
    EloquentDefinitionRepository,
    EloquentTaskRepository,
    EloquentHistoryRepository
};
use App\Services\SimpleConditionEvaluator;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Get tenant ID from context
        $this->app->bind('current_tenant_id', fn() => 
            request()->header('X-Tenant-ID') ?? config('app.default_tenant')
        );

        // Repositories (tenant-scoped)
        $this->app->singleton(WorkflowRepositoryInterface::class, function ($app) {
            return new EloquentWorkflowRepository($app->make('current_tenant_id'));
        });

        $this->app->singleton(DefinitionRepositoryInterface::class, function ($app) {
            return new EloquentDefinitionRepository($app->make('current_tenant_id'));
        });

        $this->app->singleton(TaskRepositoryInterface::class, function ($app) {
            return new EloquentTaskRepository($app->make('current_tenant_id'));
        });

        $this->app->singleton(HistoryRepositoryInterface::class, function ($app) {
            return new EloquentHistoryRepository($app->make('current_tenant_id'));
        });

        // Condition evaluator
        $this->app->singleton(ConditionEvaluatorInterface::class, SimpleConditionEvaluator::class);

        // Core engine
        $this->app->singleton(StateEngine::class);

        // Services
        $this->app->singleton(WorkflowManager::class);
        $this->app->singleton(TaskManager::class);
        $this->app->singleton(InboxService::class);
        $this->app->singleton(EscalationService::class);
        $this->app->singleton(SlaService::class);
        $this->app->singleton(DelegationService::class);
    }
}
```

### Step 6: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\WorkflowServiceProvider::class,
],
```

### Step 7: Create Condition Evaluator

```php
<?php

namespace App\Services;

use Nexus\Workflow\Contracts\ConditionEvaluatorInterface;
use Nexus\Workflow\Exceptions\InvalidConditionExpressionException;

final readonly class SimpleConditionEvaluator implements ConditionEvaluatorInterface
{
    public function evaluate(string $expression, array $context): bool
    {
        // Simple expression parser
        // Supports: >, <, >=, <=, ==, !=
        // Example: "amount > 10000"
        
        $pattern = '/^(\w+)\s*(>|<|>=|<=|==|!=)\s*(.+)$/';
        
        if (!preg_match($pattern, trim($expression), $matches)) {
            throw InvalidConditionExpressionException::invalidSyntax($expression);
        }
        
        $field = $matches[1];
        $operator = $matches[2];
        $value = trim($matches[3], '"\'');
        
        if (!array_key_exists($field, $context)) {
            return false;
        }
        
        $fieldValue = $context[$field];
        
        // Convert numeric strings
        if (is_numeric($value)) {
            $value = $value + 0;
        }
        
        return match ($operator) {
            '>' => $fieldValue > $value,
            '<' => $fieldValue < $value,
            '>=' => $fieldValue >= $value,
            '<=' => $fieldValue <= $value,
            '==' => $fieldValue == $value,
            '!=' => $fieldValue != $value,
            default => false,
        };
    }
}
```

### Step 8: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Nexus\Workflow\Services\WorkflowManager;
use Nexus\Workflow\Services\TaskManager;
use Nexus\Workflow\ValueObjects\TaskAction;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly WorkflowManager $workflowManager,
        private readonly TaskManager $taskManager
    ) {}

    public function submit(PurchaseOrder $po)
    {
        // Start workflow
        $workflow = $this->workflowManager->instantiate(
            definitionId: config('workflow.purchase_order_definition'),
            subjectType: PurchaseOrder::class,
            subjectId: $po->id,
            data: [
                'amount' => $po->total_amount,
                'department' => $po->department,
                'requester_id' => $po->created_by,
            ]
        );

        // Submit for approval
        $this->workflowManager->apply(
            workflowId: $workflow->getId(),
            transitionName: 'submit',
            actorId: auth()->id()
        );

        // Create approval task
        $this->taskManager->createTask(
            workflowId: $workflow->getId(),
            stateName: 'pending_approval',
            title: "Approve PO #{$po->number}",
            assignedRole: 'approver',
            priority: 'high',
            dueAt: now()->addDays(2)
        );

        return redirect()
            ->route('purchase-orders.show', $po)
            ->with('success', 'PO submitted for approval');
    }

    public function approve(PurchaseOrder $po, Request $request)
    {
        $workflow = $po->activeWorkflow();
        $task = $workflow->tasks()->pending()->first();

        // Complete task
        $this->taskManager->completeTask(
            taskId: $task->getId(),
            userId: auth()->id(),
            action: TaskAction::APPROVE,
            comment: $request->input('comment')
        );

        // Apply transition
        $this->workflowManager->apply(
            workflowId: $workflow->getId(),
            transitionName: 'approve',
            actorId: auth()->id(),
            comment: $request->input('comment')
        );

        return redirect()
            ->route('purchase-orders.show', $po)
            ->with('success', 'PO approved');
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/workflow:"*@dev"
```

### Step 2: Create Doctrine Entities

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Workflow\Contracts\WorkflowInterface;

#[ORM\Entity(repositoryClass: WorkflowRepository::class)]
#[ORM\Table(name: 'workflows')]
class Workflow implements WorkflowInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 26)]
    private string $definitionId;

    #[ORM\Column(type: 'string')]
    private string $subjectType;

    #[ORM\Column(type: 'string')]
    private string $subjectId;

    #[ORM\Column(type: 'string')]
    private string $currentState;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'boolean')]
    private bool $locked = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    // Interface implementation
    public function getId(): string { return $this->id; }
    public function getDefinitionId(): string { return $this->definitionId; }
    public function getCurrentState(): string { return $this->currentState; }
    public function getSubjectType(): string { return $this->subjectType; }
    public function getSubjectId(): string { return $this->subjectId; }
    public function getData(): array { return $this->data ?? []; }
    public function setData(array $data): void { $this->data = $data; }
    public function getDataValue(string $key, mixed $default = null): mixed 
    { 
        return $this->data[$key] ?? $default; 
    }
    public function isLocked(): bool { return $this->locked; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
}
```

### Step 3: Create Repository

```php
<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Workflow\Contracts\WorkflowRepositoryInterface;
use Nexus\Workflow\Contracts\WorkflowInterface;
use Nexus\Workflow\Exceptions\WorkflowNotFoundException;
use App\Entity\Workflow;
use Symfony\Component\Security\Core\Security;

class WorkflowRepository extends ServiceEntityRepository implements WorkflowRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Security $security
    ) {
        parent::__construct($registry, Workflow::class);
    }

    public function findById(string $id): WorkflowInterface
    {
        $workflow = $this->find($id);
        
        if (!$workflow) {
            throw WorkflowNotFoundException::forId($id);
        }
        
        return $workflow;
    }

    public function findBySubject(string $subjectType, string $subjectId): array
    {
        return $this->findBy([
            'subjectType' => $subjectType,
            'subjectId' => $subjectId,
        ]);
    }

    public function save(WorkflowInterface $workflow): void
    {
        $this->getEntityManager()->persist($workflow);
        $this->getEntityManager()->flush();
    }

    public function lock(string $id): void
    {
        $this->createQueryBuilder('w')
            ->update()
            ->set('w.locked', ':locked')
            ->where('w.id = :id')
            ->setParameter('locked', true)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function unlock(string $id): void
    {
        $this->createQueryBuilder('w')
            ->update()
            ->set('w.locked', ':locked')
            ->where('w.id = :id')
            ->setParameter('locked', false)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
```

### Step 4: Configure Services

`config/services.yaml`:

```yaml
services:
    # Repositories
    Nexus\Workflow\Contracts\WorkflowRepositoryInterface:
        class: App\Repository\WorkflowRepository

    Nexus\Workflow\Contracts\DefinitionRepositoryInterface:
        class: App\Repository\DefinitionRepository

    Nexus\Workflow\Contracts\TaskRepositoryInterface:
        class: App\Repository\TaskRepository

    Nexus\Workflow\Contracts\HistoryRepositoryInterface:
        class: App\Repository\HistoryRepository

    Nexus\Workflow\Contracts\ConditionEvaluatorInterface:
        class: App\Service\ConditionEvaluator

    # Core engine
    Nexus\Workflow\Core\StateEngine:
        arguments:
            $conditionEvaluator: '@Nexus\Workflow\Contracts\ConditionEvaluatorInterface'

    # Services
    Nexus\Workflow\Services\WorkflowManager:
        arguments:
            $workflowRepository: '@Nexus\Workflow\Contracts\WorkflowRepositoryInterface'
            $definitionRepository: '@Nexus\Workflow\Contracts\DefinitionRepositoryInterface'
            $historyRepository: '@Nexus\Workflow\Contracts\HistoryRepositoryInterface'
            $stateEngine: '@Nexus\Workflow\Core\StateEngine'

    Nexus\Workflow\Services\TaskManager:
        arguments:
            $taskRepository: '@Nexus\Workflow\Contracts\TaskRepositoryInterface'
            $workflowRepository: '@Nexus\Workflow\Contracts\WorkflowRepositoryInterface'

    Nexus\Workflow\Services\InboxService:
        arguments:
            $taskRepository: '@Nexus\Workflow\Contracts\TaskRepositoryInterface'
```

### Step 5: Use in Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\Workflow\Services\WorkflowManager;
use Nexus\Workflow\Services\TaskManager;
use Nexus\Workflow\ValueObjects\TaskAction;
use App\Entity\PurchaseOrder;

class PurchaseOrderController extends AbstractController
{
    public function __construct(
        private readonly WorkflowManager $workflowManager,
        private readonly TaskManager $taskManager
    ) {}

    #[Route('/purchase-orders/{id}/submit', methods: ['POST'])]
    public function submit(PurchaseOrder $po): Response
    {
        $workflow = $this->workflowManager->instantiate(
            definitionId: $this->getParameter('workflow.po_definition'),
            subjectType: PurchaseOrder::class,
            subjectId: $po->getId(),
            data: ['amount' => $po->getTotalAmount()]
        );

        $this->workflowManager->apply(
            workflowId: $workflow->getId(),
            transitionName: 'submit',
            actorId: $this->getUser()->getId()
        );

        return $this->redirectToRoute('purchase_order_show', ['id' => $po->getId()]);
    }

    #[Route('/purchase-orders/{id}/approve', methods: ['POST'])]
    public function approve(PurchaseOrder $po, Request $request): Response
    {
        $workflow = $po->getActiveWorkflow();
        $task = $workflow->getPendingTask();

        $this->taskManager->completeTask(
            taskId: $task->getId(),
            userId: $this->getUser()->getId(),
            action: TaskAction::APPROVE,
            comment: $request->request->get('comment')
        );

        $this->workflowManager->apply(
            workflowId: $workflow->getId(),
            transitionName: 'approve',
            actorId: $this->getUser()->getId()
        );

        return $this->redirectToRoute('purchase_order_show', ['id' => $po->getId()]);
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
    private readonly WorkflowManager $manager
) {}

// ❌ WRONG - Don't inject repositories directly
public function __construct(
    private readonly WorkflowRepositoryInterface $repo // Let services use this
) {}
```

### Pattern 2: Multi-Tenancy

All repositories should automatically scope by tenant:

```php
public function findById(string $id): WorkflowInterface
{
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    return $this->model
        ->where('tenant_id', $tenantId)
        ->findOrFail($id);
}
```

### Pattern 3: Workflow Trait for Models

```php
trait HasWorkflow
{
    public function activeWorkflow(): ?Workflow
    {
        return Workflow::forSubject(static::class, $this->id)
            ->active()
            ->first();
    }

    public function workflows()
    {
        return $this->morphMany(Workflow::class, 'subject');
    }

    public function getCurrentState(): ?string
    {
        return $this->activeWorkflow()?->getCurrentState();
    }
}
```

---

## Performance Optimization

### Database Indexes

```php
// Essential indexes
$table->index(['tenant_id', 'current_state']); // State filtering
$table->index(['subject_type', 'subject_id']); // Subject lookup
$table->index('assigned_user_id');              // User inbox
$table->index('due_at');                        // Overdue queries
```

### Query Optimization

```php
// Eager load relationships
$workflows = Workflow::with(['definition', 'tasks', 'history'])
    ->forTenant($tenantId)
    ->get();

// Use select for large datasets
$tasks = WorkflowTask::select(['id', 'title', 'status', 'due_at'])
    ->pending()
    ->forUser($userId)
    ->get();
```

---

## Testing

### Unit Testing

```php
use Nexus\Workflow\Services\WorkflowManager;
use Nexus\Workflow\Contracts\WorkflowRepositoryInterface;
use PHPUnit\Framework\TestCase;

class WorkflowManagerTest extends TestCase
{
    public function test_can_check_transition(): void
    {
        $repository = $this->createMock(WorkflowRepositoryInterface::class);
        $manager = new WorkflowManager(/* dependencies */);
        
        $workflow = $this->createWorkflowInState('draft');
        $repository->method('findById')->willReturn($workflow);
        
        $this->assertTrue($manager->can($workflow->getId(), 'submit'));
        $this->assertFalse($manager->can($workflow->getId(), 'approve'));
    }
}
```

### Integration Testing (Laravel)

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_lifecycle(): void
    {
        $po = PurchaseOrder::factory()->create();
        
        // Submit
        $response = $this->postJson("/api/purchase-orders/{$po->id}/submit");
        $response->assertStatus(200);
        
        // Check state
        $po->refresh();
        $this->assertEquals('pending_approval', $po->getCurrentState());
        
        // Approve
        $response = $this->postJson("/api/purchase-orders/{$po->id}/approve");
        $response->assertStatus(200);
        
        $po->refresh();
        $this->assertEquals('approved', $po->getCurrentState());
    }
}
```

---

**Last Updated:** 2025-11-26  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Architecture Team
