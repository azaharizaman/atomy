# 📚 NEXUS FIRST-PARTY PACKAGES REFERENCE GUIDE

**Version:** 1.3  
**Last Updated:** December 18, 2025  
**Target Audience:** Coding Agents & Developers  
**Purpose:** Prevent architectural violations by explicitly documenting available packages and their proper usage patterns.

**Recent Updates (December 18, 2025):**
- **NEW:** Added `Nexus\Payment` - Complete payment suite (100% implemented):
  - Payment transaction lifecycle with multi-gateway support
  - Disbursement processing with scheduling (PAY-034) and limits (PAY-035)
  - Settlement batches and reconciliation
  - Payment allocation engine (7 strategies: FIFO, LIFO, PROPORTIONAL, etc.)
  - Cross-currency support with exchange rate snapshots
- **NEW:** Added `Nexus\KycVerification` - KYC verification, risk assessment, beneficial ownership tracking
- **NEW:** Added `Nexus\AmlCompliance` - Anti-Money Laundering risk scoring and transaction monitoring
- **NEW:** Added `Nexus\DataPrivacy` - Regulation-agnostic data privacy foundation (DSAR, consent, retention)
- **NEW:** Added `Nexus\GDPR` - EU GDPR compliance extension (30-day DSAR, 72-hour breach notification)
- **NEW:** Added `Nexus\PDPA` - Malaysian PDPA compliance extension
- **NEW:** Added `Nexus\Sanctions` - International sanctions screening and PEP detection (FATF-compliant)
- **NEW:** Added `Nexus\Crypto` - Cryptographic primitives with post-quantum readiness
- **NEW:** Added HRM Sub-Package Suite under `packages/HRM/`:
  - `Nexus\HRM\Leave` - Leave management, accrual strategies, carry-forward
  - `Nexus\HRM\Attendance` - Check-in/out, schedules, overtime calculation
  - `Nexus\HRM\Shift` - Shift scheduling and templates
  - `Nexus\HRM\EmployeeProfile` - Employee lifecycle and contracts
  - `Nexus\HRM\PayrollCore` - Payslip generation and calculations
  - `Nexus\HRM\Recruitment` - Job posting, ATS, hiring decision engine
  - `Nexus\HRM\Training` - Course management and certifications
  - `Nexus\HRM\Onboarding` - Onboarding checklists and probation tracking
  - `Nexus\HRM\Disciplinary` - Misconduct cases, sanctions, policy enforcement
- **IMPROVED:** Total package count increased from 61 to 79 atomic packages
- **IMPROVED:** Total orchestrator count: 3 production-ready orchestrators

**Previous Updates (December 2025):**
- Added `Nexus\ProcurementOperations` orchestrator - Complete P2P workflow coordination (~30% coverage)
- Added `Nexus\AccountingOperations` orchestrator - Financial workflow coordination (period close, consolidation, ratios)
- Added `Nexus\HumanResourceOperations` orchestrator - HR workflow coordination (hiring, attendance, payroll)
- Added `Nexus\FinancialStatements` - Statement generation engine (Balance Sheet, P&L, Cash Flow)
- Added `Nexus\FinancialRatios` - Financial ratio analysis (DuPont, liquidity, profitability)
- Added `Nexus\AccountConsolidation` - Multi-entity consolidation
- Added `Nexus\AccountPeriodClose` - Period close management
- Added `Nexus\AccountVarianceAnalysis` - Budget vs actual variance analysis
- Added `Nexus\ProcurementML` - Procurement-specific ML feature extractors
- Added `Nexus\PerformanceReview` - Employee performance review management

**Previous Updates (November 2025):**
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
| **Building KYC verification** | **Use `Nexus\KycVerification\Contracts\KycVerificationManagerInterface`** |
| **Creating AML risk scoring** | **Use `Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface`** |
| **Managing data privacy/consent** | **Use `Nexus\DataPrivacy\Contracts\ConsentManagerInterface`** |
| **Screening against sanctions lists** | **Use `Nexus\Sanctions\Contracts\SanctionsScreenerInterface`** |
| **Custom encryption/hashing** | **Use `Nexus\Crypto\Contracts\CryptoManagerInterface`** |
| **Managing leave/attendance** | **Use `Nexus\HRM\Leave` or `Nexus\HRM\Attendance` packages** |
| **Building custom payment processor** | **Use `Nexus\Payment\Contracts\PaymentManagerInterface`** |
| **Creating custom allocation logic** | **Use `Nexus\Payment\Contracts\AllocationEngineInterface`** |
| **Building disbursement scheduler** | **Use `Nexus\Payment\Contracts\DisbursementSchedulerInterface`** |

---

## 📦 Available Packages by Category

### 🔐 **1. Security & Identity**

#### **Nexus\Identity**
**Capabilities:**
- User authentication (session, token, MFA)
- **Authorization (RBAC + ABAC)**
  - **Basic Permission Checking** via `PermissionCheckerInterface` (RBAC)
  - **Context-Aware Authorization** via `PolicyEvaluatorInterface` (ABAC)
- Role and permission management
- Password hashing and verification
- Token generation and validation
- Session management

**When to Use:**
- ✅ User login/logout
- ✅ **Basic permission checking** (RBAC)
- ✅ **Context-aware authorization** with custom policies (ABAC)
- ✅ Role assignment
- ✅ Multi-factor authentication
- ✅ API token generation

**Key Interfaces:**
```php
use Illuminate\Support\Facades\Cache;
use Nexus\Identity\ValueObjects\Policy;
use Nexus\Geo\Contracts\GeocoderInterface;
use Nexus\SSO\Contracts\SsoManagerInterface;
use Nexus\Uom\Contracts\UomManagerInterface;
use Nexus\Export\Contracts\ExporterInterface;
use Nexus\SSO\Contracts\SsoProviderInterface;
```

**Example - Basic Permission Check (RBAC):**
```php
// ✅ CORRECT: Simple permission check
public function __construct(
    private readonly PermissionCheckerInterface $permissionChecker
) {}

public function deleteInvoice(UserInterface $user, string $invoiceId): void
{
    if (!$this->permissionChecker->hasPermission($user, 'finance.invoice.delete')) {
        throw new UnauthorizedException();
    }
    // ... delete logic
}
```

**Example - Context-Aware Authorization (ABAC):**
```php
// ✅ CORRECT: Context-aware authorization with policy
public function __construct(
    private readonly PolicyEvaluatorInterface $policyEvaluator
) {}

public function applyLeaveOnBehalf(
    UserInterface $user,
    string $employeeId
): void {
    // Evaluate policy with context
    $canApply = $this->policyEvaluator->evaluate(
        user: $user,
        action: 'hrm.leave.apply_on_behalf',
        resource: null,
        context: ['target_employee_id' => $employeeId]
    );
    
    if (!$canApply) {
        throw new UnauthorizedException(
            'Not authorized to apply leave on behalf of this employee'
        );
    }
    
    // ... proceed with leave application
}
```

**Policy Registration (in Application Layer):**
```php
// Register custom policies in service provider
use Nexus\Storage\Contracts\StorageInterface;

$policy = Policy::define('hrm.leave.apply_on_behalf')
    ->description('User can apply leave on behalf of employees in same department')
    ->check(function(UserInterface $user, string $action, mixed $resource, array $context) use ($employeeQuery) {
        $targetEmployeeId = $context['target_employee_id'] ?? null;
        if (!$targetEmployeeId) {
            return false;
        }
        
        $userEmployee = $employeeQuery->findByUserId($user->getId());
        $targetEmployee = $employeeQuery->findById($targetEmployeeId);
        
        // Authorization logic based on relationship
        return $userEmployee?->getDepartmentId() === $targetEmployee?->getDepartmentId()
            || $userEmployee?->getId() === $targetEmployee?->getManagerId();
    });

$policyEvaluator->registerPolicy($policy->getName(), $policy->getEvaluator());
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
use Nexus\Hrm\Contracts\LeaveManagerInterface;
use Nexus\SSO\Contracts\SamlProviderInterface;
use Nexus\Crypto\Contracts\KeyManagerInterface;
use Nexus\SSO\Contracts\OAuthProviderInterface;
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Uom\Contracts\UomRepositoryInterface;
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
// Consuming application implements it using Nexus\Identity
namespace App\Services\SSO;

use Nexus\Import\Contracts\FieldMapperInterface;
use Nexus\Import\Contracts\TransformerInterface;

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
use Nexus\Party\Contracts\PartyManagerInterface;
use Nexus\Routing\Contracts\RouteCacheInterface;
use Nexus\Tax\Contracts\TaxRateManagerInterface;
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
use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Audit\Contracts\ChangeTrackerInterface;
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
use Nexus\Hrm\Contracts\EmployeeManagerInterface;
use Nexus\Import\Contracts\ImportParserInterface;
use Nexus\SSO\Contracts\AttributeMapperInterface;
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
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Connector\Contracts\ConnectionInterface;
use Nexus\Content\Contracts\MediaManagerInterface;
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
use Nexus\Export\Contracts\ExportManagerInterface;
use Nexus\Identity\Contracts\UserManagerInterface;
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
use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Inventory\Contracts\LotManagerInterface;
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
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\SSO\Contracts\UserProvisioningInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
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
use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\Geo\Contracts\GeofencingManagerInterface;
use Nexus\Hrm\Contracts\AttendanceManagerInterface;
use Nexus\Party\Contracts\PartyRepositoryInterface;
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
use Nexus\Workflow\Contracts\StateMachineInterface;
use Nexus\Content\Contracts\ContentManagerInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\Import\Contracts\ImportProcessorInterface;
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
use Nexus\Import\Contracts\ImportValidatorInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\JournalEntry\Services\JournalEntryManager;
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
use Nexus\JournalEntry\Services\JournalEntryManager;
use Nexus\Messaging\Contracts\QueueManagerInterface;
use Nexus\Payable\Contracts\PayableManagerInterface;
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
use Nexus\Payroll\Contracts\PayrollManagerInterface;
use Nexus\Period\Contracts\PeriodValidatorInterface;
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
use Nexus\Product\Contracts\ProductManagerInterface;
use Nexus\Routing\Contracts\RouteOptimizerInterface;
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
- Currency conversion with exchange rates
- Historical exchange rates

**Money vs Currency Package Boundary:**
- **`Nexus\Common\ValueObjects\Money`**: Immutable monetary value with arithmetic (add, subtract, multiply, divide), comparison, and formatting within a single currency
- **`Nexus\Currency` (this package)**: Exchange rate management, cross-currency conversions using rates, historical rate lookups

**When to Use:**
- ✅ Exchange rate management
- ✅ Cross-currency conversions requiring exchange rates
- ✅ Historical exchange rate lookups
- ✅ Multi-currency reporting with rate-based conversions

**Key Interfaces:**
```php
use Nexus\Sales\Contracts\QuotationManagerInterface;
use Nexus\Storage\Contracts\FileRepositoryInterface;
use Nexus\Tax\Contracts\TaxReportGeneratorInterface;
```

**Example:**
```php
// ✅ CORRECT: Convert invoice amount to base currency using exchange rates
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

#### **Nexus\ChartOfAccount**
**Capabilities:**
- Chart of accounts management
- Account hierarchy and groupings
- Account types (Asset, Liability, Equity, Revenue, Expense)
- Account validation and numbering
- Account activation/deactivation

**When to Use:**
- ✅ GL account management
- ✅ Account hierarchy setup
- ✅ Account type classification
- ✅ Account validation

**Key Interfaces:**
```php
use Nexus\Tenant\Contracts\TenantLifecycleInterface;
use Nexus\Audit\Contracts\AuditTrailManagerInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
```

---

#### **Nexus\JournalEntry**
**Capabilities:**
- Journal entry creation and posting
- Double-entry bookkeeping validation
- Journal entry reversal
- Batch journal processing
- GL transaction recording

**When to Use:**
- ✅ Journal entry posting
- ✅ Double-entry validation
- ✅ GL transaction recording
- ✅ Journal reversals

**Key Interfaces:**
```php
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Manufacturing\Contracts\MrpEngineInterface;
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
use Nexus\Messaging\Contracts\MessageRouterInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
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
use Nexus\Reporting\Contracts\ReportManagerInterface;
use Nexus\Sales\Contracts\SalesOrderManagerInterface;
use Nexus\Scheduler\Contracts\JobRepositoryInterface;
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
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Nexus\Tax\Contracts\TaxExemptionManagerInterface;
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
use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Backoffice\Contracts\BranchManagerInterface;
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
use Nexus\Content\Contracts\ContentPublisherInterface;
use Nexus\Crypto\Contracts\EncryptionManagerInterface;
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
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Document\Contracts\DocumentManagerInterface;
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
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\Import\Contracts\DuplicateDetectorInterface;
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Monitoring\Contracts\HealthCheckerInterface;
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

#### **Nexus\Payment** ✅ **NEW - 100% COMPLETE**
**Capabilities:**
- **Payment Transaction Management**: Complete lifecycle (DRAFT → PENDING → PROCESSING → COMPLETED/FAILED)
- **Multi-Gateway Support**: Pluggable gateway abstraction with circuit breaker pattern
- **Disbursement Processing**: Outbound payments with approval workflows (PAY-034, PAY-035)
- **Payment Method Management**: Bank transfer, credit/debit cards, e-wallets, virtual accounts
- **Settlement Batches**: Batch reconciliation with status tracking (OPEN → CLOSED → RECONCILED)
- **Payment Allocation Engine**: 7 strategies (FIFO, LIFO, PROPORTIONAL, MANUAL, OLDEST_FIRST, LARGEST_FIRST, SMALLEST_FIRST)
- **Cross-Currency Support**: Exchange rate snapshots for multi-currency payments
- **Idempotency**: Built-in idempotency key support for safe retries
- **Disbursement Scheduling**: Immediate, scheduled, and recurring disbursements
- **Disbursement Limits**: Per-transaction, daily, weekly, monthly controls

**When to Use:**
- ✅ Process inbound/outbound payments
- ✅ Multi-gateway payment processing
- ✅ Disbursement creation with approval workflows
- ✅ Payment allocation to invoices
- ✅ Settlement and reconciliation
- ✅ Schedule recurring disbursements
- ✅ Enforce payment limits and controls

**Key Interfaces:**
```php
use Nexus\Payment\Contracts\PaymentTransactionInterface;
use Nexus\Payment\Contracts\PaymentTransactionQueryInterface;
use Nexus\Payment\Contracts\PaymentTransactionPersistInterface;
use Nexus\Payment\Contracts\PaymentManagerInterface;
use Nexus\Payment\Contracts\PaymentGatewayInterface;
use Nexus\Payment\Contracts\GatewayRegistryInterface;
use Nexus\Payment\Contracts\DisbursementInterface;
use Nexus\Payment\Contracts\DisbursementQueryInterface;
use Nexus\Payment\Contracts\DisbursementPersistInterface;
use Nexus\Payment\Contracts\DisbursementManagerInterface;
use Nexus\Payment\Contracts\DisbursementSchedulerInterface;
use Nexus\Payment\Contracts\DisbursementLimitValidatorInterface;
use Nexus\Payment\Contracts\SettlementBatchInterface;
use Nexus\Payment\Contracts\AllocationEngineInterface;
use Nexus\Payment\Contracts\AllocationStrategyInterface;
```

**Example - Process Payment:**
```php
// ✅ CORRECT: Process payment through gateway
public function __construct(
    private readonly PaymentManagerInterface $paymentManager
) {}

public function processPayment(string $paymentId): PaymentResult
{
    return $this->paymentManager->process(
        paymentId: $paymentId,
        gatewayName: 'stripe',
        context: ExecutionContext::create([
            'idempotency_key' => IdempotencyKey::generate()->toString(),
            'metadata' => ['order_id' => 'ORD-2024-001'],
        ])
    );
}
```

**Example - Create Scheduled Disbursement:**
```php
// ✅ CORRECT: Schedule a recurring disbursement
public function __construct(
    private readonly DisbursementSchedulerInterface $scheduler,
    private readonly DisbursementLimitValidatorInterface $limitValidator
) {}

public function createMonthlyDisbursement(
    string $tenantId,
    string $recipientId,
    Money $amount
): string {
    // Validate limits first
    $this->limitValidator->validate($tenantId, $amount);
    
    // Create recurring schedule
    $schedule = DisbursementSchedule::recurring(
        startDate: new \DateTimeImmutable('first day of next month'),
        frequency: RecurrenceFrequency::MONTHLY,
        maxOccurrences: 12
    );
    
    return $this->scheduler->scheduleRecurring(
        tenantId: $tenantId,
        recipientId: $recipientId,
        amount: $amount,
        schedule: $schedule,
        reference: 'Monthly vendor payment'
    );
}
```

**Example - Allocate Payment to Invoices:**
```php
// ✅ CORRECT: Allocate payment using FIFO strategy
public function __construct(
    private readonly AllocationEngineInterface $allocationEngine
) {}

public function allocatePayment(
    Money $paymentAmount,
    array $invoices
): AllocationResult {
    return $this->allocationEngine->allocate(
        amount: $paymentAmount,
        documents: $invoices,
        method: AllocationMethod::FIFO
    );
}
```

**❌ WRONG:**
```php
// Creating custom payment processor violates DRY principle
final class CustomPaymentProcessor {
    public function process($amount, $gateway) {
        // ... duplicates Nexus\Payment functionality
    }
}

// Creating custom allocation logic
final class CustomAllocationService {
    public function allocate($payment, $invoices) {
        // ... should use AllocationEngineInterface
    }
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
use Nexus\Payroll\Contracts\PayrollStatutoryInterface;
use Nexus\Payroll\Contracts\PayslipGeneratorInterface;
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
use Nexus\Workflow\Contracts\WorkflowManagerInterface;
use Nexus\Backoffice\Contracts\CompanyManagerInterface;
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
use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\DataProcessor\Contracts\EtlPipelineInterface;
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
use Nexus\Import\Contracts\TransactionManagerInterface;
use Nexus\Inventory\Contracts\TransferManagerInterface;
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
use Nexus\Messaging\Contracts\MessageConsumerInterface;
use Nexus\Monitoring\Contracts\MetricExporterInterface;
use Nexus\Product\Contracts\ProductRepositoryInterface;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Statutory\Contracts\TaxonomyAdapterInterface;
use Nexus\Warehouse\Contracts\LocationManagerInterface;
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
use Nexus\Analytics\Contracts\AnalyticsManagerInterface;
use Nexus\Analytics\Contracts\PredictionEngineInterface;
use Nexus\Audit\Contracts\AuditReportGeneratorInterface;
use Nexus\Connector\Contracts\ConnectorManagerInterface;
use Nexus\DataProcessor\Contracts\OcrProcessorInterface;
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
use Nexus\Messaging\Contracts\MessagePublisherInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
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
use Nexus\Scheduler\Contracts\SchedulerManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
```

---

#### **HRM Sub-Package Suite (`packages/HRM/`)**

The HRM package suite provides granular, domain-specific HR functionality as independent atomic packages:

##### **Nexus\HRM\Leave**
**Capabilities:**
- Leave application and approval workflows
- Leave balance tracking and calculations
- Accrual strategies (monthly, annual, pro-rated)
- Carry-forward rules and expiry
- Leave types configuration (annual, sick, unpaid, etc.)
- Public holiday integration

**Key Interfaces:**
```php
use Nexus\HRM\Leave\Contracts\LeaveManagerInterface;
use Nexus\HRM\Leave\Contracts\LeaveBalanceCalculatorInterface;
use Nexus\HRM\Leave\Contracts\AccrualStrategyInterface;
```

---

##### **Nexus\HRM\Attendance**
**Capabilities:**
- Check-in/check-out tracking
- Work schedule management
- Overtime calculation and approval
- Late/early departure tracking
- Attendance anomaly detection
- Geolocation-based attendance

**Key Interfaces:**
```php
use Nexus\HRM\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\HRM\Attendance\Contracts\OvertimeCalculatorInterface;
use Nexus\HRM\Attendance\Contracts\ScheduleManagerInterface;
```

---

##### **Nexus\HRM\Shift**
**Capabilities:**
- Shift template creation and management
- Shift scheduling and assignment
- Rotating shift patterns
- Shift swap requests
- Coverage tracking

**Key Interfaces:**
```php
use Nexus\HRM\Shift\Contracts\ShiftManagerInterface;
use Nexus\HRM\Shift\Contracts\ShiftSchedulerInterface;
```

---

##### **Nexus\HRM\EmployeeProfile**
**Capabilities:**
- Employee lifecycle management (hire, transfer, terminate)
- Employment contracts and amendments
- Position and department assignments
- Personal information management
- Emergency contacts
- Employment history tracking

**Key Interfaces:**
```php
use Nexus\HRM\EmployeeProfile\Contracts\EmployeeManagerInterface;
use Nexus\HRM\EmployeeProfile\Contracts\ContractManagerInterface;
use Nexus\HRM\EmployeeProfile\Contracts\PositionManagerInterface;
```

---

##### **Nexus\HRM\PayrollCore**
**Capabilities:**
- Payslip generation engine
- Earnings and deductions processing
- Payroll period management
- Tax and statutory integration hooks
- Payroll approval workflows
- Payment file generation

**Key Interfaces:**
```php
use Nexus\HRM\PayrollCore\Contracts\PayslipGeneratorInterface;
use Nexus\HRM\PayrollCore\Contracts\EarningsCalculatorInterface;
use Nexus\HRM\PayrollCore\Contracts\DeductionsCalculatorInterface;
```

---

##### **Nexus\HRM\Recruitment**
**Capabilities:**
- Job posting and requisition management
- Applicant Tracking System (ATS)
- Interview scheduling and feedback
- Hiring decision engine
- Offer letter generation
- Candidate pipeline management

**Key Interfaces:**
```php
use Nexus\HRM\Recruitment\Contracts\RecruitmentManagerInterface;
use Nexus\HRM\Recruitment\Contracts\ApplicantTrackerInterface;
use Nexus\HRM\Recruitment\Contracts\HiringDecisionEngineInterface;
```

---

##### **Nexus\HRM\Training**
**Capabilities:**
- Course and curriculum management
- Training enrollment and scheduling
- Certification tracking and expiry
- Skill gap analysis
- Training completion tracking
- E-learning integration hooks

**Key Interfaces:**
```php
use Nexus\HRM\Training\Contracts\TrainingManagerInterface;
use Nexus\HRM\Training\Contracts\CertificationTrackerInterface;
use Nexus\HRM\Training\Contracts\CourseManagerInterface;
```

---

##### **Nexus\HRM\Onboarding**
**Capabilities:**
- Onboarding checklist management
- Task assignment and tracking
- Probation period management
- Welcome package configuration
- New hire documentation collection
- Integration with IT/Admin provisioning

**Key Interfaces:**
```php
use Nexus\HRM\Onboarding\Contracts\OnboardingManagerInterface;
use Nexus\HRM\Onboarding\Contracts\ChecklistManagerInterface;
use Nexus\HRM\Onboarding\Contracts\ProbationTrackerInterface;
```

---

##### **Nexus\HRM\Disciplinary**
**Capabilities:**
- Misconduct case management
- Disciplinary action tracking
- Sanction application (warning, suspension, termination)
- Policy violation documentation
- Appeal process management
- Case investigation workflows

**Key Interfaces:**
```php
use Nexus\HRM\Disciplinary\Contracts\DisciplinaryManagerInterface;
use Nexus\HRM\Disciplinary\Contracts\CaseInvestigatorInterface;
use Nexus\HRM\Disciplinary\Contracts\SanctionManagerInterface;
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
use Nexus\ChartOfAccount\Contracts\AccountQueryInterface;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
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
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;
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
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Nexus\YourPackage\Contracts\CacheRepositoryInterface;
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
use Nexus\Backoffice\Contracts\CostCenterManagerInterface;
use Nexus\Backoffice\Contracts\DepartmentManagerInterface;
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
use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Inventory\Contracts\ReservationManagerInterface;
use Nexus\MachineLearning\Contracts\MLflowClientInterface;
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
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
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
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
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
use Nexus\Accounting\Contracts\PeriodCloseManagerInterface;
use Nexus\Assets\Contracts\DepreciationCalculatorInterface;
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
use Nexus\ChartOfAccount\Contracts\AccountPersistInterface;
use Nexus\DataProcessor\Contracts\DataTransformerInterface;
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
use Nexus\FeatureFlags\Contracts\FeatureEvaluatorInterface;
use Nexus\FieldService\Contracts\WorkOrderManagerInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Inventory\Contracts\SerialNumberManagerInterface;
use Nexus\Manufacturing\Contracts\CapacityPlannerInterface;
use Nexus\PerformanceReview\Contracts\GoalTrackerInterface;
use Nexus\ProcurementML\Extractors\InvoiceAnomalyExtractor;
use Nexus\ProjectManagement\Contracts\TaskManagerInterface;
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
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
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
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\FinancialRatios\Contracts\DuPontAnalyzerInterface;
use Nexus\Identity\Contracts\AuthenticationManagerInterface;
use Nexus\JournalEntry\Contracts\JournalEntryQueryInterface;
use Nexus\Manufacturing\Contracts\DemandForecasterInterface;
use Nexus\Manufacturing\Contracts\WorkOrderManagerInterface;
use Nexus\Procurement\Contracts\RequisitionManagerInterface;
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
use Nexus\Connector\Contracts\CircuitBreakerStorageInterface;
use Nexus\Currency\Contracts\ExchangeRateRepositoryInterface;
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
use Nexus\DataProcessor\Contracts\DocumentExtractorInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
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
use Nexus\FinancialRatios\Contracts\RatioCalculatorInterface;
use Nexus\MachineLearning\Contracts\InferenceEngineInterface;
use Nexus\Notifier\Contracts\NotificationRepositoryInterface;
use Nexus\PerformanceReview\Contracts\ReviewManagerInterface;
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
use Nexus\Common\ValueObjects\Money;  // Money VO is in Common
use Nexus\JournalEntry\Contracts\JournalEntryManagerInterface;
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
use Nexus\JournalEntry\Contracts\JournalEntryManagerInterface;
use Nexus\JournalEntry\Contracts\JournalEntryPersistInterface;
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
use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;
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
use Nexus\Procurement\Contracts\PurchaseOrderManagerInterface;
use Nexus\ProcurementML\Extractors\VendorPerformanceExtractor;
use Nexus\ProjectManagement\Contracts\ProjectManagerInterface;
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
use Nexus\AccountPeriodClose\Contracts\CloseValidatorInterface;
use Nexus\CashManagement\Contracts\BankAccountManagerInterface;
```

---

#### **Nexus\Crypto**
**Capabilities:**
- **Symmetric Encryption**: AES-256-GCM with authenticated encryption
- **Asymmetric Encryption**: RSA-OAEP, ECDSA key pairs
- **Hashing**: SHA-256/384/512, BLAKE3 with algorithm agility
- **Key Management**: Key generation, rotation, derivation (HKDF, PBKDF2)
- **Digital Signatures**: Ed25519, ECDSA with verification
- **Secure Token Generation**: CSPRNG-based tokens and UUIDs
- **Post-Quantum Readiness**: Phase 1 (awareness), Phase 2 (hybrid), Phase 3 (full PQC)
- **Algorithm Agility**: Runtime algorithm switching without code changes

**When to Use:**
- ✅ Encrypt sensitive data at rest (PII, financial data)
- ✅ Generate secure API tokens and session identifiers
- ✅ Digital signature verification for document integrity
- ✅ Key derivation for password-based encryption
- ✅ Prepare for post-quantum cryptography migration

**Key Interfaces:**
```php
use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\Crypto\Contracts\KeyManagerInterface;
use Nexus\Crypto\Contracts\HashManagerInterface;
use Nexus\Crypto\Contracts\SignatureManagerInterface;
```

**Example:**
```php
// ✅ CORRECT: Encrypt sensitive data with algorithm agility
public function __construct(
    private readonly CryptoManagerInterface $crypto
) {}

public function encryptDocument(string $content): EncryptedPayload
{
    return $this->crypto->encrypt(
        plaintext: $content,
        algorithm: 'aes-256-gcm', // Can be changed via config
        context: ['document_type' => 'contract']
    );
}

// Generate secure tokens
public function generateApiToken(): string
{
    return $this->crypto->generateSecureToken(length: 32);
}
```

---

### 🛡️ **18. Compliance & Regulatory**

#### **Nexus\KycVerification**
**Capabilities:**
- **Customer Verification**: Identity verification lifecycle management
- **Risk Assessment**: Risk scoring (LOW/MEDIUM/HIGH/CRITICAL) with configurable rules
- **Beneficial Ownership**: UBO tracking with ownership percentage validation
- **Document Verification**: ID document validation with expiry tracking
- **Review Scheduling**: Automated periodic review scheduling (annual, bi-annual, etc.)
- **Verification Workflow**: Status tracking (PENDING → IN_REVIEW → APPROVED/REJECTED)

**When to Use:**
- ✅ Customer onboarding identity verification
- ✅ Beneficial ownership tracking for corporate customers
- ✅ Risk-based customer categorization
- ✅ Periodic KYC review automation
- ✅ Compliance with AML/CFT regulations

**Key Interfaces:**
```php
use Nexus\KycVerification\Contracts\KycVerificationManagerInterface;
use Nexus\KycVerification\Contracts\RiskAssessmentInterface;
use Nexus\KycVerification\Contracts\BeneficialOwnershipInterface;
use Nexus\KycVerification\Contracts\DocumentVerificationInterface;
```

**Example:**
```php
// ✅ CORRECT: Initiate KYC verification for customer
public function __construct(
    private readonly KycVerificationManagerInterface $kycManager
) {}

public function verifyCustomer(string $customerId): KycVerificationResult
{
    return $this->kycManager->initiateVerification(
        entityType: 'customer',
        entityId: $customerId,
        verificationType: 'ENHANCED_DUE_DILIGENCE',
        requiredDocuments: ['passport', 'proof_of_address']
    );
}

public function assessRisk(string $verificationId): RiskAssessment
{
    return $this->kycManager->assessRisk($verificationId, [
        'country_risk' => 'HIGH',
        'transaction_volume' => 'MEDIUM',
        'pep_status' => false,
    ]);
}
```

---

#### **Nexus\AmlCompliance**
**Capabilities:**
- **Risk Scoring**: 0-100 AML risk score calculation with weighted factors
- **Transaction Monitoring**: Pattern detection for suspicious activity
- **SAR Generation**: Suspicious Activity Report generation and filing
- **Threshold Monitoring**: Large transaction detection and reporting
- **Case Management**: Investigation workflow for flagged transactions
- **Regulatory Reporting**: Automated compliance report generation

**When to Use:**
- ✅ AML risk scoring for customers and transactions
- ✅ Suspicious transaction detection and alerting
- ✅ SAR filing workflow
- ✅ Cash threshold monitoring
- ✅ Compliance with BSA/AML regulations

**Key Interfaces:**
```php
use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;
use Nexus\AmlCompliance\Contracts\TransactionMonitorInterface;
use Nexus\AmlCompliance\Contracts\SarGeneratorInterface;
use Nexus\AmlCompliance\Contracts\CaseManagerInterface;
```

**Example:**
```php
// ✅ CORRECT: Assess AML risk for transaction
public function __construct(
    private readonly AmlRiskAssessorInterface $amlAssessor,
    private readonly TransactionMonitorInterface $monitor
) {}

public function assessTransaction(Transaction $transaction): AmlRiskResult
{
    $riskScore = $this->amlAssessor->calculateRisk(
        transaction: $transaction,
        factors: [
            'amount' => $transaction->getAmount(),
            'country' => $transaction->getDestinationCountry(),
            'customer_risk' => $this->getCustomerRiskLevel($transaction->getCustomerId()),
        ]
    );
    
    // Auto-flag if score exceeds threshold
    if ($riskScore->getScore() >= 75) {
        $this->monitor->flagForReview($transaction->getId(), $riskScore);
    }
    
    return $riskScore;
}
```

---

#### **Nexus\DataPrivacy**
**Capabilities:**
- **Consent Management**: Opt-in/opt-out tracking with purpose-based consent
- **DSAR Handling**: Data Subject Access Request workflow (30/45 day configurable)
- **Data Retention**: Configurable retention policies by data category
- **Breach Management**: Breach detection, assessment, and notification workflow
- **Data Inventory**: Personal data mapping and classification
- **Right to Erasure**: Automated data deletion with audit trail

**When to Use:**
- ✅ Manage user consent for data processing
- ✅ Handle data subject access requests
- ✅ Implement data retention policies
- ✅ Data breach incident management
- ✅ Foundation for GDPR/PDPA/CCPA compliance

**Key Interfaces:**
```php
use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;
use Nexus\DataPrivacy\Contracts\DsarHandlerInterface;
use Nexus\DataPrivacy\Contracts\RetentionPolicyInterface;
use Nexus\DataPrivacy\Contracts\BreachManagerInterface;
```

**Example:**
```php
// ✅ CORRECT: Record user consent
public function __construct(
    private readonly ConsentManagerInterface $consentManager
) {}

public function recordMarketingConsent(string $userId, bool $consented): void
{
    $this->consentManager->recordConsent(
        subjectId: $userId,
        purpose: 'marketing_emails',
        consented: $consented,
        source: 'website_form',
        expiresAt: new \DateTimeImmutable('+2 years')
    );
}

public function handleDsar(string $userId): DsarResponse
{
    return $this->dsarHandler->processRequest(
        subjectId: $userId,
        requestType: 'ACCESS',
        deadline: new \DateTimeImmutable('+30 days')
    );
}
```

---

#### **Nexus\GDPR**
**Capabilities:**
- **GDPR-Specific DSAR**: 30-day deadline enforcement with extension handling
- **72-Hour Breach Notification**: Supervisory authority notification workflow
- **Lawful Basis Tracking**: 6 lawful bases for processing (consent, contract, legal, vital, public, legitimate)
- **DPO Integration**: Data Protection Officer notification and approval workflows
- **Cross-Border Transfer**: Standard Contractual Clauses (SCC) tracking
- **DPIA Management**: Data Protection Impact Assessment workflow

**When to Use:**
- ✅ EU GDPR compliance requirements
- ✅ 30-day DSAR deadline enforcement
- ✅ 72-hour breach notification to authorities
- ✅ Lawful basis documentation
- ✅ Cross-border data transfer compliance

**Key Interfaces:**
```php
use Nexus\GDPR\Contracts\GdprDsarHandlerInterface;
use Nexus\GDPR\Contracts\BreachNotificationInterface;
use Nexus\GDPR\Contracts\LawfulBasisManagerInterface;
use Nexus\GDPR\Contracts\DpiaManagerInterface;
```

**Example:**
```php
// ✅ CORRECT: Report data breach to supervisory authority
public function __construct(
    private readonly BreachNotificationInterface $breachNotifier
) {}

public function reportBreach(DataBreach $breach): BreachNotificationResult
{
    // Must notify within 72 hours of awareness
    return $this->breachNotifier->notifySupervisoryAuthority(
        breach: $breach,
        affectedSubjects: $breach->getAffectedCount(),
        dataCategories: $breach->getAffectedDataCategories(),
        mitigationMeasures: $breach->getMitigationActions()
    );
}
```

---

#### **Nexus\PDPA**
**Capabilities:**
- **Malaysian PDPA Compliance**: Personal Data Protection Act 2010 requirements
- **Data User Registration**: Registration with Commissioner tracking
- **Consent Requirements**: Bilingual consent (Bahasa Malaysia + English)
- **Cross-Border Restrictions**: White-listed country validation
- **Retention Limits**: Malaysian-specific retention period enforcement
- **Breach Notification**: Commissioner notification workflow

**When to Use:**
- ✅ Malaysian PDPA compliance
- ✅ Data user registration with Commissioner
- ✅ Bilingual consent management
- ✅ Cross-border transfer to approved countries only

**Key Interfaces:**
```php
use Nexus\PDPA\Contracts\PdpaComplianceManagerInterface;
use Nexus\PDPA\Contracts\CommissionerNotificationInterface;
use Nexus\PDPA\Contracts\BilingualConsentInterface;
```

---

#### **Nexus\Sanctions**
**Capabilities:**
- **Multi-List Screening**: OFAC, UN, EU, UK HMT, FATF sanctions lists
- **PEP Detection**: Politically Exposed Person identification
- **Real-Time Screening**: Transaction and customer screening
- **Fuzzy Matching**: Name matching with configurable threshold (70-100%)
- **Watchlist Management**: Custom internal watchlist support
- **FATF Compliance**: Grey/black list country detection

**When to Use:**
- ✅ Customer and vendor sanctions screening
- ✅ Transaction screening against watchlists
- ✅ PEP identification for enhanced due diligence
- ✅ FATF grey/black list country blocking
- ✅ Compliance with international sanctions regimes

**Key Interfaces:**
```php
use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;
use Nexus\Sanctions\Contracts\PepDetectorInterface;
use Nexus\Sanctions\Contracts\WatchlistManagerInterface;
```

**Example:**
```php
// ✅ CORRECT: Screen customer against sanctions lists
public function __construct(
    private readonly SanctionsScreenerInterface $screener,
    private readonly PepDetectorInterface $pepDetector
) {}

public function screenCustomer(Customer $customer): ScreeningResult
{
    $sanctionsResult = $this->screener->screen(
        name: $customer->getName(),
        dateOfBirth: $customer->getDateOfBirth(),
        nationality: $customer->getNationality(),
        lists: ['OFAC', 'UN', 'EU'],
        matchThreshold: 85 // Fuzzy match percentage
    );
    
    $pepResult = $this->pepDetector->check(
        name: $customer->getName(),
        country: $customer->getNationality()
    );
    
    return new ScreeningResult(
        sanctionsHit: $sanctionsResult->hasMatch(),
        pepHit: $pepResult->isPep(),
        riskLevel: $this->calculateOverallRisk($sanctionsResult, $pepResult)
    );
}
```

---

### 📈 **19. Financial Analysis Packages**

#### **Nexus\FinancialStatements**
**Capabilities:**
- Generate complete financial statements from account balances
- Support for multiple compliance frameworks (GAAP, IFRS, custom)
- Statement types: Balance Sheet, Income Statement, Cash Flow, Changes in Equity
- Section organization and grouping
- Statement validation (balancing, compliance requirements)
- Export adapter contracts for PDF, Excel, HTML

**When to Use:**
- ✅ Generate Balance Sheets and P&L statements
- ✅ Cash Flow Statement (direct & indirect methods)
- ✅ Multi-framework compliance reporting
- ✅ Period-end financial reporting

**Key Interfaces:**
```php
use Nexus\ProcurementOperations\Workflows\ProcureToPayWorkflow;
use Nexus\FeatureFlags\Contracts\FeatureFlagRepositoryInterface;
use Nexus\HumanResourceOperations\Coordinators\LeaveCoordinator;
use Nexus\Statutory\Contracts\StatutoryReportGeneratorInterface;
```

---

#### **Nexus\FinancialRatios**
**Capabilities:**
- Calculate standard financial ratios (liquidity, profitability, leverage, efficiency)
- DuPont analysis decomposition (ROE breakdown)
- Benchmarking against industry standards
- Health assessment indicators
- Trend analysis over time
- Multi-period comparison

**Ratio Categories:**
| Category | Key Ratios |
|----------|------------|
| **Liquidity** | Current, Quick, Cash |
| **Profitability** | Gross Margin, Net Margin, ROA, ROE |
| **Leverage** | Debt-to-Equity, Interest Coverage |
| **Efficiency** | Inventory Turnover, AR Turnover |
| **Cash Flow** | Operating Cash Flow, Free Cash Flow |
| **Market** | EPS, P/E, Dividend Yield |

**When to Use:**
- ✅ Financial health assessment
- ✅ DuPont analysis for ROE decomposition
- ✅ Industry benchmarking
- ✅ Trend analysis and forecasting

**Key Interfaces:**
```php
use Nexus\FieldService\Contracts\ServiceContractManagerInterface;
use Nexus\HumanResourceOperations\Coordinators\HiringCoordinator;
use Nexus\PerformanceReview\Contracts\FeedbackCollectorInterface;
```

---

#### **Nexus\AccountConsolidation**
**Capabilities:**
- Multi-entity financial consolidation
- Elimination entries for intercompany transactions
- Currency translation for foreign subsidiaries
- Minority interest calculations
- Consolidation hierarchy management

**When to Use:**
- ✅ Consolidated financial statements
- ✅ Multi-entity group reporting
- ✅ Intercompany eliminations
- ✅ Foreign currency consolidation

**Key Interfaces:**
```php
use Nexus\Receivable\Contracts\GeneralLedgerIntegrationInterface;
use Nexus\Receivable\Contracts\GeneralLedgerIntegrationInterface;
```

---

#### **Nexus\AccountPeriodClose**
**Capabilities:**
- Period close workflow management
- Pre-close validation checks
- Close checklist tracking
- Period lock/unlock controls
- Close journal generation

**When to Use:**
- ✅ Month-end close process
- ✅ Year-end close workflows
- ✅ Period validation before close
- ✅ Close checklist enforcement

**Key Interfaces:**
```php
use Nexus\Receivable\Contracts\PaymentReceiptRepositoryInterface;
use Nexus\CashManagement\Contracts\ReconciliationManagerInterface;
```

---

#### **Nexus\AccountVarianceAnalysis**
**Capabilities:**
- Budget vs actual variance analysis
- Favorable/unfavorable variance classification
- Variance explanation tracking
- Multi-dimensional analysis (by cost center, project, department)

**When to Use:**
- ✅ Budget variance reporting
- ✅ Cost control analysis
- ✅ Management reporting
- ✅ Performance evaluation

**Key Interfaces:**
```php
use Nexus\ChartOfAccount\Contracts\ChartOfAccountManagerInterface;
use Nexus\HumanResourceOperations\Coordinators\PayrollCoordinator;
```

---

### 👥 **20. HR Extensions**

#### **Nexus\PerformanceReview**
**Capabilities:**
- Performance review cycle management
- Goal setting and tracking
- Competency assessments
- 360-degree feedback collection
- Review templates and workflows

**When to Use:**
- ✅ Annual performance reviews
- ✅ Goal setting and OKRs
- ✅ 360-degree feedback processes
- ✅ Competency evaluations

**Key Interfaces:**
```php
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\AccountingOperations\Coordinators\PeriodCloseCoordinator;
use Nexus\AccountPeriodClose\Contracts\PeriodCloseManagerInterface;
```

---

#### **Nexus\ProcurementML**
**Capabilities:**
- Procurement-specific ML feature extractors
- Vendor performance analytics interfaces
- Spend pattern analysis
- Invoice anomaly detection features
- Price variance prediction features

**When to Use:**
- ✅ ML-powered procurement analytics
- ✅ Vendor performance scoring with ML
- ✅ Anomaly detection in procurement
- ✅ Spend pattern analysis

**Key Interfaces:**
```php
use Nexus\MachineLearning\Contracts\FeatureVersionManagerInterface;
use Nexus\AccountConsolidation\Contracts\EliminationEngineInterface;
use Nexus\Accounting\Contracts\FinancialStatementGeneratorInterface;
use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
```

---

## 🔗 **21. Orchestrators (Workflow Coordination)**

Orchestrators coordinate workflows across multiple atomic packages. They own the **flow**, not the **truth** (entities remain in atomic packages).

### **Nexus\AccountingOperations** ✅ **PRODUCTION-READY**

**Capabilities:**
- Period close workflow coordination
- Financial statement generation workflows
- Consolidation process orchestration
- Variance analysis workflows
- Ratio analysis coordination

**Packages Orchestrated:**
| Package | Purpose |
|---------|---------|
| `Nexus\FinancialStatements` | Generate statements |
| `Nexus\AccountConsolidation` | Multi-entity consolidation |
| `Nexus\AccountVarianceAnalysis` | Variance analysis |
| `Nexus\AccountPeriodClose` | Period close management |
| `Nexus\FinancialRatios` | Ratio calculations |

**When to Use:**
- ✅ End-to-end period close process
- ✅ Consolidated statement generation
- ✅ Management reporting workflows

**Key Interfaces:**
```php
use Nexus\FinancialStatements\Contracts\StatementGeneratorInterface;
use Nexus\FinancialStatements\Contracts\StatementValidatorInterface;
use Nexus\ProcurementML\Contracts\SpendAnalyticsRepositoryInterface;
```

---

### **Nexus\HumanResourceOperations** ✅ **PRODUCTION-READY**

**Capabilities:**
- Hiring workflow coordination with interview tracking
- Attendance workflow with anomaly detection (unusual hours, location, consecutive absences)
- Payroll calculation workflow with Malaysian statutory compliance
- Leave management coordination
- Onboarding workflows

**Key Features:**
- ✅ Real-time attendance anomaly detection (geolocation, unusual hours)
- ✅ Malaysian Employment Act overtime validation (104 hrs/month, 4 hrs/day)
- ✅ Multi-level approval workflows with delegation
- ✅ Integration with `Nexus\PayrollMysStatutory` for statutory calculations

**Packages Orchestrated:**
| Package | Purpose |
|---------|---------|
| `Nexus\Hrm` | Employee management, leave |
| `Nexus\Payroll` | Payroll processing |
| `Nexus\PayrollMysStatutory` | Malaysian statutory (EPF, SOCSO, PCB) |
| `Nexus\Identity` | User provisioning |
| `Nexus\Party` | Contact management |

**Key Interfaces:**
```php
use Nexus\ProcurementOperations\Coordinators\ProcurementCoordinator;
use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\HumanResourceOperations\Coordinators\AttendanceCoordinator;
use Nexus\Identity\Contracts\PermissionCheckerInterface;  // For RBAC
```

---

### **Nexus\ProcurementOperations** ⚠️ **~30% COVERAGE - NEEDS IMPROVEMENT**

**Capabilities:**
- Procure-to-Pay (P2P) workflow coordination
- Three-way matching (PO-GR-Invoice)
- Payment batch processing
- GR-IR accrual posting

**Current Implementation Status:**

| Area | Coverage | Status |
|------|----------|--------|
| Basic Requisition → PO flow | 40% | ⚠️ Basic |
| Three-Way Matching | 60% | ✅ Good |
| Payment Processing | 30% | ⚠️ Basic |
| Vendor Management | 10% | 🔴 Critical Gap |
| Contract Management | 0% | 🔴 Not Implemented |
| Compliance Controls | 20% | 🔴 Critical Gap |
| Analytics/Reporting | 0% | 🔴 Not Implemented |

**⚠️ Critical Gaps (See Gap Analysis):**
- Multi-level approval routing with delegation
- Blanket/Framework POs and Contract POs
- Vendor onboarding and compliance tracking
- Duplicate invoice detection
- Payment method strategies (ACH, Wire, Check)
- Segregation of duties validation
- Spend analytics and AP aging reports

**Key Interfaces:**
```php
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;    // For ABAC
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\AccountVarianceAnalysis\Contracts\VarianceAnalyzerInterface;
use Nexus\ProcurementOperations\Coordinators\ThreeWayMatchCoordinator;
```

---

## 🚧 **22. Areas Needing Improvement**

This section highlights packages and orchestrators that require additional development.

### **ProcurementOperations - Critical Gaps**

Based on the comprehensive gap analysis comparing against SAP Ariba, Oracle Procurement Cloud, and Microsoft Dynamics 365:

#### 🔴 **Critical Priority (Must Have)**

| Gap | Description | Business Impact |
|-----|-------------|-----------------|
| **Multi-level Approval** | Approval routing by amount, category, cost center | Cannot enforce corporate policies |
| **Delegation & Substitution** | Manager absence → delegate approves | Blocks approvals |
| **Blanket/Contract POs** | Long-term agreements with release orders | Cannot manage volume commitments |
| **Vendor Hold Management** | Block POs/payments for non-compliant vendors | Payments to risky vendors |
| **Credit/Debit Memo** | Handle vendor credits | Cannot process vendor credits |
| **Duplicate Invoice Detection** | Prevent duplicate payments | Financial loss risk |
| **Segregation of Duties** | Requestor ≠ Approver ≠ Receiver | Fraud risk |
| **Payment Method Support** | ACH, Wire, Check, Virtual Card | Limited payment options |

#### 🟡 **High Priority (Should Have)**

| Gap | Description |
|-----|-------------|
| **Budget Pre-check** | Block requisition if budget unavailable |
| **Quality Inspection Integration** | QC hold before stock receipt |
| **Return to Vendor (RTV)** | Process for rejected goods |
| **Invoice Hold Management** | Hold invoice with reason codes |
| **Early Payment Discount** | Auto-capture 2/10 Net 30 discounts |
| **Tax Validation** | Validate tax amounts/codes |

#### 🟢 **Medium Priority (Nice to Have)**

| Gap | Description |
|-----|-------------|
| **Spend Analytics** | Spend by category, vendor, department |
| **Vendor Performance Scorecards** | Quality, delivery, price KPIs |
| **Cash Flow Forecasting** | Predict future cash requirements |
| **EDI Support** | Electronic PO/Invoice exchange |

**Estimated Effort to Enterprise-Ready:** 16-23 weeks

**Recommended Phases:**
1. **Phase A (4-6 weeks):** Critical Foundation - approvals, blanket POs, vendor hold, duplicate detection
2. **Phase B (3-4 weeks):** Compliance Controls - SOX readiness, spend policies, tax validation
3. **Phase C (4-6 weeks):** Advanced Features - RFQ, vendor onboarding, quality inspection
4. **Phase D (2-3 weeks):** Analytics - spend analytics, AP aging, vendor scorecards
5. **Phase E (3-4 weeks):** Integrations - bank files, EDI, vendor portal

---

### **Other Packages Needing Enhancement**

| Package | Gap | Priority |
|---------|-----|----------|
| `Nexus\SSO` | Currently PLANNED status | 🟡 High |
| `Nexus\ProcurementML` | No unit tests | 🟢 Medium |
| `Nexus\PerformanceReview` | Minimal documentation | 🟢 Medium |
| `Nexus\FinancialRatios` | Missing market ratio integrations | 🟢 Low |

---

## 🔄 Package Integration Patterns

### Pattern 1: Cross-Package Communication via Interfaces

When Package A needs functionality from Package B:

**❌ WRONG:**
```php
// Direct coupling between packages
use Nexus\AccountConsolidation\Contracts\ConsolidationManagerInterface;

public function __construct(
    private readonly JournalEntryManager $jeManager // Concrete class!
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

use Nexus\ProcurementOperations\Coordinators\PaymentProcessingCoordinator;
use Nexus\AccountingOperations\Coordinators\StatementGenerationCoordinator;

final readonly class JournalEntryAdapter implements GeneralLedgerIntegrationInterface
{
    public function __construct(
        private JournalEntryManagerInterface $jeManager
    ) {}
    
    public function postJournalEntry(JournalEntry $entry): void
    {
        $this->jeManager->post($entry);
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
use Nexus\AccountVarianceAnalysis\Contracts\VarianceReportGeneratorInterface;

public function __construct(
    private readonly JournalEntryManager $jeManager
) {}

// ✅ CORRECT: Package defines interface, consuming app wires implementation
use Nexus\PayrollMysStatutory\Contracts\MalaysianStatutoryCalculatorInterface;

public function __construct(
    private readonly GeneralLedgerIntegrationInterface $glIntegration
) {}
```

### ❌ Anti-Pattern 3: Framework Coupling in Packages

```php
// ❌ WRONG: Using Laravel facades in package
use Nexus\ProcurementML\Contracts\VendorPerformanceAnalyticsRepositoryInterface;

public function getTenant(string $id): Tenant {
    return Cache::remember("tenant.{$id}", 3600, fn() => $this->fetch($id));
}

// ✅ CORRECT: Inject cache interface
use Nexus\Identity\ValueObjects\Policy;                   // Policy builder helper

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
| Manage GL accounts | `Nexus\ChartOfAccount` | `ChartOfAccountManagerInterface` |
| Post journal entries | `Nexus\JournalEntry` | `JournalEntryManagerInterface` |
| Create customer invoices | `Nexus\Receivable` | `ReceivableManagerInterface` |
| Process vendor bills | `Nexus\Payable` | `PayableManagerInterface` |
| **Process payments** | **`Nexus\Payment`** | **`PaymentManagerInterface`** |
| **Create disbursements** | **`Nexus\Payment`** | **`DisbursementManagerInterface`** |
| **Schedule disbursements** | **`Nexus\Payment`** | **`DisbursementSchedulerInterface`** |
| **Validate disbursement limits** | **`Nexus\Payment`** | **`DisbursementLimitValidatorInterface`** |
| **Allocate payments to invoices** | **`Nexus\Payment`** | **`AllocationEngineInterface`** |
| **Manage settlement batches** | **`Nexus\Payment`** | **`SettlementBatchManagerInterface`** |
| **Multi-gateway payment processing** | **`Nexus\Payment`** | **`GatewayRegistryInterface`** |
| Track inventory | `Nexus\Inventory` | `InventoryManagerInterface` |
| **Create/manage BOMs** | **`Nexus\Manufacturing`** | **`BomManagerInterface`** |
| **Manage production routings** | **`Nexus\Manufacturing`** | **`RoutingManagerInterface`** |
| **Create work orders** | **`Nexus\Manufacturing`** | **`WorkOrderManagerInterface`** |
| **Run MRP planning** | **`Nexus\Manufacturing`** | **`MrpEngineInterface`** |
| **Plan production capacity** | **`Nexus\Manufacturing`** | **`CapacityPlannerInterface`** |
| **Forecast demand with ML** | **`Nexus\Manufacturing`** | **`DemandForecasterInterface`** |
| Manage employees | `Nexus\Hrm` | `EmployeeManagerInterface` |
| **Manage employee leave** | **`Nexus\HRM\Leave`** | **`LeaveManagerInterface`** |
| **Track attendance** | **`Nexus\HRM\Attendance`** | **`AttendanceManagerInterface`** |
| **Manage shift schedules** | **`Nexus\HRM\Shift`** | **`ShiftManagerInterface`** |
| **Manage employee profiles** | **`Nexus\HRM\EmployeeProfile`** | **`EmployeeManagerInterface`** |
| **Generate payslips** | **`Nexus\HRM\PayrollCore`** | **`PayslipGeneratorInterface`** |
| **Manage recruitment/ATS** | **`Nexus\HRM\Recruitment`** | **`RecruitmentManagerInterface`** |
| **Track training/certifications** | **`Nexus\HRM\Training`** | **`TrainingManagerInterface`** |
| **Manage onboarding** | **`Nexus\HRM\Onboarding`** | **`OnboardingManagerInterface`** |
| **Handle disciplinary cases** | **`Nexus\HRM\Disciplinary`** | **`DisciplinaryManagerInterface`** |
| Process payroll | `Nexus\Payroll` | `PayrollManagerInterface` |
| Call external APIs | `Nexus\Connector` | `ConnectorManagerInterface` |
| Validate periods | `Nexus\Period` | `PeriodValidatorInterface` |
| Check permissions | `Nexus\Identity` | `AuthorizationManagerInterface` |
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
| **Encrypt/decrypt data** | **`Nexus\Crypto`** | **`CryptoManagerInterface`** |
| **Manage encryption keys** | **`Nexus\Crypto`** | **`KeyManagerInterface`** |
| **Hash data securely** | **`Nexus\Crypto`** | **`HashManagerInterface`** |
| **Sign/verify documents** | **`Nexus\Crypto`** | **`SignatureManagerInterface`** |
| Get/set app config | `Nexus\Setting` | `SettingsManagerInterface` |
| **Manage feature flags** | **`Nexus\FeatureFlags`** | **`FeatureFlagManagerInterface`** |
| **Calculate taxes** | **`Nexus\Tax`** | **`TaxCalculatorInterface`** |
| **Publish/consume messages** | **`Nexus\Messaging`** | **`MessagePublisherInterface`, `MessageConsumerInterface`** |
| **Manage content/CMS** | **`Nexus\Content`** | **`ContentManagerInterface`** |
| **Track detailed changes** | **`Nexus\Audit`** | **`ChangeTrackerInterface`** |
| **Generate financial statements** | **`Nexus\FinancialStatements`** | **`StatementGeneratorInterface`** |
| **Calculate financial ratios** | **`Nexus\FinancialRatios`** | **`RatioCalculatorInterface`** |
| **DuPont ROE analysis** | **`Nexus\FinancialRatios`** | **`DuPontAnalyzerInterface`** |
| **Consolidate multi-entity financials** | **`Nexus\AccountConsolidation`** | **`ConsolidationManagerInterface`** |
| **Close accounting periods** | **`Nexus\AccountPeriodClose`** | **`PeriodCloseManagerInterface`** |
| **Analyze budget variances** | **`Nexus\AccountVarianceAnalysis`** | **`VarianceAnalyzerInterface`** |
| **Manage performance reviews** | **`Nexus\PerformanceReview`** | **`ReviewManagerInterface`** |
| **Verify customer identity (KYC)** | **`Nexus\KycVerification`** | **`KycVerificationManagerInterface`** |
| **Assess KYC risk** | **`Nexus\KycVerification`** | **`RiskAssessmentInterface`** |
| **Track beneficial ownership** | **`Nexus\KycVerification`** | **`BeneficialOwnershipInterface`** |
| **Calculate AML risk scores** | **`Nexus\AmlCompliance`** | **`AmlRiskAssessorInterface`** |
| **Monitor suspicious transactions** | **`Nexus\AmlCompliance`** | **`TransactionMonitorInterface`** |
| **Generate SAR reports** | **`Nexus\AmlCompliance`** | **`SarGeneratorInterface`** |
| **Manage user consent** | **`Nexus\DataPrivacy`** | **`ConsentManagerInterface`** |
| **Handle DSAR requests** | **`Nexus\DataPrivacy`** | **`DsarHandlerInterface`** |
| **Manage data retention** | **`Nexus\DataPrivacy`** | **`RetentionPolicyInterface`** |
| **Handle data breaches** | **`Nexus\DataPrivacy`** | **`BreachManagerInterface`** |
| **GDPR breach notification** | **`Nexus\GDPR`** | **`BreachNotificationInterface`** |
| **GDPR lawful basis tracking** | **`Nexus\GDPR`** | **`LawfulBasisManagerInterface`** |
| **Malaysian PDPA compliance** | **`Nexus\PDPA`** | **`PdpaComplianceManagerInterface`** |
| **Screen sanctions lists** | **`Nexus\Sanctions`** | **`SanctionsScreenerInterface`** |
| **Detect PEPs** | **`Nexus\Sanctions`** | **`PepDetectorInterface`** |
| **Manage watchlists** | **`Nexus\Sanctions`** | **`WatchlistManagerInterface`** |
| **Coordinate P2P workflows** | **`Nexus\ProcurementOperations`** | **`ProcurementCoordinator`** |
| **Coordinate HR workflows** | **`Nexus\HumanResourceOperations`** | **`HiringCoordinator`, `PayrollCoordinator`** |
| **Coordinate accounting workflows** | **`Nexus\AccountingOperations`** | **`PeriodCloseCoordinator`** |

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
- **ProcurementOperations Gap Analysis**: [`orchestrators/ProcurementOperations/GAP_ANALYSIS_PROCUREMENT_OPERATIONS.md`](../orchestrators/ProcurementOperations/GAP_ANALYSIS_PROCUREMENT_OPERATIONS.md)

---

**Last Updated:** December 18, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Mandatory for all coding agents and developers
