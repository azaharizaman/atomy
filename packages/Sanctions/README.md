# Nexus\Sanctions

**Version:** 1.0.0  
**Status:** 🔵 In Development  
**Category:** Compliance & Governance

## Overview

`Nexus\Sanctions` is a framework-agnostic, atomic PHP package for regulatory screening against global sanctions lists and Politically Exposed Persons (PEP) databases. It provides enterprise-grade fuzzy matching capabilities for international name variations and comprehensive sanctions hit workflows.

## Purpose

Screen parties (customers, vendors, employees, beneficial owners) against:
- **OFAC** (Office of Foreign Assets Control - USA)
- **UN Security Council** consolidated list
- **EU Sanctions** list
- **UK HMT** (Her Majesty's Treasury) sanctions
- **PEP Databases** (Politically Exposed Persons)
- **RCA** (Relatives & Close Associates) screening

## Key Features

- ✅ **Multi-List Screening** - OFAC, UN, EU, UK HMT simultaneous screening
- ✅ **PEP Detection** - Identify politically exposed persons
- ✅ **Fuzzy Name Matching** - Handle international name variations
- ✅ **Periodic Re-screening** - Automated re-screening workflows
- ✅ **Hit Workflow Management** - Freeze, investigate, report workflows
- ✅ **Audit Trail** - Complete screening history
- ✅ **Framework-Agnostic** - Pure PHP 8.3+, works with any framework

## Installation

```bash
composer require nexus/sanctions
```

## Quick Start

### Basic Sanctions Screening

```php
use Nexus\Sanctions\Services\SanctionsScreener;
use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;

// Inject via constructor
public function __construct(
    private readonly SanctionsScreenerInterface $sanctionsScreener
) {}

// Screen a party
$result = $this->sanctionsScreener->screen(
    partyId: 'party-12345',
    name: 'John Smith',
    country: 'US',
    dateOfBirth: new \DateTimeImmutable('1980-01-15')
);

if ($result->isMatch()) {
    // Handle sanctions match
    $matchDetails = $result->getMatches();
    // Freeze account, notify compliance officer
}
```

### PEP Screening

```php
use Nexus\Sanctions\Services\PepScreener;
use Nexus\Sanctions\Contracts\PepScreenerInterface;

public function __construct(
    private readonly PepScreenerInterface $pepScreener
) {}

$pepResult = $this->pepScreener->screen(
    partyId: 'party-12345',
    name: 'John Smith',
    country: 'US'
);

if ($pepResult->isPep()) {
    $pepLevel = $pepResult->getPepLevel(); // HIGH, MEDIUM, LOW
    $positions = $pepResult->getPositions(); // Government positions held
}
```

### Periodic Re-screening

```php
use Nexus\Sanctions\Services\PeriodicScreeningManager;

public function __construct(
    private readonly PeriodicScreeningManagerInterface $screeningManager
) {}

// Schedule automatic re-screening
$this->screeningManager->scheduleReScreening(
    partyId: 'party-12345',
    frequency: ScreeningFrequency::MONTHLY
);
```

## Architecture

### Atomic Package Compliance

This package adheres to **ARCHITECTURE.md atomicity principles**:

- **Domain-Specific**: ONE domain - Regulatory screening (sanctions/PEP)
- **Stateless**: No in-memory state, all data externalized via repositories
- **Framework-Agnostic**: Pure PHP 8.3+, zero framework coupling
- **Logic-Focused**: Business rules only, no migrations/controllers
- **Contract-Driven**: All dependencies injected as interfaces
- **Independently Deployable**: Published to Packagist independently

### Package Structure

```
packages/Sanctions/
├── composer.json
├── README.md
├── REQUIREMENTS.md
├── LICENSE
├── .gitignore
└── src/
    ├── Contracts/           # Interfaces
    │   ├── SanctionsScreenerInterface.php
    │   ├── PepScreenerInterface.php
    │   ├── SanctionsRepositoryInterface.php
    │   └── PeriodicScreeningManagerInterface.php
    ├── Services/            # Business logic
    │   ├── SanctionsScreener.php
    │   ├── PepScreener.php
    │   └── PeriodicScreeningManager.php
    ├── ValueObjects/        # Immutable domain objects
    │   ├── ScreeningResult.php
    │   ├── SanctionsMatch.php
    │   └── PepProfile.php
    ├── Enums/               # Status enums
    │   ├── SanctionsList.php
    │   ├── MatchStrength.php
    │   └── PepLevel.php
    └── Exceptions/          # Domain exceptions
        ├── SanctionsException.php
        └── ScreeningFailedException.php
```

## Key Interfaces

### SanctionsScreenerInterface

```php
interface SanctionsScreenerInterface
{
    /**
     * Screen a party against all sanctions lists
     */
    public function screen(
        string $partyId,
        string $name,
        string $country,
        ?\DateTimeImmutable $dateOfBirth = null
    ): ScreeningResult;
    
    /**
     * Get screening history for a party
     */
    public function getScreeningHistory(string $partyId): array;
}
```

### PepScreenerInterface

```php
interface PepScreenerInterface
{
    /**
     * Screen for PEP status
     */
    public function screen(
        string $partyId,
        string $name,
        string $country
    ): PepResult;
}
```

## Dependencies

- **nexus/party** - Party identity management
- **nexus/audit-logger** - Screening audit trail
- **psr/log** - PSR-3 logging interface

## Testing

Run unit tests:

```bash
composer test
```

## Integration Example (Laravel)

```php
// app/Providers/SanctionsServiceProvider.php
use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;
use App\Services\Sanctions\EloquentSanctionsRepository;

$this->app->singleton(SanctionsScreenerInterface::class, function ($app) {
    return new SanctionsScreener(
        repository: new EloquentSanctionsRepository(),
        auditLogger: $app->make(AuditLogManagerInterface::class),
        logger: $app->make(LoggerInterface::class)
    );
});
```

## Use Cases

### Financial Services
- Screen customers before account opening
- Screen beneficiaries before wire transfers
- Periodic re-screening of existing customers

### Export/Trade
- Screen vendors before purchase orders
- Screen shipping destinations
- Verify end-users for controlled goods

### Professional Services
- Client onboarding screening
- Beneficial owner verification
- Ongoing monitoring for high-risk clients

## Related Packages

- **nexus/aml-compliance** - AML risk assessment
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
