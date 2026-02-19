# Nexus Agentic Guidelines

These guidelines are designed to maximize the efficiency and accuracy of AI agents (Opus 4, Codex 5.3, etc.) working in the Nexus Monorepo.

## 1. Multi-Agent Coordination

Nexus is a large system. Agents should follow these coordination patterns:

- **Architect Agent**: Responsible for design, interface definitions, and enforcing boundaries. Operates primarily in `ARCHITECTURE.md` and `Contracts/`.
- **Developer Agent**: Responsible for implementation and logic. Follows Architect's contracts.
- **QA Agent**: Responsible for testing, verification, and regression analysis.
- **Maintenance Agent**: Handles dependency updates (Dependabot), documentation syncing, and refactoring.

## 2. Shared Multi-Agent Context

To ensure continuity across sessions and between different agents, we use the following standard artifacts:

- **`.agent/tasks/active_task.md`**: The source of truth for the current in-progress work.
- **`.agent/logs/session_history.txt`**: A brief log of major decisions made in the current session.
- **`PROJECT_STATE.md`**: A root-level file (or in `.agent/`) that tracks the high-level roadmap and completion status of all 51+ packages.

## 3. Tool Engagement Rules

- **Discovery First**: Before making any changes, use `ls -R` or `find` to map the relevant packages. Do not assume a package doesn't exist just because it wasn't in the initial prompt.
- **Interface First**: Never create a service without first defining its contract in `src/Contracts/`.
- **Validation**: Use `composer test` or equivalent within the specific package directory being modified.

## 4. Documentation as Code

Agents must treat documentation as part of the implementation:
1. Update `IMPLEMENTATION_SUMMARY.md` in individual packages after every change.
2. Update `ARCHITECTURE.md` if any new pattern is introduced.
3. Sync `docs/NEXUS_SYSTEM_OVERVIEW.md` if the project scope changes.

## 5. Frontier Model Optimizations (Opus 4 / Codex 5.3)

- **Context Packing**: When passing instructions to a sub-agent, provide the relevant `ARCHITECTURE.md` sections directly to avoid halluncinations about layer boundaries.
- **Atomic Commits**: Agents should commit after completing each atomic requirement to provide clear history for the next agent.
- **Verification Logs**: Always include the output of test runs in the `walkthrough.md` to prove completion to the human USER and future agents.

## 6. Common Code Review Patterns (From CodeRabbit Analysis)

The following patterns have been identified as recurring issues in code reviews. AI agents should AVOID these patterns:

### 6.1 Type Safety Anti-Patterns

**AVOID**: Using generic `object` type for dependencies
```php
// ❌ WRONG
private object $salesService

// ✅ CORRECT - Use specific interface
private ?SalesQuotationServiceInterface $salesService
```

**AVOID**: Using `?object` for optional dependencies
```php
// ❌ WRONG
private ?object $documentService = null

// ✅ CORRECT - Use specific interface
private ?DocumentServiceInterface $documentService = null
```

### 6.2 PHP 8.3 Readonly Anti-Patterns

**AVOID**: Mutable arrays in readonly classes
```php
// ❌ WRONG
final readonly class RoundRobinState {
    private array $indices = [];
}

// ✅ CORRECT - Use ArrayObject for internal mutation
final readonly class RoundRobinState {
    private ArrayObject $indices;
    
    public function __construct() {
        $this->indices = new ArrayObject();
    }
}
```

**AVOID**: Using `private readonly` on promoted properties
```php
// ❌ WRONG - PHP 8.3 doesn't allow this
private readonly array $items;

// ✅ CORRECT - readonly implies private for promoted properties
readonly array $items;
```

### 6.3 Method Implementation Anti-Patterns

**AVOID**: Methods that accept parameters but don't apply them
```php
// ❌ WRONG - $name and $description are ignored
public function update(string $id, ?string $name, ?string $description): Entity {
    $entity = $this->findByIdOrFail($id);
    // Missing: $entity->setName($name) or similar
    return $entity;
}

// ✅ CORRECT - Apply all non-null parameters
public function update(string $id, ?string $name, ?string $description): Entity {
    $entity = $this->findByIdOrFail($id);
    if ($name !== null) {
        $entity->setName($name);
    }
    if ($description !== null) {
        $entity->setDescription($description);
    }
    return $entity;
}
```

**AVOID**: Delete methods that don't remove from storage
```php
// ❌ WRONG - Only logs but doesn't remove
public function delete(string $id): void {
    $this->findByIdOrFail($id);
    $this->logger->info('Deleted', ['id' => $id]);
    // Missing: unset($this->entities[$id])
}

// ✅ CORRECT - Actually remove from storage
public function delete(string $id): void {
    $entity = $this->findByIdOrFail($id);
    unset($this->entities[$id]);
    $this->logger->info('Deleted', ['id' => $id]);
}
```

### 6.4 Algorithm Anti-Patterns

**AVOID**: Division without zero-check
```php
// ❌ WRONG - Division by zero if $steps is empty
$progress = count($completedSteps) / count($steps) * 100;

// ✅ CORRECT - Guard against empty array
$progress = count($steps) > 0 
    ? (count($completedSteps) / count($steps) * 100) 
    : 0.0;
```

**AVOID**: Index access without normalization
```php
// ❌ WRONG - Index can exceed array bounds
$index = $this->state->getIndex($key);
$assignee = $assignees[$index];

// ✅ CORRECT - Normalize with modulo
$index = $this->state->getIndex($key) % count($assignees);
$assignee = $assignees[$index];
```

### 6.5 Multi-Tenancy Anti-Patterns

**AVOID**: Ignoring tenant ID in queries
```php
// ❌ WRONG - Returns data across all tenants
public function getOpenOpportunities(): array {
    return $this->opportunities; // No filtering
}

// ✅ CORRECT - Filter by tenant
public function getOpenOpportunities(string $tenantId): array {
    return array_filter(
        $this->opportunities,
        fn($opp) => $opp->getTenantId() === $tenantId
    );
}
```

### 6.6 API Usage Anti-Patterns

**AVOID**: Using wrong notification method for roles vs users
```php
// ❌ WRONG - notify() expects user ID, not role name
$this->notificationProvider->notify('account_manager', 'Title', 'Message');

// ✅ CORRECT - Use notifyRole() for role-based notifications
$this->notificationProvider->notifyRole('account_manager', 'Title', 'Message');
```

### 6.7 Exception Handling Anti-Patterns

**AVOID**: Returning synthetic IDs instead of throwing exceptions
```php
// ❌ WRONG - Returns fake ID that causes downstream failures
if (!$salesService->isAvailable()) {
    return 'QUO-' . uniqid(); // Creates phantom ID
}

// ✅ CORRECT - Throw domain-specific exception
if (!$salesService->isAvailable()) {
    throw new SalesIntegrationUnavailableException('Sales service not available');
}
```

## 7. Quality Checklist

Before marking any implementation as complete, AI agents should verify:

- [ ] All dependencies use specific interface types (not `object`)
- [ ] All classes use `readonly` when appropriate (PHP 8.3+)
- [ ] All methods actually apply their parameters
- [ ] All delete methods remove from storage
- [ ] Division operations have zero guards
- [ ] Array indices are normalized with modulo
- [ ] Tenant filtering is applied in multi-tenant contexts
- [ ] Role-based notifications use `notifyRole()`
- [ ] Failures throw domain exceptions, not return synthetic values
