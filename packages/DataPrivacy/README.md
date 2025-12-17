# Nexus\DataPrivacy

**Version:** 1.0.0  
**PHP Version:** ^8.3  
**Type:** Atomic Package (Framework-Agnostic)  
**Status:** ✅ Production Ready

## Overview

`Nexus\DataPrivacy` provides a **regulation-agnostic foundation** for personal data protection and privacy compliance. It implements core privacy management capabilities without embedding any specific jurisdiction's rules.

This package follows the **Progressive Disclosure Pattern** - it serves as the core abstraction layer, while jurisdiction-specific packages (like `Nexus\GDPR` or `Nexus\PDPA`) extend it with regulatory requirements.

## Key Features

- ✅ **Consent Management** - Track, grant, withdraw, and renew data processing consents
- ✅ **Data Subject Requests (DSARs)** - Full lifecycle management for access, erasure, rectification, and portability requests
- ✅ **Retention Policy Engine** - Configurable data retention with category-based policies
- ✅ **Breach Incident Management** - Record, track, and manage data breach incidents
- ✅ **Processing Activity Records (ROPA)** - Maintain records of processing activities for compliance
- ✅ **Audit Trail Integration** - Full audit logging for all privacy-related operations
- ✅ **Request Handlers** - Pluggable handlers for each request type
- ✅ **Framework-Agnostic** - Pure PHP 8.3+, works with any framework

## Installation

```bash
composer require nexus/data-privacy
```

## Installation

```bash
composer require nexus/data-privacy
```

## Architecture

### Progressive Disclosure Pattern

This package serves as the **regulation-agnostic core**. Jurisdiction-specific requirements are provided by extension packages:

```
┌─────────────────────────────────────────────────────────┐
│                   Application Layer                      │
└───────────────────────┬─────────────────────────────────┘
                        │ uses
         ┌──────────────┼──────────────┐
         ▼              ▼              ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│ Nexus\GDPR  │  │ Nexus\PDPA  │  │ Nexus\CCPA  │
│ (EU Rules)  │  │ (MY Rules)  │  │ (CA Rules)  │
└──────┬──────┘  └──────┬──────┘  └──────┬──────┘
       │ extends        │ extends        │ extends
       └────────────────┼────────────────┘
                        ▼
              ┌─────────────────────┐
              │  Nexus\DataPrivacy  │  ← This package
              │  (Core Abstractions)│
              └─────────────────────┘
```

### Package Structure

```
DataPrivacy/
├── src/
│   ├── Contracts/
│   │   ├── External/           # Interfaces for framework dependencies
│   │   │   ├── AuditLoggerInterface.php
│   │   │   ├── DataEncryptorInterface.php
│   │   │   ├── NotificationDispatcherInterface.php
│   │   │   ├── PartyProviderInterface.php
│   │   │   └── StorageInterface.php
│   │   ├── ConsentQueryInterface.php
│   │   ├── ConsentPersistInterface.php
│   │   ├── ConsentManagerInterface.php
│   │   ├── DataSubjectRequestQueryInterface.php
│   │   ├── DataSubjectRequestPersistInterface.php
│   │   ├── DataSubjectRequestManagerInterface.php
│   │   ├── RetentionPolicyQueryInterface.php
│   │   ├── RetentionPolicyPersistInterface.php
│   │   ├── RetentionPolicyManagerInterface.php
│   │   ├── BreachRecordQueryInterface.php
│   │   ├── BreachRecordPersistInterface.php
│   │   ├── BreachRecordManagerInterface.php
│   │   ├── ProcessingActivityQueryInterface.php
│   │   ├── ProcessingActivityPersistInterface.php
│   │   ├── ProcessingActivityManagerInterface.php
│   │   └── RequestHandlerInterface.php
│   ├── Enums/
│   │   ├── RequestType.php         # ACCESS, ERASURE, RECTIFICATION, etc.
│   │   ├── RequestStatus.php       # State machine for request lifecycle
│   │   ├── ConsentStatus.php       # GRANTED, WITHDRAWN, EXPIRED, etc.
│   │   ├── DataCategory.php        # Personal data categories with risk levels
│   │   ├── LawfulBasisType.php     # GDPR Article 6 lawful bases
│   │   ├── BreachSeverity.php      # LOW, MEDIUM, HIGH, CRITICAL
│   │   ├── RetentionCategory.php   # Document retention categories
│   │   └── ConsentPurpose.php      # Standard consent purposes
│   ├── ValueObjects/
│   │   ├── DataSubjectId.php       # Unique data subject identifier
│   │   ├── Consent.php             # Immutable consent record
│   │   ├── DataSubjectRequest.php  # DSAR with state machine
│   │   ├── RetentionPolicy.php     # Retention policy configuration
│   │   ├── BreachRecord.php        # Data breach incident record
│   │   └── ProcessingActivity.php  # ROPA entry
│   ├── Services/
│   │   ├── ConsentManager.php
│   │   ├── DataSubjectRequestManager.php
│   │   ├── RetentionPolicyManager.php
│   │   ├── BreachRecordManager.php
│   │   ├── ProcessingActivityManager.php
│   │   ├── RequestHandlerRegistry.php
│   │   └── Handlers/
│   │       ├── AccessRequestHandler.php
│   │       ├── ErasureRequestHandler.php
│   │       ├── RectificationRequestHandler.php
│   │       └── PortabilityRequestHandler.php
│   └── Exceptions/
│       ├── DataPrivacyException.php
│       ├── Invalid*Exception.php   # Validation exceptions
│       └── *NotFoundException.php  # Not found exceptions
└── tests/
```

## Quick Start

### 1. Consent Management

```php
use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\LawfulBasisType;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;

public function __construct(
    private readonly ConsentManagerInterface $consentManager
) {}

// Grant consent
$consent = $this->consentManager->grantConsent(
    subjectId: DataSubjectId::fromPartyId('party-12345'),
    purpose: ConsentPurpose::MARKETING_EMAIL,
    lawfulBasis: LawfulBasisType::CONSENT,
    expiresAt: new \DateTimeImmutable('+1 year'),
    metadata: ['ip_address' => '192.168.1.1', 'source' => 'web_form']
);

// Check if consent is active
$hasConsent = $this->consentManager->hasActiveConsent(
    subjectId: DataSubjectId::fromPartyId('party-12345'),
    purpose: ConsentPurpose::MARKETING_EMAIL
); // Returns: bool

// Withdraw consent
$this->consentManager->withdrawConsent(
    consentId: $consent->getId(),
    reason: 'User requested via preference center'
);

// Renew consent before expiry
$renewed = $this->consentManager->renewConsent(
    consentId: $consent->getId(),
    newExpiresAt: new \DateTimeImmutable('+2 years')
);
```

### 2. Data Subject Requests (DSARs)

```php
use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;

public function __construct(
    private readonly DataSubjectRequestManagerInterface $dsrManager
) {}

// Create an access request
$request = $this->dsrManager->createRequest(
    subjectId: DataSubjectId::fromEmail('customer@example.com'),
    type: RequestType::ACCESS,
    metadata: ['verification_method' => 'email_otp']
);

// Verify the request
$this->dsrManager->verifyRequest($request->getId());

// Process the request (moves to IN_PROGRESS)
$this->dsrManager->startProcessing($request->getId());

// Execute the request through handler
$result = $this->dsrManager->executeRequest(
    requestId: $request->getId(),
    handlerResult: ['export_url' => 'https://...', 'expires_at' => '...']
);

// Complete the request
$this->dsrManager->completeRequest(
    requestId: $request->getId(),
    result: $result
);

// Get overdue requests for monitoring
$overdue = $this->dsrManager->getOverdueRequests();
```

### 3. Retention Policies

```php
use Nexus\DataPrivacy\Contracts\RetentionPolicyManagerInterface;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Enums\RetentionCategory;

public function __construct(
    private readonly RetentionPolicyManagerInterface $retentionManager
) {}

// Create a retention policy
$policy = $this->retentionManager->createPolicy(
    name: 'Customer Invoice Retention',
    dataCategory: DataCategory::FINANCIAL,
    retentionMonths: 84, // 7 years
    autoDelete: false,   // Require manual review
    description: 'Retain invoices for tax compliance'
);

// Find applicable policies for a data category
$policies = $this->retentionManager->findPoliciesForCategory(DataCategory::FINANCIAL);

// Get items due for deletion
$dueItems = $this->retentionManager->getItemsDueForDeletion();

// Execute retention (delete expired data)
$this->retentionManager->executeRetention(
    policyId: $policy->getId(),
    dryRun: true // Preview what would be deleted
);
```

### 4. Breach Management

```php
use Nexus\DataPrivacy\Contracts\BreachRecordManagerInterface;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\Enums\DataCategory;

public function __construct(
    private readonly BreachRecordManagerInterface $breachManager
) {}

// Report a data breach
$breach = $this->breachManager->reportBreach(
    title: 'Unauthorized Database Access',
    description: 'External actor gained access to customer table',
    discoveredAt: new \DateTimeImmutable(),
    affectedSubjectCount: 1500,
    affectedCategories: [DataCategory::CONTACT, DataCategory::FINANCIAL],
    severity: BreachSeverity::HIGH
);

// Notify regulatory authority (within 72 hours for GDPR)
$this->breachManager->notifyRegulator(
    breachId: $breach->getId(),
    authorityName: 'ICO',
    notifiedAt: new \DateTimeImmutable(),
    referenceNumber: 'ICO-2024-12345'
);

// Record containment actions
$this->breachManager->recordContainmentAction(
    breachId: $breach->getId(),
    action: 'Revoked compromised API keys',
    performedBy: 'security-team',
    performedAt: new \DateTimeImmutable()
);

// Resolve the breach
$this->breachManager->resolveBreach(
    breachId: $breach->getId(),
    resolution: 'All affected users notified, credentials reset, security audit completed',
    resolvedAt: new \DateTimeImmutable()
);
```

### 5. Processing Activities (ROPA)

```php
use Nexus\DataPrivacy\Contracts\ProcessingActivityManagerInterface;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Enums\LawfulBasisType;

public function __construct(
    private readonly ProcessingActivityManagerInterface $ropaManager
) {}

// Register a processing activity
$activity = $this->ropaManager->registerActivity(
    name: 'Customer Order Processing',
    purpose: 'Process and fulfill customer orders',
    lawfulBasis: LawfulBasisType::CONTRACT,
    dataCategories: [DataCategory::CONTACT, DataCategory::FINANCIAL, DataCategory::TRANSACTION],
    dataSubjectCategories: ['customers', 'shipping_recipients'],
    recipients: ['payment_processor', 'shipping_provider'],
    retentionPeriod: '7 years',
    technicalMeasures: ['encryption_at_rest', 'tls_1_3', 'access_controls'],
    organizationalMeasures: ['staff_training', 'data_minimization', 'access_reviews']
);

// Check if DPIA is required
if ($activity->requiresDpia()) {
    $this->ropaManager->markDpiaRequired($activity->getId());
}

// Mark activity as reviewed
$this->ropaManager->markReviewed(
    activityId: $activity->getId(),
    reviewedBy: 'dpo@company.com',
    nextReviewDate: new \DateTimeImmutable('+1 year')
);

// Get activities needing review
$needReview = $this->ropaManager->getActivitiesNeedingReview();
```

## External Dependencies

This package defines interfaces for external dependencies. Your application must provide implementations:

### PartyProviderInterface

Provides access to personal data stored in `Nexus\Party` or your party system:

```php
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;

final readonly class PartyAdapter implements PartyProviderInterface
{
    public function __construct(
        private PartyManagerInterface $partyManager
    ) {}
    
    public function partyExists(string $partyId): bool
    {
        return $this->partyManager->exists($partyId);
    }
    
    public function getPersonalData(string $partyId): array
    {
        $party = $this->partyManager->findById($partyId);
        return [
            'name' => $party->getName(),
            'email' => $party->getEmail(),
            'phone' => $party->getPhone(),
            // ... other personal data fields
        ];
    }
    
    public function deletePersonalData(string $partyId): void
    {
        $this->partyManager->anonymize($partyId);
    }
    
    // ... implement other methods
}
```

### AuditLoggerInterface

Logs all privacy operations for compliance:

```php
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;

final readonly class AuditLoggerAdapter implements AuditLoggerInterface
{
    public function __construct(
        private AuditLogManagerInterface $auditLogger
    ) {}
    
    public function log(string $action, string $entityType, string $entityId, array $metadata = []): void
    {
        $this->auditLogger->log(
            entityId: $entityId,
            action: $action,
            description: "Privacy action: {$action} on {$entityType}",
            metadata: $metadata
        );
    }
    
    // ... implement other methods
}
```

### Binding in Laravel

```php
// AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(
        PartyProviderInterface::class,
        PartyAdapter::class
    );
    
    $this->app->singleton(
        AuditLoggerInterface::class,
        AuditLoggerAdapter::class
    );
    
    $this->app->singleton(
        ConsentQueryInterface::class,
        EloquentConsentRepository::class
    );
    
    $this->app->singleton(
        ConsentPersistInterface::class,
        EloquentConsentRepository::class
    );
    
    // ... bind other interfaces
}
```

## Enums Reference

### RequestType

| Value | Description | Typical Deadline |
|-------|-------------|------------------|
| `ACCESS` | Right to access personal data | 30 days (GDPR) |
| `ERASURE` | Right to be forgotten | 30 days (GDPR) |
| `RECTIFICATION` | Right to correct data | 30 days (GDPR) |
| `RESTRICTION` | Right to restrict processing | 30 days (GDPR) |
| `PORTABILITY` | Right to data portability | 30 days (GDPR) |
| `OBJECTION` | Right to object to processing | 30 days (GDPR) |
| `AUTOMATED_DECISION` | Rights related to automated decisions | 30 days (GDPR) |

### RequestStatus

```
PENDING → VERIFIED → IN_PROGRESS → COMPLETED
                  ↘             ↗
                    → REJECTED
                    → CANCELLED
```

### DataCategory

| Category | Special Category | Risk Level |
|----------|-----------------|------------|
| `BASIC_IDENTITY` | No | Low |
| `CONTACT` | No | Low |
| `FINANCIAL` | No | High |
| `HEALTH` | Yes | Critical |
| `BIOMETRIC` | Yes | Critical |
| `GENETIC` | Yes | Critical |
| `RACIAL_ETHNIC` | Yes | High |
| `POLITICAL` | Yes | High |
| `RELIGIOUS` | Yes | High |
| `SEXUAL_ORIENTATION` | Yes | High |
| `TRADE_UNION` | Yes | High |
| `CRIMINAL` | Yes | Critical |
| `CHILDREN` | Yes | Critical |
| `LOCATION` | No | Medium |
| `BEHAVIORAL` | No | Medium |
| `COMMUNICATION` | No | Medium |
| `TRANSACTION` | No | Medium |
| `EMPLOYMENT` | No | Medium |

### BreachSeverity

| Severity | Response Time | Criteria |
|----------|--------------|----------|
| `LOW` | 5 days | < 100 subjects, no special categories |
| `MEDIUM` | 48 hours | 100-1000 subjects, low-risk categories |
| `HIGH` | 24 hours | 1000-10000 subjects or sensitive categories |
| `CRITICAL` | Immediate | > 10000 subjects or highly sensitive |

## Extension Packages

### Nexus\GDPR (EU General Data Protection Regulation)

```php
// Enforces GDPR-specific rules:
// - 30-day response deadline for DSARs
// - 72-hour breach notification to regulators
// - Data Protection Impact Assessments (DPIA)
// - Mandatory DPO appointment checks
```

### Nexus\PDPA (Malaysia Personal Data Protection Act)

```php
// Enforces PDPA-specific rules:
// - 21-day response deadline for DSARs
// - Sector-specific requirements (banking, healthcare)
// - Data Commissioner notification rules
```

## Testing

```bash
# Run tests
composer test

# Run with coverage
composer test:coverage

# Target: 85%+ code coverage
```

## Dependencies

```json
{
    "require": {
        "php": "^8.3",
        "nexus/common": "^1.0"
    }
}
```

## License

MIT License. See [LICENSE](LICENSE) file for details.

---

**Last Updated**: December 2025  
**Maintained By**: Nexus Architecture Team

