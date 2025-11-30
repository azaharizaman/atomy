# Getting Started with Nexus Workflow

## Prerequisites

- **PHP 8.3 or higher** (native enums, readonly properties, constructor property promotion)
- **Composer** for package management
- **Database** (MySQL, PostgreSQL, or SQLite) for workflow persistence
- **Queue system** (optional) for async activities and timer processing

### Optional
- **Redis** (recommended for workflow locking and SLA tracking)
- **Scheduler** (cron or queue worker) for timer and escalation processing

---

## When to Use This Package

This package is designed for:

✅ **Multi-step business processes** requiring state tracking and approvals  
✅ **Approval workflows** with single or multi-approver requirements  
✅ **Document workflows** (review, approve, reject cycles)  
✅ **Order processing** (purchase orders, sales orders, quotes)  
✅ **Leave/expense approvals** with escalation and SLA tracking  
✅ **Any process needing audit trails** of state changes

Do NOT use this package for:

❌ **Simple status tracking** (just use a status column instead)  
❌ **Real-time event processing** (use Nexus\EventStream instead)  
❌ **Background job queues** (use Laravel Queue or Symfony Messenger)  
❌ **BPMN diagram execution** (this is not a full BPMN engine)

---

## Core Concepts

### Concept 1: Framework Agnosticism

**Nexus Workflow** contains ZERO framework dependencies in its core. All business logic is pure PHP 8.3+.

- **The Package** defines WHAT needs to be done (interfaces, services, value objects)
- **The Application** defines HOW it's done (Eloquent models, Doctrine entities, queue workers)

**Example:**
```php
// Package defines the contract
interface WorkflowRepositoryInterface {
    public function findById(string $id): WorkflowInterface;
}

// Laravel app provides the implementation
class EloquentWorkflowRepository implements WorkflowRepositoryInterface {
    public function findById(string $id): WorkflowInterface {
        return Workflow::findOrFail($id); // Eloquent model
    }
}
```

### Concept 2: State Machine Foundation

Every workflow is a **state machine** with:
- **States:** Named positions in the workflow (draft, pending_approval, approved, rejected)
- **Transitions:** Named movements between states (submit, approve, reject, revise)
- **Guards:** Conditions that must be true for a transition (amount < 10000)

```
[Draft] --submit--> [Pending Approval] --approve--> [Approved]
                            |
                            +--reject--> [Rejected]
```

### Concept 3: Workflow Definitions

Workflow definitions are stored separately from workflow instances:
- **Definition:** Template (like a class)
- **Instance:** Running workflow (like an object)

```php
// One definition...
$definition = [
    'name' => 'purchase_order_approval',
    'states' => ['draft', 'pending', 'approved', 'rejected'],
    'transitions' => [
        ['name' => 'submit', 'from' => 'draft', 'to' => 'pending'],
        ['name' => 'approve', 'from' => 'pending', 'to' => 'approved'],
        ['name' => 'reject', 'from' => 'pending', 'to' => 'rejected'],
    ],
];

// ...many instances
$workflow1 = $manager->instantiate($definitionId, 'PurchaseOrder', 'PO-001');
$workflow2 = $manager->instantiate($definitionId, 'PurchaseOrder', 'PO-002');
```

### Concept 4: Tasks for Human Interaction

When a workflow reaches a state requiring human action, create a **Task**:
- Tasks have assignees (users or roles)
- Tasks have actions (approve, reject, request changes)
- Tasks can be delegated to other users

```php
// Create task when workflow enters "pending_approval" state
$task = $taskManager->createTask(
    workflowId: $workflow->getId(),
    stateName: 'pending_approval',
    title: 'Approve Purchase Order #1234',
    assignedUserId: $managerId
);

// User completes the task
$taskManager->completeTask(
    taskId: $task->getId(),
    userId: $managerId,
    action: TaskAction::APPROVE,
    comment: 'Approved - within budget'
);
```

### Concept 5: Multi-Approver Strategies

When multiple approvers are needed, choose a strategy:

| Strategy | Description | Example Use Case |
|----------|-------------|------------------|
| `UNISON` | All must approve | Legal document review |
| `MAJORITY` | >50% must approve | Committee vote |
| `QUORUM` | N of M must approve | 2 of 3 managers |
| `WEIGHTED` | Votes have weights | Senior manager = 2 votes |
| `FIRST` | First approval wins | Any manager can approve |

### Concept 6: SLA and Escalation

Track response times and escalate when deadlines approach:

```php
// Configure SLA
$slaConfig = new SlaConfiguration(
    warningThreshold: 24, // hours
    breachThreshold: 48   // hours
);

// Check status
$status = $slaService->getSlaStatus($workflowId);
// Returns: SlaStatus::ON_TRACK, AT_RISK, or BREACHED

// Process escalations (run from scheduler)
$escalationService->processEscalations();
```

---

## Installation

```bash
composer require nexus/workflow:"*@dev"
```

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The package requires repository interfaces for your persistence layer:

#### 1.1 Workflow Repository

```php
namespace App\Repositories;

use Nexus\Workflow\Contracts\WorkflowRepositoryInterface;
use Nexus\Workflow\Contracts\WorkflowInterface;
use App\Models\Workflow;

final readonly class EloquentWorkflowRepository implements WorkflowRepositoryInterface
{
    public function findById(string $id): WorkflowInterface
    {
        return Workflow::findOrFail($id);
    }

    public function save(WorkflowInterface $workflow): void
    {
        $workflow->save();
    }

    public function lock(string $id): void
    {
        Workflow::where('id', $id)->update(['locked' => true]);
    }

    public function unlock(string $id): void
    {
        Workflow::where('id', $id)->update(['locked' => false]);
    }
}
```

#### 1.2 Definition Repository

```php
namespace App\Repositories;

use Nexus\Workflow\Contracts\DefinitionRepositoryInterface;
use Nexus\Workflow\Contracts\WorkflowDefinitionInterface;
use App\Models\WorkflowDefinition;

final readonly class EloquentDefinitionRepository implements DefinitionRepositoryInterface
{
    public function findById(string $id): WorkflowDefinitionInterface
    {
        return WorkflowDefinition::findOrFail($id);
    }

    public function findByName(string $name): ?WorkflowDefinitionInterface
    {
        return WorkflowDefinition::where('name', $name)->first();
    }
}
```

#### 1.3 Task Repository

```php
namespace App\Repositories;

use Nexus\Workflow\Contracts\TaskRepositoryInterface;
use Nexus\Workflow\Contracts\TaskInterface;
use App\Models\WorkflowTask;

final readonly class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function findById(string $id): TaskInterface
    {
        return WorkflowTask::findOrFail($id);
    }

    public function findByWorkflow(string $workflowId): array
    {
        return WorkflowTask::where('workflow_id', $workflowId)->get()->all();
    }

    public function findByUser(string $userId): array
    {
        return WorkflowTask::where('assigned_user_id', $userId)
            ->where('status', 'pending')
            ->get()
            ->all();
    }

    public function save(TaskInterface $task): void
    {
        $task->save();
    }
}
```

### Step 2: Bind Interfaces in Service Provider

```php
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
    InboxService
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
        // Repositories
        $this->app->singleton(
            WorkflowRepositoryInterface::class,
            EloquentWorkflowRepository::class
        );
        $this->app->singleton(
            DefinitionRepositoryInterface::class,
            EloquentDefinitionRepository::class
        );
        $this->app->singleton(
            TaskRepositoryInterface::class,
            EloquentTaskRepository::class
        );
        $this->app->singleton(
            HistoryRepositoryInterface::class,
            EloquentHistoryRepository::class
        );

        // Condition evaluator
        $this->app->singleton(
            ConditionEvaluatorInterface::class,
            SimpleConditionEvaluator::class
        );

        // Core engine
        $this->app->singleton(StateEngine::class);

        // Services
        $this->app->singleton(WorkflowManager::class);
        $this->app->singleton(TaskManager::class);
        $this->app->singleton(InboxService::class);
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\WorkflowServiceProvider::class,
],
```

### Step 3: Create Eloquent Models

Your Eloquent models must implement package interfaces:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Workflow\Contracts\WorkflowInterface;

class Workflow extends Model implements WorkflowInterface
{
    protected $fillable = [
        'definition_id',
        'subject_type',
        'subject_id',
        'current_state',
        'data',
        'locked',
    ];

    protected $casts = [
        'data' => 'array',
        'locked' => 'boolean',
        'completed_at' => 'datetime',
    ];

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
}
```

### Step 4: Run Migrations

Create migrations for workflow tables:

```php
// database/migrations/create_workflow_tables.php
Schema::create('workflow_definitions', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('name')->unique();
    $table->json('states');
    $table->json('transitions');
    $table->json('config')->nullable();
    $table->timestamps();
});

Schema::create('workflows', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('definition_id')->constrained('workflow_definitions');
    $table->string('subject_type');
    $table->string('subject_id');
    $table->string('current_state');
    $table->json('data')->nullable();
    $table->boolean('locked')->default(false);
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    
    $table->index(['subject_type', 'subject_id']);
});

Schema::create('workflow_tasks', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('workflow_id')->constrained('workflows');
    $table->string('state_name');
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('assigned_user_id')->nullable();
    $table->string('assigned_role')->nullable();
    $table->string('status')->default('pending');
    $table->string('action')->nullable();
    $table->text('comment')->nullable();
    $table->string('priority')->default('medium');
    $table->timestamp('due_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    
    $table->index('assigned_user_id');
    $table->index('status');
});

Schema::create('workflow_history', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('workflow_id')->constrained('workflows');
    $table->string('from_state')->nullable();
    $table->string('to_state');
    $table->string('transition_name')->nullable();
    $table->string('actor_id')->nullable();
    $table->text('comment')->nullable();
    $table->json('data')->nullable();
    $table->timestamps();
    
    $table->index('workflow_id');
});
```

---

## Your First Integration

### Example 1: Create Workflow Definition

```php
// Create a purchase order approval workflow definition
$definition = WorkflowDefinition::create([
    'name' => 'purchase_order_approval',
    'states' => ['draft', 'pending_approval', 'approved', 'rejected'],
    'transitions' => [
        [
            'name' => 'submit',
            'from' => ['draft'],
            'to' => 'pending_approval',
        ],
        [
            'name' => 'approve',
            'from' => ['pending_approval'],
            'to' => 'approved',
            'guard' => 'amount <= 10000', // Optional condition
        ],
        [
            'name' => 'reject',
            'from' => ['pending_approval'],
            'to' => 'rejected',
        ],
        [
            'name' => 'revise',
            'from' => ['rejected'],
            'to' => 'draft',
        ],
    ],
]);
```

### Example 2: Start a Workflow

```php
use Nexus\Workflow\Services\WorkflowManager;

class PurchaseOrderController
{
    public function __construct(
        private readonly WorkflowManager $workflowManager
    ) {}

    public function submit(PurchaseOrder $po)
    {
        // Start workflow for this PO
        $workflow = $this->workflowManager->instantiate(
            definitionId: $po->getWorkflowDefinitionId(),
            subjectType: PurchaseOrder::class,
            subjectId: $po->id,
            data: ['amount' => $po->total_amount]
        );

        // Apply submit transition
        $this->workflowManager->apply(
            workflowId: $workflow->getId(),
            transitionName: 'submit',
            actorId: auth()->id()
        );

        return redirect()->back()->with('success', 'PO submitted for approval');
    }
}
```

### Example 3: Check If Transition Allowed

```php
// In your view or controller
if ($this->workflowManager->can($workflow->getId(), 'approve')) {
    // Show approve button
}

// Or with guard condition
$workflow->setData(['amount' => 15000]);
$canApprove = $this->workflowManager->can($workflow->getId(), 'approve');
// false if guard says "amount <= 10000"
```

### Example 4: Create and Complete Tasks

```php
use Nexus\Workflow\Services\TaskManager;
use Nexus\Workflow\ValueObjects\TaskAction;

class ApprovalController
{
    public function __construct(
        private readonly TaskManager $taskManager,
        private readonly WorkflowManager $workflowManager
    ) {}

    public function showInbox()
    {
        $tasks = $this->taskManager->getTasksForUser(auth()->id());
        return view('inbox', compact('tasks'));
    }

    public function approve(string $taskId)
    {
        $task = $this->taskManager->completeTask(
            taskId: $taskId,
            userId: auth()->id(),
            action: TaskAction::APPROVE,
            comment: request('comment')
        );

        // Apply transition to workflow
        $this->workflowManager->apply(
            workflowId: $task->getWorkflowId(),
            transitionName: 'approve',
            actorId: auth()->id()
        );

        return redirect()->back()->with('success', 'Approved!');
    }

    public function reject(string $taskId)
    {
        $task = $this->taskManager->completeTask(
            taskId: $taskId,
            userId: auth()->id(),
            action: TaskAction::REJECT,
            comment: request('comment')
        );

        $this->workflowManager->apply(
            workflowId: $task->getWorkflowId(),
            transitionName: 'reject',
            actorId: auth()->id()
        );

        return redirect()->back()->with('success', 'Rejected');
    }
}
```

---

## Next Steps

- **Read the [API Reference](api-reference.md)** for detailed interface documentation
- **Check [Integration Guide](integration-guide.md)** for complete Laravel/Symfony examples
- **See [Examples](examples/)** for more code samples
- **Review [REQUIREMENTS.md](../REQUIREMENTS.md)** for all 47 requirements

---

## Troubleshooting

### Common Issues

#### Issue 1: "Interface not bound"

**Error:**
```
Target [Nexus\Workflow\Contracts\WorkflowRepositoryInterface] is not instantiable.
```

**Cause:** Interface not bound in service provider

**Solution:**
```php
$this->app->singleton(
    WorkflowRepositoryInterface::class,
    EloquentWorkflowRepository::class
);
```

#### Issue 2: "Invalid transition"

**Error:**
```
InvalidTransitionException: Transition 'approve' is not valid from state 'draft'
```

**Cause:** Trying to apply a transition that doesn't exist from current state

**Solution:**
- Check workflow definition transitions
- Verify current state with `$workflow->getCurrentState()`
- Use `$manager->can()` before applying

#### Issue 3: "Guard condition failed"

**Error:**
```
GuardConditionFailedException: Guard condition for transition 'approve' failed
```

**Cause:** Guard expression evaluated to false

**Solution:**
- Check workflow data: `$workflow->getData()`
- Verify guard expression in definition
- Update workflow data before transition: `$workflow->setData([...])`

#### Issue 4: "Workflow is locked"

**Error:**
```
WorkflowLockedException: Workflow 'xyz' is locked
```

**Cause:** Workflow was locked to prevent concurrent transitions

**Solution:**
- Wait for current operation to complete
- Use `$manager->unlock()` if lock is stale
- Implement proper locking in high-concurrency scenarios

---

**Last Updated:** 2025-11-26  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Architecture Team
