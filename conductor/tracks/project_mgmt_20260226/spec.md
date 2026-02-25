# Specification: ProjectManagement Orchestrator

## Overview
The `ProjectManagement` orchestrator manages the professional services and project-based business lifecycle. It bridges the gap between project structures, resource allocation, and financial tracking, primarily focusing on **Project Health Monitoring** to ensure alignment between project goals, budgets, and timelines.

## Functional Requirements
- **Project Health Monitoring (Priority):**
    - **Labor Reconciliation:** Compare actual labor hours from the `Attendance` package against the labor cost allocations in the `Budget` package.
    - **Expense Tracking:** Aggregate material and overhead expenses from the `Inventory` or `Expense` modules and compare them against `Budget` constraints.
    - **Timeline Synchronization:** Compare `Projects` milestone completion status against the scheduled dates in the `Scheduler` package to detect timeline drift.
- **Milestone Billing Bridge:**
    - **Automated Invoicing:** Upon completion of a billable milestone in the `Projects` package, trigger the creation of a draft invoice in the `Receivable` package.
    - **Stakeholder Notification:** Send automated notifications via the `Messaging` package to relevant project stakeholders when milestones are achieved.
    - **Revenue Recognition:** Update the `Budget` package to record earned revenue associated with the completed milestone.
- **Contract-First Orchestration:**
    - Define all required interfaces for `Projects`, `Scheduler`, `Attendance`, `Budget`, `Document`, `Receivable`, and `Messaging` within the orchestrator's `Contracts/` directory to ensure strict isolation.

## Non-Functional Requirements
- **Architectural Purity:** Strictly adhere to the Nexus Three-Layer Architecture; the orchestrator must remain framework-agnostic (no Laravel/Symfony dependencies).
- **Testability:** Maintain >80% unit test coverage for all orchestration logic, including edge cases like budget overruns and timeline delays.
- **Immutability:** Use `readonly` classes and properties for all Value Objects and Service classes where applicable.

## Acceptance Criteria
- **Health Reports:** The system can accurately calculate and return the percentage of budget consumed vs. project progress.
- **Billing Triggers:** Completing a milestone in a mock `Project` adapter successfully calls the `Receivable` and `Messaging` contract methods.
- **Drift Detection:** The system correctly identifies projects that are behind schedule based on `Scheduler` data.

## Out of Scope
- **UI Implementation:** This track focuses on the Layer 2 Orchestrator logic; Laravel/React adapters and front-end views are not included.
- **Complex Resource Leveling:** Advanced multi-project resource optimization (levelling) is reserved for a future iteration.
