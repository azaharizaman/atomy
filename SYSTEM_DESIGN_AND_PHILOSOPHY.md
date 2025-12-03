# Nexus System Design & Philosophy: The Advanced Orchestrator Pattern

**Version:** 1.1  
**Based On:** `Nexus\AccountingOperations` Architecture  
**Target Audience:** Senior Developers & Architects

---

## 1. Core Philosophy

In the Nexus Monorepo, **Orchestrators** are the glue that binds atomic packages together. However, as complexity grows, the standard "Coordinator" pattern (where a Coordinator fetches data, validates it, and executes logic) becomes a bottleneck—a "God Class" anti-pattern.

The **Advanced Orchestrator Pattern** (exemplified by `Nexus\AccountingOperations`) solves this by strictly separating **Flow**, **Data Aggregation**, **Validation**, and **Execution**.

### The Golden Rules
1.  **Coordinators are Traffic Cops, not Workers.** They direct traffic but do not pave the road.
2.  **Data Fetching is Abstracted.** Coordinators never manually aggregate data from multiple packages; `DataProviders` do.
3.  **Validation is Composable.** Business rules are individual classes, not inline `if` statements.
4.  **Strict Contracts.** Input and Output are always typed DTOs, never associative arrays.
5.  **System First.** Always leverage existing Nexus system packages (`Identity`, `Audit`, `Tenant`) before building custom solutions.

---

## 2. Architectural Components

### 2.1 The Directory Structure

```text
src/
├── Coordinators/       # 🚦 The Entry Point (Stateless Flow Control)
├── DataProviders/      # 📦 Data Aggregation (Cross-Package Fetching)
├── Rules/              # 🛡️ Business Constraints (Isolated Validation)
├── Services/           # ⚙️ Pure Logic (Calculation, Formatting, Building)
├── Workflows/          # 🔄 Stateful Processes (Sagas/Long-running)
├── Listeners/          # 👂 Event Reactors (Async/Side-effects)
├── Exceptions/         # ⚠️ Domain-Specific Errors
├── DTOs/               # 📨 Data Transfer Objects (Strict Inputs/Outputs)
└── Contracts/          # 📝 Interfaces (Dependency Definitions)
```

---

### 2.2 Component Responsibilities

#### 🚦 Coordinators (`src/Coordinators/`)
**Responsibility:** Orchestrate the synchronous flow of a specific operation.
*   **DO:** Accept a Request DTO.
*   **DO:** Call a DataProvider to get context.
*   **DO:** Call a RuleRegistry/Engine to validate context.
*   **DO:** Call a Service to perform the action.
*   **DON'T:** Write complex `if/else` logic.
*   **DON'T:** Calculate values (e.g., tax, totals).
*   **DON'T:** Directly query repositories from multiple packages.

**Example:** `PeriodCloseCoordinator`

#### 📦 Data Providers (`src/DataProviders/`)
**Responsibility:** Aggregate and normalize data from multiple atomic packages into a single, usable object for the Coordinator.
*   **Why:** Prevents the Coordinator from knowing the intricacies of `Nexus\Finance`, `Nexus\Budget`, and `Nexus\Tenant` simultaneously.
*   **Output:** Returns a "Context" or "Aggregate" DTO.

**Example:** `ConsolidationDataProvider` (Fetches Trial Balance + Exchange Rates + Budget Data).

#### 🛡️ Rules (`src/Rules/`)
**Responsibility:** Enforce a single business constraint.
*   **Pattern:** Strategy Pattern / Specification Pattern.
*   **Interface:** Usually `check(Context $ctx): RuleResult`.
*   **Why:** Rules can be unit tested in isolation and reused across different Coordinators.

**Example:** `AllSubledgersClosedRule`, `NoUnpostedJournalsRule`.

#### ⚙️ Services (`src/Services/`)
**Responsibility:** Perform the "Heavy Lifting" or domain logic that doesn't belong in an atomic package because it crosses boundaries.
*   **Tasks:** Complex calculations, generating reports, building complex objects.

**Example:** `FinancialStatementBuilder`, `CloseEntryGenerator`.

#### 🔄 Workflows (`src/Workflows/`)
**Responsibility:** Manage long-running, stateful processes (Sagas) that may span multiple requests or require compensation logic (rollback).
*   **Use Case:** Multi-step processes where step 2 depends on step 1, and failure in step 3 requires undoing step 1.
*   **State:** Often persists state to a database to resume later.

**Example:** `YearEndCloseWorkflow` (Step 1: Lock Periods, Step 2: Calculate Retained Earnings, Step 3: Generate Opening Balances).

#### 👂 Listeners (`src/Listeners/`)
**Responsibility:** React to events emitted by Atomic Packages or other Orchestrators.
*   **Nature:** Asynchronous side-effects.
*   **Action:** Usually triggers a Coordinator or Workflow.

**Example:** `TriggerConsolidationOnPeriodClose` (Listens for `PeriodClosedEvent`, triggers `ConsolidationCoordinator`).

#### ⚠️ Exceptions (`src/Exceptions/`)
**Responsibility:** Define domain-specific error scenarios.
*   **Rule:** Never throw generic `\Exception`.
*   **Structure:** Base exception for the package, specific exceptions for scenarios.

**Example:** `PeriodNotReadyException`, `ConsolidationFailedException`.

---

## 3. Decision Matrix: "Where does this code go?"

Use this table to decide where to place new logic.

| Scenario | Component | Why? |
| :--- | :--- | :--- |
| "I need to check if the user is active AND the period is open." | **Rules** | This is validation logic. Create `UserActiveRule` and `PeriodOpenRule`. |
| "I need to fetch the User, their Profile, and their Department." | **DataProviders** | This is data aggregation. Create `EmployeeProfileProvider`. |
| "I need to calculate the weighted average cost across warehouses." | **Services** | This is complex calculation logic. Create `CostCalculationService`. |
| "I need to execute the 'Hiring' process (Validate -> Create -> Notify)." | **Coordinators** | This is flow control. Create `HiringCoordinator`. |
| "I need to pass 10 parameters to the Coordinator." | **DTOs** | Prevent "argument soup". Create `HiringRequest` DTO. |
| "I need to handle a process that takes 3 days and requires approval." | **Workflows** | This is a stateful process. Create `EmployeeOnboardingWorkflow`. |
| "I need to send an email when a user is created." | **Listeners** | This is a reactive side-effect. Create `SendWelcomeEmailListener`. |

---

## 4. Refactoring Triggers: "When to Chunk"

Developers often ask, "When should I split my class?" Follow these hard limits based on the `AccountingOperations` philosophy.

### 🚩 Trigger 1: The "And" Rule (Coordinators)
If your Coordinator method description uses "and" more than once, it's doing too much.
*   *Bad:* "Validates the request **and** fetches the user data **and** calculates the tax **and** saves the invoice."
*   *Refactor:*
    1.  Extract validation to `Rules`.
    2.  Extract fetching to `DataProviders`.
    3.  Extract calculation to `Services`.

### 🚩 Trigger 2: The Constructor Bloat
If your `__construct` has more than **5 dependencies**, you are likely violating the Single Responsibility Principle.
*   *Refactor:* Group related repositories into a `DataProvider` or related logic into a `Service`.

### 🚩 Trigger 3: The "If" Wall (Validation)
If the first 20 lines of your method are `if ($this->check...) { throw ... }`, you need a Rule Engine.
*   *Refactor:* Move checks to `src/Rules/` and inject a `RuleRegistry` or `ValidatorInterface`.

### 🚩 Trigger 4: Data Leakage
If you see array manipulation like `$data['user']['id']` inside a Coordinator, you are leaking implementation details.
*   *Refactor:* Create a DTO (`UserContext`) and a `DataProvider` that returns this object.

---

## 5. Atomic Package vs. Orchestrator Boundary

**Question:** "When should I move logic from an Orchestrator into an Atomic Package?"

**Rule of Thumb:**
*   **Atomic Package (`packages/`)**: Contains logic that deals with **ONE** domain concept and its direct dependencies. It is the "Source of Truth".
*   **Orchestrator (`orchestrators/`)**: Contains logic that coordinates **MULTIPLE** domains. It is the "Flow Manager".

**Move to Atomic Package IF:**
1.  The logic only touches entities within a single package (e.g., calculating tax for an invoice -> `Nexus\Tax` or `Nexus\Finance`).
2.  The logic is a fundamental business rule of that domain (e.g., "An invoice cannot be paid twice" -> `Nexus\Receivable`).
3.  The logic is reusable by ANY consumer of that package, not just this specific workflow.

**Keep in Orchestrator IF:**
1.  The logic requires data from Package A to update Package B (e.g., "When Employee is hired, create a User account" -> `Nexus\IdentityOperations`).
2.  The logic is specific to a business process, not a domain entity (e.g., "Month End Close" involves Finance, Budget, and Reporting).

---

## 6. System Architecture Priority

**CRITICAL:** Before writing custom logic, you **MUST** check if a Nexus First-Party Package handles the architectural concern. Re-implementing these is a strict violation.

**Priority List (Check these first):**

| Concern | Package to Use |
| :--- | :--- |
| **Identity & Auth** | `Nexus\Identity`, `Nexus\SSO` |
| **Multi-Tenancy** | `Nexus\Tenant` |
| **Logging & Audit** | `Nexus\AuditLogger`, `Nexus\Audit` |
| **File Storage** | `Nexus\Storage` |
| **Notifications** | `Nexus\Notifier` |
| **Messaging/Queues** | `Nexus\Messaging` |
| **Configuration** | `Nexus\Setting` |
| **Scheduling** | `Nexus\Scheduler` |
| **Auto-Numbering** | `Nexus\Sequencing` |
| **Import/Export** | `Nexus\Import`, `Nexus\Export` |
| **Reporting** | `Nexus\Reporting` |
| **Compliance** | `Nexus\Compliance`, `Nexus\Statutory` |
| **AI/ML** | `Nexus\MachineLearning` |
| **Event Sourcing** | `Nexus\EventStream` |
| **Documents** | `Nexus\Document` |

**Example:**
*   *Don't* write a custom CSV parser. Use `Nexus\Import`.
*   *Don't* write a custom log table. Use `Nexus\AuditLogger`.
*   *Don't* write a custom file uploader. Use `Nexus\Storage`.

---

## 7. Naming Conventions

Consistent naming reduces cognitive load.

| Component | Suffix/Pattern | Example |
| :--- | :--- | :--- |
| **Coordinator** | `*Coordinator` | `PeriodCloseCoordinator` |
| **Data Provider** | `*DataProvider` | `ConsolidationDataProvider` |
| **Rule** | `*Rule` | `AllSubledgersClosedRule` |
| **Service** | `*Service` or `*Builder` | `FinancialStatementBuilder` |
| **Workflow** | `*Workflow` | `YearEndCloseWorkflow` |
| **Listener** | `*Listener` | `TriggerConsolidationListener` |
| **Exception** | `*Exception` | `PeriodNotReadyException` |
| **DTO (Request)** | `*Request` | `PeriodCloseRequest` |
| **DTO (Result)** | `*Result` | `CloseReadinessResult` |
| **Interface** | `*Interface` | `AccountingCoordinatorInterface` |

---

## 8. Summary

The `Nexus\AccountingOperations` package demonstrates that **maintainability is a function of separation**. By treating Data Fetching, Validation, and Execution as distinct architectural citizens, we create systems that are:

1.  **Testable:** You can test a Rule without mocking the entire database.
2.  **Readable:** The Coordinator reads like a table of contents, not a novel.
3.  **Scalable:** Adding a new validation rule doesn't require modifying the Coordinator's core logic.

**Adhere to this philosophy for all complex Orchestrators.**
