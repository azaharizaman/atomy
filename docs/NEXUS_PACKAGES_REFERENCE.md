# 📚 NEXUS FIRST-PARTY PACKAGES REFERENCE GUIDE

**Version:** 1.1  
**Last Updated:** November 25, 2025  
**Target Audience:** Coding Agents & Developers  
**Purpose:** Prevent architectural violations by explicitly documenting available packages and their proper usage patterns.

**Recent Updates:**
- Added `Nexus\Manufacturing` - Complete MRP II (BOM, Routing, Work Orders, Capacity Planning, ML Forecasting)
- Added `Nexus\FeatureFlags` - Feature flag management system
- Added `Nexus\SSO` - Single Sign-On integration (SAML, OAuth2, OIDC)
- Added `Nexus\Tax` - Tax calculation and compliance engine
- Added `Nexus\Messaging` - Message queue abstraction
- Added `Nexus\Content` - Content management system
- Added `Nexus\Audit` - Advanced audit trail management
- Added `Nexus\Backoffice` - Company structure and organizational management
- Added `Nexus\DataProcessor` - OCR, ETL, and data processing engine
- Refactored `Nexus\Intelligence` → `Nexus\MachineLearning` (v2.0)

---

## 🎯 Golden Rule for Implementation

> **BEFORE implementing ANY feature, ALWAYS check this guide first.**
>
> If a first-party Nexus package already provides the capability, you MUST use it via dependency injection. Creating a new implementation is an **architectural violation** unless the package doesn't exist or doesn't cover the use case.

---

## 🚨 Common Violations & How to Avoid Them

| ❌ Violation | ✅ Correct Approach |
|-------------|---------------------|
| Creating custom metrics collector | Use `Nexus\Monitoring\Contracts\TelemetryTrackerInterface` |
| Writing custom audit logging | Use `Nexus\AuditLogger\Contracts\AuditLogManagerInterface` |
| Building notification system | Use `Nexus\Notifier\Contracts\NotificationManagerInterface` |
| Implementing file storage | Use `Nexus\Storage\Contracts\StorageInterface` |
| Creating sequence generator | Use `Nexus\Sequencing\Contracts\SequencingManagerInterface` |
| Managing multi-tenancy context | Use `Nexus\Tenant\Contracts\TenantContextInterface` |
| Handling currency conversions | Use `Nexus\Currency\Contracts\CurrencyManagerInterface` |
| Processing events | Use `Nexus\EventStream` or publish to event dispatcher |

---

## 📦 Available Packages by Category

### 🔐 **1. Security & Identity**

#### **Nexus\Domain\Identity**
**Capabilities:**
- User authentication (session, token, MFA)
- Authorization (RBAC, permissions, policies)
- Role and permission management
- Password hashing and verification
- Token generation and validation
- Session management

**When to Use:**
- ✅ User login/logout
- ✅ Permission checking
- ✅ Role assignment
- ✅ Multi-factor authentication
- ✅ API token generation

**Key Interfaces:**
```php
use Nexus\Domain\Identity\Contracts\AuthenticationManagerInterface;
use Nexus\Domain\Identity\Contracts\AuthorizationManagerInterface;
use Nexus\Domain\Identity\Contracts\UserRepositoryInterface;
use Nexus\Domain\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Domain\Identity\Contracts\PermissionRepositoryInterface;
```

**Example:**
```php
// ✅ CORRECT: Check if user can perform action
public function __construct(
    private readonly AuthorizationManagerInterface $authorization
) {}

public function deleteInvoice(string $invoiceId): void
{
    if (!$this->authorization->can('delete', 'invoice')) {
        throw new UnauthorizedException();
    }
    // ... delete logic
}
```

---

#### **Nexus\SSO** ⏳ **PLANNED**
**Capabilities:**
- Single Sign-On (SSO) orchestration
- SAML 2.0 authentication
- OAuth2/OIDC authentication
- Azure AD (Entra ID) integration
- Google Workspace integration
- Okta integration
- Just-In-Time (JIT) user provisioning
- Configurable attribute mapping (IdP → local)
- Single Logout (SLO) support
- Multi-tenant SSO configuration

**When to Use:**
- ✅ Enterprise SSO integration
- ✅ SAML 2.0 authentication
- ✅ OAuth2/OIDC authentication
- ✅ Azure AD login
- ✅ Google Workspace login
- ✅ Auto-provision users from IdP
- ✅ Map IdP attributes to local user fields

**Key Interfaces:**
```php
use Nexus\SSO\Contracts\SsoManagerInterface;
use Nexus\SSO\Contracts\SsoProviderInterface;
use Nexus\SSO\Contracts\SamlProviderInterface;
use Nexus\SSO\Contracts\OAuthProviderInterface;
use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\SSO\Contracts\AttributeMapperInterface;
```

**Example:**
```php
// ✅ CORRECT: Initiate SSO login with Azure AD
public function __construct(
    private readonly SsoManagerInterface $ssoManager
) {}

public function loginWithAzure(string $tenantId): array
{
    $result = $this->ssoManager->initiateLogin(
        providerName: 'azure',
        tenantId: $tenantId,
        parameters: ['returnUrl' => '/dashboard']
    );
    
    // Redirect user to $result['authUrl']
    return $result;
}

// Handle SSO callback
public function handleCallback(string $code, string $state): SsoSession
{
    return $this->ssoManager->handleCallback(
        providerName: 'azure',
        callbackData: ['code' => $code],
        state: $state
    );
}
```

**❌ WRONG:**
```php
// Creating custom SAML handler violates DRY principle
final class CustomSamlHandler {
    public function handleSamlResponse($response) {
        // ... duplicates Nexus\SSO functionality
    }
}
```

**Integration with Identity:**
```php
// Nexus\SSO defines UserProvisioningInterface
// Consuming application implements it using Nexus\Domain\Identity
namespace App\Services\SSO;

use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\Domain\Identity\Contracts\UserManagerInterface;

final readonly class IdentityUserProvisioner implements UserProvisioningInterface
{
    public function __construct(
        private UserManagerInterface $userManager
    ) {}
    
    public function findOrCreateUser(UserProfile $profile, string $provider, string $tenantId): string
    {
        // Find existing user or create new one (JIT provisioning)
        return $this->userManager->findOrCreateFromSso($profile);
    }
}
```

---

### 📊 **2. Observability & Monitoring**

#### **Nexus\Monitoring**
**Capabilities:**
- Metrics tracking (counters, gauges, histograms)
- Performance monitoring (APM, distributed tracing)
- Health checks and availability monitoring
- Prometheus export format
- Alert threshold configuration
- Multi-tenant metric isolation
- Cardinality protection

**When to Use:**
- ✅ Track business metrics (orders, revenue, users)
- ✅ Monitor performance (API latency, database query time)
- ✅ Record application health
- ✅ Export metrics to Prometheus/Grafana
- ✅ Set up alerts for SLA violations

**Key Interfaces:**
```php
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Nexus\Monitoring\Contracts\HealthCheckerInterface;
use Nexus\Monitoring\Contracts\MetricExporterInterface;
```

**Example:**
```php
// ✅ CORRECT: Track event append performance
public function __construct(
    private readonly TelemetryTrackerInterface $telemetry
) {}

public function appendEvent(string $streamName, EventInterface $event): void
{
    $startTime = microtime(true);
    
    try {
        $this->eventStore->append($streamName, $event);
        
        // Track success metric
        $this->telemetry->increment('eventstream.events_appended', tags: [
            'stream_name' => $streamName,
        ]);
        
        // Track duration
        $durationMs = (microtime(true) - $startTime) * 1000;
        $this->telemetry->timing('eventstream.append_duration_ms', $durationMs);
        
    } catch (\Throwable $e) {
        // Track error
        $this->telemetry->increment('eventstream.append_errors', tags: [
            'error_type' => get_class($e),
        ]);
        throw $e;
    }
}
```

**❌ WRONG:**
```php
// Creating custom PrometheusMetricsCollector violates DRY principle
final class CustomMetricsCollector {
    private Counter $eventsCounter;
    // ... duplicates Nexus\Monitoring functionality
}
```

---

#### **Nexus\AuditLogger**
**Capabilities:**
- Comprehensive audit trail (CRUD operations)
- User action tracking
- Timeline/feed views
- Retention policies
- Compliance-ready logging (SOX, GDPR)
- Multi-tenant isolation

**When to Use:**
- ✅ Log user actions (created, updated, deleted records)
- ✅ Track approval workflows
- ✅ Record configuration changes
- ✅ Compliance audit trails
- ✅ Display activity feeds to users

**Key Interfaces:**
```php
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
```

**Example:**
```php
// ✅ CORRECT: Log invoice status change
public function __construct(
    private readonly AuditLogManagerInterface $auditLogger
) {}

public function updateInvoiceStatus(string $invoiceId, string $newStatus): void
{
    $invoice = $this->repository->findById($invoiceId);
    $oldStatus = $invoice->getStatus();
    
    $invoice->setStatus($newStatus);
    $this->repository->save($invoice);
    
    // Log the change
    $this->auditLogger->log(
        entityId: $invoiceId,
        action: 'status_change',
        description: "Invoice status changed from {$oldStatus} to {$newStatus}",
        metadata: [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]
    );
}
```

---

#### **Nexus\Audit**
**Capabilities:**
- Advanced audit trail management (extends AuditLogger)
- Change data capture (before/after snapshots)
- Audit trail search and filtering
- Compliance report generation
- Audit event replay
- Configurable retention policies
- Tamper-proof audit logs

**When to Use:**
- ✅ Detailed change tracking with full snapshots
- ✅ Compliance audits requiring historical data reconstruction
- ✅ Forensic analysis of data changes
- ✅ Regulatory compliance (HIPAA, SOX, GDPR)
- ✅ Advanced audit reporting

**Key Interfaces:**
```php
use Nexus\Audit\Contracts\AuditTrailManagerInterface;
use Nexus\Audit\Contracts\ChangeTrackerInterface;
use Nexus\Audit\Contracts\AuditReportGeneratorInterface;
```

**Example:**
```php
// ✅ CORRECT: Track detailed changes with before/after snapshots
public function __construct(
    private readonly ChangeTrackerInterface $changeTracker
) {}

public function updateCustomer(string $customerId, array $updates): void
{
    $customer = $this->repository->findById($customerId);
    $beforeSnapshot = $customer->toArray();
    
    $customer->update($updates);
    $this->repository->save($customer);
    
    $afterSnapshot = $customer->toArray();
    
    // Track with full before/after comparison
    $this->changeTracker->trackChange(
        entityType: 'customer',
        entityId: $customerId,
        before: $beforeSnapshot,
        after: $afterSnapshot,
        changedBy: $this->getCurrentUserId()
    );
}
```

---

### 🔔 **3. Communication**

#### **Nexus\Notifier**
**Capabilities:**
- Multi-channel notifications (email, SMS, push, in-app)
- Template management
- Delivery tracking and retry logic
- Notification preferences per user
- Batching and throttling
- Multi-tenant isolation

**When to Use:**
- ✅ Send email notifications
- ✅ SMS alerts
- ✅ Push notifications
- ✅ In-app notifications
- ✅ Scheduled reminders

**Key Interfaces:**
```php
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\Contracts\NotificationRepositoryInterface;
```

**Example:**
```php
// ✅ CORRECT: Send invoice payment reminder
public function __construct(
    private readonly NotificationManagerInterface $notifier
) {}

public function sendPaymentReminder(string $invoiceId): void
{
    $invoice = $this->repository->findById($invoiceId);
    
    $this->notifier->send(
        recipient: $invoice->getCustomerId(),
        channel: 'email',
        template: 'invoice.payment_reminder',
        data: [
            'invoice_number' => $invoice->getNumber(),
            'amount_due' => $invoice->getAmountDue(),
            'due_date' => $invoice->getDueDate(),
        ]
    );
}
```

---

### 💾 **4. Data Management**

#### **Nexus\Storage**
**Capabilities:**
- File storage abstraction (local, S3, Azure, GCS)
- File versioning
- Access control and permissions
- Temporary file management
- Multi-tenant file isolation

**When to Use:**
- ✅ Upload user files (invoices, receipts, documents)
- ✅ Store generated reports
- ✅ Manage attachments
- ✅ Handle temporary files

**Key Interfaces:**
```php
use Nexus\Storage\Contracts\StorageInterface;
use Nexus\Storage\Contracts\FileRepositoryInterface;
```

**Example:**
```php
// ✅ CORRECT: Store uploaded invoice attachment
public function __construct(
    private readonly StorageInterface $storage
) {}

public function attachFile(string $invoiceId, string $filePath, string $fileName): string
{
    $fileId = $this->storage->store(
        path: "invoices/{$invoiceId}/{$fileName}",
        contents: file_get_contents($filePath),
        metadata: [
            'entity_type' => 'invoice',
            'entity_id' => $invoiceId,
        ]
    );
    
    return $fileId;
}
```

---

#### **Nexus\Document**
**Capabilities:**
- Document management with versioning
- Document metadata and tagging
- Access permissions and sharing
- Document workflows (draft, review, approved)
- Full-text search

**When to Use:**
- ✅ Manage contracts and agreements
- ✅ Version-controlled documents
- ✅ Document approval workflows
- ✅ Policy and procedure management

**Key Interfaces:**
```php
use Nexus\Document\Contracts\DocumentManagerInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
```

---

#### **Nexus\EventStream**
**Capabilities:**
- Event sourcing for critical domains
- Immutable event log
- State reconstruction (temporal queries)
- Snapshot management
- Projection engine
- Event versioning and upcasting

**When to Use:**
- ✅ Finance (GL) - Every debit/credit as event
- ✅ Inventory - Stock movements as events
- ✅ Compliance - Full audit trail with replay capability
- ✅ Temporal queries ("What was balance on 2024-10-15?")

**Key Interfaces:**
```php
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\ProjectorInterface;
```

**Example:**
```php
// ✅ CORRECT: Record GL transaction as events
public function __construct(
    private readonly EventStoreInterface $eventStore
) {}

public function postJournalEntry(JournalEntry $entry): void
{
    foreach ($entry->getLines() as $line) {
        $event = match ($line->getType()) {
            'debit' => new AccountDebitedEvent(
                accountId: $line->getAccountId(),
                amount: $line->getAmount(),
                journalEntryId: $entry->getId()
            ),
            'credit' => new AccountCreditedEvent(
                accountId: $line->getAccountId(),
                amount: $line->getAmount(),
                journalEntryId: $entry->getId()
            ),
        };
        
        $this->eventStore->append($line->getAccountId(), $event);
    }
}

// Query historical state
public function getBalanceAt(string $accountId, \DateTimeImmutable $timestamp): Money
{
    $events = $this->eventStore->readStreamUntil($accountId, $timestamp);
    return $this->calculateBalance($events);
}
```

---

#### **Nexus\DataProcessor**
**Capabilities:**
- OCR (Optical Character Recognition) integration
- Document text extraction
- ETL (Extract, Transform, Load) pipelines
- Data transformation and normalization
- Image processing and analysis
- PDF parsing and extraction
- Batch data processing

**When to Use:**
- ✅ Extract text from scanned documents/images
- ✅ Process uploaded invoices/receipts via OCR
- ✅ Transform data between formats
- ✅ Build ETL data pipelines
- ✅ Parse and extract data from PDFs
- ✅ Batch process large datasets

**Key Interfaces:**
```php
use Nexus\DataProcessor\Contracts\OcrProcessorInterface;
use Nexus\DataProcessor\Contracts\DocumentExtractorInterface;
use Nexus\DataProcessor\Contracts\EtlPipelineInterface;
use Nexus\DataProcessor\Contracts\DataTransformerInterface;
```

**Example:**
```php
// ✅ CORRECT: Extract data from uploaded invoice image
public function __construct(
    private readonly OcrProcessorInterface $ocrProcessor
) {}

public function processInvoiceImage(string $imagePath): array
{
    $extractedData = $this->ocrProcessor->process(
        filePath: $imagePath,
        options: [
            'language' => 'eng',
            'extract_fields' => ['invoice_number', 'date', 'total', 'vendor'],
        ]
    );
    
    return [
        'invoice_number' => $extractedData['invoice_number'],
        'invoice_date' => $extractedData['date'],
        'total_amount' => $extractedData['total'],
        'vendor_name' => $extractedData['vendor'],
        'confidence' => $extractedData['confidence_score'],
    ];
}
```

---

### 🏢 **5. Multi-Tenancy & Context**

#### **Nexus\Backoffice**
**Capabilities:**
- Company structure management
- Multi-entity organizational hierarchy
- Branch and department management
- Cost center and profit center tracking
- Inter-company relationships
- Organizational unit configuration

**When to Use:**
- ✅ Manage company organizational structure
- ✅ Define branches, departments, divisions
- ✅ Set up cost centers and profit centers
- ✅ Configure inter-company relationships
- ✅ Hierarchical organizational reporting

**Key Interfaces:**
```php
use Nexus\Backoffice\Contracts\CompanyManagerInterface;
use Nexus\Backoffice\Contracts\BranchManagerInterface;
use Nexus\Backoffice\Contracts\DepartmentManagerInterface;
use Nexus\Backoffice\Contracts\CostCenterManagerInterface;
```

**Example:**
```php
// ✅ CORRECT: Get organizational hierarchy
public function __construct(
    private readonly CompanyManagerInterface $companyManager
) {}

public function getCompanyStructure(string $companyId): array
{
    $company = $this->companyManager->findById($companyId);
    
    return [
        'company' => $company,
        'branches' => $company->getBranches(),
        'departments' => $company->getDepartments(),
        'cost_centers' => $company->getCostCenters(),
    ];
}
```

---

#### **Nexus\Tenant**
**Capabilities:**
- Multi-tenant context management
- Tenant isolation
- Queue context propagation
- Tenant switching and impersonation
- Lifecycle management (create, suspend, delete)

**When to Use:**
- ✅ Any multi-tenant operation
- ✅ Scoping data queries by tenant
- ✅ Background job tenant context
- ✅ Tenant-specific configuration

**Key Interfaces:**
```php
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\Contracts\TenantLifecycleInterface;
```

**Example:**
```php
// ✅ CORRECT: Get current tenant context
public function __construct(
    private readonly TenantContextInterface $tenantContext,
    private readonly InvoiceRepositoryInterface $invoiceRepository
) {}

public function listInvoices(): array
{
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Repository automatically scopes by tenant
    return $this->invoiceRepository->findAll();
}
```

---

#### **Nexus\Period**
**Capabilities:**
- Fiscal period management
- Period opening/closing
- Period locking (prevent backdated transactions)
- Intelligent next-period creation
- Year-end rollover

**When to Use:**
- ✅ Financial period management
- ✅ Period close validation
- ✅ Prevent posting to closed periods
- ✅ Fiscal year setup

**Key Interfaces:**
```php
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
use Nexus\Period\Contracts\PeriodValidatorInterface;
```

**Example:**
```php
// ✅ CORRECT: Validate transaction date against period
public function __construct(
    private readonly PeriodValidatorInterface $periodValidator
) {}

public function postTransaction(\DateTimeImmutable $transactionDate): void
{
    if (!$this->periodValidator->isPeriodOpen($transactionDate)) {
        throw new PeriodClosedException(
            "Cannot post transaction to closed period"
        );
    }
    
    // ... post transaction
}
```

---

### 🔢 **6. Business Logic Utilities**

#### **Nexus\Sequencing**
**Capabilities:**
- Auto-numbering with patterns (INV-{YYYY}-{0001})
- Multiple sequence scopes (per-tenant, per-branch, global)
- Atomic counter management
- Prefix/suffix customization
- Reset policies (yearly, monthly, never)

**When to Use:**
- ✅ Generate invoice numbers
- ✅ Create PO numbers
- ✅ Employee ID generation
- ✅ Any auto-incrementing identifier

**Key Interfaces:**
```php
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
```

**Example:**
```php
// ✅ CORRECT: Generate invoice number
public function __construct(
    private readonly SequencingManagerInterface $sequencing
) {}

public function createInvoice(array $data): Invoice
{
    $invoiceNumber = $this->sequencing->getNext('customer_invoice');
    
    $invoice = new Invoice(
        number: $invoiceNumber,
        // ... other data
    );
    
    return $this->repository->save($invoice);
}
```

---

#### **Nexus\Uom**
**Capabilities:**
- Unit of measurement management
- Conversion between units
- Unit categories (weight, length, volume, etc.)
- Precision handling

**When to Use:**
- ✅ Product quantity management
- ✅ Unit conversions (kg to lb, m to ft)
- ✅ Recipe calculations
- ✅ Inventory tracking

**Key Interfaces:**
```php
use Nexus\Uom\Contracts\UomManagerInterface;
use Nexus\Uom\Contracts\UomRepositoryInterface;
```

**Example:**
```php
// ✅ CORRECT: Convert product quantity
public function __construct(
    private readonly UomManagerInterface $uomManager
) {}

public function convertQuantity(float $quantity, string $fromUom, string $toUom): float
{
    return $this->uomManager->convert($quantity, $fromUom, $toUom);
}
```

---

#### **Nexus\Currency**
**Capabilities:**
- Multi-currency support
- Exchange rate management
- Money calculations with precision
- Currency conversion
- Historical exchange rates

**When to Use:**
- ✅ Financial transactions in multiple currencies
- ✅ Currency conversion
- ✅ Exchange rate tracking
- ✅ Multi-currency reporting

**Key Interfaces:**
```php
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Contracts\ExchangeRateRepositoryInterface;
use Nexus\Currency\ValueObjects\Money;
```

**Example:**
```php
// ✅ CORRECT: Convert invoice amount to base currency
public function __construct(
    private readonly CurrencyManagerInterface $currencyManager
) {}

public function convertToBaseCurrency(Money $amount): Money
{
    return $this->currencyManager->convert(
        amount: $amount,
        toCurrency: 'MYR' // Base currency
    );
}
```

---

### 💼 **7. Financial Management**

#### **Nexus\Finance**
**Capabilities:**
- General ledger management
- Chart of accounts
- Journal entries
- Double-entry bookkeeping
- Trial balance
- Financial statement generation

**When to Use:**
- ✅ GL account management
- ✅ Journal entry posting
- ✅ Financial reporting
- ✅ Account reconciliation

**Key Interfaces:**
```php
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;
use Nexus\Finance\Contracts\ChartOfAccountsInterface;
use Nexus\Finance\Contracts\JournalEntryRepositoryInterface;
```

---

#### **Nexus\Accounting**
**Capabilities:**
- Financial statement generation (P&L, Balance Sheet, Cash Flow)
- Period close and consolidation
- Variance analysis
- Cost center reporting
- Budget vs actual

**When to Use:**
- ✅ Generate financial statements
- ✅ Period close processes
- ✅ Financial consolidation
- ✅ Management reporting

**Key Interfaces:**
```php
use Nexus\Accounting\Contracts\FinancialStatementGeneratorInterface;
use Nexus\Accounting\Contracts\PeriodCloseManagerInterface;
```

---

#### **Nexus\Receivable**
**Capabilities:**
- Customer invoicing
- Payment receipt processing
- Payment allocation (FIFO, manual, oldest-first)
- Credit control and collections
- Aging analysis
- Automatic GL integration

**When to Use:**
- ✅ Create customer invoices
- ✅ Process customer payments
- ✅ Allocate payments to invoices
- ✅ Aging reports
- ✅ Credit management

**Key Interfaces:**
```php
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\Contracts\PaymentReceiptRepositoryInterface;
```

---

#### **Nexus\Payable**
**Capabilities:**
- Vendor bill management
- Payment processing
- Aging analysis
- Payment scheduling
- Automatic GL integration

**Key Interfaces:**
```php
use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
```

---

#### **Nexus\CashManagement**
**Capabilities:**
- Bank account management
- Bank reconciliation
- Cash flow forecasting
- Payment method tracking

**Key Interfaces:**
```php
use Nexus\CashManagement\Contracts\BankAccountManagerInterface;
use Nexus\CashManagement\Contracts\ReconciliationManagerInterface;
```

---

#### **Nexus\Budget**
**Capabilities:**
- Budget planning and creation
- Budget tracking and monitoring
- Budget vs actual analysis
- Multi-dimensional budgeting (department, project, cost center)

**Key Interfaces:**
```php
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
```

---

#### **Nexus\Assets**
**Capabilities:**
- Fixed asset management
- Depreciation calculation (straight-line, declining balance)
- Asset lifecycle tracking
- Asset disposal and write-off

**Key Interfaces:**
```php
use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Contracts\DepreciationCalculatorInterface;
```

---

#### **Nexus\Tax**
**Capabilities:**
- Multi-jurisdiction tax calculation
- Tax rate management (VAT, GST, sales tax)
- Tax exemption handling
- Tax reporting and filing
- Reverse charge mechanism
- Withholding tax calculation
- Tax group and composite tax support

**When to Use:**
- ✅ Calculate sales tax on transactions
- ✅ Multi-jurisdiction tax compliance
- ✅ VAT/GST calculation and reporting
- ✅ Tax exemption management
- ✅ Withholding tax processing
- ✅ Tax audit trail

**Key Interfaces:**
```php
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\Contracts\TaxRateManagerInterface;
use Nexus\Tax\Contracts\TaxReportGeneratorInterface;
use Nexus\Tax\Contracts\TaxExemptionManagerInterface;
```

**Example:**
```php
// ✅ CORRECT: Calculate tax on invoice line item
public function __construct(
    private readonly TaxCalculatorInterface $taxCalculator
) {}

public function calculateInvoiceTax(Invoice $invoice): Money
{
    $totalTax = Money::zero('MYR');
    
    foreach ($invoice->getLineItems() as $lineItem) {
        $tax = $this->taxCalculator->calculate(
            amount: $lineItem->getAmount(),
            taxCode: $lineItem->getTaxCode(),
            jurisdiction: $invoice->getShipToAddress()->getCountry(),
            date: $invoice->getInvoiceDate()
        );
        
        $totalTax = $totalTax->add($tax);
    }
    
    return $totalTax;
}
```

---

### 🛒 **8. Sales & Procurement**

#### **Nexus\Party**
**Capabilities:**
- Party management (customers, vendors, employees, contacts)
- Party categorization and tagging
- Contact information management
- Party relationships

**When to Use:**
- ✅ Customer management
- ✅ Vendor management
- ✅ Contact directory
- ✅ Party hierarchy

**Key Interfaces:**
```php
use Nexus\Party\Contracts\PartyManagerInterface;
use Nexus\Party\Contracts\PartyRepositoryInterface;
```

---

#### **Nexus\Product**
**Capabilities:**
- Product catalog management
- Product categorization
- Pricing management
- Product variants
- SKU management

**Key Interfaces:**
```php
use Nexus\Product\Contracts\ProductManagerInterface;
use Nexus\Product\Contracts\ProductRepositoryInterface;
```

---

#### **Nexus\Sales**
**Capabilities:**
- Quotation management
- Sales order processing
- Quotation-to-order conversion
- Pricing engine
- Sales workflow

**Key Interfaces:**
```php
use Nexus\Sales\Contracts\SalesOrderManagerInterface;
use Nexus\Sales\Contracts\QuotationManagerInterface;
```

---

#### **Nexus\Procurement**
**Capabilities:**
- Purchase requisition management
- Purchase order processing
- Goods receipt
- 3-way matching (PO, GR, Invoice)

**Key Interfaces:**
```php
use Nexus\Procurement\Contracts\PurchaseOrderManagerInterface;
use Nexus\Procurement\Contracts\RequisitionManagerInterface;
```

---

#### **Nexus\Manufacturing**
**Capabilities:**
- **Bill of Materials (BOM)**: Multi-level BOMs with version control and effectivity dates
- **Routing Management**: Multi-operation routings with setup/run times and effectivity
- **Work Order Processing**: Complete lifecycle (Created → Released → In Progress → Completed)
- **MRP Engine**: Multi-level explosion, net requirements, lot-sizing (L4L, FOQ, EOQ, POQ)
- **Capacity Planning**: Finite/infinite capacity, bottleneck detection, resolution suggestions
- **Demand Forecasting**: ML-powered via MachineLearning package with historical fallback
- **Change Order Management**: Engineering change control with approval workflows

**When to Use:**
- ✅ Bill of Materials management with version control
- ✅ Production routing and operation sequencing
- ✅ Work order creation and lifecycle tracking
- ✅ Material Requirements Planning (MRP I/II)
- ✅ Capacity planning and bottleneck resolution
- ✅ Demand forecasting with ML integration

**Key Interfaces:**
```php
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Contracts\WorkOrderManagerInterface;
use Nexus\Manufacturing\Contracts\MrpEngineInterface;
use Nexus\Manufacturing\Contracts\CapacityPlannerInterface;
use Nexus\Manufacturing\Contracts\DemandForecasterInterface;
```

**Example:**
```php
// ✅ CORRECT: Run MRP and get planned orders
public function __construct(
    private readonly MrpEngineInterface $mrpEngine,
    private readonly BomManagerInterface $bomManager
) {}

public function planProduction(string $productId): array
{
    $horizon = new PlanningHorizon(
        startDate: new \DateTimeImmutable('today'),
        endDate: new \DateTimeImmutable('+90 days'),
        bucketSizeDays: 7,
        frozenZoneDays: 14,
        slushyZoneDays: 28
    );
    
    // Run MRP calculation
    $result = $this->mrpEngine->runMrp($productId, $horizon);
    
    return $result->getPlannedOrders();
}

// Create work order from BOM
public function createWorkOrder(string $productId, float $quantity): WorkOrderInterface
{
    $bom = $this->bomManager->findEffectiveBom($productId, new \DateTimeImmutable());
    
    return $this->workOrderManager->create(
        productId: $productId,
        quantity: $quantity,
        plannedStartDate: new \DateTimeImmutable('+3 days'),
        plannedEndDate: new \DateTimeImmutable('+10 days'),
        bomId: $bom->getId()
    );
}
```

**❌ WRONG:**
```php
// Creating custom BOM explosion logic violates DRY principle
final class CustomBomExploder {
    public function explode(array $bom, float $qty): array {
        // ... duplicates Nexus\Manufacturing functionality
    }
}
```

---

### 📦 **9. Inventory & Warehouse**

#### **Nexus\Inventory**
**Capabilities:**
- **Multi-Valuation Stock Tracking**: FIFO (O(n)), Weighted Average (O(1)), Standard Cost (O(1))
- **Lot Tracking with FEFO**: First-Expiry-First-Out enforcement for regulatory compliance (FDA, HACCP)
- **Serial Number Management**: Tenant-scoped uniqueness with history tracking
- **Stock Reservations**: Auto-expiry with configurable TTL (24-72 hours)
- **Inter-Warehouse Transfers**: FSM-based workflow (pending → in_transit → completed/cancelled)
- **Stock Movements**: Receipt, issue, adjustment (cycle count, damage, scrap)
- **Event-Driven GL Integration**: 8 domain events for Finance package integration

**When to Use:**
- ✅ Multi-warehouse inventory management
- ✅ Accurate COGS calculation (valuation method selection)
- ✅ Lot tracking with expiry date management
- ✅ Serial number tracking for high-value items
- ✅ Stock reservations for sales orders
- ✅ Inter-warehouse stock transfers

**Key Interfaces:**
```php
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Contracts\LotManagerInterface;
use Nexus\Inventory\Contracts\SerialNumberManagerInterface;
use Nexus\Inventory\Contracts\ReservationManagerInterface;
use Nexus\Inventory\Contracts\TransferManagerInterface;
```

**Valuation Methods:**

| Method | Performance | Best For | COGS Accuracy |
|--------|-------------|----------|---------------|
| **FIFO** | O(n) issue | Perishables, pharmaceuticals, food & beverage | Matches actual flow |
| **Weighted Average** | O(1) both | Commodities, bulk materials, chemicals | Smooths fluctuations |
| **Standard Cost** | O(1) both | Manufacturing, electronics | Variance analysis |

**FEFO Enforcement:**

Automatic allocation from lots with earliest expiry date:

```php
// System automatically picks from oldest expiring lots
$allocations = $lotManager->allocateFromLots($tenantId, $productId, quantity: 80.0);

// Example allocation result:
// LOT-2024-001: 40 units (expires 2024-02-01) ← Oldest expiry
// LOT-2024-002: 40 units (expires 2024-02-10) ← Next oldest
```

**Domain Events:**

| Event | Triggered When | GL Impact |
|-------|----------------|-----------|
| `StockReceivedEvent` | Stock received | DR Inventory Asset / CR GR-IR Clearing |
| `StockIssuedEvent` | Stock issued | DR COGS / CR Inventory Asset |
| `StockAdjustedEvent` | Stock adjusted | DR/CR Inventory Asset (variance) |
| `LotCreatedEvent` | Lot created | - |
| `LotAllocatedEvent` | FEFO allocation | - |
| `SerialRegisteredEvent` | Serial registered | - |
| `ReservationCreatedEvent` | Reservation created | - |
| `ReservationExpiredEvent` | Reservation expired | - |

**Example:**
```php
// Receive stock with lot tracking
public function __construct(
    private readonly StockManagerInterface $stockManager,
    private readonly LotManagerInterface $lotManager
) {}

public function receiveStock(): void
{
    // Create lot
    $lotId = $this->lotManager->createLot(
        tenantId: 'tenant-1',
        productId: 'product-milk',
        lotNumber: 'LOT-2024-001',
        quantity: 100.0,
        expiryDate: new \DateTimeImmutable('2024-02-01')
    );
    
    // Receive stock
    $this->stockManager->receiveStock(
        tenantId: 'tenant-1',
        productId: 'product-milk',
        warehouseId: 'warehouse-main',
        quantity: 100.0,
        unitCost: Money::of(15.00, 'MYR'),
        lotNumber: 'LOT-2024-001',
        expiryDate: new \DateTimeImmutable('2024-02-01')
    );
    
    // StockReceivedEvent published → GL posts: DR Inventory Asset / CR GR-IR
}

// Issue stock using FEFO
public function issueStock(): void
{
    // Allocate from lots (FEFO automatically applied)
    $allocations = $this->lotManager->allocateFromLots(
        tenantId: 'tenant-1',
        productId: 'product-milk',
        quantity: 30.0
    );
    
    // Issue stock and get COGS
    $cogs = $this->stockManager->issueStock(
        tenantId: 'tenant-1',
        productId: 'product-milk',
        warehouseId: 'warehouse-main',
        quantity: 30.0,
        reason: IssueReason::SALE,
        reference: 'SO-2024-005'
    );
    
    // StockIssuedEvent published → GL posts: DR COGS / CR Inventory Asset
}

// Reserve stock for sales order (with TTL)
public function reserveStock(): void
{
    $reservationId = $this->reservationManager->reserve(
        tenantId: 'tenant-1',
        productId: 'product-widget',
        warehouseId: 'warehouse-main',
        quantity: 25.0,
        referenceType: 'SALES_ORDER',
        referenceId: 'SO-2024-015',
        ttlHours: 48 // Auto-expire in 48 hours
    );
    
    // ReservationCreatedEvent published
}

// Inter-warehouse transfer (FSM workflow)
public function transferStock(): void
{
    // Initiate transfer (pending state)
    $transferId = $this->transferManager->initiateTransfer(
        tenantId: 'tenant-1',
        productId: 'product-gadget',
        fromWarehouseId: 'warehouse-main',
        toWarehouseId: 'warehouse-branch',
        quantity: 50.0,
        reason: 'REBALANCING'
    );
    
    // Start shipment (pending → in_transit)
    $this->transferManager->startShipment(
        transferId: $transferId,
        trackingNumber: 'TRK-ABC-12345'
    );
    
    // Complete transfer (in_transit → completed)
    $this->transferManager->completeTransfer($transferId);
    
    // Stock decremented at source, incremented at destination
}
```

---

#### **Nexus\Warehouse**
**Capabilities:**
- Warehouse management
- Location management (zones, bins, racks)
- Picking and packing
- Stock transfer between locations

**Key Interfaces:**
```php
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\LocationManagerInterface;
```

---

### 👥 **10. Human Resources**

#### **Nexus\Hrm**
**Capabilities:**
- Employee management
- Leave management and approvals
- Attendance tracking
- Performance review
- Employee lifecycle

**Key Interfaces:**
```php
use Nexus\Hrm\Contracts\EmployeeManagerInterface;
use Nexus\Hrm\Contracts\LeaveManagerInterface;
use Nexus\Hrm\Contracts\AttendanceManagerInterface;
```

---

#### **Nexus\Payroll**
**Capabilities:**
- Payroll processing framework
- Payslip generation
- Earnings and deductions
- Statutory calculation interface (EPF, SOCSO, PCB)

**Key Interfaces:**
```php
use Nexus\Payroll\Contracts\PayrollManagerInterface;
use Nexus\Payroll\Contracts\PayslipGeneratorInterface;
use Nexus\Payroll\Contracts\PayrollStatutoryInterface;
```

---

#### **Nexus\PayrollMysStatutory**
**Capabilities:**
- Malaysian EPF calculation
- Malaysian SOCSO calculation
- Malaysian PCB (tax) calculation
- Statutory report generation

**When to Use:**
- ✅ Malaysian payroll statutory compliance

**Key Interfaces:**
```php
use Nexus\PayrollMysStatutory\Contracts\MalaysianStatutoryCalculatorInterface;
```

---

### 🏭 **11. Operations**

#### **Nexus\FieldService**
**Capabilities:**
- Work order management
- Technician assignment
- Service contract management
- SLA tracking
- Field service scheduling

**Key Interfaces:**
```php
use Nexus\FieldService\Contracts\WorkOrderManagerInterface;
use Nexus\FieldService\Contracts\ServiceContractManagerInterface;
```

---

#### **Nexus\ProjectManagement**
**Capabilities:**
- Project tracking
- Task management
- Milestone tracking
- Timesheet management
- Resource allocation

**Key Interfaces:**
```php
use Nexus\ProjectManagement\Contracts\ProjectManagerInterface;
use Nexus\ProjectManagement\Contracts\TaskManagerInterface;
```

---

### 🔗 **12. Integration & Workflow**

#### **Nexus\Connector**
**Capabilities:**
- Integration hub with external systems
- Circuit breaker pattern
- Retry logic with exponential backoff
- OAuth support
- Rate limiting
- Connection health monitoring

**When to Use:**
- ✅ Integrate with external APIs
- ✅ Handle third-party service failures gracefully
- ✅ OAuth authentication flows
- ✅ API rate limiting

**Key Interfaces:**
```php
use Nexus\Connector\Contracts\ConnectorManagerInterface;
use Nexus\Connector\Contracts\ConnectionInterface;
use Nexus\Connector\Contracts\CircuitBreakerStorageInterface;
```

**Example:**
```php
// ✅ CORRECT: Call external API with circuit breaker
public function __construct(
    private readonly ConnectorManagerInterface $connector
) {}

public function syncCustomer(string $customerId): void
{
    $connection = $this->connector->getConnection('stripe');
    
    $response = $connection->request('POST', '/v1/customers', [
        'json' => ['customer_id' => $customerId],
    ]);
    
    // Connector automatically handles:
    // - Circuit breaker (stops calls if service is down)
    // - Retries with exponential backoff
    // - OAuth token refresh
    // - Rate limiting
}
```

---

#### **Nexus\Messaging**
**Capabilities:**
- Message queue abstraction (RabbitMQ, Redis, AWS SQS, Azure Service Bus)
- Publish/subscribe patterns
- Message routing and exchange management
- Dead letter queue handling
- Message retry logic
- Priority queues

**When to Use:**
- ✅ Asynchronous job processing
- ✅ Event-driven architecture
- ✅ Microservice communication
- ✅ Long-running background tasks
- ✅ Message-based integration

**Key Interfaces:**
```php
use Nexus\Messaging\Contracts\MessagePublisherInterface;
use Nexus\Messaging\Contracts\MessageConsumerInterface;
use Nexus\Messaging\Contracts\QueueManagerInterface;
use Nexus\Messaging\Contracts\MessageRouterInterface;
```

**Example:**
```php
// ✅ CORRECT: Publish message to queue
public function __construct(
    private readonly MessagePublisherInterface $publisher
) {}

public function createInvoice(Invoice $invoice): void
{
    $this->repository->save($invoice);
    
    // Publish invoice created event
    $this->publisher->publish(
        queue: 'invoice.created',
        message: new InvoiceCreatedMessage(
            invoiceId: $invoice->getId(),
            customerId: $invoice->getCustomerId(),
            amount: $invoice->getTotal()
        )
    );
}
```

---

#### **Nexus\Workflow**
**Capabilities:**
- Workflow engine
- State machine implementation
- Process automation
- Approval workflows
- Workflow versioning

**Key Interfaces:**
```php
use Nexus\Workflow\Contracts\WorkflowManagerInterface;
use Nexus\Workflow\Contracts\StateMachineInterface;
```

---

### 📊 **13. Reporting & Analytics**

#### **Nexus\Reporting**
**Capabilities:**
- Report definition and management
- Report execution engine
- Scheduled reports
- Report templates
- Multi-format export (PDF, Excel, CSV)

**Key Interfaces:**
```php
use Nexus\Reporting\Contracts\ReportManagerInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
```

---

#### **Nexus\Export**
**Capabilities:**
- Multi-format export (PDF, Excel, CSV, JSON, XML)
- Template-based export
- Large dataset handling (streaming)
- Export job queue

**When to Use:**
- ✅ Export data to Excel
- ✅ Generate PDF reports
- ✅ CSV data export
- ✅ Bulk data export

**Key Interfaces:**
```php
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Export\Contracts\ExporterInterface;
```

---

#### **Nexus\Import**
**Capabilities:**
- Multi-format data import (CSV, JSON, XML, Excel)
- Field mapping with transformations (13 built-in rules)
- Validation engine (required, email, numeric, date, length, min/max)
- Duplicate detection (internal and external)
- Transaction strategies (TRANSACTIONAL, BATCH, STREAM)
- Import modes (CREATE, UPDATE, UPSERT, DELETE, SYNC)
- Comprehensive error reporting (row-level, severity-based)
- Memory-efficient streaming for large datasets

**When to Use:**
- ✅ Bulk data import from CSV/Excel files
- ✅ Customer, product, or inventory imports
- ✅ Data migration from external systems
- ✅ Field transformation and validation
- ✅ Duplicate detection within import or against database
- ✅ Transaction management (all-or-nothing vs partial success)

**Key Interfaces:**
```php
use Nexus\Import\Contracts\ImportParserInterface;
use Nexus\Import\Contracts\TransactionManagerInterface;
use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Import\Contracts\ImportProcessorInterface;
use Nexus\Import\Contracts\TransformerInterface;
use Nexus\Import\Contracts\FieldMapperInterface;
use Nexus\Import\Contracts\ImportValidatorInterface;
use Nexus\Import\Contracts\DuplicateDetectorInterface;
```

**Example:**
```php
// ✅ CORRECT: Import customers with validation and duplicate detection
public function __construct(
    private readonly ImportManager $importManager,
    private readonly CustomerImportHandler $handler
) {}

public function importCustomers(string $filePath): ImportResult
{
    $result = $this->importManager->import(
        filePath: $filePath,
        format: ImportFormat::CSV,
        handler: $this->handler,
        mappings: [
            new FieldMapping(
                sourceField: 'customer_name',
                targetField: 'name',
                required: true,
                transformations: ['trim', 'capitalize']
            ),
            new FieldMapping(
                sourceField: 'email_address',
                targetField: 'email',
                required: true,
                transformations: ['trim', 'lower']
            ),
        ],
        mode: ImportMode::UPSERT,
        strategy: ImportStrategy::BATCH,
        validationRules: [
            new ValidationRule('email', 'email', 'Invalid email format'),
            new ValidationRule('name', 'required', 'Name is required'),
        ]
    );
    
    // Get detailed results
    $successCount = $result->successCount;
    $errorsByField = $result->getErrorsByField();
    $successRate = $result->getSuccessRate();
    
    return $result;
}
```

**❌ WRONG:**
```php
// Creating custom CSV parser violates DRY principle
final class CustomCsvParser {
    public function parse(string $file): array {
        // ... duplicates Nexus\Import functionality
    }
}

// Creating custom field transformer
final class CustomFieldTransformer {
    public function transform(array $data): array {
        // ... should use FieldMapping with built-in transformations
    }
}
```

**Built-in Transformations:**
- String: `trim`, `upper`, `lower`, `capitalize`, `slug`
- Type: `to_bool`, `to_int`, `to_float`, `to_string`
- Date: `parse_date:format`, `date_format:format`
- Utility: `default:value`, `coalesce:val1,val2`

**Transaction Strategies:**
- **TRANSACTIONAL**: Single transaction, rollback on any error (critical imports)
- **BATCH**: Transaction per batch, continue on failure (large imports)
- **STREAM**: Row-by-row, no transaction wrapper (memory-efficient)

---

#### **Nexus\Analytics**
**Capabilities:**
- Business intelligence
- Predictive modeling
- Data analytics
- Trend analysis
- KPI tracking

**Key Interfaces:**
```php
use Nexus\Analytics\Contracts\AnalyticsManagerInterface;
use Nexus\Analytics\Contracts\PredictionEngineInterface;
```

---

#### **Nexus\MachineLearning** (formerly `Nexus\Intelligence`)
**Capabilities:**
- **Anomaly Detection** via external AI providers (OpenAI, Anthropic, Gemini)
- **Local Model Inference** via PyTorch, ONNX, remote serving
- **MLflow Integration** for model registry and experiment tracking
- **Provider Strategy** for flexible AI backend selection per domain
- **Feature Versioning** with schema compatibility checking

**Version:** v2.0.0 (breaking changes from v1.x)

**When to Use:**
- ✅ Detect anomalies in business processes (receivable, payable, procurement)
- ✅ Load and execute ML models from MLflow registry
- ✅ Track experiments with automated metrics logging
- ✅ Fine-tune OpenAI models for domain-specific tasks
- ✅ Run local PyTorch or ONNX models
- ✅ Serve models via MLflow/TensorFlow Serving

**Key Interfaces:**
```php
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\Contracts\FeatureVersionManagerInterface;
use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;
use Nexus\MachineLearning\Contracts\InferenceEngineInterface;
use Nexus\MachineLearning\Contracts\MLflowClientInterface;
```

**Example:**
```php
// Anomaly detection with external AI providers
public function __construct(
    private readonly AnomalyDetectionServiceInterface $mlService,
    private readonly InvoiceAnomalyExtractor $extractor
) {}

public function validateInvoice(Invoice $invoice): void
{
    $features = $this->extractor->extract($invoice);
    $result = $this->mlService->detectAnomalies('receivable', $features);
    
    if ($result->isAnomaly() && $result->getConfidence() >= 0.85) {
        throw new AnomalyDetectedException($result->getReason());
    }
}

// Load and run local ML model from MLflow
public function __construct(
    private readonly ModelLoaderInterface $loader,
    private readonly InferenceEngineInterface $engine
) {}

public function predict(array $data): array
{
    $model = $this->loader->load('invoice_classifier', stage: 'production');
    return $this->engine->predict($model, $data);
}
```

**Migration from v1.x:**
See `docs/MIGRATION_INTELLIGENCE_TO_MACHINELEARNING.md` for complete guide.

**Breaking Changes (v1.x → v2.0):**
- Namespace: `Nexus\Intelligence` → `Nexus\MachineLearning`
- Service: `IntelligenceManager` → `MLModelManager`
- Service: `SchemaVersionManager` → `FeatureVersionManager`
- Config keys: `intelligence.schema.*` → `machinelearning.feature_schema.*`

---

### 🌍 **14. Geographic & Routing**

#### **Nexus\Geo**
**Capabilities:**
- Geocoding (address to coordinates)
- Reverse geocoding
- Geofencing
- Distance calculation
- Location-based services

**Key Interfaces:**
```php
use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\Geo\Contracts\GeofencingManagerInterface;
```

---

#### **Nexus\Routing**
**Capabilities:**
- Route optimization
- Route caching
- Multi-stop routing
- Delivery route planning

**Key Interfaces:**
```php
use Nexus\Routing\Contracts\RouteOptimizerInterface;
use Nexus\Routing\Contracts\RouteCacheInterface;
```

---

### 📝 **15. Content Management**

#### **Nexus\Content**
**Capabilities:**
- Content management system (CMS)
- Content versioning and publishing
- Multi-language content support
- Content templates and layouts
- Media library management
- SEO metadata management
- Content workflow (draft, review, publish)

**When to Use:**
- ✅ Website content management
- ✅ Product descriptions and catalogs
- ✅ Marketing content
- ✅ Help documentation
- ✅ Knowledge base articles
- ✅ Multi-language content

**Key Interfaces:**
```php
use Nexus\Content\Contracts\ContentManagerInterface;
use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\Contracts\MediaManagerInterface;
use Nexus\Content\Contracts\ContentPublisherInterface;
```

**Example:**
```php
// ✅ CORRECT: Publish multi-language product description
public function __construct(
    private readonly ContentManagerInterface $contentManager
) {}

public function publishProductContent(string $productId, array $translations): void
{
    foreach ($translations as $locale => $content) {
        $this->contentManager->publish(
            entityType: 'product',
            entityId: $productId,
            locale: $locale,
            content: $content,
            metadata: [
                'seo_title' => $content['seo_title'],
                'seo_description' => $content['seo_description'],
            ]
        );
    }
}
```

---

### ⚖️ **16. Compliance & Statutory**

#### **Nexus\Compliance**
**Capabilities:**
- Process enforcement (ISO, SOX, internal policies)
- Feature composition based on active schemes
- Configuration audit
- Mandatory field enforcement
- Segregation of duties

**When to Use:**
- ✅ ISO certification requirements
- ✅ SOX compliance controls
- ✅ Internal policy enforcement
- ✅ Quality management system

**Key Interfaces:**
```php
use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
```

---

#### **Nexus\Statutory**
**Capabilities:**
- Statutory reporting framework
- Tax filing formats (XBRL, e-Filing)
- Statutory calculation interface
- Report metadata management
- Default safe implementations

**When to Use:**
- ✅ Tax filing reports
- ✅ Statutory financial statements
- ✅ Government compliance reports
- ✅ Country-specific filings

**Key Interfaces:**
```php
use Nexus\Statutory\Contracts\StatutoryReportGeneratorInterface;
use Nexus\Statutory\Contracts\TaxonomyAdapterInterface;
```

---

### ⚙️ **17. System Utilities**

#### **Nexus\Setting**
**Capabilities:**
- Application settings management
- Tenant-specific settings
- Setting validation
- Setting encryption
- Default values

**When to Use:**
- ✅ Store application configuration
- ✅ User preferences
- ✅ Feature flags
- ✅ System parameters

**Key Interfaces:**
```php
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
```

**Example:**
```php
// ✅ CORRECT: Get system setting
public function __construct(
    private readonly SettingsManagerInterface $settings
) {}

public function getMaxRetries(): int
{
    return $this->settings->getInt('api.max_retries', 3);
}
```

---

#### **Nexus\FeatureFlags**
**Capabilities:**
- Feature flag management (enable/disable features)
- Percentage-based rollouts
- User/tenant-specific flags
- A/B testing support
- Feature flag versioning
- Scheduled feature releases

**When to Use:**
- ✅ Gradual feature rollout
- ✅ A/B testing new features
- ✅ Toggle features per tenant or user
- ✅ Emergency feature kill-switch
- ✅ Canary deployments

**Key Interfaces:**
```php
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagRepositoryInterface;
use Nexus\FeatureFlags\Contracts\FeatureEvaluatorInterface;
```

**Example:**
```php
// ✅ CORRECT: Check if feature is enabled
public function __construct(
    private readonly FeatureFlagManagerInterface $featureFlags
) {}

public function processOrder(Order $order): void
{
    if ($this->featureFlags->isEnabled('advanced_pricing', $order->getCustomerId())) {
        $this->applyAdvancedPricing($order);
    } else {
        $this->applyStandardPricing($order);
    }
}
```

---

#### **Nexus\Scheduler**
**Capabilities:**
- Task scheduling
- Job queue management
- Recurring task management
- Job monitoring

**Key Interfaces:**
```php
use Nexus\Scheduler\Contracts\SchedulerManagerInterface;
use Nexus\Scheduler\Contracts\JobRepositoryInterface;
```

---

#### **Nexus\Crypto**
**Capabilities:**
- Encryption/decryption
- Key management
- Hashing
- Digital signatures
- Secure token generation

**Key Interfaces:**
```php
use Nexus\Crypto\Contracts\EncryptionManagerInterface;
use Nexus\Crypto\Contracts\KeyManagerInterface;
```

---

## 🔄 Package Integration Patterns

### Pattern 1: Cross-Package Communication via Interfaces

When Package A needs functionality from Package B:

**❌ WRONG:**
```php
// Direct coupling between packages
use Nexus\Finance\Services\GeneralLedgerManager;

public function __construct(
    private readonly GeneralLedgerManager $glManager // Concrete class!
) {}
```

**✅ CORRECT:**
```php
// Package A defines what it needs
namespace Nexus\Receivable\Contracts;

interface GeneralLedgerIntegrationInterface
{
    public function postJournalEntry(JournalEntry $entry): void;
}

// Consuming application implements using Package B
namespace App\Services\Receivable;

use Nexus\Receivable\Contracts\GeneralLedgerIntegrationInterface;
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

### Pattern 2: Optional Feature Injection

When a feature is optional (not all deployments need it):

```php
// Package service with optional monitoring
public function __construct(
    private readonly CustomerInvoiceRepositoryInterface $repository,
    private readonly ?TelemetryTrackerInterface $telemetry = null,
    private readonly ?AuditLogManagerInterface $auditLogger = null
) {}

public function createInvoice(array $data): Invoice
{
    $startTime = microtime(true);
    
    $invoice = $this->repository->create($data);
    
    // Optional tracking (fails gracefully if not bound)
    $this->telemetry?->increment('invoices.created');
    $this->auditLogger?->log($invoice->getId(), 'created', 'Invoice created');
    
    return $invoice;
}
```

### Pattern 3: Strategy Pattern for Business Rules

When business logic varies by configuration:

```php
// Package defines contract
namespace Nexus\Receivable\Contracts;

interface PaymentAllocationStrategyInterface
{
    public function allocate(PaymentReceipt $receipt): array;
}

// consuming application provides multiple implementations
namespace App\Services\Receivable\Strategies;

final readonly class FIFOAllocationStrategy implements PaymentAllocationStrategyInterface
{
    public function allocate(PaymentReceipt $receipt): array
    {
        // Allocate to oldest invoices first
    }
}

final readonly class ManualAllocationStrategy implements PaymentAllocationStrategyInterface
{
    public function allocate(PaymentReceipt $receipt): array
    {
        // User specifies allocation
    }
}

// Consuming application's service provider binds based on config
$this->app->singleton(
    PaymentAllocationStrategyInterface::class,
    fn() => match ($this->getConfig('receivable.allocation_strategy')) {
        'fifo' => new FIFOAllocationStrategy(),
        'manual' => new ManualAllocationStrategy(),
        default => new FIFOAllocationStrategy(),
    }
);
```

---

## ✅ Pre-Implementation Checklist

Before writing ANY new package feature, ask yourself:

- [ ] **Does a Nexus package already provide this capability?** (Check this document)
- [ ] **Am I injecting interfaces, not concrete classes?**
- [ ] **Am I using framework facades or global helpers in package code?** (❌ Strictly forbidden in `packages/`)
- [ ] **Have I checked for existing implementations in other packages?** (Avoid duplication)
- [ ] **Does my implementation follow framework-agnostic patterns?**
- [ ] **Am I defining logging needs via interface?** (Use `LoggerInterface` from PSR-3)
- [ ] **Am I defining metrics tracking via interface?** (Use `TelemetryTrackerInterface`)
- [ ] **Is tenant context defined via interface?** (Use `TenantContextInterface`)
- [ ] **Am I defining business rule validation via interfaces?** (e.g., `PeriodValidatorInterface`, `AuthorizationInterface`)

---

## 🚨 Common Anti-Patterns to Avoid

### ❌ Anti-Pattern 1: Reimplementing Package Functionality

```php
// ❌ WRONG: Creating custom metrics collector
final class CustomMetricsCollector {
    private array $counters = [];
    
    public function increment(string $metric): void {
        $this->counters[$metric] = ($this->counters[$metric] ?? 0) + 1;
    }
}

// ✅ CORRECT: Use Nexus\Monitoring
public function __construct(
    private readonly TelemetryTrackerInterface $telemetry
) {}

public function trackEvent(): void {
    $this->telemetry->increment('events.processed');
}
```

### ❌ Anti-Pattern 2: Direct Package-to-Package Coupling

```php
// ❌ WRONG: Package requires another package's concrete class
use Nexus\Finance\Services\GeneralLedgerManager;

public function __construct(
    private readonly GeneralLedgerManager $glManager
) {}

// ✅ CORRECT: Package defines interface, consuming app wires implementation
use Nexus\Receivable\Contracts\GeneralLedgerIntegrationInterface;

public function __construct(
    private readonly GeneralLedgerIntegrationInterface $glIntegration
) {}
```

### ❌ Anti-Pattern 3: Framework Coupling in Packages

```php
// ❌ WRONG: Using Laravel facades in package
use Illuminate\Support\Facades\Cache;

public function getTenant(string $id): Tenant {
    return Cache::remember("tenant.{$id}", 3600, fn() => $this->fetch($id));
}

// ✅ CORRECT: Inject cache interface
use Nexus\YourPackage\Contracts\CacheRepositoryInterface;

public function __construct(
    private readonly CacheRepositoryInterface $cache
) {}

public function getTenant(string $id): Tenant {
    return $this->cache->remember("tenant.{$id}", 3600, fn() => $this->fetch($id));
}
```

### ❌ Anti-Pattern 4: Ignoring Multi-Tenancy

```php
// ❌ WRONG: Querying without tenant context
public function getInvoices(): array {
    return Invoice::all(); // Returns ALL tenants' invoices!
}

// ✅ CORRECT: Repository auto-scopes by tenant
public function __construct(
    private readonly CustomerInvoiceRepositoryInterface $repository
) {}

public function getInvoices(): array {
    return $this->repository->findAll(); // Only current tenant
}
```

---

## 📖 Quick Reference: "I Need To..." Decision Matrix

| I Need To... | Use This Package | Interface to Inject |
|--------------|------------------|---------------------|
| Track metrics/performance | `Nexus\Monitoring` | `TelemetryTrackerInterface` |
| Log user actions | `Nexus\AuditLogger` | `AuditLogManagerInterface` |
| **Manage company structure** | **`Nexus\Backoffice`** | **`CompanyManagerInterface`** |
| **Extract text from documents/OCR** | **`Nexus\DataProcessor`** | **`OcrProcessorInterface`** |
| **Build ETL pipelines** | **`Nexus\DataProcessor`** | **`EtlPipelineInterface`** |
| Send notifications | `Nexus\Notifier` | `NotificationManagerInterface` |
| Store files | `Nexus\Storage` | `StorageInterface` |
| Manage documents | `Nexus\Document` | `DocumentManagerInterface` |
| Generate invoice numbers | `Nexus\Sequencing` | `SequencingManagerInterface` |
| Get current tenant | `Nexus\Tenant` | `TenantContextInterface` |
| Convert units | `Nexus\Uom` | `UomManagerInterface` |
| Handle currencies | `Nexus\Currency` | `CurrencyManagerInterface` |
| Post GL transactions | `Nexus\Finance` | `GeneralLedgerManagerInterface` |
| Create customer invoices | `Nexus\Receivable` | `ReceivableManagerInterface` |
| Process vendor bills | `Nexus\Payable` | `PayableManagerInterface` |
| Track inventory | `Nexus\Inventory` | `InventoryManagerInterface` |
| **Create/manage BOMs** | **`Nexus\Manufacturing`** | **`BomManagerInterface`** |
| **Manage production routings** | **`Nexus\Manufacturing`** | **`RoutingManagerInterface`** |
| **Create work orders** | **`Nexus\Manufacturing`** | **`WorkOrderManagerInterface`** |
| **Run MRP planning** | **`Nexus\Manufacturing`** | **`MrpEngineInterface`** |
| **Plan production capacity** | **`Nexus\Manufacturing`** | **`CapacityPlannerInterface`** |
| **Forecast demand with ML** | **`Nexus\Manufacturing`** | **`DemandForecasterInterface`** |
| Manage employees | `Nexus\Hrm` | `EmployeeManagerInterface` |
| Process payroll | `Nexus\Payroll` | `PayrollManagerInterface` |
| Call external APIs | `Nexus\Connector` | `ConnectorManagerInterface` |
| Validate periods | `Nexus\Period` | `PeriodValidatorInterface` |
| Check permissions | `Nexus\Domain\Identity` | `AuthorizationManagerInterface` |
| **Implement SSO authentication** | **`Nexus\SSO`** | **`SsoManagerInterface`** |
| **SAML 2.0 login** | **`Nexus\SSO`** | **`SamlProviderInterface`** |
| **OAuth2/OIDC login** | **`Nexus\SSO`** | **`OAuthProviderInterface`** |
| **Azure AD/Google login** | **`Nexus\SSO`** | **`SsoManagerInterface`** |
| **JIT user provisioning** | **`Nexus\SSO`** | **`UserProvisioningInterface`** |
| **Detect anomalies with AI** | **`Nexus\MachineLearning`** | **`AnomalyDetectionServiceInterface`** |
| **Load ML models from MLflow** | **`Nexus\MachineLearning`** | **`ModelLoaderInterface`** |
| **Execute ML model inference** | **`Nexus\MachineLearning`** | **`InferenceEngineInterface`** |
| **Configure AI provider per domain** | **`Nexus\MachineLearning`** | **`ProviderStrategyInterface`** |
| **Manage feature schemas** | **`Nexus\MachineLearning`** | **`FeatureVersionManagerInterface`** |
| **Import data from CSV/Excel** | **`Nexus\Import`** | **`ImportParserInterface`, `ImportHandlerInterface`** |
| **Validate imported data** | **`Nexus\Import`** | **`ImportValidatorInterface`** |
| **Transform import fields** | **`Nexus\Import`** | **`TransformerInterface`, `FieldMapperInterface`** |
| **Detect import duplicates** | **`Nexus\Import`** | **`DuplicateDetectorInterface`** |
| **Manage import transactions** | **`Nexus\Import`** | **`TransactionManagerInterface`** |
| Export to Excel | `Nexus\Export` | `ExportManagerInterface` |
| Generate reports | `Nexus\Reporting` | `ReportManagerInterface` |
| Event sourcing (GL/Inventory) | `Nexus\EventStream` | `EventStoreInterface` |
| Encrypt data | `Nexus\Crypto` | `EncryptionManagerInterface` |
| Get/set app config | `Nexus\Setting` | `SettingsManagerInterface` |
| **Manage feature flags** | **`Nexus\FeatureFlags`** | **`FeatureFlagManagerInterface`** |
| **Calculate taxes** | **`Nexus\Tax`** | **`TaxCalculatorInterface`** |
| **Publish/consume messages** | **`Nexus\Messaging`** | **`MessagePublisherInterface`, `MessageConsumerInterface`** |
| **Manage content/CMS** | **`Nexus\Content`** | **`ContentManagerInterface`** |
| **Track detailed changes** | **`Nexus\Audit`** | **`ChangeTrackerInterface`** |

---

## 🎓 For Coding Agents: Self-Check Protocol

Before implementing ANY feature, run this mental checklist:

1. **Package Scan**: Does a first-party Nexus package provide this capability?
   - If YES → Use the package's interface via dependency injection
   - If NO → Proceed with new package implementation

2. **Interface Check**: Are ALL constructor dependencies interfaces?
   - If NO → Refactor to use interfaces

3. **Framework Check**: Am I in `packages/` and using framework-specific code?
   - If YES → **STOP. This is a violation.** Use PSR interfaces or define package contracts

4. **Duplication Check**: Does similar functionality exist in other packages?
   - If YES → Reuse or refactor, don't duplicate

5. **Multi-Tenancy Check**: Does this feature need tenant scoping?
   - If YES → Inject `TenantContextInterface`

6. **Observability Check**: Should this be logged or tracked?
   - If YES → Inject `AuditLogManagerInterface` and/or `TelemetryTrackerInterface`

7. **Period Validation Check**: Does this involve financial transactions?
   - If YES → Inject `PeriodValidatorInterface`

---

## 📚 Further Reading

- **Architecture Overview**: [`ARCHITECTURE.md`](ARCHITECTURE.md)
- **Coding Standards**: [`.github/copilot-instructions.md`](.github/copilot-instructions.md)
- **Package-Specific Docs**: [`docs/REQUIREMENTS_*.md`](docs/)
- **Implementation Summaries**: [`docs/*_IMPLEMENTATION_SUMMARY.md`](docs/)

---

**Last Updated:** November 25, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Mandatory for all coding agents and developers
