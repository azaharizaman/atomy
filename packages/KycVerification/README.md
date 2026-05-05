# Nexus\KycVerification

**Know Your Customer (KYC) Verification Package for Nexus ERP**

A comprehensive, framework-agnostic PHP package for managing KYC verification processes, risk assessment, beneficial ownership tracking, and review scheduling.

## Overview

`Nexus\KycVerification` provides a complete solution for:

- **Party Verification**: Full lifecycle management from initiation to approval/rejection
- **Risk Assessment**: Multi-factor risk scoring with configurable thresholds
- **Beneficial Ownership**: UBO identification, circular ownership detection, PEP tracking
- **Review Scheduling**: Risk-based review frequency with SLA tracking
- **Document Verification**: Multi-document type support with expiry tracking
- **Sanctions & PEP Screening**: Integration points for screening providers

## Installation

```bash
composer require azaharizaman/nexus-kyc-verification
```

## Requirements

- PHP 8.3+
- `azaharizaman/nexus-common` package (for shared value objects)
- `psr/log` (for logging interface)

## Architecture

This package follows the **Atomic Package Pattern**:

- **No external package dependencies** beyond `azaharizaman/nexus-common`
- **Provider interfaces** for external integrations (Party, Document, Screening)
- **Framework-agnostic** - works with Laravel, Symfony, or any PHP framework
- **Orchestrator layer implements** the provider interfaces

### Package Structure

```
src/
├── Contracts/
│   ├── Providers/              # External integration interfaces
│   │   ├── PartyProviderInterface.php
│   │   ├── DocumentVerificationProviderInterface.php
│   │   ├── ScreeningProviderInterface.php
│   │   ├── AddressVerificationProviderInterface.php
│   │   └── AuditLoggerProviderInterface.php
│   ├── KycVerificationManagerInterface.php
│   ├── RiskAssessorInterface.php
│   ├── BeneficialOwnershipTrackerInterface.php
│   ├── ReviewSchedulerInterface.php
│   ├── KycProfileQueryInterface.php
│   └── KycProfilePersistInterface.php
├── Enums/
│   ├── VerificationStatus.php
│   ├── DocumentType.php
│   ├── RiskLevel.php
│   ├── DueDiligenceLevel.php
│   ├── ReviewTrigger.php
│   └── PartyType.php
├── Exceptions/
│   ├── KycVerificationException.php
│   ├── VerificationFailedException.php
│   ├── DocumentExpiredException.php
│   ├── HighRiskPartyException.php
│   ├── InvalidStatusTransitionException.php
│   ├── BeneficialOwnershipException.php
│   └── ReviewOverdueException.php
├── ValueObjects/
│   ├── DocumentVerification.php
│   ├── AddressVerification.php
│   ├── BeneficialOwner.php
│   ├── RiskAssessment.php
│   ├── RiskFactor.php
│   ├── ReviewSchedule.php
│   ├── KycProfile.php
│   └── VerificationResult.php
└── Services/
    ├── KycVerificationManager.php
    ├── RiskAssessor.php
    ├── BeneficialOwnershipTracker.php
    └── ReviewScheduler.php
```

## Quick Start

### 1. Implement Provider Interfaces

The orchestrator layer must implement the provider interfaces:

```php
use Nexus\KycVerification\Contracts\Providers\PartyProviderInterface;

class PartyProviderAdapter implements PartyProviderInterface
{
    public function __construct(
        private PartyManagerInterface $partyManager
    ) {}

    public function findById(string $partyId): ?array
    {
        $party = $this->partyManager->findById($partyId);
        return $party ? $party->toArray() : null;
    }

    public function getPartyType(string $partyId): ?string
    {
        $party = $this->partyManager->findById($partyId);
        return $party?->getType()->value;
    }

    // ... implement other methods
}
```

### 2. Initialize Services

```php
use Nexus\KycVerification\Services\KycVerificationManager;
use Nexus\KycVerification\Services\RiskAssessor;
use Nexus\KycVerification\Services\BeneficialOwnershipTracker;
use Nexus\KycVerification\Services\ReviewScheduler;

// Create service instances with dependencies
$kycManager = new KycVerificationManager(
    profileQuery: $profileQueryRepository,
    profilePersist: $profilePersistRepository,
    riskAssessor: $riskAssessor,
    ownershipTracker: $ownershipTracker,
    reviewScheduler: $reviewScheduler,
    partyProvider: $partyProviderAdapter,
    auditLogger: $auditLoggerAdapter,
    logger: $psrLogger
);
```

### 3. Initiate Verification

```php
use Nexus\KycVerification\Enums\DueDiligenceLevel;

// Initiate KYC verification for a customer
$result = $kycManager->initiateVerification(
    partyId: 'CUST-001',
    dueDiligenceLevel: DueDiligenceLevel::STANDARD
);

if ($result->isPending()) {
    // Verification initiated, collect documents
    echo "Required documents: " . count($result->details['required_documents']);
}
```

### 4. Add Document Verification

```php
use Nexus\KycVerification\ValueObjects\DocumentVerification;
use Nexus\KycVerification\Enums\DocumentType;

$document = DocumentVerification::verified(
    documentId: 'DOC-001',
    documentType: DocumentType::PASSPORT,
    confidence: 0.95,
    expiryDate: new DateTimeImmutable('+5 years')
);

$result = $kycManager->addDocumentVerification('CUST-001', $document);
```

### 5. Complete Verification

```php
$result = $kycManager->completeVerification(
    partyId: 'CUST-001',
    verifiedBy: 'OFFICER-001'
);

if ($result->isSuccess()) {
    echo "Customer verified successfully!";
} elseif ($result->isConditional()) {
    echo "Missing requirements: " . implode(', ', $result->conditions);
}
```

## Risk Assessment

### Automatic Risk Scoring

```php
use Nexus\KycVerification\Services\RiskAssessor;

$assessment = $riskAssessor->assess('CUST-001', [
    'industry' => 'gambling',
    'transaction_volume' => 1_500_000
]);

echo "Risk Level: " . $assessment->riskLevel->value;
echo "Risk Score: " . $assessment->riskScore;

foreach ($assessment->factors as $factor) {
    echo "- {$factor->name}: {$factor->score} points";
}
```

### Risk Levels

| Level | Score Range | Review Frequency | Due Diligence |
|-------|-------------|------------------|---------------|
| LOW | 0-20 | 24 months | Simplified (SDD) |
| MEDIUM | 21-40 | 12 months | Standard (CDD) |
| HIGH | 41-60 | 6 months | Enhanced (EDD) |
| VERY_HIGH | 61-80 | 3 months | Enhanced (EDD) |
| PROHIBITED | 81-100 | N/A | Blocked |

### Override Risk Level

```php
$assessment = $riskAssessor->overrideRiskLevel(
    partyId: 'CUST-001',
    newRiskLevel: RiskLevel::MEDIUM,
    reason: 'Mitigating controls in place',
    approvedBy: 'SENIOR-OFFICER-001'
);
```

## Beneficial Ownership

### Register UBOs

```php
use Nexus\KycVerification\ValueObjects\BeneficialOwner;

$owner = new BeneficialOwner(
    ownerId: 'PERSON-001',
    name: 'John Smith',
    ownershipPercentage: 40.0,
    controlRights: ['voting', 'management'],
    isPep: false,
    nationality: 'MY',
    dateOfBirth: new DateTimeImmutable('1980-05-15'),
    isVerified: true,
    verificationDate: new DateTimeImmutable()
);

$ownershipTracker->registerBeneficialOwner('CORP-001', $owner);
```

### Validate Ownership Structure

```php
$validation = $ownershipTracker->validateOwnershipStructure('CORP-001');

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo "Error: {$error}";
    }
}

// Check circular ownership
if ($ownershipTracker->detectCircularOwnership('CORP-001')) {
    throw new Exception('Circular ownership detected!');
}
```

### Get Ownership Hierarchy

```php
$hierarchy = $ownershipTracker->getOwnershipHierarchy('CORP-001');
// Returns nested tree of ownership structure
```

## Review Scheduling

### Schedule Reviews

```php
use Nexus\KycVerification\Enums\ReviewTrigger;

// Auto-schedule based on risk level
$schedule = $reviewScheduler->autoSchedulePeriodicReview('CUST-001');

// Manual schedule with specific trigger
$schedule = $reviewScheduler->scheduleReview(
    partyId: 'CUST-001',
    trigger: ReviewTrigger::ADVERSE_MEDIA_ALERT
);
```

### Process Reviews

```php
// Start review
$schedule = $reviewScheduler->startReview(
    partyId: 'CUST-001',
    reviewId: 'REV-20240115-ABC123',
    reviewerId: 'OFFICER-001'
);

// Complete review
$schedule = $reviewScheduler->completeReview(
    partyId: 'CUST-001',
    reviewId: 'REV-20240115-ABC123',
    outcome: 'approved',
    completedBy: 'OFFICER-001'
);
```

### Monitor Reviews

```php
// Get overdue reviews
$overdueReviews = $reviewScheduler->getOverdueReviews();

// Get reviews due within 7 days
$upcomingReviews = $reviewScheduler->getReviewsDueSoon(7);

// Get statistics
$stats = $reviewScheduler->getReviewStatistics();
echo "Overdue: {$stats['overdue']}";
echo "In Progress: {$stats['in_progress']}";
```

## Verification Status Workflow

```
                    ┌─────────────────────────────────────────────────────┐
                    │                                                     │
                    ▼                                                     │
┌─────────┐    ┌─────────┐    ┌──────────────┐    ┌────────────┐        │
│ PENDING │───▶│IN_REVIEW│───▶│PENDING_DOCS  │───▶│  VERIFIED  │────────┤
└─────────┘    └─────────┘    └──────────────┘    └────────────┘        │
     │              │               │                   │                │
     │              │               │                   ▼                │
     │              ▼               ▼          ┌───────────────────┐    │
     │         ┌─────────┐    ┌──────────┐    │PENDING_REVERIFICATION│   │
     │         │REJECTED │    │ EXPIRED  │    └───────────────────┘    │
     │         └─────────┘    └──────────┘              │                │
     │                                                  │                │
     ▼                                                  ▼                │
┌─────────────┐                                  ┌───────────┐          │
│  SUSPENDED  │◀─────────────────────────────────│ CANCELLED │          │
└─────────────┘                                  └───────────┘          │
```

## Document Types

The package supports 29 document types across categories:

### Primary Identity Documents
- Passport, National ID, Driving License

### Secondary Documents
- Utility Bill, Bank Statement, Tax Return

### Corporate Documents
- Certificate of Incorporation, Memorandum of Association
- Board Resolution, Shareholder Register

### Financial Documents
- Audited Financial Statements, Bank Reference Letter

## Testing

```bash
./vendor/bin/phpunit packages/KycVerification/tests
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Related Packages

- **azaharizaman/nexus-aml-compliance** - AML risk assessment and compliance
- **azaharizaman/nexus-sanctions** - Sanctions and PEP screening
- **azaharizaman/nexus-compliance** - General compliance management

---

**Last Updated**: January 2025  
**Maintained By**: Nexus Compliance Team
