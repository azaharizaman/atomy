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
24. **Nexus\Manufacturing** - Bill of materials, work orders, MRP
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
└── 📦 packages/                  # 50+ Atomic, publishable PHP packages
    ├── Tenant/                   # Nexus\Tenant (Example Package Structure)
    │   ├── composer.json         # Package metadata, dependencies, autoloading
    │   ├── README.md             # Package documentation with usage examples
    │   ├── LICENSE               # MIT License
    │   └── src/                  # Source code root
    │       ├── Contracts/        # REQUIRED: Interfaces
    │       │   ├── TenantInterface.php         # Entity contract
    │       │   ├── TenantRepositoryInterface.php # Persistence contract
    │       │   └── TenantContextInterface.php  # Service contract
    │       ├── Exceptions/       # REQUIRED: Domain exceptions
    │       │   ├── TenantNotFoundException.php
    │       │   └── InvalidTenantException.php
    │       ├── Services/         # REQUIRED: Business logic
    │       │   ├── TenantContextManager.php
    │       │   └── TenantLifecycleService.php
    │       ├── Enums/            # RECOMMENDED: Native PHP enums
    │       │   └── TenantStatus.php
    │       └── ValueObjects/     # RECOMMENDED: Immutable domain objects
    │           └── TenantConfiguration.php
    │
    ├── Inventory/                # More complex package with Core/
    │   ├── composer.json
    │   ├── README.md
    │   ├── LICENSE
    │   └── src/
    │       ├── Contracts/        # Public API contracts
    │       ├── Services/         # Public API services (orchestrators)
    │       ├── Core/             # Internal engine (hidden from consumers)
    │       │   ├── Engine/       # Complex business logic
    │       │   ├── ValueObjects/ # Internal immutable objects
    │       │   └── Contracts/    # Internal interfaces
    │       ├── Exceptions/
    │       ├── Enums/
    │       └── ValueObjects/
    │
    ├── Finance/
    ├── Receivable/
    ├── Payable/
    └── [... 48 more packages]
```

---

## 2. 📦 Package Development Rules

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
4. **`composer.json`** - Package metadata, require `"php": "^8.3"`
5. **`README.md`** - Usage examples and documentation
6. **`LICENSE`** - MIT License

**RECOMMENDED Components:**
1. **`src/Enums/`** - Native PHP enums for statuses, types, levels
2. **`src/ValueObjects/`** - Immutable domain objects (Money, Period, etc.)

**OPTIONAL Components:**
1. **`src/Core/`** - Internal engine for complex packages (see below)

### When to Use `Core/` Folder

Create `src/Core/` when your package is complex and has internal components consumers shouldn't access:

**Use `Core/` when:**
- Package has > 10 files
- Main service class > 300 lines
- Internal contracts for engine components needed
- Value Objects only used internally
- Main Manager is merely an orchestrator

**Skip `Core/` when:**
- Package has < 10 files
- Simple business logic
- All components are public API

---

## 3. 🏛️ Architectural Patterns

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

### 3.4 Compliance & Statutory Separation

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

### 5.2 Circular Dependency Prevention

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

## 7. 🚀 Development Workflow

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

## 8. 📊 Code Quality Checklist

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
- [ ] Package has comprehensive README.md
- [ ] Package has LICENSE file
- [ ] Unit tests written and passing

---

## 9. 📚 Documentation Requirements

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

## 10. 🔄 Publishing Workflow

Before publishing a package to Packagist:

1. **Complete test coverage** - All business logic tested
2. **Comprehensive README** - Usage examples included
3. **Semantic versioning** - Follow SemVer (1.0.0, 1.1.0, 2.0.0)
4. **License file** - MIT License included
5. **No application dependencies** - Only PSR and other Nexus packages
6. **Git tag** - Tag release in repository
7. **CHANGELOG.md** - Document all changes

---

## 11. Key Principles Summary

1. **Packages are pure engines** - Logic only, no persistence or framework coupling
2. **Interfaces define needs** - Every dependency is an interface
3. **Consumers provide implementations** - Applications bind contracts to concrete classes
4. **Framework agnostic** - Works with Laravel, Symfony, or any PHP framework
5. **Stateless design** - Long-term state externalized via interfaces
6. **PHP 8.3+ modern** - Use latest language features
7. **Always check NEXUS_PACKAGES_REFERENCE.md** - Avoid reinventing functionality

---

**Last Updated:** November 24, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Mandatory for all developers
