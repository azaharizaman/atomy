# Nexus ERP: Complete System Overview

**Version:** 1.0  
**Last Updated:** November 22, 2025  
**Purpose:** Comprehensive reference document for AI assistants (ChatGPT, Claude, etc.)

---

## 📋 Table of Contents

1. [Executive Summary](#executive-summary)
2. [Project Overview](#project-overview)
3. [Core Architecture](#core-architecture)
4. [Technology Stack](#technology-stack)
5. [Package Inventory](#package-inventory)
6. [Implementation Status](#implementation-status)
7. [Critical Design Decisions](#critical-design-decisions)
8. [Database Architecture](#database-architecture)
9. [API Design](#api-design)
10. [Security & Compliance](#security--compliance)
11. [Development Workflow](#development-workflow)
12. [Testing Strategy](#testing-strategy)
13. [Deployment Architecture](#deployment-architecture)
14. [Quick Reference](#quick-reference)

---

## Executive Summary

**Nexus** is a modern, modular, headless ERP system built on a strict separation of concerns principle: **"Logic in Packages, Implementation in Applications."**

### Key Characteristics

- **Monorepo Architecture:** 44+ atomic packages + 2 applications
- **Framework-Agnostic Core:** Pure PHP 8.3+ business logic
- **Headless-First:** RESTful API-driven with optional admin UI (planned)
- **Multi-Tenant Ready:** Row-level isolation with queue context propagation
- **Event Sourcing:** For critical domains (Finance GL, Inventory)
- **Production Status:** Foundation packages complete, domain packages in active development

### Business Scope

A comprehensive ERP system covering:
- **Finance & Accounting:** GL, AP, AR, Cash Management, Assets
- **Human Resources:** HRM, Payroll (Malaysia statutory support)
- **Operations:** Sales, Procurement, Inventory, Warehouse, Manufacturing
- **CRM & Service:** Customer management, Field Service, Marketing
- **Integration:** External connectors, Document management, Analytics

---

## Project Overview

### The Core Philosophy

> **"Logic in Packages, Implementation in Applications"**

The architecture enforces strict decoupling:

```
📦 packages/     → Pure business logic (the "engines")
🚀 apps/         → Runnable applications (the "cars")
```

**Golden Rules:**
1. Packages NEVER depend on applications
2. Packages NEVER contain database logic
3. Packages ONLY define contracts (interfaces)
4. Applications implement contracts with concrete classes

### Project Structure

```
nexus/
├── packages/              # 44+ atomic, publishable PHP packages
│   ├── Tenant/           # Multi-tenancy engine
│   ├── Finance/          # General ledger, journal entries
│   ├── Receivable/       # Invoicing, collections
│   ├── Inventory/        # Stock management
│   └── ...
├── apps/
│   ├── Atomy/            # Headless Laravel backend (primary deliverable)
│   │   ├── app/
│   │   │   ├── Models/           # Eloquent models (implement package interfaces)
│   │   │   ├── Repositories/     # Concrete repository implementations
│   │   │   └── Services/         # Application-layer orchestration
│   │   ├── database/migrations/  # ALL database migrations
│   │   └── routes/api.php        # RESTful API endpoints
│   └── Edward/           # Terminal UI client (demo consumer)
└── docs/                 # Comprehensive documentation
```

### Design Principles

1. **Atomic Packages:** Each package is self-contained and publishable
2. **Contract-Driven:** Packages define needs via interfaces
3. **Dependency Inversion:** Applications provide implementations
4. **Framework Agnosticism:** Packages work in any PHP framework
5. **Horizontal Scalability:** Stateless design for distributed systems

---

## Core Architecture

### The Two-Layer Model

#### Layer 1: Package Layer (Business Logic)

**Location:** `packages/`  
**Language:** Pure PHP 8.3+  
**Allowed Dependencies:** PSR interfaces, other atomic packages  
**Forbidden:** Laravel facades, Eloquent models, database access

**Structure:**
```
packages/Finance/
├── composer.json          # Defines nexus/finance
├── README.md
├── LICENSE
└── src/
    ├── Contracts/         # Interfaces (REQUIRED)
    │   ├── FinanceManagerInterface.php
    │   ├── JournalEntryInterface.php
    │   └── AccountRepositoryInterface.php
    ├── Services/          # Business logic (REQUIRED)
    │   └── FinanceManager.php
    ├── ValueObjects/      # Immutable domain objects
    │   └── Money.php
    ├── Enums/             # Native PHP 8.3 enums
    │   └── AccountType.php
    └── Exceptions/        # Domain exceptions
        └── InvalidJournalEntryException.php
```

#### Layer 2: Application Layer (Implementation)

**Location:** `apps/Atomy/`  
**Framework:** Laravel 12  
**Purpose:** Implements package contracts, provides persistence, exposes APIs

**Structure:**
```
apps/Atomy/
├── app/
│   ├── Models/                # Eloquent models (implement package interfaces)
│   │   └── Finance/
│   │       ├── Account.php               # implements AccountInterface
│   │       └── JournalEntry.php          # implements JournalEntryInterface
│   ├── Repositories/          # Concrete implementations
│   │   └── Finance/
│   │       └── EloquentAccountRepository.php  # implements AccountRepositoryInterface
│   ├── Services/              # Application-layer orchestration
│   │   └── Finance/
│   │       └── FinanceService.php
│   └── Providers/
│       └── AppServiceProvider.php  # Binds interfaces to implementations
├── database/
│   └── migrations/            # ALL database migrations
│       └── 2024_11_22_21000_create_accounts_table.php
└── routes/
    └── api.php                # RESTful API endpoints
```

### Dependency Injection Pattern

**In Package (Defines Need):**
```php
namespace Nexus\Finance\Services;

use Nexus\Finance\Contracts\AccountRepositoryInterface;

final readonly class FinanceManager
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository  // Interface only!
    ) {}
    
    public function createAccount(array $data): string
    {
        return $this->accountRepository->save($data);
    }
}
```

**In Application (Provides Implementation):**
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        AccountRepositoryInterface::class,
        EloquentAccountRepository::class  // Concrete implementation
    );
}
```

### Cross-Package Integration

When Package A needs Package B's functionality:

**Pattern:** Adapter in application layer

```
packages/Sales/src/Contracts/InvoiceCreatorInterface.php  # Sales defines need
apps/Atomy/app/Services/Sales/ReceivableInvoiceAdapter.php  # Atomy bridges packages
```

**Benefits:**
- Maintains package independence
- Enables substitution (swap implementations)
- Facilitates testing with mocks

---

## Technology Stack

### Core Technologies

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Language** | PHP | 8.3+ | Core programming language |
| **Framework** | Laravel | 12 | Application layer (Atomy only) |
| **Database** | PostgreSQL | 15+ | Primary persistence (MySQL supported) |
| **Cache/Queue** | Redis | 7+ | Caching, sessions, queues |
| **HTTP Client** | Guzzle | 7.8+ | External API communication |

### Package-Level Dependencies

**Allowed:**
- PSR interfaces (PSR-3 Logger, PSR-14 Events, PSR-15 Middleware)
- Framework-agnostic libraries (Carbon, Ramsey UUID)
- `illuminate/support` (Collections only, not facades)

**Forbidden:**
- Laravel facades (Log::, Cache::, DB::, Config::)
- Global helpers (now(), config(), app(), dd())
- Eloquent models
- Framework-specific classes (Illuminate\Http\Request)

### Modern PHP 8.3+ Features

**Required Usage:**
1. **Constructor Property Promotion:**
   ```php
   public function __construct(
       private readonly LoggerInterface $logger
   ) {}
   ```

2. **Readonly Properties:**
   ```php
   final readonly class Money
   {
       public function __construct(
           public string $amount,
           public string $currency
       ) {}
   }
   ```

3. **Native Enums:**
   ```php
   enum AccountType: string
   {
       case Asset = 'asset';
       case Liability = 'liability';
       case Equity = 'equity';
       case Revenue = 'revenue';
       case Expense = 'expense';
   }
   ```

4. **Match Expressions:**
   ```php
   $result = match($status) {
       PeriodStatus::Open => 'Can post',
       PeriodStatus::Closed => 'Cannot post',
       default => throw new InvalidStatusException()
   };
   ```

---

## Package Inventory

### Foundation & Infrastructure (7 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Tenant** | ✅ 90% | Multi-tenancy with queue context propagation |
| **Nexus\Sequencing** | ✅ 100% | Auto-numbering (Invoice #, PO #, etc.) |
| **Nexus\Period** | ✅ 100% | Fiscal period management |
| **Nexus\Uom** | ✅ 90% | Unit of measurement conversions |
| **Nexus\AuditLogger** | ✅ 90% | CRUD tracking, timeline feeds |
| **Nexus\EventStream** | 🚧 20% | Event sourcing for GL/Inventory |
| **Nexus\Setting** | ✅ 95% | Application configuration |

### Identity & Security (3 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Identity** | ✅ 95% | Authentication, RBAC, MFA |
| **Nexus\Crypto** | ✅ 85% | Encryption, key management |
| **Nexus\Audit** | 🚧 30% | Advanced audit capabilities |

### Finance & Accounting (7 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Finance** | ✅ 90% | General ledger, journal entries |
| **Nexus\Accounting** | ✅ 85% | Financial statements, period close |
| **Nexus\Receivable** | ✅ 75% | Customer invoicing (3 phases) |
| **Nexus\Payable** | ✅ 70% | Vendor bills, 3-way matching |
| **Nexus\CashManagement** | ✅ 80% | Bank reconciliation |
| **Nexus\Budget** | ✅ 75% | Budget tracking |
| **Nexus\Assets** | 🚧 40% | Fixed asset depreciation |
| **Nexus\Currency** | ✅ 90% | Multi-currency support |

### Sales & Operations (6 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Sales** | ✅ 95% | Quotation-to-order lifecycle |
| **Nexus\Party** | ✅ 90% | Customer/vendor master data |
| **Nexus\Product** | ✅ 85% | Product catalog |
| **Nexus\Inventory** | 🚧 50% | Stock management |
| **Nexus\Warehouse** | 🚧 40% | Warehouse operations |
| **Nexus\Procurement** | 🚧 30% | Purchase orders |

### Human Resources (3 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Hrm** | 🚧 40% | HR management |
| **Nexus\Payroll** | 🚧 50% | Payroll processing |
| **Nexus\PayrollMysStatutory** | ✅ 90% | Malaysia EPF/SOCSO/PCB |

### Integration & Automation (7 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Connector** | ✅ 85% | External integrations |
| **Nexus\Workflow** | 🚧 30% | Process automation |
| **Nexus\Notifier** | ✅ 95% | Multi-channel notifications |
| **Nexus\Scheduler** | ✅ 80% | Task scheduling |
| **Nexus\DataProcessor** | ✅ 50% | OCR, ETL interfaces |
| **Nexus\Intelligence** | ✅ 85% | AI predictions |
| **Nexus\Geo** | ✅ 80% | Geocoding, routing |

### Reporting & Data (6 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Reporting** | ✅ 75% | Report engine |
| **Nexus\Export** | ✅ 95% | Multi-format export |
| **Nexus\Import** | ✅ 80% | Data import |
| **Nexus\Analytics** | ✅ 70% | Business intelligence |
| **Nexus\Document** | ✅ 85% | Document management |
| **Nexus\Storage** | ✅ 95% | File storage abstraction |

### Compliance & Governance (4 packages)

| Package | Status | Purpose |
|---------|--------|---------|
| **Nexus\Compliance** | ✅ 80% | Process enforcement |
| **Nexus\Statutory** | ✅ 75% | Regulatory reporting |
| **Nexus\Backoffice** | 🚧 20% | Company structure |
| **Nexus\OrgStructure** | 🚧 15% | Organizational hierarchy |

**Legend:**
- ✅ 75%+ = Production-ready or near-complete
- 🚧 <75% = In active development

---

## Implementation Status

### Overall Progress

| Category | Packages | Complete | In Progress | Not Started |
|----------|----------|----------|-------------|-------------|
| Foundation | 7 | 6 | 1 | 0 |
| Security | 3 | 2 | 1 | 0 |
| Finance | 8 | 6 | 2 | 0 |
| Operations | 6 | 3 | 3 | 0 |
| HR | 3 | 1 | 2 | 0 |
| Integration | 7 | 5 | 2 | 0 |
| Reporting | 6 | 5 | 1 | 0 |
| Governance | 4 | 2 | 2 | 0 |
| **Total** | **44** | **30** | **14** | **0** |

**Overall Completion: ~68%**

### Critical Milestones Achieved

**✅ Phase 1 Complete (Foundation):**
- Tenant context with queue propagation
- Sequence generation with atomic counters
- Period management with intelligent next-period creation
- Setting management
- Audit logging

**✅ Phase 2 Complete (Finance Core):**
- Finance GL with double-entry bookkeeping
- Accounting with financial statements
- Currency with exchange rates
- Cash Management with reconciliation
- Budget tracking

**✅ Phase 3 Complete (Sales & Integration):**
- Sales order lifecycle
- Party (customer/vendor) management
- Product catalog
- Connector with circuit breaker
- Notifier multi-channel
- Export engine

**🚧 Phase 4 In Progress (Receivable/Payable):**
- Receivable (75% - 3 phases implemented)
- Payable (70% - core features done)
- Assets (40% - infrastructure ready)

**📋 Phase 5 Planned (Inventory & Warehouse):**
- Inventory with lot/serial tracking
- Warehouse with bin management
- Manufacturing with MRP

---

## Critical Design Decisions

### 1. Primary Keys: ULIDs (Not Auto-Increment)

**Decision:** All primary keys use ULIDs (26-character UUID v4 strings)

**Format:** `01ARZ3NDEKTSV4RRFFQ69G5FAV`

**Rationale:**
- Distributed generation (no central bottleneck)
- Sortable by creation time (timestamp prefix)
- URL-safe, case-insensitive
- Collision-resistant
- No database round-trip for ID generation

**Implementation:**
```php
Schema::create('accounts', function (Blueprint $table) {
    $table->ulid('id')->primary();  // NOT $table->id()
    $table->ulid('tenant_id')->index();
    // ...
});
```

### 2. Multi-Tenancy: Row-Level Isolation

**Decision:** Every business entity has `tenant_id` column

**Enforcement Layers:**
1. **Database Level:** Foreign key constraints
2. **Model Level:** Global scopes (automatic filtering)
3. **Queue Level:** Context serialization and restoration
4. **API Level:** Middleware validation

**Queue Context Propagation:**
```php
// Job automatically captures tenant context
class ProcessInvoice implements ShouldQueue
{
    use TenantAwareJob;  // Auto-serializes tenant_id
    
    public function handle()
    {
        // Tenant context restored automatically
        // All queries auto-scoped to current tenant
    }
}
```

### 3. Event Architecture: Hybrid Approach

**Two Systems for Different Use Cases:**

#### System A: AuditLogger (Timeline Feeds) - 95% of cases

**Purpose:** User-facing "what happened" timelines

**Use Cases:**
- Customer records
- HR data
- Settings
- Inventory adjustments
- Approval workflows

**Mechanism:**
- Logs outcomes AFTER transaction commit
- Simple chronological queries
- Human-readable descriptions

**Example:**
```php
$this->auditLogger->log(
    $invoiceId,
    'status_change',
    'Invoice status changed from Draft to Paid by John Doe'
);
```

#### System B: EventStream (Event Sourcing) - Critical domains only

**Purpose:** State reconstruction for compliance

**Use Cases (MANDATORY):**
- Finance GL (SOX/IFRS compliance)
- Inventory (Stock accuracy verification)

**Use Cases (OPTIONAL):**
- Large Enterprise AP/AR

**Mechanism:**
- Append-only immutable event log
- Temporal queries ("What was balance on 2024-10-15?")
- Projection rebuilding from events

**Example:**
```php
$this->eventStore->append(
    $accountId,
    new AccountCreditedEvent(
        accountId: '1000',
        amount: Money::of('1000.00', 'MYR'),
        journalEntryId: 'JE-2024-001'
    )
);

// Later: Rebuild state at specific point in time
$balance = $this->eventStream->getStateAt($accountId, '2024-10-15');
```

**Decision Matrix:**

| Domain | Use AuditLogger | Use EventStream |
|--------|-----------------|-----------------|
| Finance GL | ❌ | ✅ MANDATORY |
| Inventory | ❌ | ✅ MANDATORY |
| Payable (Small) | ✅ | ❌ |
| Payable (Enterprise) | ✅ | ✅ Optional |
| Receivable (Small) | ✅ | ❌ |
| Receivable (Enterprise) | ✅ | ✅ Optional |
| HRM | ✅ | ❌ |
| Payroll | ✅ | ❌ |
| CRM | ✅ | ❌ |

### 4. Stateless Package Design

**Problem:** Storing state in package properties breaks horizontal scaling

**Anti-Pattern:**
```php
// ❌ FORBIDDEN: State isolated to single PHP-FPM worker
class CircuitBreaker
{
    private array $circuitStates = [];  // Won't scale!
}
```

**Solution:** Externalize state via interfaces

```php
// ✅ CORRECT: State shared across all workers
class CircuitBreaker
{
    public function __construct(
        private readonly CircuitBreakerStorageInterface $storage  // Redis/DB
    ) {}
    
    public function recordFailure(string $service): void
    {
        $this->storage->increment("failures:$service");
    }
}
```

**Application Layer Implements Storage:**
```php
// app/Services/RedisCircuitBreakerStorage.php
class RedisCircuitBreakerStorage implements CircuitBreakerStorageInterface
{
    public function increment(string $key): int
    {
        return Redis::incr($key);
    }
}
```

### 5. Compliance Architecture: Decoupling Process from Reporting

**Problem:** Tax rates change frequently; core logic must not be refactored

**Solution:** Two separate packages

#### Nexus\Compliance (Process Enforcement)

**Purpose:** Enforce internal controls and feature composition

**Examples:**
- ISO 14001: Force hazardous material fields on assets
- Segregation of duties checks
- Feature flags based on licensing

#### Nexus\Statutory (Reporting Compliance)

**Purpose:** Filing formats mandated by legal authorities

**Examples:**
- Malaysian payroll: EPF, SOCSO, PCB calculations
- SSM BRST taxonomy for financial statements
- Tax reporting schemas

**Pluggable Architecture:**
```php
// Default (no deductions)
PayrollStatutoryInterface → DefaultStatutoryCalculator

// If Malaysia package enabled
PayrollStatutoryInterface → MYSStatutoryCalculator (EPF/SOCSO/PCB)
```

**Benefit:** New countries added as packages without touching core

### 6. Migration Numbering: 5-Digit Stepped Format

**Pattern:** `YYYY_MM_DD_NNNNN_description.php`

**Formula:** `NNNNN = (Domain × 1000) + (Step × 10)`

**Domain Allocation:**
- Domain 11 (11000-11990): Infrastructure (EventStream, Tenant, Setting)
- Domain 21 (21000-21990): Finance Core (GL, Accounting)
- Domain 22 (22000-22990): CashManagement
- Domain 23 (23000-23990): Currency, Budget
- Domain 31 (31000-31990): Assets

**Stepping:** Increment by 10 (allows 9 insertions between migrations)

**Example:**
```
2024_11_22_11000_create_event_streams_table.php
2024_11_22_11010_create_event_snapshots_table.php
2024_11_22_21000_create_accounts_table.php
2024_11_22_21010_create_journal_entries_table.php
```

**Benefits:**
- Clear domain grouping
- Room for insertions
- Human-readable ordering
- Supports 99 domains × 99 migrations = 9,801 total capacity

---

## Database Architecture

### Schema Design Principles

1. **ULID Primary Keys:**
   ```sql
   id CHAR(26) PRIMARY KEY  -- Not BIGINT AUTO_INCREMENT
   ```

2. **Tenant Isolation:**
   ```sql
   tenant_id CHAR(26) NOT NULL,
   FOREIGN KEY (tenant_id) REFERENCES tenants(id),
   INDEX idx_tenant_id (tenant_id)
   ```

3. **Monetary Precision:**
   ```sql
   amount DECIMAL(20, 4)  -- 4 decimal places for cents
   ```

4. **Soft Deletes (Audit Trail):**
   ```sql
   deleted_at TIMESTAMP NULL,
   deleted_by CHAR(26) NULL
   ```

5. **Created/Updated Tracking:**
   ```sql
   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   created_by CHAR(26) NOT NULL,
   updated_at TIMESTAMP NULL,
   updated_by CHAR(26) NULL
   ```

### Critical Tables

#### Finance Domain

**accounts** (Chart of Accounts):
```sql
CREATE TABLE accounts (
    id CHAR(26) PRIMARY KEY,
    tenant_id CHAR(26) NOT NULL,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense'),
    normal_balance ENUM('debit', 'credit'),
    parent_account_id CHAR(26) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_code (tenant_id, account_code),
    INDEX idx_type (account_type)
);
```

**journal_entries** (GL Postings):
```sql
CREATE TABLE journal_entries (
    id CHAR(26) PRIMARY KEY,
    tenant_id CHAR(26) NOT NULL,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    entry_date DATE NOT NULL,
    period_id CHAR(26) NOT NULL,
    status ENUM('draft', 'posted', 'reversed'),
    description TEXT,
    posted_at TIMESTAMP NULL,
    posted_by CHAR(26) NULL,
    INDEX idx_tenant_period (tenant_id, period_id),
    INDEX idx_entry_date (entry_date)
);
```

**journal_entry_lines** (Double-Entry Details):
```sql
CREATE TABLE journal_entry_lines (
    id CHAR(26) PRIMARY KEY,
    journal_entry_id CHAR(26) NOT NULL,
    account_id CHAR(26) NOT NULL,
    debit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    credit_amount DECIMAL(20, 4) NOT NULL DEFAULT 0,
    description VARCHAR(500),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    CONSTRAINT chk_debit_or_credit CHECK (
        (debit_amount > 0 AND credit_amount = 0) OR
        (credit_amount > 0 AND debit_amount = 0)
    )
);
```

#### Period Management

**periods**:
```sql
CREATE TABLE periods (
    id CHAR(26) PRIMARY KEY,
    tenant_id CHAR(26) NOT NULL,
    period_type ENUM('accounting', 'inventory', 'payroll', 'manufacturing'),
    period_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    fiscal_year INT NOT NULL,
    status ENUM('pending', 'open', 'closed', 'locked'),
    INDEX idx_tenant_type_dates (tenant_id, period_type, start_date, end_date),
    UNIQUE KEY uk_tenant_type_name (tenant_id, period_type, period_name)
);
```

### Database Optimizations

1. **Covering Indexes:** Include columns used in WHERE, JOIN, ORDER BY
2. **Partial Indexes:** For commonly filtered subsets (e.g., `WHERE is_active = TRUE`)
3. **Query Optimization:** Use EXPLAIN ANALYZE for critical paths
4. **Connection Pooling:** PgBouncer for PostgreSQL
5. **Read Replicas:** For reporting queries

---

## API Design

### RESTful API Principles

**Base URL:** `/api/v1/`

**Authentication:** Bearer token (JWT or Laravel Sanctum)

**Versioning:** URL-based (`/v1/`, `/v2/`)

### Standard Endpoints

**Pattern:** `/{resource}` and `/{resource}/{id}`

**Example: Finance Accounts**

```http
GET    /api/v1/accounts              # List all accounts (paginated)
GET    /api/v1/accounts/{id}         # Get single account
POST   /api/v1/accounts              # Create new account
PUT    /api/v1/accounts/{id}         # Update account
DELETE /api/v1/accounts/{id}         # Delete account (soft delete)
```

**Example: Journal Entries**

```http
GET    /api/v1/journal-entries                    # List entries
POST   /api/v1/journal-entries                    # Create draft entry
POST   /api/v1/journal-entries/{id}/post          # Post entry to GL
POST   /api/v1/journal-entries/{id}/reverse       # Reverse posted entry
GET    /api/v1/journal-entries/{id}/audit-trail   # Get audit history
```

### Request/Response Format

**Request (POST /api/v1/accounts):**
```json
{
  "account_code": "1000",
  "account_name": "Cash in Bank",
  "account_type": "asset",
  "normal_balance": "debit",
  "parent_account_id": null
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "account_code": "1000",
    "account_name": "Cash in Bank",
    "account_type": "asset",
    "normal_balance": "debit",
    "is_active": true,
    "created_at": "2024-11-22T10:30:00Z"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "Validation failed",
  "errors": {
    "account_code": ["The account code has already been taken."],
    "account_type": ["The selected account type is invalid."]
  }
}
```

### Pagination

**Request:**
```http
GET /api/v1/accounts?page=2&per_page=50
```

**Response:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "per_page": 50,
    "total": 250,
    "last_page": 5
  },
  "links": {
    "first": "/api/v1/accounts?page=1",
    "prev": "/api/v1/accounts?page=1",
    "next": "/api/v1/accounts?page=3",
    "last": "/api/v1/accounts?page=5"
  }
}
```

### Filtering & Sorting

**Request:**
```http
GET /api/v1/accounts?filter[account_type]=asset&sort=-created_at
```

**Supported Filters:**
- `filter[field]=value` - Exact match
- `filter[field]=value1,value2` - IN clause
- `sort=field` - Ascending
- `sort=-field` - Descending

---

## Security & Compliance

### Authentication Layers

1. **API Token:** Bearer token (Sanctum/JWT)
2. **Session:** For admin UI (planned Filament)
3. **OAuth2:** For external integrations

### Authorization (RBAC)

**Role-Based Access Control:**

```php
// Roles
'admin', 'accountant', 'sales_manager', 'viewer'

// Permissions (wildcard support)
'finance.accounts.*'       // All account operations
'finance.journal_entry.create'
'finance.journal_entry.post'
'reports.*.view'           // View all reports
```

**Permission Check:**
```php
if ($user->can('finance.journal_entry.post')) {
    $this->financeManager->postJournalEntry($entryId);
}
```

### Data Protection

1. **Encryption at Rest:** Sensitive fields (PII, credit cards)
2. **Encryption in Transit:** TLS 1.3 for all API traffic
3. **Data Masking:** For non-production environments
4. **Audit Trail:** All state changes logged (immutable)

### Compliance Features

**GDPR/PDPA Support:**
- Right to access (export user data)
- Right to erasure (pseudonymization)
- Data portability (JSON/CSV export)
- Consent management

**SOX/IFRS Compliance (Finance):**
- EventStream for immutable GL history
- Temporal queries for point-in-time balance
- Audit trail for all journal entries
- Period locking to prevent backdating

**Malaysian Statutory Compliance:**
- EPF, SOCSO, PCB calculations (Nexus\PayrollMysStatutory)
- SSM BRST taxonomy for financial statements
- Lembaga Hasil Dalam Negeri (LHDN) tax reporting

---

## Development Workflow

### Creating a New Package

**Step 1: Create Directory**
```bash
mkdir -p packages/MyPackage/src/{Contracts,Services,Exceptions}
cd packages/MyPackage
```

**Step 2: Initialize Composer**
```bash
composer init
# Name: nexus/my-package
# PSR-4: Nexus\MyPackage\: src/
```

**Step 3: Define Contracts**
```php
// src/Contracts/MyEntityInterface.php
namespace Nexus\MyPackage\Contracts;

interface MyEntityInterface
{
    public function getId(): string;
    public function getName(): string;
}

// src/Contracts/MyRepositoryInterface.php
interface MyRepositoryInterface
{
    public function findById(string $id): ?MyEntityInterface;
    public function save(MyEntityInterface $entity): void;
}
```

**Step 4: Implement Service**
```php
// src/Services/MyManager.php
namespace Nexus\MyPackage\Services;

final readonly class MyManager
{
    public function __construct(
        private MyRepositoryInterface $repository
    ) {}
    
    public function create(array $data): string
    {
        // Business logic here
        return $this->repository->save($data);
    }
}
```

**Step 5: Register in Monorepo**
```json
// Root composer.json
{
  "repositories": [
    {"type": "path", "url": "./packages/MyPackage"}
  ]
}
```

**Step 6: Install in Atomy**
```bash
cd apps/Atomy
composer require nexus/my-package:"*@dev"
```

**Step 7: Implement in Atomy**
```php
// app/Models/MyEntity.php
class MyEntity extends Model implements MyEntityInterface
{
    protected $fillable = ['name'];
    
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
}

// app/Repositories/EloquentMyRepository.php
class EloquentMyRepository implements MyRepositoryInterface
{
    public function findById(string $id): ?MyEntityInterface
    {
        return MyEntity::find($id);
    }
}

// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(MyRepositoryInterface::class, EloquentMyRepository::class);
}
```

### Implementing a Feature

**User Story:** "As a user, I want to view account balances"

**Step 1: Is logic missing?**
→ No, `FinanceManager` already has `getAccountBalance()`

**Step 2: Is persistence missing?**
→ Check `accounts` table exists and `AccountRepository` implemented

**Step 3: Add API route**
```php
// routes/api.php
Route::get('/v1/accounts/{id}/balance', [FinanceController::class, 'getBalance']);
```

**Step 4: Implement controller**
```php
// app/Http/Controllers/Api/FinanceController.php
public function getBalance(string $id): JsonResponse
{
    $balance = $this->financeManager->getAccountBalance($id);
    return response()->json(['balance' => $balance]);
}
```

---

## Testing Strategy

### Package-Level Tests (Unit)

**Location:** `packages/Finance/tests/`

**Purpose:** Test business logic in isolation

**Example:**
```php
// packages/Finance/tests/Services/FinanceManagerTest.php
class FinanceManagerTest extends TestCase
{
    public function test_calculates_account_balance_correctly(): void
    {
        $mockRepo = $this->createMock(AccountRepositoryInterface::class);
        $mockRepo->method('getTransactions')->willReturn([
            ['type' => 'debit', 'amount' => '1000.00'],
            ['type' => 'credit', 'amount' => '500.00'],
        ]);
        
        $manager = new FinanceManager($mockRepo);
        $balance = $manager->getAccountBalance('acc-123');
        
        $this->assertEquals('500.00', $balance);
    }
}
```

**Coverage Requirement:** >95% for packages

### Application-Level Tests (Integration)

**Location:** `apps/Atomy/tests/Feature/`

**Purpose:** Test database, repositories, API endpoints

**Example:**
```php
// apps/Atomy/tests/Feature/FinanceTest.php
class FinanceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_account_via_api(): void
    {
        $response = $this->postJson('/api/v1/accounts', [
            'account_code' => '1000',
            'account_name' => 'Cash',
            'account_type' => 'asset',
        ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'account_code']]);
            
        $this->assertDatabaseHas('accounts', [
            'account_code' => '1000',
            'account_name' => 'Cash',
        ]);
    }
}
```

**Coverage Requirement:** >85% for Atomy

### Factory Pattern (Test Data)

**State methods for test scenarios:**
```php
// database/factories/Finance/AccountFactory.php
class AccountFactory extends Factory
{
    public function asset(): static
    {
        return $this->state(['account_type' => 'asset', 'normal_balance' => 'debit']);
    }
    
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

// Usage in tests
$account = Account::factory()->asset()->active()->create();
```

**Requirement:** Every factory must have corresponding test verifying state methods

---

## Deployment Architecture

### Horizontal Scalability

**Design Principles:**
- Stateless application instances
- Shared Redis for cache/sessions
- Database connection pooling
- Queue workers scale independently

**Infrastructure:**
```
                    ┌─────────────┐
                    │Load Balancer│
                    └──────┬──────┘
         ┌─────────────────┼─────────────────┐
         │                 │                 │
    ┌────▼────┐       ┌────▼────┐      ┌────▼────┐
    │ Atomy 1 │       │ Atomy 2 │      │ Atomy 3 │
    └────┬────┘       └────┬────┘      └────┬────┘
         └─────────────────┼─────────────────┘
                           │
         ┌─────────────────┴─────────────────┐
         │                                   │
    ┌────▼────┐                         ┌────▼────┐
    │PostgreSQL│                         │  Redis  │
    │(Primary) │                         │ Cluster │
    └────┬────┘                         └─────────┘
         │
    ┌────▼────┐
    │ Replica │
    │(Read)   │
    └─────────┘
```

### Environment Separation

**Development:**
- Local Docker containers
- SQLite or PostgreSQL
- Hot reload for rapid iteration

**Staging:**
- Mirrors production architecture
- Anonymized production data
- Full integration testing

**Production:**
- Multi-instance deployment
- PostgreSQL with replication
- Redis Cluster
- CDN for API responses (optional)

### Deployment Pipeline

**Build → Test → Deploy:**
```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader

# 2. Run tests
vendor/bin/phpunit --testsuite=Feature

# 3. Cache optimization
php artisan config:cache
php artisan route:cache

# 4. Migrate database
php artisan migrate --force

# 5. Clear cache
php artisan optimize:clear
```

---

## Quick Reference

### Package Rules (MUST/MUST NOT)

**MUST:**
- Define persistence needs via interfaces
- Use constructor property promotion
- Use `readonly` properties
- Use native enums (not class constants)
- Throw descriptive exceptions
- Include comprehensive README
- Target PHP 8.3+

**MUST NOT:**
- Use Laravel facades (Log::, Cache::, DB::)
- Use global helpers (now(), config(), app())
- Contain database migrations
- Contain Eloquent models
- Reference application code
- Use auto-incrementing integer IDs

### Service Provider Binding Rules

**Rule A (REQUIRED):** Bind package interfaces to application implementations
```php
$this->app->bind(AccountRepositoryInterface::class, EloquentAccountRepository::class);
```

**Rule B (REQUIRED):** Bind interfaces to package-provided defaults
```php
$this->app->singleton(TenantContextInterface::class, TenantContextManager::class);
```

**Rule C (FORBIDDEN):** Never bind concrete package classes (auto-resolved)
```php
// ❌ WRONG: Laravel auto-resolves concrete classes
$this->app->singleton(FinanceManager::class);
```

### Common Patterns

**Value Object (Immutable):**
```php
final readonly class Money
{
    public function __construct(
        public string $amount,
        public string $currency
    ) {}
    
    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException();
        }
        return new Money(
            bcadd($this->amount, $other->amount, 4),
            $this->currency
        );
    }
}
```

**Repository Interface:**
```php
interface AccountRepositoryInterface
{
    public function findById(string $id): ?AccountInterface;
    public function findByCode(string $code): ?AccountInterface;
    public function save(AccountInterface $account): void;
    public function delete(string $id): void;
}
```

**Service Manager:**
```php
final readonly class FinanceManager implements FinanceManagerInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private JournalEntryRepositoryInterface $journalRepository,
        private AuditLoggerInterface $auditLogger
    ) {}
    
    public function postJournalEntry(string $entryId): void
    {
        $entry = $this->journalRepository->findById($entryId);
        
        if (!$entry->isBalanced()) {
            throw new UnbalancedJournalEntryException();
        }
        
        $entry->post();
        $this->journalRepository->save($entry);
        $this->auditLogger->log($entryId, 'posted', 'Journal entry posted to GL');
    }
}
```

### Dependency Graph (Simplified)

```
Foundation Layer (No Dependencies):
  Tenant, Sequencing, Uom, Setting

Core Business Layer:
  Period → (Tenant, Sequencing, AuditLogger)
  Finance → (Period, Currency, Party, Sequencing)
  Identity → (Tenant)

Domain Layer:
  Accounting → (Finance, Period, Analytics)
  Receivable → (Finance, Sales, Party, Currency, Period)
  Payable → (Finance, Party, Currency, Period)
  Sales → (Party, Product, Uom, Currency, Finance)

Integration Layer:
  Connector → (Crypto, Storage)
  Notifier → (Connector, Identity)
  Reporting → (Analytics, Export)
```

### File Naming Conventions

**Packages:**
- Interfaces: `{Name}Interface.php` (e.g., `AccountInterface.php`)
- Repositories: `{Entity}RepositoryInterface.php`
- Services: `{Domain}Manager.php` (e.g., `FinanceManager.php`)
- Value Objects: `{Concept}.php` (e.g., `Money.php`)
- Enums: `{Concept}.php` (e.g., `AccountType.php`)
- Exceptions: `{Error}Exception.php`

**Application:**
- Models: `{Entity}.php` (e.g., `Account.php`)
- Repositories: `Eloquent{Entity}Repository.php`
- Migrations: `YYYY_MM_DD_NNNNN_{description}.php`
- Factories: `{Entity}Factory.php`
- Controllers: `{Domain}Controller.php`

---

## Key Takeaways for AI Assistants

### When Working on Nexus:

1. **Always check which layer you're in:**
   - `packages/` → Pure PHP, no framework code
   - `apps/Atomy/` → Laravel implementation

2. **Never violate package isolation:**
   - Packages define interfaces
   - Applications implement interfaces
   - Packages never import from applications

3. **Use ULIDs for all primary keys:**
   - Not auto-incrementing integers
   - Format: `01ARZ3NDEKTSV4RRFFQ69G5FAV`

4. **Multi-tenancy is mandatory:**
   - Every entity needs `tenant_id`
   - Queue jobs need `TenantAwareJob` trait

5. **Choose the right event system:**
   - AuditLogger for timelines (95% of cases)
   - EventStream for compliance (GL, Inventory only)

6. **Follow migration numbering:**
   - 5-digit format: `YYYY_MM_DD_NNNNN_description.php`
   - Domain-based: Finance (21000+), Assets (31000+)
   - Step by 10: allows insertions

7. **Test everything:**
   - Packages: >95% coverage (unit tests)
   - Atomy: >85% coverage (integration tests)
   - Factory states must have tests

8. **Security first:**
   - Bearer token authentication
   - RBAC with wildcard permissions
   - Audit all state changes
   - Encrypt sensitive data

### Common Questions Answered:

**Q: Where do I add a new migration?**
→ `apps/Atomy/database/migrations/` (NEVER in packages)

**Q: Where do I define business logic?**
→ `packages/{Domain}/src/Services/{Manager}.php`

**Q: How do I access the database from a package?**
→ You don't. Define a repository interface; Atomy implements it.

**Q: Can I use `now()` in a package?**
→ No. Inject `ClockInterface` or use `new \DateTimeImmutable()`

**Q: How do I add a new API endpoint?**
→ Add route in `apps/Atomy/routes/api.php`, controller in `app/Http/Controllers/Api/`

**Q: Should I use EventStream for this feature?**
→ Only if it's Finance GL or Inventory AND you need temporal queries for compliance

**Q: How do I handle multi-currency?**
→ Use `Money` value object (in Finance package), store `currency_id` in transactions

**Q: What's the difference between Compliance and Statutory?**
→ Compliance = process enforcement (ISO, SOD)  
→ Statutory = reporting formats (tax, financial statements)

---

**End of Document**

This comprehensive overview should provide ChatGPT or any AI assistant with a complete understanding of the Nexus ERP project architecture, design decisions, implementation status, and development patterns.
