# Nexus\Sanctions

**Production-ready international sanctions screening and Politically Exposed Person (PEP) detection for financial compliance.**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![FATF Compliant](https://img.shields.io/badge/FATF-Compliant-success)](https://www.fatf-gafi.org/)

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
- [Usage Examples](#usage-examples)
  - [Sanctions Screening](#sanctions-screening)
  - [PEP Detection](#pep-detection)
  - [Periodic Re-Screening](#periodic-re-screening)
- [Available Interfaces](#available-interfaces)
- [Enums Reference](#enums-reference)
- [Value Objects](#value-objects)
- [Exception Handling](#exception-handling)
- [Integration Guide](#integration-guide)
- [Architecture](#architecture)
- [License](#license)

---

## Overview

Nexus\Sanctions is a truly atomic, framework-agnostic PHP package that provides enterprise-grade sanctions screening and PEP detection capabilities. Designed for financial institutions, compliance teams, and ERP systems, it implements FATF (Financial Action Task Force) guidelines and OFAC requirements.

---

## Features

### Sanctions Screening
- ✅ **Multi-list screening** - OFAC, UN, EU, UK HMT, AU DFAT, CA OSFI, JP METI, CH SECO
- ✅ **Advanced fuzzy matching** - Levenshtein distance, Soundex, Metaphone, token-based
- ✅ **Configurable thresholds** - Similarity threshold tuning (default: 85%)
- ✅ **Batch processing** - Screen multiple parties efficiently
- ✅ **Alias support** - Screen against known aliases

### PEP Detection
- ✅ **FATF-compliant** - Implements Financial Action Task Force guidelines
- ✅ **Risk-level classification** - HIGH, MEDIUM, LOW, NONE
- ✅ **Family & associates** - Identify related persons with PEP exposure
- ✅ **Former PEP detection** - 40% risk reduction after 12 months rule
- ✅ **Enhanced Due Diligence** - Automatic EDD requirement determination

### Periodic Re-Screening
- ✅ **Risk-based frequency** - REAL_TIME, DAILY, WEEKLY, MONTHLY, QUARTERLY, SEMI_ANNUAL, ANNUAL
- ✅ **Batch execution** - Process scheduled screenings efficiently
- ✅ **Schedule management** - Update, cancel, and query schedules
- ✅ **Retry logic** - Exponential backoff for failed screenings

### Architecture
- ✅ **Framework agnostic** - Pure PHP 8.3+, works with Laravel, Symfony, or any framework
- ✅ **Interface-based** - Define contracts, consumers provide implementations
- ✅ **Zero circular dependencies** - Only depends on `nexus/common` and PSR interfaces
- ✅ **Immutable value objects** - Type-safe, validated domain objects
- ✅ **Comprehensive logging** - PSR-3 compatible logging

---

## Installation

```bash
composer require nexus/sanctions
```

**Requirements:**
- PHP 8.3 or higher
- `nexus/common` ^1.0
- `psr/log` ^3.0

---

## Quick Start

```php
use Nexus\Sanctions\Services\SanctionsScreener;
use Nexus\Sanctions\Enums\SanctionsList;

// Create screener with your repository implementation
$screener = new SanctionsScreener($sanctionsRepository);

// Screen a party against multiple sanctions lists
$result = $screener->screen(
    party: $party,
    lists: [SanctionsList::OFAC, SanctionsList::UN, SanctionsList::EU],
    options: ['similarity_threshold' => 0.85]
);

// Check results
if ($result->requiresBlocking) {
    // Block transaction immediately
}

if ($result->requiresReview) {
    // Queue for compliance review
}
```

---

## Core Concepts

### Sanctions Lists

The package supports 8 major international sanctions lists:

| List | Authority | Description |
|------|-----------|-------------|
| `OFAC` | US Treasury | Specially Designated Nationals (SDN) List |
| `UN` | United Nations | Security Council Consolidated List |
| `EU` | European Union | Consolidated Financial Sanctions List |
| `UK_HMT` | UK Treasury | Office of Financial Sanctions (OFSI) |
| `AU_DFAT` | Australia | Department of Foreign Affairs and Trade |
| `CA_OSFI` | Canada | Office of the Superintendent of Financial Institutions |
| `JP_METI` | Japan | Ministry of Economy, Trade and Industry |
| `CH_SECO` | Switzerland | State Secretariat for Economic Affairs |

### Match Strength

Matches are classified by confidence level:

| Strength | Similarity | Action |
|----------|------------|--------|
| `EXACT` | 100% | Block transaction immediately |
| `HIGH` | 85-99% | Requires immediate compliance review |
| `MEDIUM` | 70-84% | Requires thorough investigation |
| `LOW` | 50-69% | Manual verification recommended |
| `NONE` | <50% | No action required |

### PEP Risk Levels

Based on FATF guidelines:

| Level | Description | EDD Required |
|-------|-------------|--------------|
| `HIGH` | Heads of state, senior politicians, military officials | Yes + Senior Management Approval |
| `MEDIUM` | Mid-level government officials, regional figures | Yes |
| `LOW` | Former PEPs (>12 months), honorary positions | Standard DD may suffice |
| `NONE` | No political exposure | Standard CDD applies |

---

## Usage Examples

### Sanctions Screening

#### Basic Screening

```php
use Nexus\Sanctions\Services\SanctionsScreener;
use Nexus\Sanctions\Enums\SanctionsList;

$screener = new SanctionsScreener($repository, $logger);

// Screen against all major lists
$result = $screener->screen(
    party: $party,
    lists: [
        SanctionsList::OFAC,
        SanctionsList::UN,
        SanctionsList::EU,
        SanctionsList::UK_HMT,
    ],
    options: [
        'similarity_threshold' => 0.85,
        'phonetic_matching' => true,
        'token_based' => true,
        'include_aliases' => true,
    ]
);

// Analyze results
echo "Matches found: " . $result->getMatchesCount() . "\n";
echo "Risk level: " . $result->overallRiskLevel . "\n";
echo "Processing time: " . $result->processingTimeMs . "ms\n";

if ($result->requiresBlocking) {
    throw new TransactionBlockedException('Sanctions match detected');
}
```

#### Batch Screening

```php
// Screen multiple parties efficiently
$results = $screener->screenMultiple(
    parties: [$party1, $party2, $party3],
    lists: [SanctionsList::OFAC, SanctionsList::UN],
    options: ['similarity_threshold' => 0.80]
);

foreach ($results as $partyId => $result) {
    if ($result->hasMatches) {
        $this->notifyCompliance($partyId, $result);
    }
}
```

#### Screen Single Name

```php
// Screen a single name against specific list
$matches = $screener->screenName(
    name: 'John Smith',
    list: SanctionsList::OFAC,
    options: ['threshold' => 0.85]
);

foreach ($matches as $match) {
    echo "Match: {$match->matchedName}\n";
    echo "Score: {$match->similarityScore}%\n";
    echo "List: {$match->list->getName()}\n";
    echo "Action: {$match->matchStrength->getRecommendedAction()}\n";
}
```

#### Calculate Similarity

```php
// Calculate similarity between two names
$similarity = $screener->calculateSimilarity(
    name1: 'Mohammed Ali',
    name2: 'Muhammad Ali'
);

echo "Similarity: " . ($similarity * 100) . "%\n"; // ~92%
```

### PEP Detection

#### Basic PEP Screening

```php
use Nexus\Sanctions\Services\PepScreener;
use Nexus\Sanctions\Enums\PepLevel;

$pepScreener = new PepScreener($repository, $logger);

// Screen for PEP status
$pepProfiles = $pepScreener->screenForPep(
    party: $party,
    options: [
        'include_family' => true,
        'include_associates' => true,
        'include_former' => true,
        'min_risk_level' => 'low',
    ]
);

foreach ($pepProfiles as $profile) {
    echo "PEP: {$profile->name}\n";
    echo "Position: {$profile->position}\n";
    echo "Country: {$profile->country}\n";
    echo "Level: {$profile->level->value}\n";
    echo "Active: " . ($profile->isActive() ? 'Yes' : 'No') . "\n";
    echo "Risk Score: {$profile->getRiskScore()}\n";
}
```

#### Assess Risk Level

```php
// Determine overall PEP risk level
$riskLevel = $pepScreener->assessRiskLevel($party, $pepProfiles);

echo "Risk Level: {$riskLevel->value}\n";

// Get EDD requirements
$eddRequirements = $riskLevel->getEddRequirements();
if ($eddRequirements['senior_management_approval']) {
    $this->requestSeniorApproval($party);
}
```

#### Check EDD Requirement

```php
// Check if Enhanced Due Diligence is required
if ($pepScreener->requiresEdd($party, $pepProfiles)) {
    $this->initiateEddWorkflow($party, [
        'source_of_wealth' => true,
        'source_of_funds' => true,
        'ongoing_monitoring' => 'monthly',
    ]);
}
```

#### Check Related Persons

```php
// Find family members and close associates
$relatedProfiles = $pepScreener->checkRelatedPersons($party);

foreach ($relatedProfiles as $related) {
    echo "Related PEP: {$related->name}\n";
    echo "Relationship: " . ($related->additionalInfo['relationship'] ?? 'Unknown') . "\n";
}
```

### Periodic Re-Screening

#### Schedule Screening

```php
use Nexus\Sanctions\Services\PeriodicScreeningManager;
use Nexus\Sanctions\Enums\ScreeningFrequency;

$manager = new PeriodicScreeningManager($screener, $logger);

// Schedule based on risk level
$schedule = $manager->scheduleScreening(
    partyId: 'party-123',
    frequency: ScreeningFrequency::MONTHLY,
    options: [
        'lists' => [SanctionsList::OFAC, SanctionsList::UN],
        'start_date' => new DateTimeImmutable(),
        'metadata' => ['risk_tier' => 'medium'],
    ]
);

echo "Next screening: " . $schedule['next_screening_date']->format('Y-m-d') . "\n";
```

#### Execute Scheduled Screenings

```php
// Execute all due screenings
$summary = $manager->executeScheduledScreenings(
    asOfDate: new DateTimeImmutable(),
    options: [
        'batch_size' => 100,
        'continue_on_error' => true,
    ]
);

echo "Executed: {$summary['total_executed']}\n";
echo "Successful: {$summary['successful']}\n";
echo "Failed: {$summary['failed']}\n";
echo "Matches found: {$summary['total_matches']}\n";
```

#### Update Frequency

```php
// Increase frequency for higher risk party
$manager->updateScreeningFrequency(
    partyId: 'party-123',
    newFrequency: ScreeningFrequency::WEEKLY
);

// Cancel screening when relationship ends
$manager->cancelScheduledScreening('party-123');
```

---

## Available Interfaces

### SanctionsScreenerInterface

Primary interface for sanctions screening operations.

```php
interface SanctionsScreenerInterface
{
    public function screen(
        PartyInterface $party,
        array $lists,
        array $options = []
    ): ScreeningResult;
    
    public function screenMultiple(
        array $parties,
        array $lists,
        array $options = []
    ): array;
    
    public function screenName(
        string $name,
        SanctionsList $list,
        array $options = []
    ): array;
    
    public function calculateSimilarity(string $name1, string $name2): float;
}
```

### PepScreenerInterface

Interface for PEP detection and risk assessment.

```php
interface PepScreenerInterface
{
    public function screenForPep(
        PartyInterface $party,
        array $options = []
    ): array;
    
    public function checkRelatedPersons(PartyInterface $party): array;
    
    public function assessRiskLevel(
        PartyInterface $party,
        array $pepProfiles
    ): PepLevel;
    
    public function requiresEdd(
        PartyInterface $party,
        array $pepProfiles
    ): bool;
}
```

### PeriodicScreeningManagerInterface

Interface for managing periodic re-screening schedules.

```php
interface PeriodicScreeningManagerInterface
{
    public function scheduleScreening(
        string $partyId,
        ScreeningFrequency $frequency,
        array $options = []
    ): array;
    
    public function executeScheduledScreenings(
        DateTimeImmutable $asOfDate,
        array $options = []
    ): array;
    
    public function updateScreeningFrequency(
        string $partyId,
        ScreeningFrequency $newFrequency
    ): void;
    
    public function cancelScheduledScreening(string $partyId): void;
}
```

### SanctionsRepositoryInterface

Interface for data access - must be implemented by consuming application.

```php
interface SanctionsRepositoryInterface
{
    public function findByName(
        string $name,
        SanctionsList $list,
        float $similarityThreshold = 0.85
    ): array;
    
    public function findById(string $entryId, SanctionsList $list): ?array;
    
    public function findPepByName(
        string $name,
        float $similarityThreshold = 0.85
    ): array;
    
    public function findPepById(string $pepId): ?array;
    
    public function getRelatedPersons(string $pepId): array;
    
    public function isListAvailable(SanctionsList $list): bool;
}
```

### PartyInterface

Interface for party data required for screening.

```php
interface PartyInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getType(): string; // 'INDIVIDUAL' or 'ORGANIZATION'
    public function getDateOfBirth(): ?DateTimeImmutable;
    public function getNationality(): ?string;
    public function getCountryOfIncorporation(): ?string;
    public function getAliases(): array;
    public function getIdentificationDocuments(): array;
}
```

---

## Enums Reference

### SanctionsList

```php
enum SanctionsList: string
{
    case OFAC = 'ofac';          // US Treasury
    case UN = 'un';              // United Nations
    case EU = 'eu';              // European Union
    case UK_HMT = 'uk_hmt';      // UK Treasury
    case AU_DFAT = 'au_dfat';    // Australia
    case CA_OSFI = 'ca_osfi';    // Canada
    case JP_METI = 'jp_meti';    // Japan
    case CH_SECO = 'ch_seco';    // Switzerland
    
    public function getName(): string;
    public function getAuthority(): string;
}
```

### MatchStrength

```php
enum MatchStrength: string
{
    case EXACT = 'exact';    // 100% match
    case HIGH = 'high';      // 85-99% match
    case MEDIUM = 'medium';  // 70-84% match
    case LOW = 'low';        // 50-69% match
    case NONE = 'none';      // <50% match
    
    public function getSimilarityRange(): array;
    public function getRecommendedAction(): string;
    public function requiresBlocking(): bool;
    public function requiresReview(): bool;
    
    public static function fromSimilarityScore(float $score): self;
}
```

### PepLevel

```php
enum PepLevel: string
{
    case HIGH = 'high';      // Senior officials, heads of state
    case MEDIUM = 'medium';  // Mid-level officials
    case LOW = 'low';        // Former PEPs, honorary positions
    case NONE = 'none';      // Not a PEP
    
    public function getEddRequirements(): array;
    public function getRiskScore(): int;
    public function requiresEdd(): bool;
    public function requiresSeniorApproval(): bool;
    public function getMonitoringFrequencyDays(): int;
}
```

### ScreeningFrequency

```php
enum ScreeningFrequency: string
{
    case REAL_TIME = 'real_time';     // Every transaction
    case DAILY = 'daily';              // Every day
    case WEEKLY = 'weekly';            // Every 7 days
    case MONTHLY = 'monthly';          // Every 30 days
    case QUARTERLY = 'quarterly';      // Every 90 days
    case SEMI_ANNUAL = 'semi_annual';  // Every 180 days
    case ANNUAL = 'annual';            // Every 365 days
    
    public function getDays(): int;
    public function getHours(): int;
    public function calculateNextScreeningDate(DateTimeImmutable $from): DateTimeImmutable;
}
```

---

## Value Objects

### ScreeningResult

Complete result of a sanctions screening operation.

```php
final class ScreeningResult
{
    public readonly string $screeningId;
    public readonly string $partyId;
    public readonly string $partyName;
    public readonly string $partyType;
    public readonly bool $hasMatches;
    public readonly array $matches;          // SanctionsMatch[]
    public readonly array $pepProfiles;      // PepProfile[]
    public readonly bool $requiresBlocking;
    public readonly bool $requiresReview;
    public readonly string $overallRiskLevel; // CRITICAL, HIGH, MEDIUM, LOW, NONE
    public readonly array $metadata;
    public readonly DateTimeImmutable $screenedAt;
    public readonly float $processingTimeMs;
    
    // Helper methods
    public function getMatchesCount(): int;
    public function getPepCount(): int;
    public function isClean(): bool;
    public function hasPepMatches(): bool;
    public function getHighestMatchStrength(): ?string;
    
    public static function clean(...): self;  // Factory for clean results
}
```

### SanctionsMatch

A single match from a sanctions list.

```php
final class SanctionsMatch
{
    public readonly string $listEntryId;
    public readonly SanctionsList $list;
    public readonly string $matchedName;
    public readonly MatchStrength $matchStrength;
    public readonly float $similarityScore;  // 0-100
    public readonly array $additionalInfo;
    public readonly DateTimeImmutable $matchedAt;
    
    // Accessors for additional info
    public function getAliases(): array;
    public function getDateOfBirth(): ?DateTimeImmutable;
    public function getNationality(): ?string;
    public function getPassportNumber(): ?string;
    public function getNationalId(): ?string;
    public function getAddress(): ?string;
    public function getProgram(): ?string;
    public function getRemarks(): ?string;
    
    public function requiresBlocking(): bool;
    public function requiresReview(): bool;
}
```

### PepProfile

A PEP (Politically Exposed Person) profile.

```php
final class PepProfile
{
    public readonly string $pepId;
    public readonly string $name;
    public readonly PepLevel $level;
    public readonly string $position;
    public readonly string $country;
    public readonly ?string $organization;
    public readonly ?DateTimeImmutable $startDate;
    public readonly ?DateTimeImmutable $endDate;
    public readonly array $relatedPersons;
    public readonly array $additionalInfo;
    public readonly DateTimeImmutable $identifiedAt;
    
    public function isActive(): bool;
    public function isFormer(): bool;  // Left position >12 months ago
    public function requiresEdd(): bool;
    public function requiresSeniorApproval(): bool;
    public function getRiskScore(): int;  // 40% reduction for former PEPs
    public function getEddRequirements(): array;
    public function getMonitoringFrequencyDays(): int;
    public function getTenureDays(): ?int;
    public function getYearsSinceEnd(): ?float;
}
```

---

## Exception Handling

### Exception Hierarchy

```
SanctionsException (base)
├── InvalidPartyException
├── ScreeningFailedException
└── SanctionsListUnavailableException
```

### InvalidPartyException

Thrown when party data is invalid for screening.

```php
use Nexus\Sanctions\Exceptions\InvalidPartyException;

try {
    $screener->screen($party, $lists);
} catch (InvalidPartyException $e) {
    $errors = $e->getValidationErrors();
    $required = $e->getRequiredFields();
    $this->logger->warning('Invalid party data', [
        'errors' => $errors,
        'required' => $required,
    ]);
}
```

Factory methods:
- `missingRequiredFields(string $partyId, array $missingFields)`
- `invalidName(string $partyId, string $name, string $reason)`
- `invalidDateOfBirth(string $partyId, string $dob, string $reason)`
- `invalidNationality(string $partyId, string $nationality)`

### ScreeningFailedException

Thrown when screening operation fails.

```php
use Nexus\Sanctions\Exceptions\ScreeningFailedException;

try {
    $screener->screen($party, $lists);
} catch (ScreeningFailedException $e) {
    $this->logger->error('Screening failed', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);
}
```

Factory methods:
- `apiTimeout(string $partyId, SanctionsList $list, int $timeoutSeconds)`
- `networkError(string $partyId, SanctionsList $list, string $error)`
- `invalidListData(SanctionsList $list, string $reason)`
- `screeningFailed(string $partyId, string $reason)`

### SanctionsListUnavailableException

Thrown when a sanctions list is unavailable.

```php
use Nexus\Sanctions\Exceptions\SanctionsListUnavailableException;

try {
    $screener->screen($party, [SanctionsList::OFAC]);
} catch (SanctionsListUnavailableException $e) {
    $list = $e->getSanctionsList();
    $this->alertOperations("List {$list->value} unavailable");
}
```

---

## Integration Guide

### 1. Implement SanctionsRepositoryInterface

```php
namespace App\Repositories;

use Nexus\Sanctions\Contracts\SanctionsRepositoryInterface;
use Nexus\Sanctions\Enums\SanctionsList;

final readonly class EloquentSanctionsRepository implements SanctionsRepositoryInterface
{
    public function findByName(
        string $name,
        SanctionsList $list,
        float $similarityThreshold = 0.85
    ): array {
        // Query your database or external API
        return SanctionsEntry::query()
            ->where('list', $list->value)
            ->whereRaw('SIMILARITY(name, ?) > ?', [$name, $similarityThreshold])
            ->get()
            ->toArray();
    }
    
    public function findPepByName(
        string $name,
        float $similarityThreshold = 0.85
    ): array {
        // Query PEP database
        return PepEntry::query()
            ->whereRaw('SIMILARITY(name, ?) > ?', [$name, $similarityThreshold])
            ->get()
            ->toArray();
    }
    
    public function isListAvailable(SanctionsList $list): bool
    {
        return Cache::remember(
            "sanctions_list_{$list->value}_available",
            3600,
            fn() => $this->checkListAvailability($list)
        );
    }
    
    // ... implement other methods
}
```

### 2. Implement PartyInterface Adapter

```php
namespace App\Adapters;

use Nexus\Sanctions\Contracts\PartyInterface;
use App\Models\Customer;

final readonly class CustomerPartyAdapter implements PartyInterface
{
    public function __construct(
        private Customer $customer
    ) {}
    
    public function getId(): string
    {
        return $this->customer->id;
    }
    
    public function getName(): string
    {
        return $this->customer->legal_name ?? $this->customer->name;
    }
    
    public function getType(): string
    {
        return $this->customer->is_company ? 'ORGANIZATION' : 'INDIVIDUAL';
    }
    
    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->customer->date_of_birth 
            ? new \DateTimeImmutable($this->customer->date_of_birth) 
            : null;
    }
    
    public function getNationality(): ?string
    {
        return $this->customer->nationality;
    }
    
    public function getAliases(): array
    {
        return $this->customer->aliases ?? [];
    }
    
    // ... implement other methods
}
```

### 3. Register in Service Container (Laravel)

```php
// AppServiceProvider.php
use Nexus\Sanctions\Contracts\SanctionsRepositoryInterface;
use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;
use Nexus\Sanctions\Contracts\PepScreenerInterface;
use Nexus\Sanctions\Services\SanctionsScreener;
use Nexus\Sanctions\Services\PepScreener;

public function register(): void
{
    $this->app->singleton(
        SanctionsRepositoryInterface::class,
        EloquentSanctionsRepository::class
    );
    
    $this->app->singleton(SanctionsScreenerInterface::class, function ($app) {
        return new SanctionsScreener(
            $app->make(SanctionsRepositoryInterface::class),
            $app->make(LoggerInterface::class)
        );
    });
    
    $this->app->singleton(PepScreenerInterface::class, function ($app) {
        return new PepScreener(
            $app->make(SanctionsRepositoryInterface::class),
            $app->make(LoggerInterface::class)
        );
    });
}
```

### 4. Use in Application Services

```php
namespace App\Services;

use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;
use Nexus\Sanctions\Contracts\PepScreenerInterface;
use Nexus\Sanctions\Enums\SanctionsList;

final readonly class CustomerOnboardingService
{
    public function __construct(
        private SanctionsScreenerInterface $sanctionsScreener,
        private PepScreenerInterface $pepScreener,
    ) {}
    
    public function screenCustomer(Customer $customer): OnboardingResult
    {
        $party = new CustomerPartyAdapter($customer);
        
        // Screen against sanctions lists
        $sanctionsResult = $this->sanctionsScreener->screen(
            party: $party,
            lists: [SanctionsList::OFAC, SanctionsList::UN, SanctionsList::EU],
        );
        
        if ($sanctionsResult->requiresBlocking) {
            return OnboardingResult::blocked('Sanctions match detected');
        }
        
        // Screen for PEP status
        $pepProfiles = $this->pepScreener->screenForPep($party);
        
        if ($this->pepScreener->requiresEdd($party, $pepProfiles)) {
            return OnboardingResult::requiresEdd($pepProfiles);
        }
        
        return OnboardingResult::approved();
    }
}
```

---

## Architecture

### Package Structure

```
packages/Sanctions/
├── src/
│   ├── Contracts/
│   │   ├── PartyInterface.php
│   │   ├── PepScreenerInterface.php
│   │   ├── PeriodicScreeningManagerInterface.php
│   │   ├── SanctionsRepositoryInterface.php
│   │   └── SanctionsScreenerInterface.php
│   ├── Enums/
│   │   ├── MatchStrength.php
│   │   ├── PepLevel.php
│   │   ├── SanctionsList.php
│   │   └── ScreeningFrequency.php
│   ├── Exceptions/
│   │   ├── InvalidPartyException.php
│   │   ├── SanctionsException.php
│   │   ├── SanctionsListUnavailableException.php
│   │   └── ScreeningFailedException.php
│   ├── Services/
│   │   ├── PepScreener.php
│   │   ├── PeriodicScreeningManager.php
│   │   └── SanctionsScreener.php
│   └── ValueObjects/
│       ├── PepProfile.php
│       ├── SanctionsMatch.php
│       └── ScreeningResult.php
├── tests/
├── composer.json
└── README.md
```

### Atomic Architecture Principles

- **Zero Circular Dependencies**: Only depends on `nexus/common` and PSR interfaces
- **Interface-Based**: Package provides contracts, consuming applications implement
- **Independently Testable**: Can be unit tested without database or framework
- **Framework Agnostic**: Pure PHP 8.3+, works with any framework
- **Stateless Services**: All services are `final readonly` with no mutable state

---

## License

MIT License. See [LICENSE](LICENSE) for details.

---

**Maintained by:** Nexus Architecture Team  
**Last Updated:** December 2025
