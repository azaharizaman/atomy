# EVERY LINES AND DETAILS IN THIS FILE IS TO BE FULLY UNDERSTOOD AND FOLLOWED BY THE CODING AGENT WHENEVER IT IS WORKING WITHIN THIS MONOREPO. DO NOT SKIM OR IGNORE ANY PART OF THIS FILE.

# GitHub Copilot Instructions for Nexus Monorepo

## 🚨 MANDATORY PRE-IMPLEMENTATION CHECKLIST

**BEFORE implementing ANY feature in `apps/Atomy/`, you MUST:**

1. **Consult [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md)** - This document lists all 40+ available first-party packages and their capabilities
2. **Use existing packages FIRST** - If a Nexus package provides the functionality, you MUST use it via dependency injection
3. **Never reimplement package functionality** - Creating custom implementations when packages exist is an architectural violation

**Example Violations to Avoid:**
- ❌ Creating `PrometheusMetricsCollector` when `Nexus\Monitoring` exists
- ❌ Building custom audit logger when `Nexus\AuditLogger` exists  
- ❌ Implementing file storage when `Nexus\Storage` exists
- ❌ Creating notification system when `Nexus\Notifier` exists

**See [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md) for the complete "I Need To..." decision matrix.**

---

## Project Overview

You are working on **Nexus**, a modular PHP monorepo for an ERP system built on Laravel 12. This project follows a strict architectural pattern: **"Logic in Packages, Implementation in Applications."**

## Core Philosophy

**Decoupling is mandatory.** The monorepo has two main components:

- **📦 `packages/`**: Framework-agnostic, reusable business logic (the "engines")
- **🚀 `apps/`**: Runnable applications that implement and orchestrate packages (the "cars")

## Directory Structure

```
```
nexus/
├── packages/               # Atomic, publishable PHP packages
│   ├── Accounting/        # Financial accounting
│   ├── Period/        # Period management
│   ├── AccountPayable/        # AP accounting
│   ├── AccountReceivable/        # AR accounting
│   ├── DataProcessor/        # Data processing capability like OCR, ETL, etc.
│   ├── Analytics/         # Business intelligence
│   ├── AuditLogger/       # Audit logging (timeline/feed views)
│   ├── EventStream/       # Event sourcing engine for critical domains
│   ├── Backoffice/        # Company structure
│   ├── Crm/               # Customer relationship management
│   ├── FieldService/      # Field service management
│   ├── Hrm/               # Human resources
│   ├── Manufacturing/     # Production management
│   ├── Marketing/         # Marketing campaigns
│   ├── OrgStructure/      # Organizational hierarchy
│   ├── Payroll/           # Payroll processing (Malaysia)
│   ├── Procurement/       # Purchase management
│   ├── ProjectManagement/ # Project tracking
│   ├── Sequencing/        # Auto-numbering
│   ├── Tenant/            # Multi-tenancy (if applicable)
│   ├── Uom/               # Unit of measurement
│   ├── Inventory/         # Inventory management
│   ├── Setting/           # Setting management
│   ├── Connector/         # Connector as integration hub engine (if applicable)
│   ├── Storage/           # Storage engine (if applicable)
│   ├── Document/          # Document engine (if applicable)
│   ├── Identity/          # Identity engine (if applicable)
│   ├── Statutory/         # Statutory reporting engine (if applicable)
│   ├── Compliance/          # Compliance engine (if applicable)
│   ├── Notifier/          # Notification engine (if applicable)
│   ├── Connector/         # Connector as integration hub engine (if applicable)
│   └── Workflow/          # Workflow engine (if applicable)
|   
```   
└── apps/
    ├── Atomy/             # Headless Laravel ERP backend
    └── Edward/            # Terminal UI client (TUI)
```

## Critical Architectural Rules

### 🔑 The Golden Rule: Framework Agnosticism

Your package must be a **pure PHP engine** that is ignorant of the application it runs in.

- **Logic Over Implementation:** The package defines *what* needs to be done (the logic), not *how* it's done (the implementation).
- **NEVER Reference the Framework:** Do not use any classes, facades, or components specific to Laravel (e.g., `Illuminate\Http\Request`, `DB::`, or `Eloquent\Model`).
- **No Persistence:** Packages must not contain database migrations or concrete database querying logic. They only define the **interfaces** needed for persistence.

### When Working in `packages/`

**ALWAYS:**
- Write pure PHP or framework-agnostic code
- Define persistence needs via **Contracts (Interfaces)** only
- Use dependency injection via constructor
- Make packages publishable (include composer.json, LICENSE, README.md)
- Place interfaces in `src/Contracts/`
- Place business logic in `src/Services/`
- Place exceptions in `src/Exceptions/`
- Use **Value Objects** (immutable objects) for specific data types like money, time, or severity levels to enforce business rules early

**NEVER:**
- Use Laravel-specific classes like `Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, or facades
- Include database migrations or schema definitions
- Create Eloquent models or concrete database logic
- Depend on or reference any application code (e.g., `Atomy`, `Edward`)
- Use `Route::`, `DB::`, or any other Laravel facades

**ACCEPTABLE:**
- Light dependency on `illuminate/support` for Collections and Contracts (but avoid if possible)
- Framework-agnostic libraries like `psr/log`
- Requiring other atomic packages (e.g., `nexus/inventory` can require `nexus/uom`)

## 🔄 Hybrid Approach: Feed vs. Replay (AuditLogger vs. EventStream)

The Nexus monorepo implements a **Hybrid Architecture** for event tracking and state reconstruction:

### The "Feed" View: `Nexus\AuditLogger` (Standard Approach - 95% of Records)

**Purpose:** User-facing timeline/feed displaying "what happened" on an entity's page.

**Use Case:** Customer records, HR data, settings, inventory adjustments, approval workflows.

**Mechanism:**
- Domain packages call `AuditLogger::log()` **after** transaction commit
- Records the **result** of an action (e.g., "Invoice status changed to Paid")
- Simple to query and display in chronological order
- Human-readable descriptions for non-technical users

**Example:**
```php
$this->auditLogger->log(
    $entityId,
    'status_change',
    'Invoice status updated from Draft to Paid by Azahari Zaman'
);
```

**Limitations:**
- Cannot **replay** system state to a specific point in time
- Only records outcomes, not the underlying business events

-----

### The "Replay" Capability: `Nexus\EventStream` (Event Sourcing - Critical Domains Only)

**Purpose:** Immutable event log enabling **state reconstruction** at any point in history.

**Use Case (Critical Domains Only):**
- **Finance (GL)**: Every debit/credit is an event (`AccountCreditedEvent`, `AccountDebitedEvent`)
- **Inventory**: Every stock change is an event (`StockReservedEvent`, `StockAddedEvent`, `StockShippedEvent`)
- **Large Enterprise AP/AR**: Optional event sourcing for payment lifecycle tracking

**Mechanism:**
- Aggregate publishes events to `EventStoreInterface`
- Events are **append-only** (immutable)
- Read models (projections) are rebuilt from event stream
- Supports temporal queries: "What was the balance of account 1000 on 2024-10-15?"

**Example:**
```php
// Publish event to EventStream
$this->eventStore->append(
    $aggregateId,
    new AccountCreditedEvent(
        accountId: '1000',
        amount: Money::of(1000, 'MYR'),
        description: 'Customer payment received'
    )
);

// Rebuild state at specific point in time
$balance = $this->eventStream->getStateAt($accountId, '2024-10-15');
```

**Benefits:**
- **Complete audit trail** with full replay capability
- **Temporal queries** for compliance and forensic analysis
- **Event versioning** for schema evolution
- **Projections** for optimized read models

**Tradeoffs:**
- **Higher complexity** (snapshots, projections, upcasters)
- **Storage overhead** (every event is stored forever)
- **Performance tuning required** (partitioning, snapshots for large streams)


### Decision Matrix: When to Use Which Approach

| Domain | Use AuditLogger (Feed) | Use EventStream (Replay) |
| :--- | :--- | :--- |
| **Finance (GL)** | ❌ | ✅ MANDATORY (SOX/IFRS compliance) |
| **Inventory** | ❌ | ✅ MANDATORY (stock accuracy verification) |
| **Payable (AP)** | ✅ Small/Medium | ✅ Large Enterprise (optional) |
| **Receivable (AR)** | ✅ Small/Medium | ✅ Large Enterprise (optional) |
| **Hrm** | ✅ Always | ❌ Not required |
| **Payroll** | ✅ Always | ❌ Not required (use AuditLogger for timeline) |
| **Crm** | ✅ Always | ❌ Not required |
| **Procurement** | ✅ Always | ❌ Not required |
| **Workflow** | ✅ Always | ❌ Not required (process tracking via AuditLogger) |

**Rule of Thumb:**
- **Use AuditLogger** if you only need to show "a timeline of changes" to users
- **Use EventStream** if you need to answer: *"What was the exact state of this entity on [date]?"* for compliance/legal reasons
-----

# 🛡️ Statutory and Compliance Architecture: Decoupling Governance and Reporting

This section defines the architecture for handling all mandatory legal, tax, quality, and reporting requirements. The design principle is the strict **decoupling of Process Enforcement from Output Formatting** to ensure agility in response to global regulatory changes.

---

## 1. 🎯 Architectural Goals

The design is engineered to meet the following objectives:

* **Risk Mitigation:** The system must prevent posting transactions (e.g., in `Nexus\Finance`) to closed periods, ensuring all financial records are final and auditable.
* **Feature Gating & Monetization:** Core domain packages (like `Payroll`) must function neutrally, allowing proprietary, paid compliance adapters (e.g., `Nexus\Statutory.Payroll.SGP`) to be plugged in only when enabled and purchased.
* **Zero Refactoring Debt:** Core packages (`Nexus\Payroll`, `Nexus\Accounting`) are decoupled from country-specific logic and should never be refactored when a country's tax rate changes.

---

## 2. 🧩 Separation of Concerns: The Two Pillars

All compliance activities are divided into two distinct, atomic, and non-overlapping packages, reflecting the difference between **What the company *does*** (Process) and **What the company *reports*** (Data).

### A. 🛡️ `Nexus\Compliance` (The Orchestrator & Rulebook)

This package manages **Operational Compliance** and the **System's internal governance**. It deals with the mandatory *behavior* and *configuration* required by a scheme (e.g., ISO, internal policy).

| Focus | Responsibility | Example Action |
| :--- | :--- | :--- |
| **Process Enforcement** | Enforces internal controls (e.g., Segregation of Duties). | If ISO 14001 is active, the adapter forces the addition of **Hazardous Material fields** onto assets in the `Nexus\Asset` package. |
| **Feature Composition** | Manages which concrete service implementations are active based on user licensing/feature flags. | Binds the `ISO14001AuditLogFormatter` to the `AuditLogFormatterInterface`, hijacking the system's default behavior. |
| **Configuration Audit** | Audits the ERP's settings to ensure all features required by a scheme are configured (e.g., checking if the mandatory **Management Review Meeting** auto-planner is enabled). |

### B. 💰 `Nexus\Statutory` (The Contract Hub & Reporter)

This package manages **Reporting Compliance** and the specific formats mandated by a legal authority. It deals with the data tags, schemas, and logistical metadata required for filing.

| Focus | Responsibility | Example Action |
| :--- | :--- | :--- |
| **Reporting Contracts** | Defines interfaces like `TaxonomyReportGeneratorInterface` and `PayrollStatutoryInterface`. | Provides the generic method `$calculator->calculate()` that `Nexus\Payroll` calls, regardless of the country. |
| **Metadata Management** | Defines the mandatory structure for reporting schemas (e.g., `ReportMetadataInterface`). | Requires implementations (e.g., `SSMBRSTaxonomyAdapter`) to define the **Filing Frequency**, **Output Format (XBRL)**, and **Recipient**. |
| **Default Services** | Provides safe, open-source default implementations (e.g., **`DefaultAccountingAdapter`** for P&L/BS, **`DefaultPayrollCalculator`** for zero-deductions). |

---

## 3. 🔄 System Flow: Pluggable Architecture

The system operates on a **Default-Override** mechanism managed by the **`Nexus\Atomy`** orchestrator.

1.  **Default Binding:** In `Nexus\Atomy`, the IoC container always binds the **Default Adapter** (from `Nexus\Statutory`) to the interface.
    * *Example:* `PayrollStatutoryInterface` $\rightarrow$ `DefaultStatutoryCalculator`.
2.  **Feature Check:** The `Nexus\Compliance` orchestrator checks the active compliance schemes and user licenses.
3.  **Override (If Paid/Enabled):** If a compliance package is enabled (e.g., `Nexus\Statutory.Payroll.MYS`), `Nexus\Atomy` **overrides** the default binding with the specific implementation.
    * *Example:* `PayrollStatutoryInterface` $\rightarrow$ `MYSStatutoryCalculator`.
4.  **Transaction:** `Nexus\Payroll` executes a pay run. It calls the generic `PayrollStatutoryInterface` and is unaware if it's running the default or the full Malaysian logic.

This design ensures that any future regulatory body or compliance scheme can be added as a self-contained, atomic package without touching any existing core ERP logic. 

____

# 🚫 The Principle of Atomic Package Statelessness

## 1. Core Principle: No Internal State

All Core and Atomic packages (e.g., `Nexus\Connector`, `Nexus\Events`, `Nexus\Security`) **must be stateless** across execution cycles. An instance of a service class must contain only **immutable dependencies** (interfaces) and **ephemeral, runtime data** required for the current method execution.

This rule is enforced to ensure services are **horizontally scalable** and **resilient** in a modern, distributed, or multi-process environment (e.g., PHP-FPM, Dockerized Microservices, Laravel Octane).

## 2. The Contradiction: The Circuit Breaker Problem

A common architectural error is managing application state internally.

| Anti-Pattern | Impact in a Distributed System |
| :--- | :--- |
| **Storing State in Instance Properties** | When a critical feature like the **Circuit Breaker** uses an internal array (`$this->circuitStates`), the state is isolated to a single PHP-FPM worker or process. |
| **Failure to Scale** | If **Worker A** fails 10 times, the circuit opens **only** for Worker A. Workers B, C, and D are still unaware, and will continue to hammer the failing external service, causing instability and defeating the entire purpose of the pattern. |

## 3. The Solution: Dependency Inversion for State

State management must be explicitly delegated to a **shared, persistent store** (Redis, Database, etc.) via a dedicated **Storage Interface**. This ensures that state changes recorded by any worker are instantly visible to all other workers.

### 🔑 The Storage Interface Requirement

Any service requiring long-term state (e.g., Circuit Breaker thresholds, Rate Limiter counters, Idempotency keys) **must** accept a `StorageInterface` dependency in its constructor.

| Pattern | Description | Example |
| :--- | :--- | :--- |
| **DO** | Delegate the state management to an external, injected service. | `ConnectorManager` must be injected with `CircuitBreakerStorageInterface`. |
| **DON'T** | Use internal properties for state tracking. | The `ConnectorManager` class must **NOT** contain `private array $circuitStates = [];`. |

---

## 4. Coding Agent Instructions (Mandatory Checks)

The coding agent must validate the following when developing or refactoring atomic services:

1.  **Stateless Check:** If a class property holds data that needs to persist or be synchronized across multiple requests (e.g., configuration, counters, flags), that property **must be removed**.
2.  **Interface Requirement:** All state-related logic (read/write/update) must be implemented by invoking a method on an injected, **immutable interface**.
3.  **No Direct Implementation:** The atomic package (`Nexus\Connector`) **MUST NOT** include any concrete implementation of the storage interface (e.g., `RedisCircuitBreakerStorage`). That implementation is reserved for the `Nexus\Atomy` integration layer.

This principle ensures that the `Nexus\Connector` package remains atomic, has **zero infrastructure coupling**, and can be effortlessly scaled horizontally.

-----

### Integration Patterns

#### Pattern 1: AuditLogger for Timeline (Standard)
```php
// In Nexus\Hrm\Services\EmployeeManager
public function updateEmployee(string $id, array $data): void
{
    $employee = $this->repository->findById($id);
    $oldData = $employee->toArray();
    
    $employee->update($data);
    $this->repository->save($employee);
    
    // Log the result for timeline feed
    $this->auditLogger->log(
        $id,
        'employee_updated',
        "Employee {$employee->getName()} updated by {$this->authContext->getCurrentUser()}"
    );
}
```

#### Pattern 2: EventStream for Replay (Critical Domains)
```php
// In Nexus\Finance\Services\LedgerManager
public function postJournalEntry(JournalEntry $entry): void
{
    // Publish events to EventStream
    foreach ($entry->getLines() as $line) {
        if ($line->isDebit()) {
            $this->eventStore->append(
                $line->getAccountId(),
                new AccountDebitedEvent(
                    accountId: $line->getAccountId(),
                    amount: $line->getAmount(),
                    journalEntryId: $entry->getId()
                )
            );
        } else {
            $this->eventStore->append(
                $line->getAccountId(),
                new AccountCreditedEvent(
                    accountId: $line->getAccountId(),
                    amount: $line->getAmount(),
                    journalEntryId: $entry->getId()
                )
            );
        }
    }
    
    // Projection updates the "current balance" read model
    // AuditLogger separately logs "Journal Entry #JE-2024-001 posted" for timeline
}
```

**Key Architectural Principle:**
- `Nexus\AuditLogger` logs **outcomes** for display (timeline/feed views)
- `Nexus\EventStream` stores **events** for replay (state reconstruction)
- Both can coexist: EventStream for technical accuracy, AuditLogger for user-friendly timelines

-----

- Requiring other atomic packages (e.g., `nexus/inventory` can require `nexus/uom`)

### 🧱 Package Design Principles

#### Contract-Driven Design

- **Define Needs, Not Solutions:** Use **Interfaces (`Contracts/`)** to define every external dependency your package needs. This includes data structures (`EntityInterface`), persistence (`RepositoryInterface`), and external services (`DataSourceContract`).
- **One Interface Per Responsibility:** Keep interfaces small and focused.
- **The Consumer Implements:** Always remember that the consuming application (`Nexus\Atomy`) is responsible for providing the concrete class that fulfills your package's interface.

#### Clear Separation of Concerns

| Folder | Rule of Thumb | Example Content |
|--------|---------------|-----------------|
| **`src/Services/`** | **Public API:** Only expose the high-level logic the user needs to interact with (e.g., `AuditLogManager::log()`). | Managers, Coordinators, Façade accessors. |
| **`src/Core/`** | **Internal Engine:** Use this optional folder for complex, internal logic that is not part of the public API (e.g., `QueryExecutor.php`, `PipelineEngine.php`). | Internal Contracts, Value Objects, Engine components. |
| **`src/Exceptions/`** | **Domain-Specific Errors:** Create custom exceptions (e.g., `AuditLogNotFoundException`) that communicate specific domain failures to the consuming app. | All exceptions extending PHP's base exceptions. |

#### Dependency Management

- **Limit External Dependencies:** Keep the `composer.json` file as lean as possible. Only include truly necessary dependencies (e.g., a logging PSR interface or a date/time library).
- **Internal Dependencies are Fine:** It's acceptable and often necessary for a package to require another atomic package (e.g., `nexus/inventory` requiring `nexus/uom`). This is how you share logic across the monorepo.
- **NEVER Depend on an App:** Your package must never require or reference code from the `apps/` directory (`Atomy` or `Edward`).

### When Working in `apps/Atomy/`

**Atomy is the headless Laravel orchestrator. This is where implementation happens.**

**ALWAYS:**
- Implement all package Contracts with concrete classes
- Place Eloquent models in `app/Models/` (these implement package interfaces)
- Place repositories in `app/Repositories/` (these implement repository interfaces)
- Create all database migrations in `database/migrations/`
- Bind interfaces to implementations in `app/Providers/AppServiceProvider.php`
- Expose functionality via API/GraphQL routes
- Keep `resources/views/` empty (headless principle)

**NEVER:**
- Create business logic here (it belongs in packages)
- Implement features without checking if logic should be in a package first

### When Working in `apps/Edward/`

**Edward is a Terminal UI client that consumes Atomy's API.**

**ALWAYS:**
- Use `app/Http/Clients/AtomyApiClient.php` to communicate with Atomy
- Build UI using Laravel Artisan commands
- Treat Atomy as a remote API (even though it's in the same monorepo)

**NEVER:**
- Access the Atomy database directly
- Require any atomic packages (like `nexus/tenant`)
- Share models or repositories with Atomy

## Package Structure Template

When creating a new package, use this structure (based on `Nexus\Tenant`):

```
packages/NewPackage/
├── composer.json              # Package definition
├── README.md                  # Package documentation
├── LICENSE                    # Licensing information
└── src/
    ├── Contracts/             # REQUIRED: Interfaces
    │   ├── EntityInterface.php
    │   └── RepositoryInterface.php
    ├── Exceptions/            # REQUIRED: Domain exceptions
    │   └── EntityNotFoundException.php
    ├── Services/              # REQUIRED: Business logic
    │   └── EntityManager.php
    ├── Core/                  # OPTIONAL: Internal engine (see organization rules below)
    │   ├── Engine/
    │   ├── ValueObjects/
    │   └── Entities/
    └── ServiceProvider.php    # OPTIONAL: Laravel integration
```

## Package Organization: When to Use `Core/` Folder

The `Core/` folder is an organizational pattern to **protect the package's internal engine** and clearly distinguish between the **Public API** (what consumers use) and the **Internal Engine** (implementation details).

### ✅ When to Create a `Core/` Folder (Recommended for Complex Packages)

Create a `Core/` folder when your package is **complex** and contains internal components that are essential for the package to work but should **never be accessed directly** by the consuming application.

| Scenario | Example | Files to place in `Core/` |
|----------|---------|---------------------------|
| **High Complexity** | The package implements a complex system (like `Analytics` or `Workflow` engine). | Finite State Machines, Expression Evaluators, Internal Entities, Value Objects. |
| **Internal Contracts** | You need to use interfaces for internal dependency injection that are irrelevant to the consumer. | Contracts used only by `Core/Engine/` components. |
| **Data Protection** | You have **Value Objects** or **Internal Entities** that should be instantiated and handled only by the main **`Services/Manager`**. | `PredictionRequest.php`, `AuditLevel.php`, `QueryDefinition.php`. |
| **Engine Logic** | You want to enforce that the main `Manager` class is merely an **orchestrator** for a more complex component. | `Engine/QueryExecutor.php`, `Engine/PipelineEngine.php`. |

**Example structure for complex package (`Nexus\Analytics`):**

```
src/
├── Contracts/                 # Public API interfaces
│   └── AnalyticsManagerInterface.php
├── Services/                  # Public API services
│   └── AnalyticsManager.php   # Orchestrator only
├── Core/                      # Internal engine (do not expose)
│   ├── Engine/
│   │   ├── QueryExecutor.php
│   │   └── PredictionEngine.php
│   ├── ValueObjects/
│   │   ├── QueryDefinition.php
│   │   └── PredictionRequest.php
│   └── Contracts/             # Internal contracts
│       └── ExecutorInterface.php
└── Exceptions/
```

**In the Nexus monorepo, using a `Core/` folder is highly recommended** for packages like `Analytics`, `Workflow`, `Manufacturing`, and `Inventory` to maintain the **"Pure Logic"** principle.

### ❌ When to Skip the `Core/` Folder (Simple Packages)

Skip the `Core/` folder when your package is **simple** and the distinction between the API and the engine is minimal.

| Scenario | Example | Structure |
|----------|---------|-----------|
| **Low Complexity** | The package performs a few simple, well-defined tasks (like `Uom` or `Tenant`). | All logic can go directly into `src/Services/Manager.php`. |
| **Minimal Files** | The package contains fewer than 10 total files and no internal helper contracts or value objects. | `src/Contracts/`, `src/Exceptions/`, `src/Services/` |

**Example structure for simple package (`Nexus\Tenant`):**

```
src/
├── Contracts/
│   └── TenantManagerInterface.php
├── Exceptions/
│   └── TenantNotFoundException.php
└── Services/
    └── TenantManager.php      # Both API and engine in one file
```

**Rule of thumb:** If your package's main `Manager` class is under 200 lines and doesn't need internal helpers, skip `Core/`. If it exceeds 300 lines or requires internal components, introduce `Core/` for better separation.

## Development Workflows

### Implementing a New Feature in Atomy

Follow this decision tree:

1. **Is core logic missing?** → Create/update package
2. **How is logic stored?** → Create migrations and models in Atomy
3. **How is logic orchestrated?** → Create service/controller in Atomy
4. **How is logic exposed?** → Add API endpoint in Atomy
5. **How does user access it?** → Add command/client method in Edward

### Creating a New Package

1. Create `packages/PackageName/` directory
2. Run `composer init` (set name to `nexus/package-name`)
3. Define PSR-4 autoloader: `"Nexus\\PackageName\\": "src/"`
4. Create Contracts in `src/Contracts/`
5. Create Services in `src/Services/`
6. Update root `composer.json` repositories array
7. Install in Atomy: `composer require nexus/package-name:"*@dev"`
8. Implement contracts in Atomy with migrations, models, and repositories
9. Bind implementations in `AppServiceProvider.php`

## Code Generation Guidelines

### When I Ask for Package Code

Generate:
- Interface definitions with clear docblocks
- Service classes with constructor dependency injection
- Custom exceptions extending base PHP exceptions
- Framework-agnostic validation logic
- Pure PHP business rules
- Value Objects for specific data types
- Unit tests using PHPUnit

### When I Ask for Atomy Code

Generate:
- Eloquent models implementing package interfaces
- Repository classes implementing repository contracts
- Laravel migrations with proper schema
- API controllers using package services
- Service provider bindings
- API routes in `routes/api.php`

### When I Ask for Edward Code

Generate:
- Artisan commands in `app/Console/Commands/`
- API client methods in `AtomyApiClient.php`
- Terminal UI formatting (colors, tables, menus)
- Input validation and error handling

## Naming Conventions

- **Packages**: PascalCase (e.g., `Tenant`, `AuditLogger`)
- **Composer names**: kebab-case (e.g., `nexus/audit-logger`)
- **Namespaces**: `Nexus\PackageName`
- **Interfaces**: Descriptive with `Interface` suffix (e.g., `TenantRepositoryInterface`)
- **Services**: Domain-specific managers (e.g., `TenantManager`, `StockManager`)
- **Exceptions**: Descriptive with `Exception` suffix (e.g., `TenantNotFoundException`)

## Available Packages

**📚 COMPREHENSIVE GUIDE:** See [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md) for detailed capabilities, interfaces, and usage examples for ALL packages.

The following packages are implemented or under development in this monorepo:

### ✅ Production-Ready Packages (Fully Implemented)
1. **Nexus\Tenant** - Multi-tenancy context and isolation engine with queue context propagation
2. **Nexus\Sequencing** - Auto-numbering with patterns, scopes, and atomic counter management
3. **Nexus\Period** - Fiscal period management with intelligent next-period creation
4. **Nexus\Uom** - Unit of measurement management and conversions
5. **Nexus\AuditLogger** - Comprehensive audit logging with CRUD tracking, retention policies
6. **Nexus\Identity** - Authentication, authorization (RBAC), session/token management, MFA
7. **Nexus\Notifier** - Multi-channel notification engine (email, SMS, push, in-app)
8. **Nexus\Finance** - General ledger, chart of accounts, journal entries, double-entry bookkeeping
9. **Nexus\Accounting** - Financial statement generation, period close, consolidation, variance analysis
10. **Nexus\Party** - Party management (customers, vendors, employees, contacts)
11. **Nexus\Product** - Product catalog, pricing, categorization
12. **Nexus\Currency** - Multi-currency management, exchange rates, money calculations
13. **Nexus\Sales** - Quotation-to-order lifecycle, pricing engine
14. **Nexus\Receivable** - Customer invoicing, payment receipts, credit control, collections (3 phases complete)
15. **Nexus\Payable** - Vendor bills, payment processing, aging analysis
16. **Nexus\Connector** - Integration hub with circuit breaker, retry logic, OAuth support
17. **Nexus\Storage** - File storage abstraction layer
18. **Nexus\Document** - Document management with versioning and permissions
19. **Nexus\Setting** - Application settings management
20. **Nexus\Geo** - Geocoding, geofencing, routing capabilities
21. **Nexus\Routing** - Route optimization and caching
22. **Nexus\CashManagement** - Bank accounts, reconciliation, cash flow forecasting
23. **Nexus\Intelligence** - AI-assisted automation and predictions
24. **Nexus\Reporting** - Report definition, execution, and export engine
25. **Nexus\Export** - Multi-format export engine (PDF, Excel, CSV, JSON)
26. **Nexus\Import** - Data import with validation and transformation
27. **Nexus\Assets** - Fixed asset management, depreciation tracking
28. **Nexus\Budget** - Budget planning and tracking
29. **Nexus\Scheduler** - Task scheduling and job management
30. **Nexus\Compliance** - Process enforcement and operational compliance
31. **Nexus\Statutory** - Reporting compliance and statutory filing
32. **Nexus\PayrollMysStatutory** - Malaysian payroll statutory calculations (EPF, SOCSO, PCB)

### 🚧 In Development
33. **Nexus\Hrm** - Human resource management, leave, attendance, performance reviews
34. **Nexus\Payroll** - Payroll processing framework
35. **Nexus\Inventory** - Inventory and stock management with lot/serial tracking
36. **Nexus\Warehouse** - Warehouse management and operations
37. **Nexus\FieldService** - Work orders, technicians, service contracts, SLA management
38. **Nexus\Workflow** - Workflow engine, process automation, state machines
39. **Nexus\EventStream** - Event sourcing engine for critical domains (Finance GL, Inventory)
40. **Nexus\DataProcessor** - Data processing capability (OCR, ETL, etc.) - Interface-only package
41. **Nexus\Analytics** - Business intelligence, predictive models, data analytics
42. **Nexus\Backoffice** - Company structure, offices, departments, staff organizational units
43. **Nexus\OrgStructure** - Organizational hierarchy and structure management
44. **Nexus\Crm** - Customer relationship management, leads, opportunities, sales pipeline
45. **Nexus\Marketing** - Campaigns, lead nurturing, A/B testing, GDPR compliance
46. **Nexus\Manufacturing** - Bill of materials, work orders, production planning, MRP
47. **Nexus\Procurement** - Purchase requisitions, POs, goods receipt, 3-way matching
48. **Nexus\ProjectManagement** - Projects, tasks, timesheets, milestones, resource allocation
49. **Nexus\Audit** - Advanced audit capabilities (extends AuditLogger)
50. **Nexus\Crypto** - Cryptographic operations and key management

## Quality Standards

- Always use strict types: `declare(strict_types=1);`
- **Target PHP Version: 8.3+** - All packages must require `"php": "^8.3"` in composer.json
- All auto-incrementing primary keys are ULIDs (UUID v4) strings.
- Use type hints for all parameters and return types
- Write comprehensive docblocks with `@param`, `@return`, `@throws`
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Validate inputs in services before processing
- Throw descriptive exceptions for error cases
- **Service Layer Must Use Interfaces:** All dependencies in service constructors must be injected as interfaces, never concrete classes

## ✨ Modern PHP 8.x Standards (Targeting 8.3+)

The coding agent MUST strictly adhere to these modern conventions to reduce boilerplate, enhance type safety, and enforce immutability:

1.  **Constructor Property Promotion:** Use for all injected dependencies and properties initialized in `__construct`.
2.  **`readonly` Modifier:** All properties (especially in Services, Managers, Repositories, and Value Objects) defined via property promotion MUST be declared as `readonly`.
3.  **Native PHP Enums:** Use native `enum` (backed by `int` or `string`) instead of defining constants within classes for fixed value sets (statuses, levels, types).
4.  **`match` Expression:** Use the `match` expression exclusively instead of the traditional `switch` statement.
5.  **New/Throw in Expressions:** Use `new` and `throw` within expressions for simplified conditional object creation and exception handling (e.g., `?? throw new Exception()`).

## Attributes Over DocBlocks (PHP 8.0+)

The agent **MUST** use native PHP Attributes for all metadata configuration, especially in testing and application-level configuration, instead of relying on DocBlock annotations.

* **Testing:** Use `#[DataProvider]`, `#[Group]`, `#[CoversClass]`, `#[Test]`, etc., instead of the corresponding `@annotation` in DocBlocks.
* **Custom Metadata:** If generating custom package configuration (e.g., marking a class as tenant-aware), define and use a dedicated Attribute class (e.g., `#[TenantAware]`) placed in a `src/Attributes/` directory.
* **Laravel Attributes:** When working in `apps/Atomy`, use Laravel's native PHP 8 Attributes for routing, validation, and model casting instead of DocBlock annotations.
* **No DocBlock Annotations:** Avoid using DocBlock annotations for metadata purposes; reserve DocBlocks for descriptive comments only.

-----

## 🚫 Strict Anti-Pattern: Facade & Global Helper Prohibition

The use of Laravel Facades (`Log::`, `Cache::`, `DB::`) and global helpers (`now()`, `config()`, `dd()`, `app()`) is **strictly forbidden** in all code within the `packages/` directory.

### 1. 🛑 Absolute Prohibitions (Zero Tolerance)

The following must **NEVER** appear in any file within the `packages/` directory:

| Forbidden Laravel Artifact | Atomic Replacement Principle |
| :--- | :--- |
| **`Log::...`** (Facade) | Must use an **injected `LoggerInterface`** (from PSR-3). |
| **`Cache::...`** (Facade) | Must use an **injected `CacheRepositoryInterface`** (from package Contracts). |
| **`DB::...`** or **`\Illuminate\Database\...`** | Must use an **injected Repository Interface** (e.g., `UserRepositoryInterface`). |
| **`Config::...`** (Facade) | Must use an **injected `SettingsManager`** (from `Nexus\Setting`). |
| **`Mail::...`** (Facade) | Must use an **injected `NotifierInterface`** (from `Nexus\Notifier`). |
| **`Storage::...`** (Facade) | Must use an **injected `StorageInterface`** (from `Nexus\Storage`). |
| **`Event::...`** (Facade) | Must use an **injected `EventDispatcherInterface`** (PSR-14 or package-specific). |
| **`Queue::...`** (Facade) | Must use an **injected `QueueInterface`** (from package Contracts). |
| **Global Helpers** (`now()`, `today()`, `config()`, `app()`, `dd()`, `response()`, `abort()`, `redirect()`, `session()`, `request()`, `auth()`, `bcrypt()`, `collect()`, `env()`, `old()`, `route()`, `url()`, `view()`) | Must use injected services or native PHP functions/classes. |

-----

### 2. ✅ Required Replacements (The "How To")

When the agent attempts to use a forbidden artifact, it must immediately stop and refactor the code to use the following dependency injection pattern:

#### A. Logging Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\Log;

public function processData(array $data): void
{
    Log::info('Processing data', ['count' => count($data)]);
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Modify Constructor:** Inject the required interface.
    ```php
    use Psr\Log\LoggerInterface;

    public function __construct(
        private readonly LoggerInterface $logger // Must be PSR-3 compliant
    ) {}
    ```

2. **Use Injected Dependency:**
    ```php
    public function processData(array $data): void
    {
        $this->logger->info('Processing data', ['count' => count($data)]);
    }
    ```

#### B. Caching Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\Cache;

public function getTenantConfig(string $tenantId): array
{
    return Cache::remember("tenant.{$tenantId}", 3600, function() use ($tenantId) {
        return $this->fetchFromDatabase($tenantId);
    });
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Define Contract in Package:**
    ```php
    namespace Nexus\YourPackage\Contracts;

    interface CacheRepositoryInterface
    {
        public function get(string $key, mixed $default = null): mixed;
        public function put(string $key, mixed $value, int $ttl): bool;
        public function remember(string $key, int $ttl, callable $callback): mixed;
        public function forget(string $key): bool;
    }
    ```

2. **Inject and Use in Service:**
    ```php
    use Nexus\YourPackage\Contracts\CacheRepositoryInterface;

    public function __construct(
        private readonly CacheRepositoryInterface $cache
    ) {}

    public function getTenantConfig(string $tenantId): array
    {
        return $this->cache->remember(
            "tenant.{$tenantId}",
            3600,
            fn() => $this->fetchFromDatabase($tenantId)
        );
    }
    ```

3. **Implement in Atomy (`apps/Atomy/app/Repositories/LaravelCacheRepository.php`):**
    ```php
    namespace App\Repositories;

    use Illuminate\Support\Facades\Cache;
    use Nexus\YourPackage\Contracts\CacheRepositoryInterface;

    final class LaravelCacheRepository implements CacheRepositoryInterface
    {
        public function remember(string $key, int $ttl, callable $callback): mixed
        {
            return Cache::remember($key, $ttl, $callback);
        }
        // ... implement other methods
    }
    ```

#### C. Time/Date Example

**❌ WRONG (Forbidden):**
```php
public function isExpired(): bool
{
    return $this->expiresAt < now(); // 'now()' is a Laravel helper
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Define Clock Contract (Recommended Pattern):**
    ```php
    namespace Nexus\YourPackage\Contracts;

    interface ClockInterface
    {
        public function getCurrentTime(): \DateTimeImmutable;
        public function getCurrentDate(): \DateTimeImmutable;
    }
    ```

2. **Inject and Use in Service:**
    ```php
    use Nexus\YourPackage\Contracts\ClockInterface;

    public function __construct(
        private readonly ClockInterface $clock
    ) {}

    public function isExpired(\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt < $this->clock->getCurrentTime();
    }
    ```

3. **Or Use Native PHP (Simpler for Basic Cases):**
    ```php
    public function isExpired(\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt < new \DateTimeImmutable('now');
    }
    ```

**Note:** The `ClockInterface` pattern is crucial for testing time-sensitive logic without relying on the system clock.

#### D. Configuration Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\Config;

public function getMaxRetries(): int
{
    return config('services.api.max_retries', 3); // Global helper
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Inject Settings Manager:**
    ```php
    use Nexus\Setting\Services\SettingsManager;

    public function __construct(
        private readonly SettingsManager $settings
    ) {}
    ```

2. **Use Injected Dependency:**
    ```php
    public function getMaxRetries(): int
    {
        return $this->settings->getInt('services.api.max_retries', 3);
    }
    ```

#### E. Database Query Example

**❌ WRONG (Forbidden):**
```php
use Illuminate\Support\Facades\DB;

public function getUserCount(): int
{
    return DB::table('users')->count();
}
```

**✅ CORRECT (Framework-Agnostic):**

1. **Define Repository Contract:**
    ```php
    namespace Nexus\YourPackage\Contracts;

    interface UserRepositoryInterface
    {
        public function count(): int;
        public function findById(string $id): ?UserInterface;
    }
    ```

2. **Inject and Use in Service:**
    ```php
    use Nexus\YourPackage\Contracts\UserRepositoryInterface;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function getUserCount(): int
    {
        return $this->userRepository->count();
    }
    ```

#### F. Collection Helper Example

**❌ WRONG (Acceptable but Discouraged):**
```php
$items = collect([1, 2, 3])->map(fn($n) => $n * 2); // Laravel helper
```

**✅ CORRECT (Framework-Agnostic):**
```php
use Illuminate\Support\Collection;

$items = new Collection([1, 2, 3]);
$result = $items->map(fn($n) => $n * 2);
```

**Note:** If using `Illuminate\Support\Collection` directly, ensure `illuminate/collections` is listed in the package's `composer.json` as a dependency. For ultimate framework agnosticism, use native PHP arrays with `array_map()`, `array_filter()`, etc.

-----

### 3. 🔍 Detection and Self-Correction Protocol

Before committing any code to the `packages/` directory, the agent MUST run this mental checklist:

1. **Facade Scan:** Does the code contain any class name ending in `::` that is not a static method call on a package-owned class?
   - If YES → **STOP. Refactor using dependency injection.**

2. **Global Helper Scan:** Does the code contain any of these function calls: `now()`, `config()`, `app()`, `dd()`, `dump()`, `abort()`, `redirect()`, `session()`, `request()`, `auth()`, `bcrypt()`, `collect()`, `env()`, `old()`, `route()`, `url()`, `view()`?
   - If YES → **STOP. Replace with injected services or native PHP.**

3. **Illuminate Namespace Scan:** Does the code import any class from `Illuminate\` **except** `Illuminate\Support\Collection` (and only if it's in `composer.json`)?
   - If YES → **STOP. Replace with PSR interfaces or package contracts.**

4. **Repository Pattern Check:** Does the code directly query a database (e.g., using PDO, Eloquent, or any ORM)?
   - If YES → **STOP. Define a repository interface and inject it.**

5. **Configuration Hardcoding Check:** Does the code contain any hardcoded configuration values that should be externalized?
   - If YES → **Inject `SettingsManager` and retrieve the value dynamically.**

-----

### 4. 📋 Quick Reference: Common Violations and Fixes

| Violation | Forbidden Code | Correct Replacement |
|-----------|----------------|---------------------|
| **Logging** | `Log::info('message')` | `$this->logger->info('message')` (Inject `LoggerInterface`) |
| **Caching** | `Cache::get('key')` | `$this->cache->get('key')` (Inject `CacheRepositoryInterface`) |
| **Database** | `DB::table('users')->get()` | `$this->repository->getAll()` (Inject `RepositoryInterface`) |
| **Config** | `config('app.timezone')` | `$this->settings->getString('app.timezone')` (Inject `SettingsManager`) |
| **Time** | `now()` | `$this->clock->getCurrentTime()` (Inject `ClockInterface`) or `new \DateTimeImmutable()` |
| **Collections** | `collect([1,2,3])` | `new Collection([1,2,3])` (Import and list in `composer.json`) |
| **Environment** | `env('APP_ENV')` | `$this->settings->getString('app.env')` (Inject `SettingsManager`) |
| **Request Data** | `request()->input('name')` | Pass as method parameter: `public function process(string $name)` |
| **Auth** | `auth()->user()` | `$this->authContext->getCurrentUser()` (Inject `AuthContextInterface`) |
| **Storage** | `Storage::put('file.txt', 'content')` | `$this->storage->write('file.txt', 'content')` (Inject `StorageInterface`) |

-----

### 5. ⚠️ Acceptable Exceptions (Rare Cases)

The following are the **ONLY** acceptable uses of Laravel-specific code in packages:

1. **`Illuminate\Support\Collection`** - Only if explicitly listed in the package's `composer.json` dependencies.
2. **`Illuminate\Contracts\Support\Arrayable`** - For compatibility with Laravel's array conversion.
3. **Package Service Provider** - A package MAY include a `ServiceProvider.php` for Laravel integration, but this file must **only** register bindings and should not contain business logic.

-----

### 6. 🎯 Golden Rule Summary

**If you're working in `packages/`, ask yourself:**
> "Could this code run in Symfony, Slim, or even a plain PHP CLI script without any modifications?"

**If the answer is NO, you're violating the framework-agnostic principle.**

The correct approach is:
1. **Define the need** (via an Interface in `src/Contracts/`)
2. **Use the need** (via constructor injection in `src/Services/`)
3. **Implement the need** (in `apps/Atomy/app/Repositories/` or `app/Services/`)

By explicitly defining these forbidden patterns and providing the precise, contract-driven replacements, the agent will significantly improve its adherence to the framework-agnostic architecture.

-----

## 🧠 Thought Process: Application Service Provider Bindings

The primary goal of an Application Service Provider (in `apps/Atomy/app/Providers`) is to act as the **Orchestrator's Configuration Hub**. It wires the application's unique, concrete implementations to the generic contracts defined in the atomic packages.

### Step 1: Verify the Core Nexus Principle (The "Why")

Ask: **What is the job of an application service provider in Nexus?**

  * **Answer:** Its job is to bind **Package Contracts** to **Application Implementations**. It is *not* responsible for telling Laravel how to resolve classes it already knows how to resolve.
  * **The Check:** If a class is a concrete class defined in a package (`Nexus\Tenant\Services\TenantLifecycleService`), Laravel's auto-resolver (Reflection) can handle it unless a dependency requires an interface that hasn't been bound yet.

-----

### Step 2: The Binding Decision Tree (The "What to Bind")

When looking at a class name in a binding, determine its type and origin:

| Decision Rule | Class Type/Origin | Binding Action in `Atomy` Provider |
| :--- | :--- | :--- |
| **Rule A: The Essential Bindings** | **Interface** (from Package Contracts) | **MANDATORY.** Bind to the application's concrete implementation (`DbTenantRepository::class` or `LaravelCacheRepository::class`). |
| **Rule B: The Package's Default** | **Interface** (from Package Contracts) **$\rightarrow$** **Package Concrete Class** | **MANDATORY.** Use this when the package provides a concrete default that the application wants to use (e.g., `TenantContextInterface` $\rightarrow$ `TenantContextManager`). |
| **Rule C: The Redundant Bindings** | **Concrete Class** (from Package Services, e.g., `TenantLifecycleService`) | **REMOVE (Redundant).** Laravel resolves this automatically via IoC. Explicit binding is only needed for mocking or complex initial setup that belongs in the package's provider. |
| **Rule D: Application Utilities** | **Concrete Class** (from App Services, e.g., `App\Services\FileUploader`) | **OPTIONAL.** Only bind if the class has complex constructor arguments or needs to be a singleton. Often, these are auto-resolvable too. |

-----

### Step 3: Self-Correction Checklist

Before finalizing the `register()` method, run this mental checklist:

1.  **Is every Repository Interface bound?** (e.g., `TenantRepositoryInterface` $\rightarrow$ `DbTenantRepository`). **(Must be YES)**
2.  **Does any bound class originate from the `Nexus\...` namespace and *not* implement a contract?** (e.g., binding `TenantImpersonationService::class`). **(Must be NO)**
3.  **If I remove all bindings to concrete package classes, will the application still run?** (Yes, because Laravel's IoC container will automatically construct them, pulling in the dependencies I correctly bound in Step 1).

**Error Correction Example (Tenant Package):**

The agent previously included:

```php
$this->app->singleton(TenantLifecycleService::class);
```

**Correction:** Apply **Rule C**. `TenantLifecycleService` is a concrete class from the package. It must be **removed**. Its dependencies (which are interfaces like `TenantRepositoryInterface`) are already correctly bound, so Laravel handles the rest.

-----

## 🔧 Service Layer Patterns & Best Practices

### Service Organization in Applications

When implementing services in `apps/Atomy/app/Services/`, follow these organizational patterns:

#### 1. Domain-Specific Service Directories

Create domain-specific directories for complex packages that require multiple application-layer services:

```
apps/Atomy/app/Services/
├── Analytics/              # Analytics-specific services
│   └── TrendAnalyzer.php
├── Hrm/                    # HRM-specific services
│   └── LeaveBalanceCalculator.php
├── Inventory/              # Inventory-specific services
│   └── StockLevelMonitor.php
├── Payroll/                # Payroll-specific services
│   └── PayslipGenerator.php
├── Receivable/             # Receivable-specific services
│   └── ReceivableManager.php
├── Sales/                  # Sales-specific services
│   └── SalesOrderProcessor.php
└── Storage/                # Storage-specific services
    └── LocalStorageAdapter.php
```

#### 2. Framework Adapter Services

For framework-specific implementations of package contracts, use clear naming:

- **Pattern:** `Laravel{Purpose}.php` or `{Purpose}Adapter.php`
- **Location:** `app/Services/` (root level for cross-cutting concerns)

**Examples:**
```
LaravelCacheAdapter.php      # Implements CacheRepositoryInterface
LaravelPasswordHasher.php    # Implements PasswordHasherInterface
PeriodAuditLoggerAdapter.php # Implements AuditLoggerInterface
SystemClock.php              # Implements ClockInterface
```

#### 3. Channel/Strategy Implementations

For strategy pattern implementations (notifications, exports, etc.), use dedicated subdirectories:

```
app/Services/Channels/       # Notification channels
├── EmailChannel.php
├── SmsChannel.php
├── PushChannel.php
└── InAppChannel.php
```

### Naming Conventions for Services

| Service Type | Naming Pattern | Example | Location |
|--------------|----------------|---------|----------|
| **Domain Manager** | `{Domain}Manager.php` | `ReceivableManager.php` | `app/Services/{Domain}/` |
| **Laravel Adapter** | `Laravel{Purpose}.php` | `LaravelGeocoder.php` | `app/Services/` |
| **Package Adapter** | `{Package}{Purpose}Adapter.php` | `PeriodAuditLoggerAdapter.php` | `app/Services/` |
| **Strategy Implementation** | `{Strategy}{Type}.php` | `SimpleTaxCalculator.php` | `app/Services/{Domain}/` |
| **Channel Implementation** | `{Channel}Channel.php` | `EmailChannel.php` | `app/Services/Channels/` |
| **Repository** | `Db{Entity}Repository.php` or `Eloquent{Entity}Repository.php` | `DbUserRepository.php` | `app/Repositories/` |

### Service Constructor Best Practices

**✅ CORRECT - All dependencies are interfaces:**
```php
namespace App\Services\Receivable;

final readonly class ReceivableManager implements ReceivableManagerInterface
{
    public function __construct(
        private CustomerInvoiceRepositoryInterface $invoiceRepository,
        private PaymentReceiptRepositoryInterface $receiptRepository,
        private GeneralLedgerManagerInterface $glManager,
        private SequencingManagerInterface $sequencing,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}
}
```

**❌ WRONG - Mixing concrete classes and interfaces:**
```php
public function __construct(
    private DbCustomerInvoiceRepository $invoiceRepository,  // Concrete class!
    private PaymentReceiptRepositoryInterface $receiptRepository,
    private FinanceManager $financeManager,                   // Concrete class!
    private LoggerInterface $logger
) {}
```

-----

## 📦 Advanced Package Patterns

### 1. Cross-Package Integration Adapters

When Package A needs to integrate with Package B, create an adapter in the **application layer** that implements Package A's interface using Package B's services:

**Example:** Sales package needs to create invoices in Receivable package

```
packages/Sales/src/Contracts/InvoiceCreatorInterface.php  # Sales defines what it needs
apps/Atomy/app/Services/Sales/ReceivableInvoiceAdapter.php  # Atomy implements using Receivable
```

**Implementation:**
```php
namespace App\Services\Sales;

use Nexus\Sales\Contracts\InvoiceCreatorInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;

final readonly class ReceivableInvoiceAdapter implements InvoiceCreatorInterface
{
    public function __construct(
        private ReceivableManagerInterface $receivableManager
    ) {}
    
    public function createInvoiceFromOrder(string $orderId): string
    {
        return $this->receivableManager->createInvoiceFromOrder($orderId);
    }
}
```

### 2. Strategy Pattern for Business Rules

Use strategy interfaces for business rules that vary by domain or configuration:

**Package defines the contract:**
```php
namespace Nexus\Receivable\Contracts;

interface PaymentAllocationStrategyInterface
{
    public function allocate(PaymentReceiptInterface $receipt): array;
}
```

**Application provides implementations:**
```
app/Services/Receivable/Strategies/
├── FIFOAllocationStrategy.php         # First-in, first-out
├── OldestInvoiceFirstStrategy.php     # Prioritize oldest
└── ManualAllocationStrategy.php       # User-specified
```

### 3. Default vs. Configured Implementations

For features that have safe defaults but can be enhanced:

**Pattern:**
1. Package provides `DefaultXxxService` for basic functionality
2. Application can bind enhanced implementation when needed
3. Service provider uses conditional binding

**Example from Statutory/Compliance:**
```php
// Default binding (always safe)
$this->app->singleton(
    PayrollStatutoryInterface::class,
    DefaultStatutoryCalculator::class
);

// Override if Malaysia package is enabled
if (config('features.malaysia_payroll')) {
    $this->app->singleton(
        PayrollStatutoryInterface::class,
        MYSStatutoryCalculator::class
    );
}
```

### 4. Observability Integration Pattern (Monitoring)

**CRITICAL:** The `Nexus\Monitoring` package provides universal observability contracts. **NEVER** create custom metrics collectors or monitoring interfaces.

#### The Correct Pattern

**Package Layer (Framework-Agnostic):**
```php
namespace Nexus\EventStream\Services;

use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;

final readonly class EventStreamManager
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private ?TelemetryTrackerInterface $telemetry = null  // Optional dependency
    ) {}
    
    public function appendEvent(string $stream, EventInterface $event): void
    {
        $startTime = microtime(true);
        
        $this->eventStore->append($stream, $event);
        
        // Track metrics using universal interface
        $this->telemetry?->timing(
            'event_append_duration_ms',
            (microtime(true) - $startTime) * 1000,
            ['stream_name' => $stream]
        );
        
        $this->telemetry?->increment(
            'events_appended_total',
            1,
            ['stream_name' => $stream]
        );
    }
}
```

**Application Layer (Vendor-Specific Adapter):**
```php
namespace App\Services\Monitoring;

use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Prometheus\CollectorRegistry;

final readonly class PrometheusTelemetryAdapter implements TelemetryTrackerInterface
{
    public function __construct(
        private CollectorRegistry $registry
    ) {}
    
    public function timing(string $key, float $ms, array $tags = [], ...): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            'eventstream',
            $key,
            'Timing metric',
            array_keys($tags)
        );
        $histogram->observe($ms, array_values($tags));
    }
    
    public function increment(string $key, float $value = 1.0, array $tags = [], ...): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'eventstream',
            $key,
            'Counter metric',
            array_keys($tags)
        );
        $counter->incBy($value, array_values($tags));
    }
    
    // ... implement other methods
}
```

**Service Provider Binding:**
```php
namespace App\Providers;

class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind universal interface to vendor-specific implementation
        $this->app->singleton(
            TelemetryTrackerInterface::class,
            PrometheusTelemetryAdapter::class
        );
    }
}
```

#### Key Benefits

1. **Vendor Independence**: Switching from Prometheus to DataDog/New Relic only requires changing the adapter
2. **Package Purity**: Domain packages have zero knowledge of monitoring vendor
3. **Testability**: Inject null or mock `TelemetryTrackerInterface` in tests
4. **Consistency**: All packages use the same observability interface

#### Common Violations to Avoid

❌ **WRONG:** Creating package-specific metrics interfaces
```php
namespace Nexus\EventStream\Contracts;

interface MetricsCollectorInterface  // ❌ Duplicates Nexus\Monitoring
{
    public function recordEventAppended(...);
}
```

❌ **WRONG:** Coupling package to specific monitoring vendor
```php
use Prometheus\Counter;  // ❌ Vendor-specific in package layer

class EventStreamManager
{
    public function __construct(private Counter $counter) {}
}
```

✅ **CORRECT:** Use universal interface with optional injection
```php
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;

class EventStreamManager
{
    public function __construct(
        private ?TelemetryTrackerInterface $telemetry = null
    ) {}
}
```

#### Observability Best Practices

1. **Make telemetry optional**: Use `?TelemetryTrackerInterface $telemetry = null` for optional dependency
2. **Use null-safe operator**: `$this->telemetry?->increment(...)` prevents errors when monitoring disabled
3. **Tag everything**: Always include relevant tags (tenant_id, stream_name, operation_type)
4. **Measure durations**: Track timing for performance-sensitive operations
5. **Count events**: Increment counters for business metrics (events_appended, errors_total)
6. **Set gauges**: Record point-in-time values (projection_lag_seconds, queue_size)

-----

## 🔍 Code Quality Checklist

Before committing code to the repository, verify:

### For Packages (`packages/*/src/`)
- [ ] **Consulted [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md)** to avoid reimplementing existing functionality
- [ ] No Laravel facades used (`Log::`, `Cache::`, `DB::`, etc.)
- [ ] No global helpers used (`now()`, `config()`, `app()`, `dd()`, etc.)
- [ ] All dependencies injected via constructor as **interfaces**
- [ ] All properties are `readonly` (for PHP 8.3+)
- [ ] Native enums used instead of class constants
- [ ] `declare(strict_types=1);` at top of every file
- [ ] All public methods have complete docblocks
- [ ] Custom exceptions thrown for domain errors
- [ ] No direct database access (use Repository interfaces)
- [ ] If tracking metrics, uses `TelemetryTrackerInterface` from `Nexus\Monitoring` (never custom metrics interface)

### For Application Layer (`apps/Atomy/`)
- [ ] All package interfaces implemented with concrete classes
- [ ] Repository classes properly implement repository interfaces
- [ ] Service provider bindings follow the Decision Tree rules (A, B, C, D)
- [ ] No redundant bindings for concrete package services
- [ ] Migrations use ULID for primary keys
- [ ] Models implement package entity interfaces
- [ ] API controllers use package services (not repositories directly)

### For Service Classes
- [ ] All constructor parameters are interfaces (not concrete classes)
- [ ] Service located in appropriate directory (domain-specific or root)
- [ ] Follows naming convention (`{Domain}Manager`, `Laravel{Purpose}`, etc.)
- [ ] Implements an interface from the package layer
- [ ] Uses dependency injection (no service locator pattern)
- [ ] Logs important operations to `LoggerInterface`
- [ ] Calls `AuditLogManagerInterface` for audit trail

-----

## Testing Approach

- Package tests should be unit tests (no database)
- Mock repository implementations in package tests
- Atomy tests can be feature tests (with database)
- Test contract implementations in Atomy
- Test API endpoints in Atomy
- Test commands in Edward

## Key Reminders

1. **Packages are engines**: Pure logic, no persistence
2. **Atomy is the implementation**: Database, models, API
3. **Edward is the demo client**: Terminal UI, no database access
4. **Always check ARCHITECTURE.md** before making architectural decisions
5. **When in doubt, put logic in packages, implementation in apps**

## Important Documentation
- Package README files (e.g., `packages/AuditLogger/README.md`)
- Architecture guidelines (`ARCHITECTURE.md`)
- Requirements and implementation docs in `docs/` folder
- Consolidated Requirements in `REQUIREMENTS.csv`
- All package implementation must have  its PACKAGE_NAME_IMPLEMENTATION_SUMMARY.md file in docs folder
- ALL new package must have REQUIREMENTS_PACKAGE_NAME.md file in docs folder
- All new addition or deletion to packages must be reflected in its respective REQUIREMENTS and IMPLEMENTATION_SUMMARY files
- All postponed implementation or planned implementation must have the placeholder methods/classes/properties with proper docblock in the respective package service class commented out and marked with `// TODO: Implement ...`