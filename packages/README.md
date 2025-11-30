# Nexus Atomic Packages

This directory contains **54 atomic, framework-agnostic PHP packages** that form the core business logic of the Nexus ERP system. Each package is:

- **Pure PHP 8.3+** - No framework dependencies
- **Stateless** - No long-term state stored in memory
- **Contract-driven** - Defines needs via interfaces, consumers provide implementations
- **Independently publishable** - Can be published to Packagist as standalone packages
- **Thoroughly tested** - Comprehensive unit and integration tests

---

## 📋 Package Inventory (54 Packages)

### Foundation Layer (9 packages)
Core infrastructure and multi-tenancy support:
- **Tenant** - Multi-tenancy context and isolation
- **Sequencing** - Auto-numbering with patterns
- **Period** - Fiscal period management
- **Uom** - Unit of measurement conversions
- **AuditLogger** - Timeline feeds and audit trails
- **EventStream** - Event sourcing for critical domains
- **Setting** - Application settings management
- **Monitoring** - Observability and telemetry
- **FeatureFlags** - Feature flag management

### Identity & Security (3 packages)
- **Identity** - Authentication, RBAC, MFA
- **Crypto** - Cryptographic operations
- **Audit** - Advanced audit capabilities

### Financial Management (7 packages)
- **Finance** - General ledger
- **Accounting** - Financial statements
- **Receivable** - Customer invoicing
- **Payable** - Vendor bills
- **CashManagement** - Bank reconciliation
- **Budget** - Budget planning
- **Assets** - Fixed asset management

### Sales & Operations (6 packages)
- **Sales** - Quotation-to-order lifecycle
- **Inventory** - Stock management with lot/serial tracking
- **Warehouse** - Warehouse operations
- **Procurement** - Purchase management
- **Manufacturing** - MRP II, BOMs, work orders
- **Product** - Product catalog

### Human Resources (3 packages)
- **Hrm** - Leave, attendance, performance
- **Payroll** - Payroll processing
- **PayrollMysStatutory** - Malaysian statutory

### Customer & Partner (4 packages)
- **Party** - Customers, vendors, employees
- **Crm** - Leads and opportunities
- **Marketing** - Campaigns and A/B testing
- **FieldService** - Work orders and service

### Integration & Automation (8 packages)
- **Connector** - Integration hub
- **Workflow** - Process automation
- **Notifier** - Multi-channel notifications
- **Scheduler** - Task scheduling
- **DataProcessor** - OCR and ETL
- **MachineLearning** - ML orchestration
- **Geo** - Geocoding and geofencing
- **Routing** - Route optimization

### Reporting & Data (6 packages)
- **Reporting** - Report engine
- **Export** - Multi-format export
- **Import** - Data import with validation
- **Analytics** - Business intelligence
- **Currency** - Multi-currency management
- **Document** - Document management

### Compliance & Governance (4 packages)
- **Compliance** - Process enforcement
- **Statutory** - Reporting compliance
- **Backoffice** - Company structure
- **OrgStructure** - Organizational hierarchy

### Support & Utilities (3 packages)
- **Storage** - File storage abstraction
- **ProjectManagement** - Projects and tasks
- **Messaging** - Message queue abstraction

### Shared (1 package)
- **SharedKernel** - Common VOs, Contracts, Traits

---

## 🏗️ Package Architecture Patterns

Nexus packages follow two architectural patterns based on complexity:

### 1. Simple Atomic Packages (Utility/Foundational)

**When to use:** Low cyclomatic complexity, pure utility functions, or straightforward business logic.

**Examples:** `Uom`, `Sequencing`, `Notifier`, `Currency`, `Period`

**Structure:**
```
PackageName/
├── composer.json
├── LICENSE
├── README.md
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── .gitignore
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
│       ├── basic-usage.php
│       └── advanced-usage.php
├── src/
│   ├── Contracts/           # Interfaces
│   ├── Services/            # Stateless business logic
│   ├── ValueObjects/        # Immutable data
│   ├── Enums/              # Status/type enums
│   └── Exceptions/         # Domain exceptions
└── tests/
    ├── Unit/
    └── Feature/
```

**Characteristics:**
- Flat `src/` structure
- Minimal internal layering
- Direct service implementation
- Quick to understand and modify

**Example:** `Uom` package for unit conversions
- **Contracts:** `UomManagerInterface`, `UomRepositoryInterface`
- **Services:** `UomManager` (handles conversions)
- **ValueObjects:** `UnitOfMeasurement` (immutable representation)
- **Enums:** `UomCategory` (weight, length, volume, etc.)

---

### 2. Complex Atomic Packages (Core Domains)

**When to use:** Heavy business domains with intricate rules, multiple aggregates, or requiring strict boundaries.

**Examples:** `Inventory`, `Finance`, `Manufacturing`, `Receivable`, `Identity`

**Structure:**
```
PackageName/
├── composer.json
├── LICENSE
├── README.md
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── .gitignore
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Domain/              # THE TRUTH (Pure Logic)
│   │   ├── Entities/        # Pure PHP entities (NOT Eloquent)
│   │   ├── ValueObjects/    # Immutable domain data
│   │   ├── Events/          # Domain events
│   │   ├── Contracts/       # Repository interfaces defined here
│   │   ├── Services/        # Domain services (e.g., StockCalculator)
│   │   ├── Policies/        # Business rules
│   │   └── README.md
│   │
│   ├── Application/         # THE USE CASES
│   │   ├── DTOs/            # Input/Output Data Transfer Objects
│   │   ├── Commands/        # e.g., ReceiveStockCommand
│   │   ├── Queries/         # e.g., GetStockLevelQuery
│   │   ├── Handlers/        # Orchestrates Domain Services
│   │   └── README.md
│   │
│   └── Infrastructure/      # INTERNAL ADAPTERS (Optional)
│       ├── InMemory/        # In-memory repositories for testing
│       ├── Mappers/         # Domain objects to DTOs/arrays
│       └── README.md
│
└── tests/
    ├── Unit/
    └── Feature/
```

**Characteristics:**
- DDD-inspired internal layering
- Clear separation: Domain → Application → Infrastructure
- Domain defines "What", Application defines "How"
- Infrastructure provides testing utilities

**Example:** `Inventory` package for stock management
- **Domain/Entities:** `Stock`, `Lot`, `SerialNumber`
- **Domain/ValueObjects:** `Quantity`, `StockLocation`, `ExpiryDate`
- **Domain/Events:** `StockReceivedEvent`, `StockIssuedEvent`
- **Domain/Contracts:** `StockRepositoryInterface`, `LotRepositoryInterface`
- **Domain/Services:** `ValuationCalculator` (FIFO, weighted average, standard cost)
- **Application/Commands:** `ReceiveStockCommand`, `IssueStockCommand`
- **Application/Handlers:** `ReceiveStockHandler`, `IssueStockHandler`
- **Infrastructure/InMemory:** `InMemoryStockRepository` (for unit tests)

---

## 📐 Architectural Principles

### 1. Framework Agnosticism (MANDATORY)

**✅ ALLOWED:**
- Pure PHP 8.3+ code
- PSR interfaces (`Psr\Log\LoggerInterface`, `Psr\Cache\CacheInterface`)
- Other Nexus packages (via dependency injection)
- Native PHP features (enums, readonly, match, etc.)

**❌ FORBIDDEN:**
- Framework-specific classes (`Illuminate\*`, `Symfony\*`)
- Framework facades (`Log::`, `Cache::`, `DB::`, `Event::`)
- Global helpers (`config()`, `app()`, `now()`, `dd()`, `env()`)
- Direct database access (use repository interfaces)
- ORM models (Eloquent, Doctrine) in `src/` directory

### 2. Stateless Architecture

**Rule:** Packages must not store long-term state in memory.

**✅ CORRECT:**
```php
final readonly class CircuitBreaker
{
    public function __construct(
        private CircuitBreakerStorageInterface $storage // Externalized state
    ) {}
    
    public function getState(string $connectionId): string
    {
        return $this->storage->get("circuit:{$connectionId}");
    }
}
```

**❌ WRONG:**
```php
final class CircuitBreaker
{
    private array $states = []; // Lost when instance destroyed!
}
```

### 3. Contract-Driven Design

**Packages define WHAT they need, not HOW it's implemented.**

**Example:** `Receivable` package needs GL integration
```php
// Package defines interface
namespace Nexus\Receivable\Contracts;

interface GeneralLedgerIntegrationInterface
{
    public function postJournalEntry(JournalEntry $entry): void;
}

// Consuming application provides implementation
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

### 4. Dependency Injection Only

All dependencies injected via constructor as **interfaces**.

**✅ CORRECT:**
```php
final readonly class InvoiceManager
{
    public function __construct(
        private InvoiceRepositoryInterface $repository,
        private TenantContextInterface $tenantContext,
        private AuditLogManagerInterface $auditLogger
    ) {}
}
```

**❌ WRONG:**
```php
final class InvoiceManager
{
    public function __construct(
        private InvoiceRepository $repository // Concrete class!
    ) {}
}
```

---

## 🔧 Package Creation Guidelines

### When to Create a New Package

Create a new atomic package when:
- ✅ Logic is reusable across multiple domains
- ✅ Functionality has clear, single responsibility
- ✅ Package can be independently tested
- ✅ Package can be independently published
- ✅ No existing package provides the capability (check `docs/NEXUS_PACKAGES_REFERENCE.md`)

### When NOT to Create a New Package

Extend existing package when:
- ❌ Logic is tightly coupled to existing package domain
- ❌ Functionality requires deep knowledge of existing package internals
- ❌ Only adds 1-2 methods to existing interfaces
- ❌ Would create circular dependencies

### Package Naming Conventions

- **PascalCase** - Package directory names (e.g., `Inventory`, `AuditLogger`)
- **Singular nouns** - Preferred over plural (e.g., `Product` not `Products`)
- **Domain-focused** - Name reflects business domain, not technical implementation
- **Composer name** - kebab-case (e.g., `nexus/audit-logger`)
- **Namespace** - `Nexus\PackageName`

---

## 📚 Mandatory Documentation Files

Every package **MUST** include:

1. **composer.json** - Package metadata, require `"php": "^8.3"`
2. **LICENSE** - MIT License
3. **.gitignore** - Package-specific ignores
4. **README.md** - Comprehensive usage guide with examples
5. **IMPLEMENTATION_SUMMARY.md** - Implementation tracking
6. **REQUIREMENTS.md** - Detailed requirements table
7. **TEST_SUITE_SUMMARY.md** - Test coverage and results
8. **VALUATION_MATRIX.md** - Package valuation metrics
9. **docs/getting-started.md** - Quick start guide
10. **docs/api-reference.md** - API documentation
11. **docs/integration-guide.md** - Framework integration examples
12. **docs/examples/basic-usage.php** - Basic code example
13. **docs/examples/advanced-usage.php** - Advanced code example

---

## 🧪 Testing Standards

### Unit Tests
- Test business logic in isolation
- Mock all external dependencies
- No database required
- Fast execution (< 1 second per test)
- Coverage target: 90%+

### Feature Tests
- Test complete workflows
- Use in-memory repositories from `Infrastructure/InMemory/`
- No framework required
- Verify business rules end-to-end

### Integration Tests
- Handled by consuming applications (not in packages)
- Test repository implementations
- Test database migrations
- Test API endpoints

---

## 🚨 Common Anti-Patterns to Avoid

### ❌ Anti-Pattern 1: God Package
**Problem:** Single package doing too much (e.g., `Finance` handling GL, AR, AP, budgets, assets)

**Solution:** Split into focused packages (Finance, Receivable, Payable, Budget, Assets)

### ❌ Anti-Pattern 2: Framework Coupling
**Problem:** Using Laravel facades or Eloquent in package code

**Solution:** Define interfaces, inject dependencies, keep packages pure PHP

### ❌ Anti-Pattern 3: Circular Dependencies
**Problem:** Package A depends on Package B, Package B depends on Package A

**Solution:** Extract shared logic to `SharedKernel` or use event-driven communication

### ❌ Anti-Pattern 4: Stateful Services
**Problem:** Storing state in class properties (e.g., `private array $cache = []`)

**Solution:** Externalize state via `*StorageInterface` dependencies

### ❌ Anti-Pattern 5: Missing Type Hints
**Problem:** Methods without complete parameter and return type hints

**Solution:** Always use PHP 8.3+ type hints (`string`, `int`, `?Type`, `array<Type>`)

---

## 📖 Key References

- **Architecture Guidelines:** `../ARCHITECTURE.md`
- **Coding Standards:** `../CODING_GUIDELINES.md`
- **Package Reference:** `../docs/NEXUS_PACKAGES_REFERENCE.md`
- **Package Creation Guide:** `../.github/prompts/create-package-instruction.prompt.md`
- **Refactoring Exercise:** `../REFACTOR_EXERCISE_12.md`

---

## 🎯 Quick Decision Matrix

**"Should this code go in an atomic package or elsewhere?"**

| Question | Atomic Package | Orchestrator | Adapter |
|----------|---------------|--------------|---------|
| Does it define core business logic? | ✅ Yes | ❌ No | ❌ No |
| Can it work without a framework? | ✅ Yes | ✅ Yes | ❌ No |
| Does it coordinate multiple packages? | ❌ No | ✅ Yes | ❌ No |
| Does it contain Eloquent/Jobs/Controllers? | ❌ No | ❌ No | ✅ Yes |
| Can it be published to Packagist independently? | ✅ Yes | ✅ Yes | ❌ No |

---

**Last Updated:** 2025-11-24  
**Maintained By:** Nexus Architecture Team  
**Total Packages:** 54 (53 atomic + SharedKernel)
