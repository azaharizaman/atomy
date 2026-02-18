# Nexus Orchestrators

This directory contains **orchestrator packages** that coordinate workflows spanning multiple atomic packages. Orchestrators are the "wiring layer" that implements complex business processes by composing capabilities from atomic packages.

**Key Principle:** Orchestrators own the **flow**, not the **truth**. They define **how** things move through the system, while atomic packages define **what** things are.

---

## 🎯 What is an Orchestrator?

An orchestrator is a **pure PHP package** that:
- ✅ Coordinates 2 or more atomic packages
- ✅ Implements multi-step business processes
- ✅ Handles cross-domain workflows
- ✅ Reacts to events from atomic packages
- ✅ Manages process state and saga patterns
- ❌ Does NOT define core entities (those belong in atomic packages)
- ❌ Does NOT access databases directly (uses repository interfaces)
- ❌ Does NOT contain framework code (still pure PHP)

---

## 📋 Current Orchestrators

### IdentityOperations
**Coordinates:** Identity, Tenant, AuditLogger

**Responsibilities:**
- User lifecycle management across tenants
- Multi-factor authentication workflows
- Session and token management
- Authentication event handling

**Example Workflow:** User registration
1. Create user via `Identity\UserManager`
2. Assign tenant via `Tenant\TenantContextManager`
3. Log creation event via `AuditLogger\AuditLogManager`
4. Send welcome notification via `Notifier\NotificationManager`

### SupplyChainOperations
**Coordinates:** Inventory, Procurement, Sales, Warehouse, Receivable (via adapters)

**Responsibilities:**
- Dropship order fulfillment
- Landed cost capitalization
- RMA (Return Merchandise Authorization) workflows
- Warehouse inventory balancing
- Replenishment forecasting
- Available-to-Promise (ATP) calculations

**Architecture Note:** Uses Interface Segregation Pattern - defines own interfaces in `Contracts/` and depends only on PSR libraries. Adapters bridge to atomic packages.

---

## 🏗️ Orchestrator Architecture

### Interface Segregation (CRITICAL)

**Orchestrators MUST define their own interfaces and NOT depend directly on atomic package interfaces.**

```
Orchestrator composer.json MUST only depend on:
├── php: ^8.3
├── psr/log: ^3.0
└── psr/event-dispatcher: ^1.0

Orchestrator Contracts/ folder contains:
├── {Orchestrator}ManagerInterface.php   # Service contracts
├── {Entity}Interface.php                # Entity data contracts
├── {Entity}ProviderInterface.php        # Repository contracts
└── {Action}Interface.php                # Action-specific contracts
```

**Why?**
- Orchestrators become independently publishable
- Atomic packages remain unaware of orchestrator needs
- Adapters bridge orchestrator interfaces to atomic package implementations
- Enables implementation swappability (e.g., swap Inventory package)

**Full Guidelines:** See [docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md](../docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md)

### Standard Folder Structure

```
orchestrators/OrchestratorName/
├── composer.json             # Depends on: 2+ Nexus packages
├── LICENSE
├── README.md
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── .gitignore
├── docs/
│   ├── workflows/            # Workflow diagrams and documentation
│   ├── getting-started.md
│   ├── api-reference.md
│   └── examples/
├── src/
│   ├── Workflows/            # Stateful processes (Sagas/State Machines)
│   │   ├── ProcessName/
│   │   │   ├── Steps/        # Individual workflow steps
│   │   │   ├── States/       # Process states
│   │   │   └── ProcessNameWorkflow.php
│   │   └── README.md
│   │
│   ├── Coordinators/         # Stateless orchestrators (Synchronous)
│   │   ├── CoordinatorName.php
│   │   └── README.md
│   │
│   ├── Listeners/            # Reactive logic (Event subscribers)
│   │   ├── ListenerName.php
│   │   └── README.md
│   │
│   ├── Contracts/            # Dependency inversion for workflows
│   │   ├── WorkflowStateRepositoryInterface.php
│   │   └── README.md
│   │
│   ├── DTOs/                 # Data Transfer Objects for processes
│   │   └── ProcessRequest.php
│   │
│   └── Exceptions/           # Process failures
│       └── ProcessFailedException.php
│
└── tests/
    ├── Unit/
    └── Feature/
```

---

## 📖 Key References

- **Architecture Guidelines:** `../ARCHITECTURE.md`
- **Interface Segregation Pattern:** `../docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md` (**CRITICAL** for orchestrator development)
- **Coding Standards:** `../CODING_GUIDELINES.md`
- **Package Reference:** `../docs/NEXUS_PACKAGES_REFERENCE.md`
- **Refactoring Exercise:** `../REFACTOR_EXERCISE_12.md`
- **Atomic Packages:** `../packages/README.md`

---

**Last Updated:** 2026-02-18  
**Maintained By:** Nexus Architecture Team  
**Current Orchestrators:** 2 (IdentityOperations, SupplyChainOperations)
