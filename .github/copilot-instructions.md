# EVERY LINE AND DETAIL IN THIS FILE MUST BE FULLY UNDERSTOOD AND FOLLOWED BY THE CODING AGENT. DO NOT SKIM OR IGNORE ANY PART OF THIS FILE.

# GitHub Copilot Instructions for Nexus Package Monorepo

## 🚨 MANDATORY PRE-IMPLEMENTATION CHECKLIST

**BEFORE implementing ANY feature, you MUST:**

1. **Consult [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md)** - This document lists all 50+ available first-party packages and their capabilities
2. **Use existing packages FIRST** - If a Nexus package provides the functionality, you MUST use it via dependency injection
3. **Never reimplement package functionality** - Creating custom implementations when packages exist is an architectural violation

**Example Violations to Avoid:**
- ❌ Creating custom metrics collector when `Nexus\Monitoring` exists
- ❌ Building custom audit logger when `Nexus\AuditLogger` exists  
- ❌ Implementing file storage when `Nexus\Storage` exists
- ❌ Creating notification system when `Nexus\Notifier` exists

**See [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md) for the complete "I Need To..." decision matrix.**

---

## Project Overview

You are working on **Nexus**, a **package-only monorepo** containing 50+ framework-agnostic PHP packages for ERP systems. This project is strictly focused on **atomic, reusable packages** that can be integrated into any PHP framework (Laravel, Symfony, Slim, etc.).

## Core Philosophy

**Framework Agnosticism is Mandatory.** The monorepo contains:

- **📦 `packages/`**: Pure, framework-agnostic business logic packages (the core focus)
- **📄 `docs/`**: Comprehensive implementation guides and API documentation
- **🧪 `tests/`**: Package-level unit and integration tests

**NO application layer. NO Laravel-specific code. Pure PHP packages only.**

## Directory Structure

```
nexus/
├── packages/               # 50+ Atomic, publishable PHP packages
│   ├── Accounting/         # Financial accounting
│   ├── Analytics/          # Business intelligence
│   ├── Assets/             # Fixed asset management
│   ├── AuditLogger/        # Audit logging (timeline/feed views)
│   ├── Backoffice/         # Company structure
│   ├── Budget/             # Budget planning
│   ├── CashManagement/     # Bank reconciliation
│   ├── Compliance/         # Compliance engine
│   ├── Connector/          # Integration hub
│   ├── Crm/                # Customer relationship management
│   ├── Crypto/             # Cryptographic operations
│   ├── Currency/           # Multi-currency management
│   ├── DataProcessor/      # Data processing (OCR, ETL)
│   ├── Document/           # Document management
│   ├── EventStream/        # Event sourcing engine
│   ├── Export/             # Multi-format export
│   ├── FeatureFlags/       # Feature flag management
│   ├── FieldService/       # Field service management
│   ├── Finance/            # General ledger
│   ├── Geo/                # Geocoding and geofencing
│   ├── Hrm/                # Human resources
│   ├── Identity/           # Authentication & authorization
│   ├── Import/             # Data import
│   ├── Intelligence/       # AI-assisted automation
│   ├── Inventory/          # Inventory management
│   ├── Manufacturing/      # Production management
│   ├── Marketing/          # Marketing campaigns
│   ├── Monitoring/         # Observability & telemetry
│   ├── Notifier/           # Multi-channel notifications
│   ├── OrgStructure/       # Organizational hierarchy
│   ├── Party/              # Customer/vendor management
│   ├── Payable/            # Accounts payable
│   ├── Payroll/            # Payroll processing
│   ├── PayrollMysStatutory/ # Malaysian payroll statutory
│   ├── Period/             # Fiscal period management
│   ├── Procurement/        # Purchase management
│   ├── Product/            # Product catalog
│   ├── ProjectManagement/  # Project tracking
│   ├── Receivable/         # Accounts receivable
│   ├── Reporting/          # Report engine
│   ├── Routing/            # Route optimization
│   ├── Sales/              # Sales order management
│   ├── Scheduler/          # Task scheduling
│   ├── Sequencing/         # Auto-numbering
│   ├── Setting/            # Settings management
│   ├── Statutory/          # Statutory reporting
│   ├── Storage/            # File storage abstraction
│   ├── Tenant/             # Multi-tenancy
│   ├── Uom/                # Unit of measurement
│   ├── Warehouse/          # Warehouse management
│   └── Workflow/           # Workflow engine
├── docs/                   # Implementation guides & references
└── composer.json           # Monorepo package registry
```

## Critical Architectural Rules

### 🔑 The Golden Rule: Framework Agnosticism

Your package must be a **pure PHP engine** that is ignorant of any specific framework.

- **Logic Over Implementation:** The package defines *what* needs to be done (the logic), not *how* it's done (the framework-specific implementation).
- **NEVER Reference a Framework:** Do not use any classes, facades, or components specific to Laravel, Symfony, or any other framework
- **No Persistence:** Packages must not contain database migrations or concrete database querying logic. They only define the **interfaces** needed for persistence.

### When Working in `packages/`

**ALWAYS:**
- Write pure PHP 8.3+ code
- Define persistence needs via **Contracts (Interfaces)** only
- Use dependency injection via constructor
- Make packages publishable (include composer.json, LICENSE, README.md)
- Place interfaces in `src/Contracts/`
- Place business logic in `src/Services/`
- Place exceptions in `src/Exceptions/`
- Use **Value Objects** (immutable objects) for domain data types
- Use **Enums** for fixed value sets (statuses, levels, types)
- Use `readonly` properties for all injected dependencies
- Use constructor property promotion
- Use `declare(strict_types=1);` at the top of every file

**NEVER:**
- Use Laravel-specific classes like `Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, or facades
- Include database migrations or schema definitions
- Create Eloquent models or concrete database logic
- Use `Route::`, `DB::`, `Cache::`, or any other framework facades
- Use global helpers like `config()`, `app()`, `now()`, `dd()`, `env()`
- Reference any application-specific code

**ACCEPTABLE:**
- Light dependency on `illuminate/support` for Collections and Contracts (but avoid if possible)
- Framework-agnostic libraries like `psr/log`, `psr/http-client`, `psr/cache`
- Requiring other atomic packages (e.g., `nexus/inventory` can require `nexus/uom`)

## 🔄 Hybrid Approach: Feed vs. Replay (AuditLogger vs. EventStream)

The Nexus packages implement a **Hybrid Architecture** for event tracking and state reconstruction:

### The "Feed" View: `Nexus\AuditLogger` (Standard Approach - 95% of Records)

**Purpose:** User-facing timeline/feed displaying "what happened" on an entity's page.

**Use Case:** Customer records, HR data, settings, inventory adjustments, approval workflows.

**Mechanism:**
- Domain packages call `AuditLogManagerInterface::log()` after transaction commit
- Records the **result** of an action (e.g., "Invoice status changed to Paid")
- Simple to query and display in chronological order
- Human-readable descriptions for non-technical users

**Example:**
```php
$this->auditLogger->log(
    $entityId,
    'status_change',
    'Invoice status updated from Draft to Paid by User'
);
```

### The "Replay" Capability: `Nexus\EventStream` (Event Sourcing - Critical Domains Only)

**Purpose:** Immutable event log enabling **state reconstruction** at any point in history.

**Use Case (Critical Domains Only):**
- **Finance (GL)**: Every debit/credit is an event (`AccountCreditedEvent`, `AccountDebitedEvent`)
- **Inventory**: Every stock change is an event (`StockReservedEvent`, `StockAddedEvent`, `StockShippedEvent`)
- **Large Enterprise AP/AR**: Optional event sourcing for payment lifecycle tracking

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

## 🛡️ Statutory and Compliance Architecture

All compliance activities are divided into two distinct packages:

### A. 🛡️ `Nexus\Compliance` (The Orchestrator & Rulebook)

Manages **Operational Compliance** and the **System's internal governance**. It deals with the mandatory *behavior* and *configuration* required by a scheme (e.g., ISO, internal policy).

### B. 💰 `Nexus\Statutory` (The Contract Hub & Reporter)

Manages **Reporting Compliance** and the specific formats mandated by a legal authority. It deals with the data tags, schemas, and logistical metadata required for filing.

## 🚫 The Principle of Atomic Package Statelessness

All packages **must be stateless** across execution cycles. An instance of a service class must contain only **immutable dependencies** (interfaces) and **ephemeral, runtime data** required for the current method execution.

**Key Rule:** Any service requiring long-term state (e.g., Circuit Breaker thresholds, Rate Limiter counters, Idempotency keys) **must** accept a `StorageInterface` dependency in its constructor. State management is delegated to an external store (Redis, Database, etc.) via interfaces.

## Package Design Principles

### Contract-Driven Design

- **Define Needs, Not Solutions:** Use **Interfaces (`Contracts/`)** to define every external dependency
- **One Interface Per Responsibility:** Keep interfaces small and focused
- **The Consumer Implements:** Remember that the consuming application is responsible for providing concrete implementations

### Clear Separation of Concerns

| Folder | Rule of Thumb | Example Content |
|--------|---------------|-----------------|
| **`src/Services/`** | **Public API:** Only expose the high-level logic users need | Managers, Coordinators, Façade accessors |
| **`src/Core/`** | **Internal Engine:** Complex internal logic not part of the public API | Internal Contracts, Value Objects, Engine components |
| **`src/Exceptions/`** | **Domain-Specific Errors:** Custom exceptions | All exceptions extending PHP base exceptions |
| **`src/Enums/`** | **Fixed Value Sets:** Native PHP enums for statuses, types | Status, Level, Type enums |
| **`src/ValueObjects/`** | **Immutable Domain Data:** Money, Period, Coordinates | All readonly classes with validation |

### Dependency Management

- **Limit External Dependencies:** Keep `composer.json` lean - only include truly necessary dependencies
- **Internal Dependencies are Fine:** A package can require another Nexus package
- **NEVER Depend on Framework Code:** No Laravel, Symfony, or framework-specific dependencies

## Package Structure Template

```
packages/NewPackage/
├── composer.json              # Package definition (require "php": "^8.3")
├── README.md                  # Package documentation
├── LICENSE                    # MIT License
└── src/
    ├── Contracts/             # REQUIRED: Interfaces
    │   ├── EntityInterface.php
    │   ├── RepositoryInterface.php
    │   └── ManagerInterface.php
    ├── Exceptions/            # REQUIRED: Domain exceptions
    │   └── EntityNotFoundException.php
    ├── Services/              # REQUIRED: Business logic
    │   └── EntityManager.php
    ├── Enums/                 # RECOMMENDED: Native PHP enums
    │   └── EntityStatus.php
    ├── ValueObjects/          # RECOMMENDED: Immutable domain data
    │   └── Money.php
    ├── Core/                  # OPTIONAL: Internal engine
    │   ├── Engine/
    │   ├── ValueObjects/
    │   └── Entities/
    └── ServiceProvider.php    # OPTIONAL: Framework integration helper
```

## Package Organization: When to Use `Core/` Folder

Create a `Core/` folder when your package is **complex** and contains internal components that should **never be accessed directly** by consumers.

**When to Create:**
- High complexity (Analytics, Workflow, Manufacturing)
- Internal contracts for engine components
- Value Objects or Internal Entities that should only be handled by the main Manager
- Engine logic where the main Manager is merely an orchestrator

**When to Skip:**
- Simple packages (Uom, Tenant)
- Fewer than 10 total files
- Manager class under 200 lines

## ✨ Modern PHP 8.3+ Standards

**MUST strictly adhere to:**

1. **Constructor Property Promotion:** Use for all injected dependencies
2. **`readonly` Modifier:** All properties defined via property promotion MUST be `readonly`
3. **Native PHP Enums:** Use `enum` (backed by `int` or `string`) for fixed value sets
4. **`match` Expression:** Use exclusively instead of `switch`
5. **New/Throw in Expressions:** Use for simplified conditional logic
6. **Attributes Over DocBlocks:** Use native PHP Attributes for metadata

## 🚫 Strict Anti-Pattern: Facade & Global Helper Prohibition

The use of framework Facades and global helpers is **strictly forbidden** in all code within the `packages/` directory.

### Absolute Prohibitions (Zero Tolerance)

| Forbidden Artifact | Atomic Replacement |
| :--- | :--- |
| **`Log::...`** | Inject `LoggerInterface` (PSR-3) |
| **`Cache::...`** | Inject `CacheRepositoryInterface` |
| **`DB::...`** | Inject `RepositoryInterface` |
| **`Config::...`** | Inject `SettingsManagerInterface` |
| **`Mail::...`** | Inject `NotificationManagerInterface` |
| **`Storage::...`** | Inject `StorageInterface` |
| **`Event::...`** | Inject `EventDispatcherInterface` |
| **`Queue::...`** | Inject `QueueInterface` |
| **Global Helpers** (`now()`, `config()`, `app()`, `dd()`, `env()`, etc.) | Inject interfaces or use native PHP |

### Required Replacements

**Logging Example:**

```php
// ✅ CORRECT
use Psr\Log\LoggerInterface;

public function __construct(
    private readonly LoggerInterface $logger
) {}

public function processData(array $data): void
{
    $this->logger->info('Processing data', ['count' => count($data)]);
}
```

**Time/Date Example:**

```php
// ✅ CORRECT - Define Clock Contract
namespace Nexus\YourPackage\Contracts;

interface ClockInterface
{
    public function getCurrentTime(): \DateTimeImmutable;
}

// Use in service
public function __construct(
    private readonly ClockInterface $clock
) {}

public function isExpired(\DateTimeImmutable $expiresAt): bool
{
    return $expiresAt < $this->clock->getCurrentTime();
}
```

## 🔍 Code Quality Checklist

Before committing code to any package, verify:

### For All Packages
- [ ] **Consulted [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md)** to avoid reimplementing functionality
- [ ] No framework facades used (`Log::`, `Cache::`, `DB::`, etc.)
- [ ] No global helpers used (`now()`, `config()`, `app()`, `dd()`, etc.)
- [ ] All dependencies injected via constructor as **interfaces**
- [ ] All properties are `readonly` (for PHP 8.3+)
- [ ] Native enums used instead of class constants
- [ ] `declare(strict_types=1);` at top of every file
- [ ] All public methods have complete docblocks
- [ ] Custom exceptions thrown for domain errors
- [ ] No direct database access (use Repository interfaces)
- [ ] If tracking metrics, uses `TelemetryTrackerInterface` from `Nexus\Monitoring`
- [ ] Package has `composer.json` with proper autoloading
- [ ] Package has `README.md` with usage examples
- [ ] Package has `LICENSE` file

### Testing
- Package tests should be unit tests (no database, no framework)
- Mock repository implementations in package tests
- Test contract implementations separately
- Use PHPUnit for all tests

## Key Reminders

1. **Packages are pure engines**: Pure logic, no persistence, no framework coupling
2. **Interfaces define needs**: Every external dependency is an interface
3. **Consumers provide implementations**: Applications bind concrete classes to interfaces
4. **Always check NEXUS_PACKAGES_REFERENCE.md** before creating new functionality
5. **When in doubt, inject an interface**

## Important Documentation

- **Package Reference:** [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md) - **MANDATORY READ**
- **Architecture Guidelines:** `ARCHITECTURE.md`
- **Package Requirements:** `docs/REQUIREMENTS_*.md`
- **Implementation Summaries:** `docs/*_IMPLEMENTATION_SUMMARY.md`

## Development Workflow

### Creating a New Package

1. Create `packages/PackageName/` directory
2. Run `composer init` (set name to `nexus/package-name`, require `php: ^8.3`)
3. Define PSR-4 autoloader: `"Nexus\\PackageName\\": "src/"`
4. Create Contracts in `src/Contracts/`
5. Create Services in `src/Services/`
6. Create Enums in `src/Enums/` (if needed)
7. Create Value Objects in `src/ValueObjects/` (if needed)
8. Update root `composer.json` repositories array
9. Install in monorepo: `composer require nexus/package-name:"*@dev"`
10. Write comprehensive README.md with usage examples
11. Write unit tests in `tests/`

### Implementing a New Feature

1. **Check if logic exists** → Consult NEXUS_PACKAGES_REFERENCE.md
2. **Define contracts** → Create interfaces in appropriate package
3. **Implement services** → Create manager/service classes
4. **Create exceptions** → Define domain-specific errors
5. **Write tests** → Unit tests for all business logic
6. **Document** → Update README.md with examples

## Naming Conventions

- **Packages**: PascalCase (e.g., `Tenant`, `AuditLogger`)
- **Composer names**: kebab-case (e.g., `nexus/audit-logger`)
- **Namespaces**: `Nexus\PackageName`
- **Interfaces**: Descriptive with `Interface` suffix (e.g., `TenantRepositoryInterface`)
- **Services**: Domain-specific managers (e.g., `TenantManager`, `StockManager`)
- **Exceptions**: Descriptive with `Exception` suffix (e.g., `TenantNotFoundException`)
- **Enums**: Descriptive nouns (e.g., `InvoiceStatus`, `PaymentMethod`)
- **Value Objects**: Domain nouns (e.g., `Money`, `Period`, `Coordinates`)

## Quality Standards

- Always use strict types: `declare(strict_types=1);`
- **Target PHP Version: 8.3+** - All packages must require `"php": "^8.3"`
- All primary keys are ULIDs (string-based UUID v4)
- Use type hints for all parameters and return types
- Write comprehensive docblocks with `@param`, `@return`, `@throws`
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Validate inputs in services before processing
- Throw descriptive exceptions for error cases
- **All dependencies must be interfaces, never concrete classes**

## Available Packages (50+)

See [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md) for the complete list with capabilities, interfaces, and usage examples.

**Key Packages:**
- `Nexus\Monitoring` - Telemetry, metrics, health checks
- `Nexus\AuditLogger` - Audit trails and timeline feeds
- `Nexus\EventStream` - Event sourcing for critical domains
- `Nexus\Identity` - Authentication and authorization
- `Nexus\Finance` - General ledger and accounting
- `Nexus\Receivable` - Customer invoicing and collections
- `Nexus\Payable` - Vendor bills and payments
- `Nexus\Inventory` - Stock management with lot/serial tracking
- `Nexus\Notifier` - Multi-channel notifications
- `Nexus\Connector` - Integration hub with circuit breaker
- `Nexus\Workflow` - Process automation
- `Nexus\Compliance` - Compliance enforcement
- `Nexus\Statutory` - Statutory reporting

---

**Last Updated:** November 24, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Mandatory for all coding agents and developers
