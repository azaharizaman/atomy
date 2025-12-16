# Nexus\KycVerification

**Version:** 1.0.0  
**Status:** 🔵 In Development  
**Category:** Compliance & Governance

## Overview

`Nexus\KycVerification` is a framework-agnostic, atomic PHP package for Know Your Customer (KYC) identity verification and customer due diligence (CDD). It provides document verification workflows, beneficial ownership (UBO) tracking, and Enhanced Due Diligence (EDD) capabilities.

## Purpose

Verify customer identity and track beneficial ownership:
- **Identity Verification** - Passport, national ID, driver's license verification
- **Address Verification** - Utility bills, bank statements
- **UBO Tracking** - Ultimate Beneficial Owner identification (25%+ ownership)
- **CDD/EDD** - Customer/Enhanced Due Diligence workflows
- **Periodic KYC Review** - Ongoing verification requirements

## Key Features

- ✅ **Document Verification** - Verify identity documents (passport, ID, license)
- ✅ **Address Verification** - Verify proof of address documents
- ✅ **UBO Tracking** - Identify beneficial owners with 25%+ ownership
- ✅ **CDD Workflows** - Standard customer due diligence
- ✅ **EDD Workflows** - Enhanced due diligence for high-risk customers
- ✅ **Periodic Review** - Automated KYC refresh requirements
- ✅ **Framework-Agnostic** - Pure PHP 8.3+, works with any framework

## Installation

```bash
composer require nexus/kyc-verification
```

## Quick Start

### Identity Verification

```php
use Nexus\KycVerification\Services\IdentityVerifier;
use Nexus\KycVerification\Contracts\IdentityVerifierInterface;

public function __construct(
    private readonly IdentityVerifierInterface $identityVerifier
) {}

// Verify passport document
$result = $this->identityVerifier->verifyDocument(
    partyId: 'party-12345',
    documentType: DocumentType::PASSPORT,
    documentNumber: 'P123456789',
    issueDate: new \DateTimeImmutable('2020-01-01'),
    expiryDate: new \DateTimeImmutable('2030-01-01'),
    documentFileId: 'file-abc123'
);

if ($result->isVerified()) {
    // Identity verified successfully
} else {
    $rejectionReasons = $result->getRejectionReasons();
    // ['document_expired', 'quality_insufficient']
}
```

### UBO Tracking

```php
use Nexus\KycVerification\Services\UboTracker;
use Nexus\KycVerification\Contracts\UboTrackerInterface;

public function __construct(
    private readonly UboTrackerInterface $uboTracker
) {}

// Register beneficial owner
$this->uboTracker->registerUbo(
    entityPartyId: 'party-company-123',
    uboPartyId: 'party-person-456',
    ownershipPercentage: 35.5,
    controlType: ControlType::DIRECT_OWNERSHIP
);

// Get all UBOs for entity
$ubos = $this->uboTracker->getUbos('party-company-123');
// Returns array of UBO records with ownership % and control types
```

### Enhanced Due Diligence (EDD)

```php
use Nexus\KycVerification\Services\DueDiligenceManager;

public function __construct(
    private readonly DueDiligenceManagerInterface $ddManager
) {}

// Perform EDD for high-risk customer
$eddResult = $this->ddManager->performEdd(
    partyId: 'party-12345',
    riskFactors: ['high_risk_jurisdiction', 'pep_match', 'high_value_transactions'],
    additionalDocuments: ['source_of_wealth_declaration', 'tax_residency_cert']
);
```

## Architecture

### Atomic Package Compliance

- **Domain-Specific**: ONE domain - KYC identity verification
- **Stateless**: No in-memory state, all data externalized
- **Framework-Agnostic**: Pure PHP 8.3+, zero framework coupling
- **Logic-Focused**: Business rules only, no migrations
- **Contract-Driven**: All dependencies injected as interfaces
- **Independently Deployable**: Published to Packagist

## Dependencies

- **nexus/party** - Party identity management
- **nexus/document** - Document storage and retrieval
- **nexus/identity** - User context for verification operators
- **psr/log** - PSR-3 logging

## Related Packages

- **nexus/sanctions** - Sanctions/PEP screening
- **nexus/aml-compliance** - AML risk assessment
- **nexus/party-compliance** - Comprehensive party compliance orchestration

## License

MIT License. See LICENSE file for details.

---

**Last Updated**: December 16, 2025  
**Maintained By**: Nexus Compliance Team
