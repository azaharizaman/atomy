# Implementation Plan: ProjectManagement Orchestrator

## Phase 1: Infrastructure & Core Contracts [checkpoint: 37bd717]
This phase focuses on establishing the orchestrator's foundation, including the `Contracts/` and initial domain services.

- [x] **Task: Setup Orchestrator Structure** c697702
    - [ ] Create `orchestrators/ProjectManagementOperations/` directory structure.
    - [ ] Initialize `composer.json` with PSR-4 autoloading for `Nexus\ProjectManagementOperations`.
- [x] **Task: Define Package Contracts (Incremental)** a2033a0
    - [ ] **Write Failing Tests:** Create a test to ensure `ProjectHealthManager` requires specific contracts.
    - [ ] **Implement:** Define `ProjectQueryInterface`, `BudgetQueryInterface`, `AttendanceQueryInterface`, and `SchedulerQueryInterface` in `src/Contracts/`.
- [x] **Task: Conductor - User Manual Verification 'Phase 1: Infrastructure & Core Contracts' (Protocol in workflow.md)**

## Phase 2: Project Health Monitoring [checkpoint: f39090e]
Implement the core logic to reconcile project progress with financial and time-based constraints.

- [x] **Task: Implement Labor Health Logic** 679bd83
    - [ ] **Write Failing Tests:** Write a test that provides `Attendance` data and expects a labor-to-budget percentage calculation.
    - [ ] **Implement:** Create `LaborHealthService` to compare hours recorded in `Attendance` against `Budget` allocations.
- [x] **Task: Implement Expense Health Logic** 36794c5
    - [ ] **Write Failing Tests:** Write a test that reconciles material expenses from `Inventory` (mocked via contract) with `Budget` limits.
    - [ ] **Implement:** Create `ExpenseHealthService` to aggregate costs and check against budget thresholds.
- [x] **Task: Implement Timeline Drift Detection** 4a17284
    - [ ] **Write Failing Tests:** Write a test that compares `Projects` milestone completion dates with `Scheduler` anticipated dates.
    - [ ] **Implement:** Create `TimelineDriftService` to identify schedule deviations.
- [x] **Task: Conductor - User Manual Verification 'Phase 2: Project Health Monitoring' (Protocol in workflow.md)**

## Phase 3: Milestone Billing Bridge
Automate the financial and communication flows triggered by project milestones.

- [x] **Task: Define Billing & Notification Contracts** 42c131a
    - [ ] **Write Failing Tests:** Create a test to ensure `BillingBridge` requires `Receivable` and `Messaging` contracts.
    - [ ] **Implement:** Define `ReceivablePersistInterface` and `MessagingServiceInterface` in `src/Contracts/`.
- [x] **Task: Implement Billing Automation Flow** d72286c
    - [ ] **Write Failing Tests:** Write a test where completing a milestone results in a call to `createDraftInvoice` in the `Receivable` contract.
    - [ ] **Implement:** Create `MilestoneBillingService` to orchestrate billing actions.
- [x] **Task: Implement Notification & Revenue Update Flow** f2cd7e7
    - [ ] **Write Failing Tests:** Write a test where completing a milestone triggers a `Messaging` notification and an `updateEarnedRevenue` call in `Budget`.
    - [ ] **Implement:** Update `MilestoneBillingService` to include notification and budget update logic.
- [ ] **Task: Conductor - User Manual Verification 'Phase 3: Milestone Billing Bridge' (Protocol in workflow.md)**

## Phase 4: Integration & Documentation
Finalize the orchestrator and provide documentation for its usage.

- [ ] **Task: Implement Main Orchestrator Facade**
    - [ ] **Write Failing Tests:** Write a comprehensive test for `ProjectManagementOperationsCoordinator` that executes the full health check.
    - [ ] **Implement:** Create the `ProjectManagementOperationsCoordinator` class to unify the domain services.
- [ ] **Task: Documentation & Final Review**
    - [ ] Update `docs/FUTURE_ORCHESTRATORS_PLAN.md` to reflect that `ProjectManagement` is implemented.
    - [ ] Create `orchestrators/ProjectManagementOperations/README.md` with usage examples.
- [ ] **Task: Conductor - User Manual Verification 'Phase 4: Integration & Documentation' (Protocol in workflow.md)**
