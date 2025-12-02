# Nexus Architecture: Package-Only Monorepo Architectural Guidelines

This document outlines the architecture of the **Nexus** package monorepo. Its purpose is to enforce a clean, scalable, and framework-agnostic package structure. Adhering to these rules is mandatory for all development.

**The Core Philosophy: "Pure Business Logic in Framework-Agnostic Packages"**

Our architecture is built on one primary concept: **Framework Agnosticism**.

  * **`📦 packages/`** contain pure, framework-agnostic business logic packages
  * These packages can be integrated into **any PHP framework** (Laravel, Symfony, Slim, etc.)
  * Consuming applications provide concrete implementations via dependency injection

## What is Nexus?

Nexus is a **package-only monorepo** containing 52 atomic, reusable PHP packages for building ERP systems. Each package is:

- **Framework-agnostic** - Works with Laravel, Symfony, or any PHP framework
- **Publishable** - Can be published to Packagist independently
- **Contract-driven** - Defines needs via interfaces, consumers provide implementations
- **Stateless** - No session state, all long-term state externalized
- **Testable** - Pure business logic with mockable dependencies

## Package Inventory (52 Atomic Packages)

### Foundation & Infrastructure (9 packages)
1. **Nexus\Tenant** - Multi-tenancy context and isolation with queue propagation
2. **Nexus\Sequencing** - Auto-numbering with atomic counter management
3. **Nexus\Period** - Fiscal period management for compliance
4. **Nexus\Uom** - Unit of measurement conversions
5. **Nexus\AuditLogger** - Timeline feeds and audit trails
6. **Nexus\EventStream** - Event sourcing for critical domains (Finance GL, Inventory)
7. **Nexus\Setting** - Application settings management
8. **Nexus\Monitoring** - Observability with telemetry, health checks, alerting, SLO tracking
9. **Nexus\FeatureFlags** - Feature flag management and A/B testing

### Identity & Security (3 packages)
10. **Nexus\Identity** - Authentication, RBAC, MFA, session/token management
11. **Nexus\Crypto** - Cryptographic operations and key management
12. **Nexus\Audit** - Advanced audit capabilities (extends AuditLogger)

### Finance & Accounting (7 packages)
13. **Nexus\Finance** - General ledger, journal entries, double-entry bookkeeping
14. **Nexus\Accounting** - Financial statements, period close, consolidation
15. **Nexus\Receivable** - Customer invoicing, collections, credit control
16. **Nexus\Payable** - Vendor bills, payment processing, 3-way matching
17. **Nexus\CashManagement** - Bank reconciliation, cash flow forecasting
18. **Nexus\Budget** - Budget planning and variance tracking
19. **Nexus\Assets** - Fixed asset management, depreciation

### Sales & Operations (6 packages)
20. **Nexus\Sales** - Quotation-to-order lifecycle, pricing engine
21. **Nexus\Inventory** - Stock management with lot/serial tracking (depends on Uom)
22. **Nexus\Warehouse** - Warehouse operations and bin management
23. **Nexus\Procurement** - Purchase requisitions, POs, goods receipt
24. **Nexus\Manufacturing** - Versioned BOMs with effectivity, MRP II, capacity planning, ML forecasting
25. **Nexus\Product** - Product catalog, pricing, categorization

### Human Resources (3 packages)
26. **Nexus\Hrm** - Leave, attendance, performance reviews
27. **Nexus\Payroll** - Payroll processing framework
28. **Nexus\PayrollMysStatutory** - Malaysian statutory calculations (EPF, SOCSO, PCB)

### Customer & Partner Management (4 packages)
29. **Nexus\Party** - Customers, vendors, employees, contacts
30. **Nexus\Crm** - Leads, opportunities, sales pipeline
31. **Nexus\Marketing** - Campaigns, A/B testing, GDPR compliance
32. **Nexus\FieldService** - Work orders, technicians, service contracts

### Integration & Automation (8 packages)
33. **Nexus\Connector** - Integration hub with circuit breaker, OAuth
34. **Nexus\Workflow** - Process automation, state machines
35. **Nexus\Notifier** - Multi-channel notifications (email, SMS, push, in-app)
36. **Nexus\Scheduler** - Task scheduling and job management
37. **Nexus\DataProcessor** - OCR, ETL interfaces (interface-only package)
38. **Nexus\MachineLearning** - ML orchestration: anomaly detection via AI providers (OpenAI, Anthropic, Gemini), local inference (PyTorch, ONNX), MLflow integration
39. **Nexus\Geo** - Geocoding, geofencing, routing
40. **Nexus\Routing** - Route optimization and caching

### Reporting & Data (6 packages)
41. **Nexus\Reporting** - Report definition and execution engine
42. **Nexus\Export** - Multi-format export (PDF, Excel, CSV, JSON)
43. **Nexus\Import** - Data import with validation and transformation
44. **Nexus\Analytics** - Business intelligence, predictive models
45. **Nexus\Currency** - Multi-currency management, exchange rates
46. **Nexus\Document** - Document management with versioning

### Compliance & Governance (4 packages)
47. **Nexus\Compliance** - Process enforcement, operational compliance
48. **Nexus\Statutory** - Reporting compliance, statutory filing
49. **Nexus\Backoffice** - Company structure, offices, departments
50. **Nexus\OrgStructure** - Organizational hierarchy management

### Support & Utilities (2 packages)
51. **Nexus\Storage** - File storage abstraction layer
52. **Nexus\ProjectManagement** - Projects, tasks, timesheets, milestones

**Total Packages:** 52 atomic, framework-agnostic packages

**Package Dependencies:** Packages may depend on other packages (e.g., `Inventory` requires `Uom`, `Receivable` requires `Finance`, `Sales`, `Party`). All dependencies must be explicit in `composer.json`.

---

## 1. 🌲 Monorepo Structure

```
nexus/
├── .gitignore
├── composer.json               # Root monorepo workspace (defines 'path' repositories)
├── ARCHITECTURE.md             # (This document)
├── README.md
│
├── packages/                 # 54 Atomic, publishable PHP packages (FLAT STRUCTURE)
│   ├── README.md                # Package layer guidelines and inventory
│   │
│   ├── Common/            # Nexus\Common (Common building blocks)
│   │   ├── composer.json        # NO business logic, NO dependencies on other packages
│   │   ├── README.md
│   │   ├── LICENSE
│   │   └── src/
│   │       ├── Contracts/       # Common interfaces (LoggerInterface, etc.)
│   │       ├── ValueObjects/    # Shared VOs (TenantId, Money, Period, etc.)
│   │       └── Traits/          # Reusable traits
│   │
│   ├── Tenant/                  # Example: Simple Atomic Package (Flat Structure)
│   │   ├── composer.json        # Package metadata, dependencies, autoloading
│   │   ├── README.md            # Package documentation with usage examples
│   │   ├── LICENSE              # MIT License
│   │   ├── IMPLEMENTATION_SUMMARY.md  # Implementation progress tracking
│   │   ├── REQUIREMENTS.md      # Detailed requirements table
│   │   ├── TEST_SUITE_SUMMARY.md      # Test coverage and results
│   │   ├── VALUATION_MATRIX.md  # Package valuation metrics
│   │   ├── CHANGELOG.md         # Version history
│   │   ├── UPGRADE.md           # Upgrade instructions
│   │   ├── CONTRIBUTING.md      # Contribution guidelines
│   │   ├── SECURITY.md          # Security policy
│   │   ├── CODE_OF_CONDUCT.md   # Code of conduct
│   │   ├── .gitignore           # Git ignore file
│   │   └── src/                 # Source code root
│   │       ├── Contracts/       # REQUIRED: Interfaces
│   │       │   ├── TenantInterface.php         # Entity contract
│   │       │   ├── TenantQueryInterface.php    # Read operations (CQRS)
│   │       │   ├── TenantPersistInterface.php  # Write operations (CQRS)
│   │       │   └── TenantContextInterface.php  # Service contract
│   │       ├── Exceptions/      # REQUIRED: Domain exceptions
│   │       │   ├── TenantNotFoundException.php
│   │       │   └── InvalidTenantException.php
│   │       ├── Services/        # REQUIRED: Business logic
│   │       │   ├── TenantContextManager.php
│   │       │   └── TenantLifecycleService.php
│   │       ├── Enums/           # RECOMMENDED: Native PHP enums
│   │       │   └── TenantStatus.php
│   │       └── ValueObjects/    # RECOMMENDED: Immutable domain objects
│   │           └── TenantConfiguration.php
│   │
│   ├── Inventory/               # Example: Complex Atomic Package (DDD-Layered)
│   │   ├── composer.json
│   │   ├── README.md
│   │   ├── LICENSE
│   │   ├── CHANGELOG.md
│   │   ├── UPGRADE.md
│   │   ├── CONTRIBUTING.md
│   │   ├── SECURITY.md
│   │   ├── CODE_OF_CONDUCT.md
│   │   ├── IMPLEMENTATION_SUMMARY.md
│   │   ├── REQUIREMENTS.md
│   │   ├── TEST_SUITE_SUMMARY.md
│   │   ├── VALUATION_MATRIX.md
│   │   ├── .gitignore
│   │   └── src/
│   │       ├── Domain/          # THE TRUTH (Pure Logic)
│   │       │   ├── Entities/    # Pure PHP Entities (Not Eloquent)
│   │       │   ├── ValueObjects/
│   │       │   ├── Events/
│   │       │   ├── Contracts/   # Repository Interfaces
│   │       │   ├── Services/    # Domain Services
│   │       │   ├── Policies/    # Business Rules
│   │       │   └── Exceptions/    # Exceptions specific to Domain layer
│   │       ├── Application/     # THE USE CASES
│   │       │   ├── DTOs/        # Data Transfer Objects
│   │       │   ├── Commands/    # e.g., ReceiveStockCommand
│   │       │   ├── Queries/     # e.g., GetStockLevelQuery
│   │       │   ├── Handlers/    # Orchestrates Domain Services
│   │       │   └── Exceptions/    # Exceptions specific to Application layer
│   │       └── Infrastructure/  # INTERNAL ADAPTERS (Optional)
│   │           ├── InMemory/    # In-memory repos for testing
│   │           ├── Mappers/     # Domain to DTO mapping
│   │           └── Exceptions/         # Infrastructure Exceptions
│   │
│   ├── Finance/
│   ├── Receivable/
│   ├── Payable/
│   └── [... 51 more packages - ALL FLAT, NO NESTING]
│
├── orchestrators/            # Cross-package workflow coordination (PURE PHP)
│   ├── README.md                # Orchestrator layer guidelines
│   │
│   ├── IdentityOperations/      # Example: Multi-package workflow orchestrator
│   │   ├── composer.json        # Depends on: Nexus/Identity, Nexus/Party, etc.
│   │   ├── README.md
│   │   ├── LICENSE
│   │   ├── IMPLEMENTATION_SUMMARY.md
│   │   ├── REQUIREMENTS.md
│   │   ├── TEST_SUITE_SUMMARY.md
│   │   ├── VALUATION_MATRIX.md
│   │   ├── CHANGELOG.md
│   │   ├── UPGRADE.md
│   │   ├── CONTRIBUTING.md
│   │   ├── SECURITY.md
│   │   ├── CODE_OF_CONDUCT.md
│   │   ├── .gitignore
│   │   └── src/
│   │       ├── Workflows/       # Stateful processes (Sagas)
│   │       │   └── UserRegistration/
│   │       ├── Coordinators/    # Stateless orchestration
│   │       │   └── RegistrationCoordinator.php
│   │       ├── Listeners/       # Event handlers
│   │       │   └── SendWelcomeEmailOnRegistered.php
│   │       ├── Contracts/       # Workflow interfaces
│   │       ├── DTOs/            # Process-specific data transfer
│   │       └── Exceptions/      # Workflow-specific errors
│   │
│   └── [... more orchestrators as needed]
│
└── adapters/                 # Framework-specific implementations (ONLY place for framework code)
    ├── README.md                # Adapter layer guidelines
    │
    └── Laravel/                 # Laravel-specific adapters
        ├── Finance/             # Finance package Laravel adapter
        │   ├── composer.json    # Requires: Nexus/Finance, illuminate/database
        │   ├── README.md
        │   └── src/
        │       ├── Providers/   # ServiceProviders (bind interfaces)
        │       ├── Models/      # Eloquent Models
        │       ├── Repositories/ # Concrete repository implementations
        │       ├── Database/
        │       │   ├── Migrations/
        │       │   └── Seeders/
        │       ├── Http/
        │       │   ├── Controllers/
        │       │   └── Resources/
        │       └── Jobs/        # Laravel queued jobs
        │
        └── [... more domain adapters]
```

---

## 2. 📦 Three-Layer Architecture

Nexus follows a strict three-layer architecture to separate concerns:

### Layer 1: Atomic Packages (`packages/`) - Pure Business Logic

**Purpose:** Framework-agnostic, reusable business logic components.

**Characteristics:**
- ✅ Pure PHP 8.3+ (no framework dependencies)
- ✅ Stateless architecture (externalize state via interfaces)
- ✅ Contract-driven (define interfaces, consumers implement)
- ✅ Publishable to Packagist independently
- ✅ Testable with mocks (no database required)
- ❌ NO framework code (no Eloquent, Symfony, Laravel)
- ❌ NO database migrations or seeds
- ❌ NO HTTP controllers or routes

**Two Package Patterns:**

#### Pattern A: Simple Atomic Packages (Flat Structure)

For packages with low cyclomatic complexity or pure utility functions.

**Examples:** `Nexus\Uom`, `Nexus\Sequencing`, `Nexus\Notifier`, `Nexus\Storage`

**Structure:**
```
packages/PackageName/
├── composer.json            # Require: "php": "^8.3"
├── README.md                # Usage examples, API reference
├── LICENSE                  # MIT License
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── CHANGELOG.md
├── UPGRADE.md
├── CONTRIBUTING.md
├── SECURITY.md
├── CODE_OF_CONDUCT.md
├── .gitignore
├── src/
│   ├── Contracts/          # Interfaces only
│   ├── Services/           # Stateless business logic
│   ├── ValueObjects/       # Immutable data objects
│   ├── Enums/              # Native PHP enums
│   └── Exceptions/         # Domain-specific errors
└── tests/
```

**Rule:** Do not over-engineer these. The flat structure works perfectly here.

#### Pattern B: Complex Atomic Packages (DDD-Layered)

For heavy business domains requiring strict boundaries.

**Examples:** `Nexus\Inventory`, `Nexus\Finance`, `Nexus\Manufacturing`, `Nexus\Receivable`

**Structure:**
```
packages/PackageName/
├── composer.json
├── README.md
├── LICENSE
├── CHANGELOG.md
├── UPGRADE.md
├── CONTRIBUTING.md
├── SECURITY.md
├── CODE_OF_CONDUCT.md
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── .gitignore
├── src/
│   ├── Domain/              # THE TRUTH (Pure Logic)
│   │   ├── Entities/        # Pure PHP Entities (Not Eloquent)
│   │   ├── ValueObjects/    # Domain-specific immutable objects
│   │   ├── Events/          # Domain events
│   │   ├── Contracts/       # Repository interfaces
│   │   ├── Services/        # Domain services (e.g., StockCalculator)
│   │   └── Policies/        # Business rules
│   │
│   ├── Application/         # THE USE CASES
│   │   ├── DTOs/            # Input/Output data transfer
│   │   ├── Commands/        # Write operations (e.g., ReceiveStockCommand)
│   │   ├── Queries/         # Read operations (e.g., GetStockLevelQuery)
│   │   └── Handlers/        # Orchestrates domain services
│   │
│   └── Infrastructure/      # INTERNAL ADAPTERS (Optional)
│       ├── InMemory/        # In-memory repos for unit testing
│       └── Mappers/         # Domain objects to DTOs/arrays
└── tests/
```

**Rules:**
- **NEVER** put Laravel/Symfony code here
- The `Infrastructure` folder is for *internal* package infrastructure only (like in-memory stores)
- Database implementations belong in `adapters/Laravel/PackageName/`

### Layer 2: Orchestrators (`orchestrators/`) - Workflow Coordination

**Purpose:** Orchestrate workflows that cross multiple atomic packages.

**Characteristics:**
- ✅ Pure PHP (still framework-agnostic)
- ✅ Depends on multiple atomic packages
- ✅ Owns "Flow" (processes), not "Truth" (entities)
- ✅ Implements Saga patterns for distributed transactions
- ✅ Event-driven coordination
- ❌ Does NOT define core entities (those belong in atomic packages)
- ❌ Does NOT access databases directly (uses repository interfaces)
- ❌ NO framework code (controllers, jobs, routes)

**Examples:** `Nexus\IdentityOperations`, `Nexus\OrderManagement`, `Nexus\ProcurementManagement`

**Structure:**
```
orchestrators/OrchestratorName/
├── composer.json            # Depends on: Nexus/Sales, Nexus/Inventory, etc.
├── README.md                # Workflow diagrams and documentation
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── CHANGELOG.md
├── UPGRADE.md
├── CONTRIBUTING.md
├── SECURITY.md
├── CODE_OF_CONDUCT.md
├── .gitignore
├── src/
│   ├── Workflows/           # Stateful processes (Sagas, state machines)
│   │   └── ProcessName/
│   │       ├── Steps/       # Individual workflow steps
│   │       └── States/      # Process states
│   │
│   ├── Coordinators/        # Stateless orchestration (synchronous)
│   │   └── FulfillmentCoordinator.php
│   │
│   ├── Listeners/           # Reactive logic (event subscribers)
│   │   └── TriggerShippingOnPaymentCaptured.php
│   │
│   ├── Contracts/           # Workflow-specific interfaces
│   ├── DTOs/                # Process-specific data transfer
│   └── Exceptions/          # Workflow-specific errors
└── tests/
```

**Component Definitions:**

| Component | Purpose | Example |
|-----------|---------|---------|
| **Workflows/** | Long-running processes that track state | `OrderToCashWorkflow` - knows that after Payment, then Shipping |
| **Coordinators/** | Synchronous glue that calls multiple packages in order | `FulfillmentCoordinator::fulfill()` calls Inventory + Sales + Notifier |
| **Listeners/** | Reactive handlers for events from atomic packages | `TriggerShippingOnPaymentCaptured` listens to `PaymentCaptured` event |

**Decision Rule:** If code defines "what a thing is" → Atomic Package. If code defines "how it moves through the system" → Orchestrator.

### Layer 3: Adapters (`adapters/`) - Framework-Specific Implementations

**Purpose:** Concrete implementations of interfaces defined in atomic packages. The **ONLY** place where framework code is allowed.

**Characteristics:**
- ✅ Implements repository interfaces using Eloquent/Doctrine
- ✅ Contains database migrations and seeders
- ✅ Provides HTTP controllers and API resources
- ✅ Handles framework-specific jobs/queues
- ✅ THIS IS THE ONLY PLACE FOR `use Illuminate\...` or `use Symfony\...`
- ❌ Does NOT contain business logic (that's in atomic packages)
- ❌ Does NOT define domain entities (those are in atomic packages)

**Examples:** `Laravel/Finance`, `Laravel/Inventory`, `Laravel/Identity`

**Structure:**
```
adapters/Laravel/PackageName/
├── composer.json            # Requires: Nexus/PackageName, illuminate/database
├── README.md
├── LICENSE
├── src/
│   ├── Providers/           # ServiceProviders (bind interfaces)
│   │   └── PackageNameServiceProvider.php
│   │
│   ├── Models/              # Eloquent models (persistence only)
│   │   └── Account.php      # extends Illuminate\Database\Eloquent\Model
│   │
│   ├── Repositories/        # Concrete repository implementations
│   │   └── EloquentAccountRepository.php  # implements AccountRepositoryInterface
│   │
│   ├── Database/
│   │   ├── Migrations/      # Schema definitions
│   │   └── Seeders/         # Test data
│   │
│   ├── Http/
│   │   ├── Controllers/     # API/Web controllers
│   │   └── Resources/       # API response transformers
│   │
│   ├── Jobs/                # Laravel queued jobs
│   └── Exceptions/          # Laravel-specific exceptions
└── tests/                   # Integration tests (require database)
```

**Dependency Direction:**
- `adapters/` → depends on → `packages/` ✅
- `packages/` → **NEVER** depends on → `adapters/` ❌
- `apps/` → depends on → `adapters/` AND `packages/` ✅

### The "Use" Test

If you can use the code in a generic PHP script without `composer require laravel/framework`, it belongs in `packages/` or `orchestrators/`.

If it requires `artisan`, `Eloquent`, `Blade`, or framework-specific features, it belongs in `adapters/Laravel/`.

---

## 3. 📋 Documentation Standards

Every package (atomic or orchestrator) **MUST** include these 13 files:

### Required Files (13 total)

1. **`composer.json`** - Package metadata (require `"php": "^8.3"`)
2. **`README.md`** - Comprehensive usage guide with examples
3. **`LICENSE`** - MIT License
4. **`IMPLEMENTATION_SUMMARY.md`** - Implementation progress tracking
5. **`REQUIREMENTS.md`** - Detailed requirements table
6. **`TEST_SUITE_SUMMARY.md`** - Test coverage and results
7. **`VALUATION_MATRIX.md`** - Package valuation metrics
8. **`CHANGELOG.md`** - Version history
9. **`UPGRADE.md`** - Upgrade instructions between versions
10. **`CONTRIBUTING.md`** - Contribution guidelines
11. **`SECURITY.md`** - Security policy and vulnerability reporting
12. **`CODE_OF_CONDUCT.md`** - Code of conduct for contributors
13. **`.gitignore`** - Package-specific ignores

**Reference:** See `packages/README.md`, `orchestrators/README.md`, and `adapters/README.md` for detailed guidelines on each layer.

---

## 4. 📦 Package Development Rules

### The Golden Rule: Framework Agnosticism

> A package must be a **pure PHP engine** that works with any framework.

**NEVER:**
- Use Laravel-specific classes (`Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, facades)
- Include database migrations or schema definitions
- Use global helpers (`config()`, `app()`, `now()`, `dd()`, `env()`)
- Reference framework components (`Route::`, `DB::`, `Cache::`, `Log::`)
- Depend on application-specific code

**ALWAYS:**
- Write pure PHP 8.3+ code
- Define persistence needs via **Contracts (Interfaces)**
- Use dependency injection via constructor
- Use `readonly` properties for injected dependencies
- Use constructor property promotion
- Use `declare(strict_types=1);` at top of every file
- Make packages publishable (include composer.json, LICENSE, README.md)

**ACCEPTABLE:**
- PSR interfaces (`psr/log`, `psr/http-client`, `psr/cache`)
- Light dependency on `illuminate/support` for Collections (avoid if possible)
- Requiring other Nexus packages (explicit in composer.json)

## 4. 📦 Package Development Rules

### The Golden Rule: Framework Agnosticism

> A package must be a **pure PHP engine** that works with any framework.

**NEVER:**
- Use Laravel-specific classes (`Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, facades)
- Include database migrations or schema definitions
- Use global helpers (`config()`, `app()`, `now()`, `dd()`, `env()`)
- Reference framework components (`Route::`, `DB::`, `Cache::`, `Log::`)
- Depend on application-specific code

**ALWAYS:**
- Write pure PHP 8.3+ code
- Define persistence needs via **Contracts (Interfaces)**
- Use dependency injection via constructor
- Use `readonly` properties for injected dependencies
- Use constructor property promotion
- Use `declare(strict_types=1);` at top of every file
- Make packages publishable (include composer.json, LICENSE, README.md)

**ACCEPTABLE:**
- PSR interfaces (`psr/log`, `psr/http-client`, `psr/cache`)
- Light dependency on `illuminate/support` for Collections (avoid if possible)
- Requiring other Nexus packages (explicit in composer.json)

### Package Structure Requirements

**REQUIRED Components:**
1. **`src/Contracts/`** - All interfaces (Repository, Manager, Entity)
2. **`src/Services/`** - Business logic (Managers, Coordinators)
3. **`src/Exceptions/`** - Domain-specific exceptions
4. **13 Mandatory Files** - composer.json, README.md, LICENSE, IMPLEMENTATION_SUMMARY.md, REQUIREMENTS.md, TEST_SUITE_SUMMARY.md, VALUATION_MATRIX.md, CHANGELOG.md, UPGRADE.md, CONTRIBUTING.md, SECURITY.md, CODE_OF_CONDUCT.md, .gitignore

**RECOMMENDED Components:**
1. **`src/Enums/`** - Native PHP enums for statuses, types, levels
2. **`src/ValueObjects/`** - Immutable domain objects (Money, Period, etc.)

**OPTIONAL Components (Complex Packages Only):**
1. **`src/Domain/`** - Domain layer (Entities, ValueObjects, Events, Contracts, Services, Policies)
2. **`src/Application/`** - Use case layer (DTOs, Commands, Queries, Handlers)
3. **`src/Infrastructure/`** - Internal adapters (InMemory repositories, Mappers)

### When to Use Domain/Application/Infrastructure Layers (DDD)

Create `src/Domain/`, `src/Application/`, `src/Infrastructure/` when your package:

**Use DDD Layers when:**
- High complexity (Analytics, Workflow, Manufacturing, Finance, Receivable)
- Package has > 15 files
- Main service class > 300 lines
- Multiple bounded contexts within the package
- Complex business rules requiring separation
- Event sourcing or CQRS patterns needed

**Skip DDD Layers when:**
- Package has < 15 files
- Simple business logic (Uom, Sequencing, Tenant)
- Utility package with no complex domain
- All components are public API

### Orchestrator Development Rules

Orchestrators are **still framework-agnostic** like packages:

**REQUIRED for Orchestrators:**
- Pure PHP 8.3+ (NO framework code)
- Depend on multiple atomic packages via interfaces
- Define workflow contracts (WorkflowInterface, SagaInterface)
- Use event-driven coordination
- Include 13 mandatory documentation files

**FORBIDDEN in Orchestrators:**
- Framework facades or global helpers
- Direct database access (use package repositories)
- Defining core entities (those belong in atomic packages)
- Application-layer code (HTTP controllers, jobs)

**Components:**
- `Workflows/` - Long-running stateful processes (Saga pattern)
- `Coordinators/` - Synchronous stateless orchestration
- `Listeners/` - Event handlers that trigger cross-package actions
- `Contracts/` - Workflow-specific interfaces
- `DTOs/` - Process-level data transfer objects
- `Exceptions/` - Workflow-specific errors

### Adapter Development Rules

Adapters are **the ONLY place** where framework code is allowed:

**REQUIRED for Adapters:**
- Implement package interfaces using framework features
- Include database migrations and seeders
- Provide HTTP controllers and API resources
- Handle framework-specific jobs/queues
- Include 13 mandatory documentation files

**ALLOWED in Adapters:**
- `use Illuminate\...` (Laravel)
- `use Symfony\...` (Symfony)
- Eloquent models extending `Model`
- Framework facades (only in adapters!)
- Global helpers (only in adapters!)
- Application-specific code

**Components:**
- `Providers/` - ServiceProviders (bind package interfaces to concrete implementations)
- `Models/` - Eloquent models (persistence only, no business logic)
- `Repositories/` - Concrete repository implementations
- `Database/` - Migrations, seeders
- `Http/` - Controllers, resources, requests
- `Jobs/` - Laravel queued jobs

---

## 5. 🏛️ Architectural Patterns

### 3.1 Contract-Driven Design

All external dependencies must be defined as interfaces:

```php
// Package defines what it needs
namespace Nexus\Receivable\Contracts;

interface GeneralLedgerIntegrationInterface
{
    public function postJournalEntry(JournalEntry $entry): void;
}

// Consumer application implements using another package
namespace App\Services\Receivable;

use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

final readonly class FinanceGLAdapter implements GeneralLedgerIntegrationInterface
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager
    ) {}
    
    public function postJournalEntry(JournalEntry $entry): void
    {
        $this->glManager->post($entry);
    }
}
```

### 3.2 Stateless Package Architecture

Packages must be stateless across execution cycles:

**WRONG:**
```php
// ❌ State stored in package class
final class CircuitBreaker
{
    private array $states = []; // Lost when instance destroyed!
}
```

**CORRECT:**
```php
// ✅ State externalized via interface
final readonly class CircuitBreaker
{
    public function __construct(
        private CircuitBreakerStorageInterface $storage // Redis, Database, etc.
    ) {}
    
    public function getState(string $connectionId): string
    {
        return $this->storage->get("circuit:{$connectionId}");
    }
}
```

**Rule:** Any long-term state (cache, counters, flags) must be delegated to an injected `StorageInterface`.

### 3.3 Hybrid Event Architecture

Nexus uses two event patterns for different needs:

**A. Timeline Feed (`Nexus\AuditLogger`) - 95% of use cases**
- User-facing activity timelines
- Records outcomes ("Invoice paid")
- Simple chronological display
- Used for: HR, Settings, Workflows, Customer records

**B. Event Sourcing (`Nexus\EventStream`) - Critical domains only**
- Immutable event log for compliance
- State reconstruction at any point in time
- Used for: Finance GL (SOX/IFRS), Inventory (stock accuracy)

**Decision Rule:** Use EventStream only when you need temporal queries for legal compliance.

### 3.4 Repository Interface Design Principles

#### The Baseline: Query and Persist

All packages start with two foundational repository interfaces:

1. **`EntityQueryInterface`** - Read operations (findById, findAll, etc.)
2. **`EntityPersistInterface`** - Write operations (create, update, delete)

This separation enforces **CQRS (Command Query Responsibility Segregation)** at the interface level.

#### When to Create Additional Repository Interfaces

Beyond Query and Persist, create additional repository interfaces based on these four factors:

##### 1. Behavioral Intent (What are we doing?)

Separates analytical/reporting actions from transactional data access.

| Factor | Description | Example Repository | Justification |
|--------|-------------|-------------------|---------------|
| **Search/Indexing** | Full-text search, fuzzy matching, relevance ranking (ElasticSearch, Algolia) | `ContentSearchInterface` | Decouples search engine implementation from domain queries. Prevents `ContentQueryInterface` from search-specific methods (relevance boosting, fuzzy matching). |
| **Bulk Processing** | Large-scale data retrieval for background jobs, exports, ETL | `PayslipStreamInterface` | Optimizes I/O with generators/lazy collections. Different concern than synchronous `PayslipQueryInterface`. |

##### 2. Aggregate Boundary (Ensuring Consistency)

Ensures repository manages a specific unit of consistency (Aggregate Root) per DDD principles.

| Factor | Description | Example Repository | Justification |
|--------|-------------|-------------------|---------------|
| **Relationship Management** | Fetching related data managed by different package (cross-aggregate queries) | `CaseMessagingInterface` | Enforces boundaries. Core `CaseRepository` manages `Ticket` VO. Separate interface manages associated `MessageRecords` from `Nexus\Messaging`. Prevents package bloat. |
| **Data Context** | Same data with different context/representation (Draft vs Published) | `DraftRepositoryInterface` | Enforces consistency. Services requiring *only* draft content cannot accidentally access published data. |

##### 3. External System Dependency (Connector Pattern)

Used when "persistence" interacts with third-party APIs with transient/external storage.

| Factor | Description | Example Repository | Justification |
|--------|-------------|-------------------|---------------|
| **Connector/Gateway** | Abstract third-party API (payment gateways, communication providers) | `MessagingConnectorInterface` | Decouples vendor. Abstracts Twilio/Meta/SendGrid complexity. Core `Nexus\Messaging` only deals with standardized VOs, not vendor-specific API structures. |

##### 4. Write Intent (Specialized Modifications)

Isolates specific, non-standard modification patterns outside simple CRUD.

| Factor | Description | Example Repository | Justification |
|--------|-------------|-------------------|---------------|
| **Transaction/State Change** | Atomic state changes not bundled with general `update()` | `CaseTransitionInterface` | Granular control. `CaseStatusService` depends *only* on state transition capability, not full entity modification. Improves security and auditability. |

#### Decision Matrix: Should I Create a New Repository Interface?

Ask these questions:

1. **Is this a read operation?** → Use `EntityQueryInterface`
2. **Is this a write operation?** → Use `EntityPersistInterface`
3. **Does this require specialized search/indexing?** → Create `EntitySearchInterface`
4. **Is this for bulk/streaming data?** → Create `EntityStreamInterface`
5. **Does this cross aggregate boundaries?** → Create relationship-specific interface
6. **Is this interfacing with external system?** → Create connector interface
7. **Is this a specific state transition?** → Create transition interface

**Golden Rule:** Every repository interface must serve a distinct, justifiable purpose following SRP and ISP.

### 3.5 Compliance & Statutory Separation

**`Nexus\Compliance`** - Process Enforcement
- Controls system behavior (e.g., mandatory approvals)
- Feature composition via flags/licenses
- Example: ISO 14001 forces environmental fields

**`Nexus\Statutory`** - Reporting Compliance
- Defines report formats for authorities
- Pluggable country-specific implementations
- Example: Malaysian EPF/SOCSO/PCB calculations

---

## 4. 🔒 Architectural Constraints

### 4.1 Forbidden Artifacts in Packages

| Forbidden | Use Instead |
|-----------|-------------|
| `Log::...` | Inject `Psr\Log\LoggerInterface` |
| `Cache::...` | Inject `CacheRepositoryInterface` |
| `DB::...` | Inject `RepositoryInterface` |
| `Config::...` | Inject `SettingsManagerInterface` |
| `Storage::...` | Inject `StorageInterface` |
| `now()` | Inject `ClockInterface` or use `new \DateTimeImmutable()` |
| `config()` | Inject `SettingsManagerInterface` |
| `dd()`, `dump()` | Use PSR-3 Logger or throw exceptions |

### 4.2 PHP 8.3+ Requirements

**MUST use:**
1. Constructor property promotion
2. `readonly` modifier for all injected dependencies
3. Native PHP enums (not class constants)
4. `match` expression (not `switch`)
5. Type hints for all parameters and returns
6. `declare(strict_types=1);` at top of file

**Example:**
```php
declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Nexus\Inventory\Contracts\StockRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class StockManager
{
    public function __construct(
        private StockRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}
    
    public function addStock(string $productId, float $quantity): void
    {
        // Implementation
    }
}
```

### 4.3 Data Sovereignty

**Primary Keys:**
- All primary keys use ULIDs (26-character UUID v4 strings)
- Never use auto-incrementing integers
- Benefits: Distributed generation, no collisions, sortable

**Multi-Tenancy:**
- All business entities should support tenant_id (defined in consumer migrations)
- Packages define need via contracts, consumers implement isolation
- Queue jobs should preserve tenant context (defined in consumer implementations)

---

## 5. 📐 Package Dependencies

### 5.1 Dependency Graph

**Foundation Layer (Zero internal dependencies):**
- Tenant, Sequencing, Uom, Setting, Crypto

**Core Business Layer:**
- Period → Tenant, Sequencing, AuditLogger
- Finance → Period, Currency, Party, Sequencing
- Identity → Tenant

**Domain Layer:**
- Receivable → Finance, Sales, Party, Currency, Period, Sequencing
- Payable → Finance, Party, Currency, Period, Sequencing
- Inventory → Uom, EventStream (optional)

**Integration Layer:**
- Connector → Crypto, Storage, AuditLogger
- Notifier → Connector, Identity

**Orchestrator Layer:**
- IdentityOperations → Identity, Party, Notifier, AuditLogger
- OrderManagement → Sales, Inventory, Finance, Notifier
- ProcurementManagement → Procurement, Inventory, Payable, Notifier

**Adapter Layer:**
- Laravel/Finance → Finance (package), illuminate/database, illuminate/support
- Laravel/Inventory → Inventory (package), illuminate/database
- Laravel/Identity → Identity (package), illuminate/database, illuminate/auth

### 5.2 Dependency Direction Rules

**CRITICAL: Strict dependency flow must be enforced.**

```
┌─────────────────┐
│  adapters/      │ ← Application Layer (Framework-Specific)
└────────┬────────┘
         │ depends on (✅)
         ▼
┌─────────────────┐
│ orchestrators/  │ ← Workflow Coordination Layer (Pure PHP)
└────────┬────────┘
         │ depends on (✅)
         ▼
┌─────────────────┐
│  packages/      │ ← Business Logic Layer (Pure PHP)
└─────────────────┘
```

**Allowed Dependencies:**
- ✅ `adapters/` → `orchestrators/` (adapters can call orchestrators)
- ✅ `adapters/` → `packages/` (adapters MUST implement package interfaces)
- ✅ `orchestrators/` → `packages/` (orchestrators coordinate multiple packages)
- ✅ `packages/` → `packages/` (packages can depend on other packages)

**FORBIDDEN Dependencies:**
- ❌ `packages/` → `orchestrators/` (packages NEVER know about orchestrators)
- ❌ `packages/` → `adapters/` (packages NEVER know about framework)
- ❌ `orchestrators/` → `adapters/` (orchestrators remain framework-agnostic)

**Example Violations:**
```php
// ❌ WRONG: Package depending on orchestrator
namespace Nexus\Finance\Services;

use Nexus\OrderManagement\Coordinators\OrderCoordinator; // VIOLATION!

final readonly class GeneralLedgerManager
{
    public function __construct(
        private OrderCoordinator $orderCoordinator // ❌ Package depends on orchestrator
    ) {}
}

// ✅ CORRECT: Package defines interface, orchestrator calls package
namespace Nexus\Finance\Contracts;

interface GeneralLedgerManagerInterface
{
    public function postJournalEntry(JournalEntry $entry): void;
}

// Orchestrator injects package interface
namespace Nexus\OrderManagement\Coordinators;

use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

final readonly class OrderCoordinator
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager // ✅ Orchestrator depends on package
    ) {}
}
```

### 5.3 Circular Dependency Prevention

**Rule:** Package A cannot depend on Package B if B already depends on A.

**Solution:** Use interfaces and adapter pattern in consuming application:
```php
// Finance defines interface
namespace Nexus\Finance\Contracts;
interface GLManagerInterface { }

// Receivable depends on interface (not concrete Finance package)
namespace Nexus\Receivable\Contracts;
interface GLIntegrationInterface extends GLManagerInterface { }

// Consumer application binds them together
// App\Providers\AppServiceProvider
$this->app->bind(GLIntegrationInterface::class, FinanceGLManager::class);
```

### 5.4 Common Special Rules

**Common is the only exception to dependency rules:**

- ✅ ALL packages can depend on Common
- ✅ ALL orchestrators can depend on Common
- ❌ Common MUST NOT depend on ANY package
- ❌ Common MUST NOT depend on ANY orchestrator

**Why:** Common contains common building blocks (TenantId, Money, Period VOs, common interfaces). If Common depends on other packages, it creates circular dependencies.

**Example:**
```php
// ✅ CORRECT: Package depends on Common
namespace Nexus\Finance;

use Nexus\Common\ValueObjects\Money;
use Nexus\Common\ValueObjects\Period;

// ❌ WRONG: Common depends on package
namespace Nexus\Common\ValueObjects;

use Nexus\Finance\Contracts\CurrencyManagerInterface; // VIOLATION!
```

---

## 6. 🧪 Testing Strategy

### 6.1 Package Unit Tests

**Requirements:**
- Test business logic in isolation
- Mock all external dependencies (use interfaces)
- No database or framework required
- Fast execution (< 1 second per test)

**Example:**
```php
final class StockManagerTest extends TestCase
{
    public function test_add_stock_increases_quantity(): void
    {
        $repository = $this->createMock(StockRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $manager = new StockManager($repository, $logger);
        
        // Test business logic
    }
}
```

### 6.2 Consumer Integration Tests

Consuming applications test:
- Repository implementations
- Database migrations
- API endpoints
- Cross-package integration

---

## 7. 🚨 Architectural Violations & Prevention

This section documents common architectural violations discovered in the codebase and provides guidance for prevention.

### 7.1 Interface Segregation Principle (ISP) Violations

**Problem:** "Fat" interfaces with too many responsibilities force consumers to implement methods they don't need.

#### ❌ VIOLATION EXAMPLE: Fat Repository Interface

```php
// WRONG: Single interface with 15+ methods covering 5 responsibilities
interface TenantRepositoryInterface
{
    // CRUD operations (Persistence)
    public function create(array $data): TenantInterface;
    public function update(string $id, array $data): TenantInterface;
    public function delete(string $id): bool;
    
    // Query operations (Read)
    public function findById(string $id): ?TenantInterface;
    public function findByCode(string $code): ?TenantInterface;
    public function all(): array;
    
    // Validation (Business logic)
    public function codeExists(string $code): bool;
    public function domainExists(string $domain): bool;
    
    // Business logic filtering (Domain service)
    public function getExpiredTrials(): array;
    public function getSuspendedTenants(): array;
    public function getStatistics(): array;
    
    // Pagination (Read model)
    public function paginate(int $page, int $perPage): array;
}
```

**Problems with this design:**
1. **Violation of Single Responsibility:** Mixes write, read, validation, business logic, and pagination
2. **Tight Coupling:** Changes to one responsibility affect all consumers
3. **Cannot Mock Granularly:** Test doubles must implement all 15+ methods
4. **CQRS Violation:** No separation between commands (write) and queries (read)

#### ✅ CORRECT: Split into Focused Interfaces (ISP Compliant)

```php
// WRITE OPERATIONS ONLY (CQRS Command Model)
interface TenantPersistenceInterface
{
    public function create(array $data): TenantInterface;
    public function update(string $id, array $data): TenantInterface;
    public function delete(string $id): bool;
    public function forceDelete(string $id): bool;
    public function restore(string $id): bool;
}

// READ OPERATIONS ONLY (CQRS Query Model)
interface TenantQueryInterface
{
    public function findById(string $id): ?TenantInterface;
    public function findByCode(string $code): ?TenantInterface;
    public function findByDomain(string $domain): ?TenantInterface;
    public function findBySubdomain(string $subdomain): ?TenantInterface;
    public function all(): array; // Returns raw array, no pagination in domain layer
    public function getChildren(string $parentId): array;
}

// VALIDATION OPERATIONS ONLY
interface TenantValidationInterface
{
    public function codeExists(string $code, ?string $excludeId = null): bool;
    public function domainExists(string $domain, ?string $excludeId = null): bool;
}

// BUSINESS LOGIC (Domain Service - NOT in repository)
final readonly class TenantStatusService
{
    public function __construct(
        private TenantQueryInterface $query
    ) {}
    
    public function getExpiredTrials(): array
    {
        // Business logic filtering using query interface
        $trials = array_filter(
            $this->query->all(),
            fn($t) => $t->isTrial() && $this->isExpired($t)
        );
        return array_values($trials);
    }
    
    public function getSuspendedTenants(): array { /* ... */ }
    public function getStatistics(): array { /* ... */ }
}
```

**Benefits:**
- **Single Responsibility:** Each interface has one clear purpose
- **Loose Coupling:** Services inject only what they need
- **Testability:** Mock only the interface methods you're testing
- **CQRS Compliance:** Clear separation between write and read models
- **Maintainability:** Changes to validation don't affect persistence

#### Service Usage Example

```php
// Service only needs write operations
final readonly class TenantLifecycleService
{
    public function __construct(
        private TenantPersistenceInterface $persistence, // Only write ops
        private TenantQueryInterface $query,              // Only read ops
        private TenantValidationInterface $validation,    // Only validation
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger()
    ) {}
    
    public function createTenant(string $code, string $name, ...): TenantInterface
    {
        // Validate (uses TenantValidationInterface)
        if ($this->validation->codeExists($code)) {
            throw new DuplicateTenantCodeException();
        }
        
        // Persist (uses TenantPersistenceInterface)
        $tenant = $this->persistence->create(['code' => $code, 'name' => $name]);
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new TenantCreatedEvent($tenant));
        
        return $tenant;
    }
}
```

---

### 7.2 CQRS (Command Query Responsibility Segregation) Violations

**Problem:** Mixing write operations (commands) with read operations (queries) in the same interface/service.

#### ❌ VIOLATION EXAMPLE: Mixed Read/Write in Repository

```php
// WRONG: Single interface handles both commands and queries
interface InvoiceRepositoryInterface
{
    // Commands (Write Model)
    public function create(array $data): InvoiceInterface;
    public function update(string $id, array $data): InvoiceInterface;
    
    // Queries (Read Model)
    public function findById(string $id): ?InvoiceInterface;
    public function findOverdue(): array;
    
    // VIOLATION: Pagination in domain layer
    public function paginate(int $page, int $perPage, array $filters): array;
    
    // VIOLATION: Reporting query with joins
    public function getAgingReport(string $tenantId): array;
}
```

**Problems:**
1. **No Clear Separation:** Write and read operations intermingled
2. **Pagination in Domain Layer:** Domain layer should return raw collections
3. **Reporting Logic in Repository:** Complex queries belong in read models

#### ✅ CORRECT: Separate Write and Read Models

```php
// WRITE MODEL (Commands)
interface InvoicePersistenceInterface
{
    public function create(array $data): InvoiceInterface;
    public function update(string $id, array $data): InvoiceInterface;
    public function delete(string $id): bool;
}

// READ MODEL (Queries)
interface InvoiceQueryInterface
{
    public function findById(string $id): ?InvoiceInterface;
    public function findByNumber(string $number): ?InvoiceInterface;
    public function all(): array; // Raw array, no pagination
}

// APPLICATION LAYER: Read Model for Reporting (outside package)
interface InvoiceReadModelInterface
{
    // Pagination is application layer concern
    public function paginate(int $page, int $perPage, array $filters): PaginatedResult;
    
    // Complex reporting queries
    public function getAgingReport(string $tenantId): array;
    public function getRevenueByMonth(string $tenantId, int $year): array;
}
```

**Rule:** Domain layer (package) provides raw operations. Application layer adds pagination, filtering, reporting.

---

### 7.3 Stateful Service Violations

**Problem:** Services storing state in-memory that should persist across requests/processes.

#### ❌ VIOLATION EXAMPLE: In-Memory State

```php
// WRONG: Impersonation state stored in service instance
final class TenantImpersonationService
{
    private ?string $originalTenantId = null;      // Lost when instance destroyed!
    private ?string $impersonatedTenantId = null;
    private ?string $impersonatorId = null;
    
    public function impersonate(string $tenantId, string $userId): void
    {
        $this->originalTenantId = $this->contextManager->getCurrentTenantId();
        $this->impersonatedTenantId = $tenantId;
        $this->impersonatorId = $userId;
        
        $this->contextManager->setTenant($tenantId);
    }
    
    public function stopImpersonation(): void
    {
        // PROBLEM: What if service instance was destroyed and recreated?
        $this->contextManager->setTenant($this->originalTenantId);
    }
}
```

**Problems:**
1. **State Lost on Destruction:** PHP services can be recreated between requests
2. **Not Scalable:** Won't work across load-balanced servers
3. **No Queue Support:** Background jobs can't access impersonation context

#### ✅ CORRECT: Externalized State via Interface

```php
// Define storage contract
interface ImpersonationStorageInterface
{
    public function store(string $key, string $originalTenantId, string $targetTenantId, ?string $impersonatorId): void;
    public function retrieve(string $key): ?array;
    public function clear(string $key): void;
    public function isActive(string $key): bool;
}

// Stateless service
final readonly class TenantImpersonationService
{
    public function __construct(
        private TenantQueryInterface $tenantQuery,
        private TenantContextManager $contextManager,
        private ImpersonationStorageInterface $storage, // State externalized
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger()
    ) {}
    
    public function impersonate(string $storageKey, string $tenantId, string $impersonatorId): void
    {
        $originalTenantId = $this->contextManager->getCurrentTenantId();
        $targetTenant = $this->tenantQuery->findById($tenantId);
        
        // Store state externally (session, cache, database)
        $this->storage->store($storageKey, $originalTenantId, $tenantId, $impersonatorId);
        
        $this->contextManager->setTenant($tenantId);
        
        $this->eventDispatcher->dispatch(
            new ImpersonationStartedEvent($originalTenant, $targetTenant, $impersonatorId)
        );
    }
    
    public function stopImpersonation(string $storageKey): void
    {
        $data = $this->storage->retrieve($storageKey);
        
        if ($data) {
            $this->contextManager->setTenant($data['original_tenant_id']);
            $this->storage->clear($storageKey);
        }
    }
}
```

**Benefits:**
- **Persistent State:** Survives service recreation
- **Scalable:** Works across load-balanced servers
- **Queue-Compatible:** Background jobs can access context
- **Testable:** Storage can be mocked

---

### 7.4 Framework References in Docblocks

**Problem:** Package code references framework-specific implementations in comments.

#### ❌ VIOLATION EXAMPLE

```php
/**
 * This interface must be implemented using Eloquent models.
 * 
 * @see \Illuminate\Database\Eloquent\Model
 */
interface TenantRepositoryInterface
{
    /**
     * Uses Laravel's cache system.
     */
    public function findById(string $id): ?TenantInterface;
}
```

#### ✅ CORRECT: Framework-Agnostic Documentation

```php
/**
 * Defines contract for tenant data persistence operations.
 * 
 * Consuming applications must provide concrete implementation
 * using their chosen ORM (Doctrine, Eloquent, etc.).
 */
interface TenantPersistenceInterface
{
    /**
     * Creates a new tenant.
     * 
     * @param array $data Tenant data
     * @return TenantInterface Created tenant entity
     * @throws \RuntimeException If creation fails
     */
    public function create(array $data): TenantInterface;
}
```

---

### 7.5 Checklist: Preventing Architectural Violations

Before committing package code, verify:

#### ISP Compliance
- [ ] No interface has more than 7 methods
- [ ] Each interface has single, clear responsibility
- [ ] Write operations separated from read operations
- [ ] Validation operations in separate interface
- [ ] Business logic in domain services, not repositories

#### CQRS Compliance
- [ ] Write operations (create, update, delete) in `*PersistenceInterface`
- [ ] Read operations (find, get, all) in `*QueryInterface`
- [ ] No pagination in domain layer (returns raw arrays)
- [ ] Reporting queries in application layer read models

#### Stateless Architecture
- [ ] No private properties storing long-term state
- [ ] All persistent state externalized via `*StorageInterface`
- [ ] Services are `readonly` (all dependencies immutable)
- [ ] Request-scoped state (e.g., current tenant) is acceptable

#### Framework Agnosticism
- [ ] No framework references in docblocks
- [ ] No framework facades or global helpers
- [ ] All dependencies injected as interfaces
- [ ] Package works with any PHP framework

---

## 8. 🚀 Development Workflow

### Creating a New Package

1. **Create directory:** `packages/NewPackage/`
2. **Initialize composer:** `cd packages/NewPackage && composer init`
   - Name: `nexus/new-package`
   - Require: `"php": "^8.3"`
   - PSR-4: `"Nexus\\NewPackage\\": "src/"`
3. **Create structure:**
   - `src/Contracts/` - Define interfaces
   - `src/Services/` - Implement business logic
   - `src/Exceptions/` - Domain exceptions
   - `src/Enums/` - Status enums
   - `README.md` - Usage documentation
   - `LICENSE` - MIT License
4. **Update root composer.json:** Add to repositories array
5. **Install in monorepo:** `composer require nexus/new-package:"*@dev"`

### Adding a Feature to Existing Package

1. **Check NEXUS_PACKAGES_REFERENCE.md** - Avoid duplication
2. **Define contracts** - Create/update interfaces
3. **Implement services** - Add business logic
4. **Create exceptions** - Domain-specific errors
5. **Write tests** - Unit tests for all logic
6. **Document** - Update README.md

---

## 9. 📊 Code Quality Checklist

Before committing to any package:

- [ ] Consulted `docs/NEXUS_PACKAGES_REFERENCE.md`
- [ ] No framework facades or global helpers
- [ ] All dependencies are interfaces
- [ ] All properties are `readonly`
- [ ] Native enums used for fixed values
- [ ] `declare(strict_types=1);` present
- [ ] Complete docblocks on public methods
- [ ] Custom exceptions for domain errors
- [ ] No direct database access
- [ ] Package has valid composer.json
- [ ] Package has all 13 mandatory files:
  - [ ] `composer.json`
  - [ ] `README.md`
  - [ ] `LICENSE`
  - [ ] `IMPLEMENTATION_SUMMARY.md`
  - [ ] `REQUIREMENTS.md`
  - [ ] `TEST_SUITE_SUMMARY.md`
  - [ ] `VALUATION_MATRIX.md`
  - [ ] `CHANGELOG.md`
  - [ ] `UPGRADE.md`
  - [ ] `CONTRIBUTING.md`
  - [ ] `SECURITY.md`
  - [ ] `CODE_OF_CONDUCT.md`
  - [ ] `.gitignore`
- [ ] Unit tests written and passing

---

## 10. 📚 Documentation Requirements

Each package must include:

**README.md:**
- Package purpose and capabilities
- Installation instructions
- Usage examples with code
- Contract descriptions
- Integration guide for consumers
- Available enums and value objects

**Inline Documentation:**
- DocBlocks on all public methods
- `@param`, `@return`, `@throws` annotations
- Description of business logic
- Examples for complex methods

---

## 11. 🔄 Publishing Workflow

Before publishing a package to Packagist:

1. **Complete test coverage** - All business logic tested
2. **Comprehensive README** - Usage examples included
3. **Semantic versioning** - Follow SemVer (1.0.0, 1.1.0, 2.0.0)
4. **License file** - MIT License included
5. **No application dependencies** - Only PSR and other Nexus packages
6. **Git tag** - Tag release in repository
7. **CHANGELOG.md** - Document all changes

---

## 12. Key Principles Summary

1. **Packages are pure engines** - Logic only, no persistence or framework coupling
2. **Interfaces define needs** - Every dependency is an interface
3. **Consumers provide implementations** - Applications bind contracts to concrete classes
4. **Framework agnostic** - Works with Laravel, Symfony, or any PHP framework
5. **Three-layer architecture** - Packages (logic), Orchestrators (workflows), Adapters (framework)
6. **Dependency direction** - Adapters → Orchestrators → Packages (never reverse)
7. **Stateless design** - Long-term state externalized via interfaces
8. **PHP 8.3+ modern** - Use latest language features
9. **Always check NEXUS_PACKAGES_REFERENCE.md** - Avoid reinventing functionality
10. **13 mandatory documentation files** - Every package must be comprehensively documented

---

**Last Updated:** November 26, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Mandatory for all developers
