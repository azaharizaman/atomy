# Nexus\HumanResourceOperations

> **⚠️ REFACTORED (Dec 2025):** This orchestrator has been completely refactored to follow the **Advanced Orchestrator Pattern**.
>
> **📖 New Architecture:** See [NEW_ARCHITECTURE.md](./NEW_ARCHITECTURE.md) for complete documentation.
> 
> **📜 Previous Design:** See [README.OLD.md](./README.OLD.md) for historical reference.

---

## Quick Start

The HumanResourceOperations orchestrator coordinates HR workflows across multiple atomic packages using a clean, maintainable architecture.

### Core Components

- **Coordinators** - Traffic cops that orchestrate workflows
- **DataProviders** - Aggregate data from multiple packages
- **Rules** - Composable validation logic
- **Services** - Complex business operations
- **Workflows** - Long-running stateful processes
- **Listeners** - Reactive event handlers
- **DTOs** - Strict type contracts

### Example: Hiring Workflow

```php
use Nexus\HumanResourceOperations\Coordinators\HiringCoordinator;
use Nexus\HumanResourceOperations\DTOs\HiringRequest;

$coordinator = $container->get(HiringCoordinator::class);

$request = new HiringRequest(
    applicationId: 'app-123',
    jobPostingId: 'job-456',
    hired: true,
    decidedBy: 'manager-789',
    startDate: '2025-01-01',
    positionId: 'senior-dev',
    departmentId: 'engineering',
);

$result = $coordinator->processHiringDecision($request);

if ($result->success) {
    echo "Employee hired: {$result->employeeId}";
    echo "User created: {$result->userId}";
} else {
    echo "Hiring failed: {$result->message}";
    print_r($result->issues);
}
```

### Example: Leave Application

```php
use Nexus\HumanResourceOperations\Coordinators\LeaveCoordinator;
use Nexus\HumanResourceOperations\DTOs\LeaveApplicationRequest;

$coordinator = $container->get(LeaveCoordinator::class);

$request = new LeaveApplicationRequest(
    employeeId: 'emp-123',
    leaveTypeId: 'annual-leave',
    startDate: '2025-01-10',
    endDate: '2025-01-15',
    reason: 'Family vacation',
    requestedBy: 'emp-123',
);

$result = $coordinator->applyLeave($request);

if ($result->success) {
    echo "Leave approved: {$result->leaveRequestId}";
    echo "New balance: {$result->newBalance} days";
}
```

---

## Architecture Principles

This orchestrator follows the **Advanced Orchestrator Pattern** with these golden rules:

1. **Coordinators are Traffic Cops, not Workers** - They direct flow, don't do work
2. **Data Fetching is Abstracted** - DataProviders aggregate cross-package data
3. **Validation is Composable** - Rules are individual, testable classes
4. **Strict Contracts** - Always use DTOs, never raw arrays
5. **System First** - Always use Nexus packages (Identity, Notifier, AuditLogger, etc.)

See [NEW_ARCHITECTURE.md](./NEW_ARCHITECTURE.md) for complete details.

---

## Directory Structure

```
src/
├── Coordinators/       # Entry points for operations
├── DataProviders/      # Cross-package data aggregation
├── Rules/              # Validation constraints
├── Services/           # Complex business logic
├── Workflows/          # Stateful processes
├── Listeners/          # Event reactors
├── DTOs/               # Request/Response objects
├── Contracts/          # Interfaces
└── Exceptions/         # Domain errors
```

---

## Available Coordinators

| Coordinator | Purpose | Key Operations |
|-------------|---------|----------------|
| `HiringCoordinator` | Process hiring decisions | `processHiringDecision()` |
| `LeaveCoordinator` | Manage leave applications | `applyLeave()`, `approveLeave()`, `cancelLeave()` |
| `AttendanceCoordinator` | (Planned) Attendance tracking | `recordCheckIn()`, `detectAnomalies()` |
| `PayrollCoordinator` | (Planned) Payroll processing | `calculatePayroll()`, `generatePayslip()` |

---

## Authorization Policies

This orchestrator uses **`Nexus\Identity`** for context-aware authorization via `PolicyEvaluatorInterface`.

### Policy-Based Authorization (ABAC)

Complex authorization scenarios (e.g., "Can user apply leave on behalf of employee?") are handled by policies that check relationships and context:

```php
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;

// In ProxyApplicationAuthorizedRule
$canApply = $this->policyEvaluator->evaluate(
    user: $applicant,
    action: 'hrm.leave.apply_on_behalf',
    resource: null,
    context: [
        'target_employee_id' => $context->employeeId,
    ]
);
```

### Registering Policies

Policies must be registered in your application's service provider:

```php
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\Identity\ValueObjects\Policy;
use Nexus\Hrm\Contracts\EmployeeQueryInterface;

public function boot(): void
{
    $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
    $employeeQuery = $this->app->make(EmployeeQueryInterface::class);
    
    // Register leave proxy policy
    $policy = Policy::define('hrm.leave.apply_on_behalf')
        ->description('User can apply leave on behalf of employees in same department or as manager')
        ->check(function($user, $action, $resource, $context) use ($employeeQuery) {
            $targetEmployeeId = $context['target_employee_id'] ?? null;
            if (!$targetEmployeeId) {
                return false;
            }
            
            $userEmployee = $employeeQuery->findByUserId($user->getId());
            $targetEmployee = $employeeQuery->findById($targetEmployeeId);
            
            if (!$userEmployee || !$targetEmployee) {
                return false;
            }
            
            // Same department OR user is manager
            return $userEmployee->getDepartmentId() === $targetEmployee->getDepartmentId()
                || $userEmployee->getId() === $targetEmployee->getManagerId();
        });
    
    $policyEvaluator->registerPolicy($policy->getName(), $policy->getEvaluator());
}
```

**📖 See:** [adapters/Laravel/HRM/docs/POLICY_REGISTRATION_EXAMPLE.md](../../../adapters/Laravel/HRM/docs/POLICY_REGISTRATION_EXAMPLE.md) for complete examples.

**📖 See:** [CODING_GUIDELINES.md - Section 5.1](/CODING_GUIDELINES.md#51-authorization--policy-based-access-control) for authorization patterns.

---

## Installation

```bash
composer require azaharizaman/nexus-human-resource-operations
```

Dependencies:
- `azaharizaman/nexus-hrm` - Employee management
- `azaharizaman/nexus-identity` - User accounts and authorization
- `azaharizaman/nexus-party` - Party records
- `azaharizaman/nexus-org-structure` - Organizational hierarchy
- `azaharizaman/nexus-leave` - Leave management
- `azaharizaman/nexus-notifier` - Notifications
- `azaharizaman/nexus-audit-logger` - Audit trails

---

## Testing

```bash
# Unit tests (Rules, Services)
vendor/bin/phpunit tests/Unit

# Integration tests (Coordinators)
vendor/bin/phpunit tests/Integration
```

---

## Migration Guide

If migrating from the old architecture:

1. **UseCases → Coordinators** - Entry points
2. **Pipelines → Workflows** - Long-running processes
3. **Inline validation → Rules** - Composable validation
4. **Array params → DTOs** - Typed contracts
5. **Direct repo calls → DataProviders** - Data aggregation

See [NEW_ARCHITECTURE.md](./NEW_ARCHITECTURE.md) for complete migration guide.

---

## License

MIT License

---

**Documentation:**
- [New Architecture](./NEW_ARCHITECTURE.md) - Complete refactored design
- [Old Architecture](./README.OLD.md) - Historical reference
- [System Design Philosophy](/SYSTEM_DESIGN_AND_PHILOSOPHY.md) - Pattern rationale
