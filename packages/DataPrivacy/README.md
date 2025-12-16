# Nexus\DataPrivacy

**Version:** 1.0.0  
**Status:** 🔵 In Development  
**Category:** Compliance & Governance

## Overview

`Nexus\DataPrivacy` is a framework-agnostic, atomic PHP package for managing data subject rights under GDPR, CCPA, LGPD, and PIPEDA. It provides comprehensive consent management, data retention policies, and automated data subject request (DSR) workflows.

## Purpose

Manage data privacy compliance and data subject rights:
- **GDPR Data Subject Rights** - Right to erasure, access, portability, rectification, restriction
- **Consent Management** - Granular consent tracking with audit trail
- **Data Retention** - Automated retention policy enforcement
- **Breach Notification** - Data breach notification workflows
- **Multi-Regulation Support** - GDPR, CCPA, LGPD, PIPEDA

## Key Features

- ✅ **Right to Erasure** - GDPR Article 17 "right to be forgotten"
- ✅ **Right to Access** - Data subject access requests (DSAR)
- ✅ **Right to Portability** - Export data in machine-readable format
- ✅ **Right to Rectification** - Data correction workflows
- ✅ **Right to Restriction** - Restrict processing of personal data
- ✅ **Consent Management** - Granular consent with versioning
- ✅ **Retention Policies** - Automated data purge based on retention rules
- ✅ **Breach Notification** - 72-hour notification workflows
- ✅ **Framework-Agnostic** - Pure PHP 8.3+, works with any framework

## Installation

```bash
composer require nexus/data-privacy
```

## Quick Start

### Data Subject Access Request (DSAR)

```php
use Nexus\DataPrivacy\Services\DataSubjectRightsManager;
use Nexus\DataPrivacy\Contracts\DataSubjectRightsManagerInterface;

public function __construct(
    private readonly DataSubjectRightsManagerInterface $dsrManager
) {}

// Create DSAR
$request = $this->dsrManager->createAccessRequest(
    partyId: 'party-12345',
    requestorEmail: 'customer@example.com'
);

// Request includes: Request ID, party ID, status, deadline (30 days)

// Fulfill request - export all personal data
$dataExport = $this->dsrManager->fulfillAccessRequest(
    requestId: $request->getId()
);
// Returns: JSON export of all personal data
```

### Right to Erasure ("Right to be Forgotten")

```php
$erasureRequest = $this->dsrManager->createErasureRequest(
    partyId: 'party-12345',
    reason: 'Customer requested account deletion'
);

// Validate erasure (check for legal hold, contractual obligations)
$validation = $this->dsrManager->validateErasure($erasureRequest->getId());

if ($validation->canErase()) {
    // Execute erasure
    $this->dsrManager->fulfillErasureRequest(
        requestId: $erasureRequest->getId()
    );
    // Anonymizes/deletes all personal data
}
```

### Consent Management

```php
use Nexus\DataPrivacy\Services\ConsentManager;
use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;

public function __construct(
    private readonly ConsentManagerInterface $consentManager
) {}

// Record consent
$this->consentManager->recordConsent(
    partyId: 'party-12345',
    purpose: 'marketing_emails',
    consented: true,
    consentVersion: 'v2.1',
    ipAddress: '192.168.1.1',
    userAgent: 'Mozilla/5.0...'
);

// Check consent
$hasConsent = $this->consentManager->hasConsent(
    partyId: 'party-12345',
    purpose: 'marketing_emails'
); // true or false

// Withdraw consent
$this->consentManager->withdrawConsent(
    partyId: 'party-12345',
    purpose: 'marketing_emails'
);
```

### Data Retention Policies

```php
use Nexus\DataPrivacy\Services\RetentionPolicyManager;

public function __construct(
    private readonly RetentionPolicyManagerInterface $retentionManager
) {}

// Define retention policy
$this->retentionManager->definePolicy(
    dataCategory: 'customer_invoices',
    retentionPeriodYears: 7,
    purgeAction: PurgeAction::ANONYMIZE
);

// Execute retention policies (scheduled job)
$purged = $this->retentionManager->executePolicies();
// Purges/anonymizes data exceeding retention period
```

### Breach Notification

```php
use Nexus\DataPrivacy\Services\BreachNotificationManager;

public function __construct(
    private readonly BreachNotificationManagerInterface $breachManager
) {}

// Report data breach
$breach = $this->breachManager->reportBreach(
    description: 'Unauthorized access to customer database',
    affectedParties: ['party-12345', 'party-67890'],
    dataTypes: ['email', 'phone', 'address'],
    severity: BreachSeverity::HIGH
);

// Auto-triggers 72-hour notification countdown
// Sends notifications to affected data subjects
```

## Architecture

### Atomic Package Compliance

- **Domain-Specific**: ONE domain - Data privacy & data subject rights
- **Stateless**: No in-memory state, all data externalized
- **Framework-Agnostic**: Pure PHP 8.3+, zero framework coupling
- **Logic-Focused**: Business rules only, no migrations
- **Contract-Driven**: All dependencies injected as interfaces
- **Independently Deployable**: Published to Packagist

## Dependencies

- **nexus/party** - Party identity management
- **nexus/audit-logger** - Consent and DSR audit trail
- **psr/log** - PSR-3 logging

## Multi-Regulation Support

| Regulation | Coverage |
|------------|----------|
| **GDPR** (EU) | Full compliance - all 8 data subject rights |
| **CCPA** (California) | Right to know, delete, opt-out of sale |
| **LGPD** (Brazil) | Data subject rights, consent requirements |
| **PIPEDA** (Canada) | Access, correction, consent management |

## Related Packages

- **nexus/party-compliance** - Comprehensive party compliance orchestration
- **nexus/audit-logger** - Audit trail for all privacy operations

## License

MIT License. See LICENSE file for details.

---

**Last Updated**: December 16, 2025  
**Maintained By**: Nexus Compliance Team
