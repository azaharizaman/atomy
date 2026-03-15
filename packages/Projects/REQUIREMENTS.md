# Requirements: Project Management (DEPRECATED – Split)

**This single fat requirements document has been split per Nexus Layer 1 package atomicity.**

Requirements are now owned by the following Layer 1 packages and the Layer 2 orchestrator:

| Package | Path | Scope |
|---------|------|--------|
| **Nexus\Project** | [packages/Project/REQUIREMENTS.md](../Project/REQUIREMENTS.md) | Project entity, PM, status, completion rules, client visibility |
| **Nexus\Task** | [packages/Task/REQUIREMENTS.md](../Task/REQUIREMENTS.md) | Task CRUD, dependencies, Gantt, circular-ref check |
| **Nexus\TimeTracking** | [packages/TimeTracking/REQUIREMENTS.md](../TimeTracking/REQUIREMENTS.md) | Timesheet entry, approval, immutability, hours rules |
| **Nexus\ResourceAllocation** | [packages/ResourceAllocation/REQUIREMENTS.md](../ResourceAllocation/REQUIREMENTS.md) | Allocation %, overallocation, double-booking prevention |
| **Nexus\Milestone** | [packages/Milestone/REQUIREMENTS.md](../Milestone/REQUIREMENTS.md) | Milestone, approvals, deliverables, billing vs budget |
| **Budget** (existing) | packages/Budget | Budget planned/actual, earned value |
| **Receivable** (existing) | packages/Receivable | Invoicing; orchestrator coordinates |
| **ProjectManagementOperations** (L2) | orchestrators/ProjectManagementOperations | Cross-package workflow, dashboard, reports, "task belongs to project" |

Cross-cutting (tenant isolation, RBAC, client portal, audit): **Identity**, **Tenant**, **Audit** + L2 Rules.

See [docs/project/ARCHITECTURE.md](../../docs/project/ARCHITECTURE.md) and the Project Domain L1/L2 Split plan for the full mapping.
