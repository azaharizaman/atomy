# Nexus\HumanResourceOperations - Advanced Orchestrator Pattern

**Version:** 2.0 (Refactored)  
**Architecture:** Advanced Orchestrator Pattern  
**Based On:** `SYSTEM_DESIGN_AND_PHILOSOPHY.md`

---

## 🏗️ Architecture Overview

This orchestrator follows the **Advanced Orchestrator Pattern** which strictly separates:

1. **Flow Control** (Coordinators)
2. **Data Aggregation** (DataProviders)
3. **Validation** (Rules)
4. **Business Logic** (Services)
5. **Stateful Processes** (Workflows)
6. **Reactive Logic** (Listeners)
7. **Error Handling** (Exceptions)

### The Golden Rules

1. **Coordinators are Traffic Cops, not Workers** - They direct traffic but do not do the work
2. **Data Fetching is Abstracted** - DataProviders aggregate cross-package data
3. **Validation is Composable** - Business rules are individual classes, not inline if statements
4. **Strict Contracts** - Input/Output always use DTOs, never arrays
5. **System First** - Always leverage existing Nexus packages before building custom solutions

---

## 📁 Directory Structure

```
src/
├── Coordinators/       # 🚦 Entry Point - Stateless Flow Control
│   ├── HiringCoordinator.php
│   ├── LeaveCoordinator.php
│   ├── AttendanceCoordinator.php
│   └── PayrollCoordinator.php
│
├── DataProviders/      # 📦 Cross-Package Data Aggregation
│   ├── RecruitmentDataProvider.php
│   ├── LeaveDataProvider.php
│   └── EmployeeDataProvider.php
│
├── Rules/              # 🛡️ Business Constraints (Isolated Validation)
│   ├── AllInterviewsCompletedRule.php
│   ├── MeetsMinimumQualificationsRule.php
│   ├── SufficientLeaveBalanceRule.php
│   └── NoOverlappingLeavesRule.php
│
├── Services/           # ⚙️ Pure Logic (Calculation, Complex Operations)
│   ├── EmployeeRegistrationService.php
│   ├── HiringRuleRegistry.php
│   └── LeaveRuleRegistry.php
│
├── Workflows/          # 🔄 Stateful Processes (Sagas/Long-running)
│   └── Onboarding/
│       └── OnboardingWorkflow.php
│
├── Listeners/          # 👂 Event Reactors (Async/Side-effects)
│   ├── TriggerOnboardingOnHired.php
│   └── SendWelcomeEmailOnHired.php
│
├── DTOs/               # 📨 Data Transfer Objects (Strict Contracts)
│   ├── HiringRequest.php
│   ├── HiringResult.php
│   ├── ApplicationContext.php
│   ├── LeaveApplicationRequest.php
│   ├── LeaveApplicationResult.php
│   ├── LeaveContext.php
│   └── RuleCheckResult.php
│
├── Contracts/          # 📝 Interfaces
│   ├── HiringRuleInterface.php
│   └── LeaveRuleInterface.php
│
└── Exceptions/         # ⚠️ Domain-Specific Errors
    ├── OperationException.php
    └── WorkflowException.php
```

---

## 🎯 Component Responsibilities

### 🚦 Coordinators

**Purpose:** Orchestrate synchronous flow of operations.

**DO:**
- Accept Request DTOs
- Call DataProvider to get context
- Call RuleRegistry to validate
- Call Service to execute
- Return Result DTOs

**DON'T:**
- Write complex if/else logic
- Calculate values
- Directly query multiple package repositories

**Example:**
```php
public function processHiringDecision(HiringRequest $request): HiringResult
{
    // 1. Get context
    $context = $this->dataProvider->getApplicationContext($request->applicationId);
    
    // 2. Validate
    $issues = $this->validateWithRules($context);
    
    // 3. Execute
    if (empty($issues)) {
        $employee = $this->registrationService->registerNewEmployee(...);
        return new HiringResult(success: true, employeeId: $employee['employeeId']);
    }
    
    return new HiringResult(success: false, issues: $issues);
}
```

### 📦 DataProviders

**Purpose:** Aggregate data from multiple atomic packages into single usable objects.

**Why:** Prevents Coordinators from knowing the intricacies of multiple packages.

**Example:**
```php
public function getApplicationContext(string $applicationId): ApplicationContext
{
    // Fetch from Nexus\Recruitment
    $application = $this->applicationRepo->findById($applicationId);
    
    // Fetch from Nexus\Hrm
    $jobPosting = $this->jobRepo->findById($application->jobPostingId);
    
    // Fetch from Nexus\OrgStructure
    $department = $this->deptRepo->findById($jobPosting->departmentId);
    
    return new ApplicationContext(
        applicationId: $applicationId,
        candidateName: $application->candidateName,
        // ... aggregated data
    );
}
```

### 🛡️ Rules

**Purpose:** Enforce single business constraint.

**Pattern:** Strategy/Specification Pattern

**Benefits:**
- Unit testable in isolation
- Reusable across coordinators
- Clear separation of concerns

**Example:**
```php
final readonly class SufficientLeaveBalanceRule implements LeaveRuleInterface
{
    public function check(LeaveContext $context): RuleCheckResult
    {
        $passed = $context->currentBalance >= $context->daysRequested;
        
        return new RuleCheckResult(
            ruleName: 'sufficient_leave_balance',
            passed: $passed,
            severity: $passed ? 'INFO' : 'ERROR',
            message: $passed ? 'Sufficient balance' : 'Insufficient balance',
        );
    }
}
```

### ⚙️ Services

**Purpose:** Complex calculations, building objects, cross-boundary operations.

**Example:**
```php
final readonly class EmployeeRegistrationService
{
    public function registerNewEmployee(...): array
    {
        // Orchestrates multiple packages:
        $partyId = $this->createPartyRecord(...);        // Nexus\Party
        $userId = $this->createUserAccount(...);          // Nexus\Identity
        $employeeId = $this->createEmployeeRecord(...);   // Nexus\Hrm
        $this->assignToOrganization(...);                 // Nexus\OrgStructure
        
        return ['employeeId' => $employeeId, 'userId' => $userId];
    }
}
```

### 🔄 Workflows

**Purpose:** Long-running, stateful processes (Sagas).

**Use When:**
- Process spans multiple requests
- Requires state persistence
- Needs compensation logic (rollback)

**Example:**
```php
final readonly class OnboardingWorkflow
{
    public function start(string $employeeId): string
    {
        $workflowId = $this->createWorkflowInstance();
        $this->executeStep1_CreateUserAccount($workflowId);
        $this->persistState($workflowId, 'step_1_complete');
        return $workflowId;
    }
}
```

### 👂 Listeners

**Purpose:** React to events, async side-effects.

**Example:**
```php
final readonly class TriggerOnboardingOnHired
{
    public function handle(array $event): void
    {
        $employeeId = $event['employeeId'];
        $this->onboardingWorkflow->start($employeeId);
    }
}
```

---

## 🔀 Decision Matrix: "Where does this code go?"

| Scenario | Component | Why? |
|----------|-----------|------|
| Check if user is active AND period is open | **Rules** | Validation logic → Create `UserActiveRule`, `PeriodOpenRule` |
| Fetch User, Profile, and Department | **DataProviders** | Data aggregation → Create `EmployeeProfileProvider` |
| Calculate weighted average cost | **Services** | Complex calculation → Create `CostCalculationService` |
| Execute hiring process (Validate → Create → Notify) | **Coordinators** | Flow control → Create `HiringCoordinator` |
| Pass 10 parameters to Coordinator | **DTOs** | Prevent "argument soup" → Create `HiringRequest` DTO |
| 3-day process requiring approval | **Workflows** | Stateful process → Create `EmployeeOnboardingWorkflow` |
| Send email when user created | **Listeners** | Reactive side-effect → Create `SendWelcomeEmailListener` |

---

## 🚩 Refactoring Triggers

### Trigger 1: The "And" Rule
If method description uses "and" more than once, it's doing too much.

**Bad:** "Validates request **and** fetches data **and** calculates **and** saves"

**Refactor:**
1. Extract validation → Rules
2. Extract fetching → DataProviders
3. Extract calculation → Services

### Trigger 2: Constructor Bloat
More than 5 dependencies = likely violating Single Responsibility Principle.

**Refactor:** Group related repos into DataProvider or logic into Service.

### Trigger 3: The "If" Wall
First 20 lines are validation checks = need Rule Engine.

**Refactor:** Move to `Rules/` with RuleRegistry.

### Trigger 4: Data Leakage
Array manipulation like `$data['user']['id']` in Coordinator.

**Refactor:** Create DTO and DataProvider returning typed object.

---

## 📊 Dependency Graph

```
Coordinators
    ↓
DataProviders + RuleRegistry + Services
    ↓
Atomic Packages (Hrm, Identity, Leave, etc.)
```

**Flow:**
1. Controller → Coordinator
2. Coordinator → DataProvider (get context)
3. Coordinator → RuleRegistry (validate)
4. Coordinator → Service (execute)
5. Service → Atomic Packages
6. Coordinator → Return Result DTO

---

## 🎓 Usage Examples

### Hiring Workflow

```php
// Controller
$request = new HiringRequest(
    applicationId: 'app-123',
    jobPostingId: 'job-456',
    hired: true,
    decidedBy: 'manager-789',
    startDate: '2025-01-01',
);

$result = $hiringCoordinator->processHiringDecision($request);

if ($result->success) {
    echo "Employee hired: {$result->employeeId}";
}
```

### Leave Application

```php
$request = new LeaveApplicationRequest(
    employeeId: 'emp-123',
    leaveTypeId: 'annual-leave',
    startDate: '2025-01-10',
    endDate: '2025-01-15',
    reason: 'Family vacation',
    requestedBy: 'emp-123',
);

$result = $leaveCoordinator->applyLeave($request);

if ($result->success) {
    echo "Leave approved. New balance: {$result->newBalance}";
}
```

---

## 🧪 Testing Strategy

### Unit Testing Rules (Isolated)

```php
public function test_sufficient_balance_rule_passes_when_balance_enough(): void
{
    $context = new LeaveContext(
        employeeId: 'emp-1',
        employeeName: 'John',
        departmentId: 'dept-1',
        leaveTypeId: 'annual',
        leaveTypeName: 'Annual Leave',
        currentBalance: 10.0,
        daysRequested: 5.0,
        startDate: '2025-01-10',
        endDate: '2025-01-15',
    );
    
    $rule = new SufficientLeaveBalanceRule();
    $result = $rule->check($context);
    
    $this->assertTrue($result->passed);
}
```

### Integration Testing Coordinators

```php
public function test_hiring_coordinator_creates_employee_when_valid(): void
{
    $coordinator = new HiringCoordinator(
        dataProvider: $this->mockDataProvider(),
        ruleRegistry: $this->mockRuleRegistry(),
        registrationService: $this->mockRegistrationService(),
    );
    
    $result = $coordinator->processHiringDecision($this->validRequest());
    
    $this->assertTrue($result->success);
    $this->assertNotNull($result->employeeId);
}
```

---

## 📚 Key Differences from Old Architecture

| Old Approach | New Approach (Advanced Orchestrator Pattern) |
|--------------|---------------------------------------------|
| UseCases with inline validation | Coordinators + separate Rules |
| Pipelines with mixed concerns | DataProviders + Services + Rules |
| Array parameters everywhere | Strict DTOs for all inputs/outputs |
| Repository calls scattered | DataProviders aggregate data |
| Validation in if/else blocks | Composable Rule classes |
| No clear separation | Clear component boundaries |

---

## 🔗 Related Documentation

- **System Design Philosophy:** `/SYSTEM_DESIGN_AND_PHILOSOPHY.md`
- **Coding Guidelines:** `/CODING_GUIDELINES.md`
- **Architecture Guide:** `/ARCHITECTURE.md`
- **Reference Implementation:** `/orchestrators/AccountingOperations/`

---

**Last Updated:** December 3, 2025  
**Refactored By:** AI Assistant  
**Architecture Pattern:** Advanced Orchestrator Pattern v1.1
