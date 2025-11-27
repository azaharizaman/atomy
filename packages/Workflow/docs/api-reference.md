# API Reference: Workflow

**Package:** `Nexus\Workflow`  
**Version:** 1.0.0

---

## Interfaces

### WorkflowInterface

**Location:** `src/Contracts/WorkflowInterface.php`

**Purpose:** Represents a workflow instance tied to a subject entity.

**Methods:**

#### getId()

```php
public function getId(): string;
```

**Description:** Get the workflow instance unique identifier.

**Returns:** `string` - ULID of the workflow

---

#### getDefinitionId()

```php
public function getDefinitionId(): string;
```

**Description:** Get the workflow definition identifier.

**Returns:** `string` - ULID of the definition

---

#### getCurrentState()

```php
public function getCurrentState(): string;
```

**Description:** Get the current state name of the workflow.

**Returns:** `string` - State name (e.g., "draft", "pending_approval")

---

#### getSubjectType()

```php
public function getSubjectType(): string;
```

**Description:** Get the subject model type (e.g., `App\Models\PurchaseOrder`).

**Returns:** `string` - Fully qualified class name

---

#### getSubjectId()

```php
public function getSubjectId(): string;
```

**Description:** Get the subject model identifier.

**Returns:** `string` - Subject entity ID

---

#### getData()

```php
public function getData(): array;
```

**Description:** Get workflow data as associative array.

**Returns:** `array<string, mixed>` - Workflow context data

---

#### setData()

```php
public function setData(array $data): void;
```

**Description:** Set workflow data.

**Parameters:**
- `$data` (array<string, mixed>) - Workflow context data

---

#### getDataValue()

```php
public function getDataValue(string $key, mixed $default = null): mixed;
```

**Description:** Get specific data value by key.

**Parameters:**
- `$key` (string) - Data key
- `$default` (mixed) - Default value if key not found

**Returns:** `mixed` - Data value

---

#### isLocked()

```php
public function isLocked(): bool;
```

**Description:** Check if workflow is locked (prevents transitions).

**Returns:** `bool` - True if locked

---

### WorkflowDefinitionInterface

**Location:** `src/Contracts/WorkflowDefinitionInterface.php`

**Purpose:** Represents a workflow definition template.

**Methods:**

#### getId()

```php
public function getId(): string;
```

**Returns:** `string` - Definition ULID

---

#### getName()

```php
public function getName(): string;
```

**Returns:** `string` - Definition name

---

#### getStates()

```php
public function getStates(): array;
```

**Returns:** `array<StateInterface>` - All defined states

---

#### getTransitions()

```php
public function getTransitions(): array;
```

**Returns:** `array<TransitionInterface>` - All defined transitions

---

#### getAvailableTransitions()

```php
public function getAvailableTransitions(string $fromState): array;
```

**Description:** Get transitions available from a specific state.

**Parameters:**
- `$fromState` (string) - Current state name

**Returns:** `array<TransitionInterface>` - Available transitions

---

### TransitionInterface

**Location:** `src/Contracts/TransitionInterface.php`

**Purpose:** Represents a state transition definition.

**Methods:**

#### getName()

```php
public function getName(): string;
```

**Returns:** `string` - Transition name (e.g., "approve")

---

#### getFromStates()

```php
public function getFromStates(): array;
```

**Returns:** `array<string>` - States this transition can be applied from

---

#### getToState()

```php
public function getToState(): string;
```

**Returns:** `string` - Target state name

---

#### getGuard()

```php
public function getGuard(): ?string;
```

**Returns:** `?string` - Guard expression (e.g., "amount > 1000")

---

### TaskInterface

**Location:** `src/Contracts/TaskInterface.php`

**Purpose:** Represents a user task in a workflow.

**Methods:**

#### getId()

```php
public function getId(): string;
```

**Returns:** `string` - Task ULID

---

#### getWorkflowId()

```php
public function getWorkflowId(): string;
```

**Returns:** `string` - Parent workflow ULID

---

#### getStateName()

```php
public function getStateName(): string;
```

**Returns:** `string` - Workflow state this task was created for

---

#### getTitle()

```php
public function getTitle(): string;
```

**Returns:** `string` - Task title

---

#### getDescription()

```php
public function getDescription(): ?string;
```

**Returns:** `?string` - Task description

---

#### getAssignedUserId()

```php
public function getAssignedUserId(): ?string;
```

**Returns:** `?string` - Assigned user ID

---

#### getAssignedRole()

```php
public function getAssignedRole(): ?string;
```

**Returns:** `?string` - Assigned role name

---

#### getStatus()

```php
public function getStatus(): string;
```

**Returns:** `string` - Task status (pending, completed, cancelled)

---

#### getPriority()

```php
public function getPriority(): string;
```

**Returns:** `string` - Priority (low, medium, high, urgent)

---

#### getDueAt()

```php
public function getDueAt(): ?\DateTimeInterface;
```

**Returns:** `?\DateTimeInterface` - Due date/time

---

### WorkflowRepositoryInterface

**Location:** `src/Contracts/WorkflowRepositoryInterface.php`

**Purpose:** Persistence contract for workflow instances.

**Methods:**

#### findById()

```php
public function findById(string $id): WorkflowInterface;
```

**Throws:** `WorkflowNotFoundException` - When workflow not found

---

#### findBySubject()

```php
public function findBySubject(string $subjectType, string $subjectId): array;
```

**Returns:** `array<WorkflowInterface>` - Workflows for subject

---

#### save()

```php
public function save(WorkflowInterface $workflow): void;
```

---

#### lock()

```php
public function lock(string $id): void;
```

---

#### unlock()

```php
public function unlock(string $id): void;
```

---

### ConditionEvaluatorInterface

**Location:** `src/Contracts/ConditionEvaluatorInterface.php`

**Purpose:** Evaluates guard condition expressions.

**Methods:**

#### evaluate()

```php
public function evaluate(string $expression, array $context): bool;
```

**Description:** Evaluate a condition expression against context data.

**Parameters:**
- `$expression` (string) - Condition expression (e.g., "amount > 1000")
- `$context` (array<string, mixed>) - Context data

**Returns:** `bool` - True if condition passes

**Throws:**
- `InvalidConditionExpressionException` - When expression is malformed

**Example:**
```php
$result = $evaluator->evaluate('amount > 1000', ['amount' => 5000]);
// true
```

---

## Services

### WorkflowManager

**Location:** `src/Services/WorkflowManager.php`

**Purpose:** Primary workflow operations service.

**Constructor Dependencies:**
- `WorkflowRepositoryInterface` - Workflow persistence
- `DefinitionRepositoryInterface` - Definition persistence
- `HistoryRepositoryInterface` - History persistence
- `StateEngine` - State transition engine

**Public Methods:**

#### instantiate()

```php
public function instantiate(
    string $definitionId,
    string $subjectType,
    string $subjectId,
    array $data = []
): WorkflowInterface;
```

**Description:** Create a new workflow instance.

**Parameters:**
- `$definitionId` (string) - Workflow definition ID
- `$subjectType` (string) - Subject entity class name
- `$subjectId` (string) - Subject entity ID
- `$data` (array) - Initial workflow data

**Returns:** `WorkflowInterface` - New workflow instance

**Example:**
```php
$workflow = $manager->instantiate(
    definitionId: $definition->getId(),
    subjectType: PurchaseOrder::class,
    subjectId: $po->id,
    data: ['amount' => $po->total]
);
```

---

#### apply()

```php
public function apply(
    string $workflowId,
    string $transitionName,
    ?string $actorId = null,
    ?string $comment = null
): WorkflowInterface;
```

**Description:** Apply a transition to a workflow.

**Parameters:**
- `$workflowId` (string) - Workflow ID
- `$transitionName` (string) - Transition name to apply
- `$actorId` (string|null) - User performing the action
- `$comment` (string|null) - Optional comment

**Returns:** `WorkflowInterface` - Updated workflow

**Throws:**
- `WorkflowNotFoundException` - Workflow not found
- `WorkflowLockedException` - Workflow is locked
- `InvalidTransitionException` - Transition not valid from current state
- `GuardConditionFailedException` - Guard condition failed

**Example:**
```php
$workflow = $manager->apply(
    workflowId: $workflow->getId(),
    transitionName: 'approve',
    actorId: auth()->id(),
    comment: 'Approved per policy'
);
```

---

#### can()

```php
public function can(string $workflowId, string $transitionName): bool;
```

**Description:** Check if a transition is allowed.

**Parameters:**
- `$workflowId` (string) - Workflow ID
- `$transitionName` (string) - Transition name to check

**Returns:** `bool` - True if transition is allowed

**Example:**
```php
if ($manager->can($workflow->getId(), 'approve')) {
    // Show approve button
}
```

---

#### history()

```php
public function history(string $workflowId): array;
```

**Description:** Get workflow history.

**Returns:** `array<HistoryInterface>` - Workflow history entries

---

#### lock()

```php
public function lock(string $workflowId): void;
```

**Description:** Lock workflow to prevent transitions.

---

#### unlock()

```php
public function unlock(string $workflowId): void;
```

**Description:** Unlock workflow.

---

### TaskManager

**Location:** `src/Services/TaskManager.php`

**Purpose:** Task creation and completion service.

**Public Methods:**

#### createTask()

```php
public function createTask(
    string $workflowId,
    string $stateName,
    string $title,
    ?string $assignedUserId = null,
    ?string $assignedRole = null,
    ?string $description = null,
    string $priority = 'medium',
    ?\DateTimeInterface $dueAt = null
): TaskInterface;
```

**Description:** Create a new user task.

---

#### completeTask()

```php
public function completeTask(
    string $taskId,
    string $userId,
    TaskAction $action,
    ?string $comment = null
): TaskInterface;
```

**Description:** Complete a task with an action.

**Throws:**
- `TaskNotFoundException` - Task not found
- `UnauthorizedTaskActionException` - User cannot act on task

---

#### delegateTask()

```php
public function delegateTask(
    string $taskId,
    string $fromUserId,
    string $toUserId
): TaskInterface;
```

**Description:** Delegate task to another user.

---

### InboxService

**Location:** `src/Services/InboxService.php`

**Purpose:** Task inbox queries.

**Public Methods:**

#### forUser()

```php
public function forUser(string $userId): array;
```

**Returns:** `array<TaskInterface>` - Tasks assigned to user

---

#### pending()

```php
public function pending(): array;
```

**Returns:** `array<TaskInterface>` - All pending tasks

---

#### filter()

```php
public function filter(array $criteria): array;
```

**Parameters:**
- `$criteria` (array) - Filter criteria (workflow_type, priority, due_before, etc.)

**Returns:** `array<TaskInterface>` - Filtered tasks

---

### SlaService

**Location:** `src/Services/SlaService.php`

**Purpose:** SLA monitoring service.

**Public Methods:**

#### trackSla()

```php
public function trackSla(string $workflowId): void;
```

**Description:** Start or update SLA tracking for a workflow.

---

#### getSlaStatus()

```php
public function getSlaStatus(string $workflowId): SlaStatus;
```

**Returns:** `SlaStatus` - ON_TRACK, AT_RISK, or BREACHED

---

#### getBreaches()

```php
public function getBreaches(): array;
```

**Returns:** `array<WorkflowInterface>` - Workflows with breached SLAs

---

### EscalationService

**Location:** `src/Services/EscalationService.php`

**Purpose:** Escalation rule processing.

**Public Methods:**

#### processEscalations()

```php
public function processEscalations(): void;
```

**Description:** Process all overdue tasks and apply escalation rules.

---

#### defineRule()

```php
public function defineRule(
    string $stateName,
    int $thresholdHours,
    string $action
): void;
```

**Description:** Define an escalation rule.

---

### DelegationService

**Location:** `src/Services/DelegationService.php`

**Purpose:** User delegation management.

**Public Methods:**

#### createDelegation()

```php
public function createDelegation(
    string $delegatorId,
    string $delegateeId,
    \DateTimeInterface $startDate,
    \DateTimeInterface $endDate
): DelegationInterface;
```

---

#### revokeDelegation()

```php
public function revokeDelegation(string $delegationId): void;
```

---

#### getDelegationChain()

```php
public function getDelegationChain(string $userId): array;
```

**Returns:** `array<DelegationInterface>` - Delegation chain for user

---

## Value Objects

### ApprovalStrategy (Enum)

**Location:** `src/ValueObjects/ApprovalStrategy.php`

**Cases:**
- `UNISON` - All assignees must approve
- `MAJORITY` - More than 50% must approve
- `QUORUM` - Configurable threshold (e.g., 3 of 5)
- `WEIGHTED` - Votes have different weights
- `FIRST` - First approval wins

**Example:**
```php
$strategy = ApprovalStrategy::MAJORITY;
```

---

### SlaStatus (Enum)

**Location:** `src/ValueObjects/SlaStatus.php`

**Cases:**
- `ON_TRACK` - Within SLA
- `AT_RISK` - Approaching SLA breach
- `BREACHED` - SLA breached

---

### TaskAction (Enum)

**Location:** `src/ValueObjects/TaskAction.php`

**Cases:**
- `APPROVE` - Approve the task
- `REJECT` - Reject the task
- `REQUEST_CHANGES` - Request changes
- `DELEGATE` - Delegate to another user
- `CANCEL` - Cancel the task

---

### TaskPriority (Enum)

**Location:** `src/ValueObjects/TaskPriority.php`

**Cases:**
- `LOW`
- `MEDIUM`
- `HIGH`
- `URGENT`

---

### TaskStatus (Enum)

**Location:** `src/ValueObjects/TaskStatus.php`

**Cases:**
- `PENDING`
- `COMPLETED`
- `CANCELLED`

---

### WorkflowStatus (Enum)

**Location:** `src/ValueObjects/WorkflowStatus.php`

**Cases:**
- `ACTIVE`
- `COMPLETED`
- `CANCELLED`

---

## Exceptions

### WorkflowNotFoundException

**Location:** `src/Exceptions/WorkflowNotFoundException.php`

**Purpose:** Thrown when workflow not found.

**Factory Methods:**

```php
public static function forId(string $id): self
```

---

### InvalidTransitionException

**Location:** `src/Exceptions/InvalidTransitionException.php`

**Purpose:** Thrown when transition is not valid.

**Factory Methods:**

```php
public static function notDefined(string $transitionName): self
public static function fromState(string $transitionName, string $currentState): self
```

---

### GuardConditionFailedException

**Location:** `src/Exceptions/GuardConditionFailedException.php`

**Purpose:** Thrown when guard condition fails.

**Factory Methods:**

```php
public static function forTransition(string $transitionName): self
```

---

### WorkflowLockedException

**Location:** `src/Exceptions/WorkflowLockedException.php`

**Purpose:** Thrown when workflow is locked.

**Factory Methods:**

```php
public static function forWorkflow(string $workflowId): self
```

---

### TaskNotFoundException

**Location:** `src/Exceptions/TaskNotFoundException.php`

**Purpose:** Thrown when task not found.

---

### UnauthorizedTaskActionException

**Location:** `src/Exceptions/UnauthorizedTaskActionException.php`

**Purpose:** Thrown when user is not authorized to act on task.

---

### DelegationChainExceededException

**Location:** `src/Exceptions/DelegationChainExceededException.php`

**Purpose:** Thrown when delegation chain is too deep.

---

### SlaBreachException

**Location:** `src/Exceptions/SlaBreachException.php`

**Purpose:** Thrown when SLA is breached.

---

## Usage Patterns

### Pattern 1: Basic Workflow Lifecycle

```php
// 1. Instantiate workflow
$workflow = $manager->instantiate($definitionId, Subject::class, $subjectId);

// 2. Check if can transition
if ($manager->can($workflow->getId(), 'submit')) {
    // 3. Apply transition
    $manager->apply($workflow->getId(), 'submit', $userId);
}

// 4. Get history
$history = $manager->history($workflow->getId());
```

### Pattern 2: Task-Based Approval

```php
// 1. Create task when entering approval state
$task = $taskManager->createTask(
    workflowId: $workflow->getId(),
    stateName: 'pending_approval',
    title: 'Approve Document',
    assignedUserId: $managerId
);

// 2. User completes task
$task = $taskManager->completeTask(
    taskId: $task->getId(),
    userId: $managerId,
    action: TaskAction::APPROVE
);

// 3. Apply corresponding transition
$manager->apply($workflow->getId(), 'approve', $managerId);
```

### Pattern 3: Conditional Guards

```php
// Define workflow with guard
$definition = [
    'transitions' => [
        [
            'name' => 'approve',
            'from' => ['pending'],
            'to' => 'approved',
            'guard' => 'amount <= 10000',
        ],
        [
            'name' => 'escalate',
            'from' => ['pending'],
            'to' => 'pending_senior',
            'guard' => 'amount > 10000',
        ],
    ],
];

// Set workflow data
$workflow->setData(['amount' => 15000]);

// Only escalate is available
$manager->can($workflow->getId(), 'approve');   // false
$manager->can($workflow->getId(), 'escalate');  // true
```

---

**Last Updated:** 2025-11-26  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Architecture Team
