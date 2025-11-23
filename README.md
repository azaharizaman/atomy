# Nexus - Framework-Agnostic PHP Packages for ERP Systems

Nexus is a **package-only monorepo** containing 50+ atomic, reusable PHP packages for building Enterprise Resource Planning (ERP) systems. Each package is framework-agnostic, making them usable with Laravel, Symfony, Slim, or any other PHP framework.

## 📖 The Philosophy: "Pure Business Logic, Framework Independent"

The core philosophy of Nexus is **Framework Agnosticism**. Business logic should be portable and reusable across different frameworks and applications.

- **🎯 Pure Business Logic**: Packages contain only business rules and domain logic
- **🔌 Interface-Driven**: All external dependencies defined as contracts
- **📦 Atomic & Publishable**: Each package can be published independently to Packagist
- **🧪 Testable**: Pure PHP logic with mockable dependencies
- **🌍 Framework-Agnostic**: Works with Laravel, Symfony, or any PHP framework

## 🏗️ Architecture

### 📦 Atomic Packages

All packages in `packages/` are self-contained units of functionality designed to be:

- **Framework-Agnostic:** Pure PHP 8.3+ logic with no framework dependencies
- **Persistence-Agnostic:** No migrations or models - data access defined via interfaces
- **Publishable:** Each package can be published independently to Packagist
- **Contract-Driven:** All external dependencies injected as interfaces
- **Stateless:** Long-term state externalized via storage interfaces

## 📦 Available Packages (51 packages)

### Core Infrastructure (8 packages)
- **`Nexus\Tenant`** - Multi-tenancy context and isolation engine
- **`Nexus\Setting`** - Global and tenant-specific configuration management
- **`Nexus\Sequencing`** - Auto-numbering with atomic counter management
- **`Nexus\Period`** - Fiscal period management and transaction validation
- **`Nexus\AuditLogger`** - Timeline feeds and audit trails
- **`Nexus\EventStream`** - Event sourcing for critical domains (Finance GL, Inventory)
- **`Nexus\Uom`** - Unit of measurement management and conversion
- **`Nexus\Monitoring`** - Observability with telemetry, health checks, alerting, SLO tracking

### Identity & Security (3 packages)
- **`Nexus\Identity`** - Authentication, RBAC, MFA, session/token management
- **`Nexus\Crypto`** - Cryptographic operations and key management
- **`Nexus\Audit`** - Advanced audit capabilities (extends AuditLogger)

### Finance & Accounting (7 packages)
- **`Nexus\Finance`** - General ledger, journal entries, double-entry bookkeeping
- **`Nexus\Accounting`** - Financial statements, period close, consolidation
- **`Nexus\Receivable`** - Customer invoicing, collections, credit control
- **`Nexus\Payable`** - Vendor bills, payment processing, 3-way matching
- **`Nexus\CashManagement`** - Bank reconciliation, cash flow forecasting
- **`Nexus\Budget`** - Budget planning and variance tracking
- **`Nexus\Assets`** - Fixed asset management, depreciation
- **`Nexus\Currency`** - Multi-currency management and exchange rates

### Sales & Operations (6 packages)
- **`Nexus\Sales`** - Quotation-to-order lifecycle, pricing engine
- **`Nexus\Inventory`** - Stock management with lot/serial tracking
- **`Nexus\Warehouse`** - Warehouse operations and bin management
- **`Nexus\Procurement`** - Purchase requisitions, POs, goods receipt
- **`Nexus\Manufacturing`** - Bill of materials, work orders, MRP
- **`Nexus\Product`** - Product catalog, pricing, categorization

### Human Resources (3 packages)
- **`Nexus\Hrm`** - Leave, attendance, performance reviews
- **`Nexus\Payroll`** - Payroll processing framework
- **`Nexus\PayrollMysStatutory`** - Malaysian statutory calculations (EPF, SOCSO, PCB)

### Customer & Partner Management (4 packages)
- **`Nexus\Party`** - Customers, vendors, employees, contacts
- **`Nexus\Crm`** - Leads, opportunities, sales pipeline
- **`Nexus\Marketing`** - Campaigns, A/B testing, GDPR compliance
- **`Nexus\FieldService`** - Work orders, technicians, service contracts

### Integration & Automation (7 packages)
- **`Nexus\Connector`** - Integration hub with circuit breaker, OAuth
- **`Nexus\Workflow`** - Process automation, state machines
- **`Nexus\Notifier`** - Multi-channel notifications (email, SMS, push, in-app)
- **`Nexus\Scheduler`** - Task scheduling and job management
- **`Nexus\DataProcessor`** - OCR, ETL interfaces (interface-only package)
- **`Nexus\Intelligence`** - AI-assisted automation and predictions
- **`Nexus\Geo`** - Geocoding, geofencing, routing
- **`Nexus\Routing`** - Route optimization and caching

### Reporting & Data (5 packages)
- **`Nexus\Reporting`** - Report definition and execution engine
- **`Nexus\Export`** - Multi-format export (PDF, Excel, CSV, JSON)
- **`Nexus\Import`** - Data import with validation and transformation
- **`Nexus\Analytics`** - Business intelligence, predictive models
- **`Nexus\Document`** - Document management with versioning

### Compliance & Governance (4 packages)
- **`Nexus\Compliance`** - Process enforcement, operational compliance
- **`Nexus\Statutory`** - Reporting compliance, statutory filing
- **`Nexus\Backoffice`** - Company structure, offices, departments
- **`Nexus\OrgStructure`** - Organizational hierarchy management

### Support & Utilities (3 packages)
- **`Nexus\Storage`** - File storage abstraction layer
- **`Nexus\ProjectManagement`** - Projects, tasks, timesheets, milestones
- **`Nexus\FeatureFlags`** - Feature flag management

## 🛠️ Getting Started

### Prerequisites
- PHP 8.3+
- Composer

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url> nexus
   cd nexus
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   ```

3. **Explore Packages:**
   ```bash
   # Browse available packages
   ls packages/
   
   # Read package documentation
   cat packages/Tenant/README.md
   cat packages/Finance/README.md
   ```

## 📚 Usage

### Installing a Package

Each package can be installed independently in your PHP application:

```bash
# In your Laravel, Symfony, or other PHP application
composer require nexus/tenant
composer require nexus/finance
composer require nexus/receivable
```

### Implementing Package Contracts

Packages define interfaces, your application provides implementations:

```php
// Package defines the interface
namespace Nexus\Tenant\Contracts;

interface TenantRepositoryInterface
{
    public function findById(string $id): ?TenantInterface;
    public function save(TenantInterface $tenant): void;
}

// Your Laravel application implements it
namespace App\Repositories;

use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\Contracts\TenantInterface;
use App\Models\Tenant;

final class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function findById(string $id): ?TenantInterface
    {
        return Tenant::find($id);
    }
    
    public function save(TenantInterface $tenant): void
    {
        Tenant::updateOrCreate(['id' => $tenant->getId()], [
            'name' => $tenant->getName(),
            'status' => $tenant->getStatus()->value,
        ]);
    }
}

// Bind in service provider
$this->app->bind(
    TenantRepositoryInterface::class,
    EloquentTenantRepository::class
);
```

### Using Package Services

```php
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

class InvoiceController
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext,
        private readonly GeneralLedgerManagerInterface $glManager
    ) {}
    
    public function store(Request $request)
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        // Use package business logic
        $this->glManager->postJournalEntry($journalEntry);
    }
}
```

## 🏛️ Architectural Principles

### 1. Framework Agnosticism
- No Laravel, Symfony, or framework-specific code in packages
- Use PSR interfaces (`psr/log`, `psr/http-client`, `psr/cache`)
- All framework integration happens in consuming applications

### 2. Contract-Driven Design
- Packages define needs via interfaces
- Consuming applications provide implementations
- Dependency injection for all external dependencies

### 3. Stateless Design
- No session state in package classes
- Long-term state externalized via storage interfaces
- Horizontally scalable by design

### 4. Modern PHP Standards
- PHP 8.3+ with strict types
- Constructor property promotion
- Readonly properties for dependencies
- Native enums for fixed value sets
- Match expressions over switch statements

## 📖 Documentation

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Architectural guidelines and rules
- **[docs/NEXUS_PACKAGES_REFERENCE.md](docs/NEXUS_PACKAGES_REFERENCE.md)** - Complete package capabilities reference
- **[.github/copilot-instructions.md](.github/copilot-instructions.md)** - Development guidelines
- **Package READMEs** - Individual package documentation (e.g., `packages/Finance/README.md`)

## 🤝 Contributing

Please refer to [ARCHITECTURE.md](ARCHITECTURE.md) for detailed architectural guidelines.

### Key Rules:
1. **Packages must be framework-agnostic** - No Laravel, Symfony, or framework-specific code
2. **Packages define persistence needs via Contracts** - No migrations or models in packages
3. **All dependencies must be interfaces** - Use dependency injection
4. **Modern PHP 8.3+ standards** - Use latest language features
5. **Consult NEXUS_PACKAGES_REFERENCE.md** - Avoid reimplementing existing functionality

### Creating a New Package

1. Create `packages/NewPackage/` directory
2. Run `composer init` (require `"php": "^8.3"`)
3. Define PSR-4 autoloader: `"Nexus\\NewPackage\\": "src/"`
4. Create `src/Contracts/`, `src/Services/`, `src/Exceptions/`
5. Write comprehensive `README.md` with usage examples
6. Add MIT `LICENSE` file
7. Update root `composer.json` repositories array

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- **Package Reference Guide**: [docs/NEXUS_PACKAGES_REFERENCE.md](docs/NEXUS_PACKAGES_REFERENCE.md)
- **Architecture Documentation**: [ARCHITECTURE.md](ARCHITECTURE.md)
- **Implementation Summaries**: `docs/*_IMPLEMENTATION_SUMMARY.md`

---

**Nexus** - Building the future of modular ERP systems with framework-agnostic PHP packages.
