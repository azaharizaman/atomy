# Orchestrators Directory

This directory contains orchestrator packages that coordinate multiple domain packages.

Orchestrators are responsible for:
- Cross-package workflow coordination
- Complex business process orchestration
- Integration between multiple Nexus packages

## Creating an Orchestrator

1. Create a new directory: `orchestrators/YourOrchestrator/`
2. Initialize with `composer init` (name: `nexus/your-orchestrator`)
3. Depend on required Nexus packages and SharedKernel
4. Follow the same architectural guidelines as domain packages

## Structure

```
orchestrators/
├── SalesOrchestrator/     # Coordinates Sales, Inventory, Finance
├── ProcurementOrchestrator/  # Coordinates Procurement, Inventory, Payable
└── HrOrchestrator/        # Coordinates Hrm, Payroll, Identity
```
