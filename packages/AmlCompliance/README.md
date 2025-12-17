# Nexus\AmlCompliance

**Version:** 1.0.0  
**Status:** 🔵 In Development  
**Category:** Compliance & Governance

## Overview

`Nexus\AmlCompliance` is a framework-agnostic, atomic PHP package for Anti-Money Laundering (AML) risk assessment and transaction monitoring. It provides sophisticated risk scoring algorithms (0-100 scale) for parties and transactions, with automated Suspicious Activity Report (SAR) generation.

## Purpose

Assess AML risk and detect suspicious financial activity:
- **AML Risk Scoring** (0-100 scale) for customers, vendors, transactions
- **Transaction Monitoring** for unusual patterns
- **SAR Generation** (Suspicious Activity Reports)
- **Jurisdiction Risk** assessment
- **Business Type Risk** classification

## Key Features

- ✅ **Risk Score Calculation** - 0-100 AML risk score with factor breakdown
- ✅ **Transaction Monitoring** - Detect unusual patterns (velocity, amounts, geography)
- ✅ **SAR Generation** - Automated suspicious activity reporting
- ✅ **Jurisdiction Risk** - Country-specific risk levels
- ✅ **Business Type Risk** - Industry-based risk classification
- ✅ **Risk Thresholds** - Configurable HIGH/MEDIUM/LOW thresholds
- ✅ **Framework-Agnostic** - Pure PHP 8.3+, works with any framework

## Installation

```bash
composer require nexus/aml-compliance
```

## Quick Start

### AML Risk Assessment

```php
use Nexus\AmlCompliance\Services\AmlRiskAssessor;
use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;

// Inject via constructor
public function __construct(
    private readonly AmlRiskAssessorInterface $amlAssessor
) {}

// Assess party risk
$riskScore = $this->amlAssessor->assessParty(
    partyId: 'party-12345'
);

// Get overall score (0-100)
$score = $riskScore->getScore(); // e.g., 75

// Get risk level (HIGH/MEDIUM/LOW)
$level = $riskScore->getRiskLevel(); // RiskLevel::HIGH

// Get risk factors breakdown
$factors = $riskScore->getFactors();
// [
//     'jurisdiction_risk' => 30,
//     'business_type_risk' => 20,
//     'sanctions_match' => 25,
//     'transaction_patterns' => 0
// ]
```

### Transaction Monitoring

```php
use Nexus\AmlCompliance\Services\TransactionMonitor;
use Nexus\AmlCompliance\Contracts\TransactionMonitorInterface;

public function __construct(
    private readonly TransactionMonitorInterface $transactionMonitor
) {}

// Monitor transaction
$result = $this->transactionMonitor->monitorTransaction(
    transactionId: 'tx-67890',
    amount: Money::of(50000, 'USD'),
    fromPartyId: 'party-12345',
    toPartyId: 'party-67890',
    transactionDate: new \DateTimeImmutable()
);

if ($result->isSuspicious()) {
    $suspicionReasons = $result->getReasons();
    // ['velocity_anomaly', 'amount_threshold_exceeded', 'high_risk_jurisdiction']
}
```

### SAR Generation

```php
use Nexus\AmlCompliance\Services\SarGenerator;
use Nexus\AmlCompliance\Contracts\SarGeneratorInterface;

public function __construct(
    private readonly SarGeneratorInterface $sarGenerator
) {}

// Generate SAR
$sar = $this->sarGenerator->generateSar(
    partyId: 'party-12345',
    reason: 'Unusual transaction patterns detected',
    suspiciousActivities: [
        'Multiple transactions just below $10,000 threshold',
        'Transactions with high-risk jurisdictions',
    ],
    amount: Money::of(45000, 'USD')
);

// SAR includes: SAR ID, party details, activity description, compliance officer assignment
```

## Architecture

### Atomic Package Compliance

This package adheres to **ARCHITECTURE.md atomicity principles**:

- **Domain-Specific**: ONE domain - AML risk assessment & transaction monitoring
- **Stateless**: No in-memory state, all data externalized via repositories
- **Framework-Agnostic**: Pure PHP 8.3+, zero framework coupling
- **Logic-Focused**: Business rules only, no migrations/controllers
- **Contract-Driven**: All dependencies injected as interfaces
- **Independently Deployable**: Published to Packagist independently

### Package Structure

```
packages/AmlCompliance/
├── composer.json
├── README.md
├── REQUIREMENTS.md
├── LICENSE
├── .gitignore
└── src/
    ├── Contracts/           # Interfaces
    │   ├── AmlRiskAssessorInterface.php
    │   ├── TransactionMonitorInterface.php
    │   ├── SarGeneratorInterface.php
    │   └── AmlRepositoryInterface.php
    ├── Services/            # Business logic
    │   ├── AmlRiskAssessor.php
    │   ├── TransactionMonitor.php
    │   └── SarGenerator.php
    ├── ValueObjects/        # Immutable domain objects
    │   ├── AmlRiskScore.php
    │   ├── RiskFactors.php
    │   ├── TransactionMonitoringResult.php
    │   └── SuspiciousActivityReport.php
    ├── Enums/               # Status enums
    │   ├── RiskLevel.php
    │   ├── JurisdictionRisk.php
    │   └── BusinessTypeRisk.php
    └── Exceptions/          # Domain exceptions
        ├── AmlException.php
        └── RiskAssessmentFailedException.php
```

## Key Interfaces

### AmlRiskAssessorInterface

```php
interface AmlRiskAssessorInterface
{
    /**
     * Assess AML risk for a party
     * 
     * @return AmlRiskScore Risk score (0-100) with factor breakdown
     */
    public function assessParty(string $partyId): AmlRiskScore;
    
    /**
     * Reassess risk for all parties above threshold
     */
    public function reassessHighRiskParties(int $threshold = 70): array;
}
```

### TransactionMonitorInterface

```php
interface TransactionMonitorInterface
{
    /**
     * Monitor transaction for suspicious patterns
     */
    public function monitorTransaction(
        string $transactionId,
        Money $amount,
        string $fromPartyId,
        string $toPartyId,
        \DateTimeImmutable $transactionDate
    ): TransactionMonitoringResult;
}
```

### SarGeneratorInterface

```php
interface SarGeneratorInterface
{
    /**
     * Generate Suspicious Activity Report
     */
    public function generateSar(
        string $partyId,
        string $reason,
        array $suspiciousActivities,
        Money $amount
    ): SuspiciousActivityReport;
}
```

## Risk Scoring Formula

### Overall AML Risk Score (0-100)

```
AML Risk Score = 
    (Jurisdiction Risk × 0.30) +
    (Business Type Risk × 0.20) +
    (Sanctions Match × 0.25) +
    (Transaction Patterns × 0.25)
```

### Risk Factors

| Factor | Weight | Description |
|--------|--------|-------------|
| **Jurisdiction Risk** | 30% | Country risk level (high-risk jurisdictions) |
| **Business Type Risk** | 20% | Industry risk (MSB, cryptocurrency, gambling) |
| **Sanctions Match** | 25% | Sanctions/PEP screening results |
| **Transaction Patterns** | 25% | Unusual transaction patterns (velocity, structuring) |

### Risk Level Thresholds

| Score Range | Risk Level | Action Required |
|-------------|------------|-----------------|
| 0-39 | LOW | Standard monitoring |
| 40-69 | MEDIUM | Enhanced due diligence |
| 70-100 | HIGH | SAR filing, account freeze |

## Dependencies

- **nexus/party** - Party identity management
- **nexus/sanctions** - Sanctions screening results for risk scoring
- **psr/log** - PSR-3 logging interface

## Testing

Run unit tests:

```bash
composer test
```

## Integration Example (Laravel)

```php
// app/Providers/AmlServiceProvider.php
use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;
use App\Repositories\Aml\EloquentAmlRepository;

$this->app->singleton(AmlRiskAssessorInterface::class, function ($app) {
    return new AmlRiskAssessor(
        repository: new EloquentAmlRepository(),
        sanctionsScreener: $app->make(SanctionsScreenerInterface::class),
        logger: $app->make(LoggerInterface::class)
    );
});
```

## Use Cases

### Financial Services
- Customer risk assessment during onboarding
- Transaction monitoring for unusual patterns
- SAR filing automation
- Enhanced due diligence triggers

### Cryptocurrency Exchanges
- High-risk jurisdiction detection
- Structuring detection (transactions just below reporting thresholds)
- Velocity anomaly detection

### Money Service Businesses (MSBs)
- Continuous risk monitoring
- Automated SAR generation
- Compliance officer alerts

## Related Packages

- **nexus/sanctions** - Regulatory screening (used for risk scoring)
- **nexus/kyc-verification** - Identity verification
- **nexus/party-compliance** - Comprehensive party compliance orchestration

## License

MIT License. See LICENSE file for details.

## Support

- **Documentation**: [REQUIREMENTS.md](REQUIREMENTS.md)
- **Issues**: GitHub Issues
- **Email**: support@nexus-erp.com

---

**Last Updated**: December 16, 2025  
**Maintained By**: Nexus Compliance Team
