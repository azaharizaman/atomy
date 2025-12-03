# Nexus\Disciplinary

Employee misconduct reporting, case management, warnings, sanctions, and policy enforcement.

## Features

- Disciplinary case management
- Evidence collection and tracking
- Policy violation detection
- Sanction decision engine
- Severity scoring and classification

## Installation

```bash
composer require nexus/disciplinary
```

## Usage

```php
use Nexus\Disciplinary\Contracts\DisciplinaryCaseRepositoryInterface;
use Nexus\Disciplinary\Contracts\SanctionDecisionEngineInterface;

// Inject via constructor
public function __construct(
    private readonly DisciplinaryCaseRepositoryInterface $caseRepository,
    private readonly SanctionDecisionEngineInterface $sanctionEngine
) {}
```

## Architecture

This package follows Clean Architecture principles:
- **Entities**: DisciplinaryCase, Evidence, Sanction
- **ValueObjects**: CaseNumber, OffenceType, SanctionLevel, CaseOutcome
- **Policies**: CodeOfConduct, Fraud, WorkplaceHarassment, SafetyViolation
- **Services**: SanctionDecisionEngine, CaseClassificationService, SeverityScoringService

## License

MIT
