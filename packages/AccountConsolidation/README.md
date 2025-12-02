# Nexus\AccountConsolidation

**Framework-Agnostic Financial Consolidation Engine**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\AccountConsolidation` is a pure PHP package that provides the core engine for consolidating financial statements from multiple entities (parent company and subsidiaries). It handles intercompany eliminations, currency translation, non-controlling interest (NCI) calculations, and ownership hierarchy resolution.

This package is **framework-agnostic** and contains no database access, no HTTP controllers, and no framework-specific code. Consuming applications provide entity data through injected interfaces.

## Installation

```bash
composer require nexus/account-consolidation
```

## Package Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Multi-Entity Consolidation** | Combine financial data from parent and subsidiaries |
| **Intercompany Eliminations** | Remove intercompany transactions and balances |
| **Currency Translation** | Translate foreign subsidiary financials |
| **NCI Calculations** | Calculate non-controlling interest allocations |
| **Ownership Resolution** | Determine consolidation method based on ownership % |
| **Goodwill Calculation** | Calculate goodwill from acquisitions |

## Key Concepts

### Consolidation Methods

| Method | Ownership | Treatment |
|--------|-----------|-----------|
| **Full Consolidation** | > 50% | 100% of assets/liabilities, NCI for minority |
| **Proportionate** | 20-50% (Joint Venture) | Pro-rata share of assets/liabilities |
| **Equity Method** | 20-50% (Associate) | Single-line investment + share of profit |
| **Cost Method** | < 20% | Investment at cost, dividends as income |

### Elimination Types

- **Intercompany Revenue/Expense** - Sales between group entities
- **Intercompany Receivables/Payables** - Balances between entities
- **Intercompany Dividends** - Dividend payments within group
- **Investment Elimination** - Parent's investment in subsidiary
- **Unrealized Profit** - Profit on intercompany inventory/assets

---

## Architecture

```
src/
├── Contracts/           # Interfaces defining the public API
├── ValueObjects/        # Immutable consolidation data structures
├── Enums/               # Consolidation types and methods
├── Services/            # Core consolidation logic
├── Rules/               # Elimination rule implementations
└── Exceptions/          # Domain-specific errors
```

---

## Contracts (Interfaces)

### Core Interfaces

#### `ConsolidationEngineInterface`

The main entry point for consolidation operations.

```php
interface ConsolidationEngineInterface
{
    /**
     * Consolidate financial statements from multiple entities
     *
     * @param string $parentEntityId The parent company ID
     * @param array<string> $subsidiaryIds List of subsidiary IDs to consolidate
     * @param string $periodId The reporting period
     * @param ConsolidationMethod $method The consolidation method
     * @return ConsolidationResult
     */
    public function consolidate(
        string $parentEntityId,
        array $subsidiaryIds,
        string $periodId,
        ConsolidationMethod $method = ConsolidationMethod::FULL
    ): ConsolidationResult;
    
    /**
     * Preview consolidation adjustments without applying
     */
    public function preview(
        string $parentEntityId,
        array $subsidiaryIds,
        string $periodId
    ): ConsolidationPreview;
}
```

#### `EliminationRuleInterface`

Contract for intercompany elimination rules.

```php
interface EliminationRuleInterface
{
    /**
     * Get the elimination type this rule handles
     */
    public function getType(): EliminationType;
    
    /**
     * Check if this rule applies to the given transaction
     */
    public function applies(IntercompanyBalance $balance): bool;
    
    /**
     * Generate elimination entries for the balance
     *
     * @return array<EliminationEntry>
     */
    public function eliminate(IntercompanyBalance $balance): array;
    
    /**
     * Get rule priority (lower = higher priority)
     */
    public function getPriority(): int;
}
```

#### `CurrencyTranslatorInterface`

Handles foreign currency translation.

```php
interface CurrencyTranslatorInterface
{
    /**
     * Translate entity financials to reporting currency
     *
     * @param ConsolidationEntity $entity The foreign entity
     * @param string $reportingCurrency Target currency code
     * @param TranslationMethod $method Translation method
     * @return TranslationAdjustment
     */
    public function translate(
        ConsolidationEntity $entity,
        string $reportingCurrency,
        TranslationMethod $method = TranslationMethod::CURRENT_RATE
    ): TranslationAdjustment;
    
    /**
     * Get cumulative translation adjustment for entity
     */
    public function getCumulativeTranslationAdjustment(
        string $entityId,
        string $reportingCurrency
    ): Money;
}
```

#### `NciCalculatorInterface`

Calculates non-controlling interest.

```php
interface NciCalculatorInterface
{
    /**
     * Calculate NCI allocation for a subsidiary
     *
     * @param ConsolidationEntity $subsidiary
     * @param OwnershipStructure $ownership
     * @return NciAllocation
     */
    public function calculate(
        ConsolidationEntity $subsidiary,
        OwnershipStructure $ownership
    ): NciAllocation;
    
    /**
     * Calculate NCI share of subsidiary profit/loss
     */
    public function calculateProfitShare(
        Money $subsidiaryNetIncome,
        float $nciPercentage
    ): Money;
}
```

#### `OwnershipResolverInterface`

Determines ownership percentages and consolidation requirements.

```php
interface OwnershipResolverInterface
{
    /**
     * Resolve ownership structure for an entity
     */
    public function resolve(
        string $parentEntityId,
        string $subsidiaryId
    ): OwnershipStructure;
    
    /**
     * Determine the appropriate consolidation method
     */
    public function determineConsolidationMethod(
        OwnershipStructure $ownership
    ): ConsolidationMethod;
    
    /**
     * Get effective ownership percentage (direct + indirect)
     */
    public function getEffectiveOwnership(
        string $parentEntityId,
        string $targetEntityId
    ): float;
    
    /**
     * Detect circular ownership
     */
    public function detectCircularOwnership(
        string $entityId,
        array $visitedEntities = []
    ): bool;
}
```

#### `ConsolidationDataProviderInterface`

Contract for consuming applications to provide entity data.

```php
interface ConsolidationDataProviderInterface
{
    /**
     * Get consolidation entity data
     */
    public function getEntity(string $entityId): ConsolidationEntity;
    
    /**
     * Get all intercompany balances for a period
     *
     * @return array<IntercompanyBalance>
     */
    public function getIntercompanyBalances(
        string $periodId,
        array $entityIds
    ): array;
    
    /**
     * Get investment balances
     */
    public function getInvestmentBalances(
        string $parentEntityId,
        string $periodId
    ): array;
}
```

---

## Value Objects

### `ConsolidationEntity`

```php
final readonly class ConsolidationEntity
{
    public function __construct(
        public string $entityId,
        public string $entityName,
        public string $functionalCurrency,
        public string $parentEntityId,
        public float $ownershipPercentage,
        public ControlType $controlType,
        public \DateTimeImmutable $acquisitionDate,
        public array $financialData = []
    ) {}
}
```

### `OwnershipStructure`

```php
final readonly class OwnershipStructure
{
    public function __construct(
        public string $parentEntityId,
        public string $subsidiaryEntityId,
        public float $directOwnership,
        public float $indirectOwnership,
        public float $effectiveOwnership,
        public ConsolidationMethod $recommendedMethod,
        public array $ownershipChain = []
    ) {}
    
    public function getNciPercentage(): float
    {
        return 100.0 - $this->effectiveOwnership;
    }
}
```

### `EliminationEntry`

```php
final readonly class EliminationEntry
{
    public function __construct(
        public string $id,
        public EliminationType $type,
        public string $debitAccountId,
        public string $creditAccountId,
        public Money $amount,
        public string $description,
        public string $relatedEntityId,
        public string $counterpartyEntityId
    ) {}
}
```

### `TranslationAdjustment`

```php
final readonly class TranslationAdjustment
{
    public function __construct(
        public string $entityId,
        public string $fromCurrency,
        public string $toCurrency,
        public Money $translationGainLoss,
        public float $averageRate,
        public float $closingRate,
        public float $historicalRate,
        public array $adjustedBalances = []
    ) {}
}
```

### `ConsolidationResult`

```php
final readonly class ConsolidationResult
{
    public function __construct(
        public string $consolidationId,
        public string $parentEntityId,
        public string $periodId,
        public array $consolidatedBalances,
        public array $eliminationEntries,
        public array $translationAdjustments,
        public Money $totalNci,
        public Money $goodwill,
        public int $eliminationsCount,
        public int $translationAdjustmentsCount,
        public \DateTimeImmutable $generatedAt
    ) {}
}
```

### `IntercompanyBalance`

```php
final readonly class IntercompanyBalance
{
    public function __construct(
        public string $fromEntityId,
        public string $toEntityId,
        public string $accountId,
        public EliminationType $type,
        public Money $amount,
        public string $transactionReference
    ) {}
}
```

---

## Enums

### `ConsolidationMethod`

```php
enum ConsolidationMethod: string
{
    case FULL = 'full';                    // > 50% ownership
    case PROPORTIONATE = 'proportionate';  // Joint ventures
    case EQUITY = 'equity';                // Associates (20-50%)
    case COST = 'cost';                    // < 20% ownership
}
```

### `EliminationType`

```php
enum EliminationType: string
{
    case INTERCOMPANY_REVENUE = 'intercompany_revenue';
    case INTERCOMPANY_RECEIVABLE = 'intercompany_receivable';
    case INTERCOMPANY_DIVIDEND = 'intercompany_dividend';
    case INVESTMENT_ELIMINATION = 'investment_elimination';
    case UNREALIZED_PROFIT = 'unrealized_profit';
}
```

### `TranslationMethod`

```php
enum TranslationMethod: string
{
    case CURRENT_RATE = 'current_rate';      // All at closing rate
    case TEMPORAL = 'temporal';               // Historical rates for non-monetary
    case MONETARY_NONMONETARY = 'monetary';   // Split by account type
}
```

### `ControlType`

```php
enum ControlType: string
{
    case SUBSIDIARY = 'subsidiary';           // Controlled entity
    case ASSOCIATE = 'associate';             // Significant influence
    case JOINT_VENTURE = 'joint_venture';     // Joint control
    case INVESTMENT = 'investment';           // No significant influence
}
```

---

## Services

### `ConsolidationCalculator`

Orchestrates the consolidation process:
1. Collect entity financial data
2. Determine consolidation method per entity
3. Apply currency translation
4. Execute elimination rules
5. Calculate NCI allocations
6. Produce consolidated result

### `IntercompanyEliminator`

Identifies and eliminates intercompany transactions:
- Matches receivables with payables
- Eliminates revenue/expense
- Handles partial matching
- Tracks elimination mismatches

### `CurrencyTranslator`

Translates foreign currency financials:
- Current rate method (IFRS default)
- Temporal method
- Calculates translation gain/loss
- Maintains cumulative translation adjustment (CTA)

### `NciCalculator`

Calculates non-controlling interest:
- NCI at acquisition
- NCI share of profits
- NCI in net assets
- Changes in NCI without loss of control

### `OwnershipResolver`

Resolves complex ownership structures:
- Direct ownership
- Indirect ownership (chains)
- Cross-holdings
- Circular ownership detection

### `GoodwillCalculator`

Calculates acquisition goodwill:
- Consideration paid
- Less: Fair value of net assets acquired
- Plus: NCI at fair value
- Equals: Goodwill

### `MinorityInterestAdjuster`

Adjusts for minority interests in multi-tier structures.

---

## Rules

### Elimination Rule Implementations

| Rule | Description |
|------|-------------|
| `IntercompanyRevenueRule` | Eliminates sales between group entities |
| `IntercompanyReceivableRule` | Eliminates A/R and A/P between entities |
| `IntercompanyDividendRule` | Eliminates intra-group dividends |
| `InvestmentEliminationRule` | Eliminates parent investment vs subsidiary equity |
| `UnrealizedProfitRule` | Eliminates unrealized profit in inventory |

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `ConsolidationException` | General consolidation failure |
| `CircularOwnershipException` | Circular ownership detected in structure |
| `InvalidOwnershipException` | Ownership percentage invalid (< 0 or > 100) |
| `CurrencyTranslationException` | Currency translation fails |
| `EliminationException` | Elimination entry cannot be created |

---

## Usage Example

```php
use Nexus\AccountConsolidation\Contracts\ConsolidationEngineInterface;
use Nexus\AccountConsolidation\Contracts\OwnershipResolverInterface;
use Nexus\AccountConsolidation\Enums\ConsolidationMethod;

final readonly class GroupConsolidationService
{
    public function __construct(
        private ConsolidationEngineInterface $engine,
        private OwnershipResolverInterface $ownershipResolver
    ) {}
    
    public function consolidateGroup(
        string $parentEntityId,
        array $subsidiaryIds,
        string $periodId
    ): ConsolidationResult {
        // Verify no circular ownership
        foreach ($subsidiaryIds as $subId) {
            if ($this->ownershipResolver->detectCircularOwnership($subId)) {
                throw new CircularOwnershipException($subId);
            }
        }
        
        // Perform consolidation
        return $this->engine->consolidate(
            parentEntityId: $parentEntityId,
            subsidiaryIds: $subsidiaryIds,
            periodId: $periodId,
            method: ConsolidationMethod::FULL
        );
    }
}
```

---

## Integration with Other Packages

| Package | Integration |
|---------|-------------|
| `Nexus\Finance` | Provides GL balances for each entity |
| `Nexus\Currency` | Provides exchange rates for translation |
| `Nexus\FinancialStatements` | Generates consolidated statements |
| `Nexus\Backoffice` | Provides entity/company structure |
| `Nexus\AuditLogger` | Logs consolidation events |

---

## Consolidation Process Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    CONSOLIDATION PROCESS                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. OWNERSHIP RESOLUTION                                     │
│     ├── Determine ownership %                                │
│     ├── Check for circular ownership                         │
│     └── Select consolidation method                          │
│                                                              │
│  2. CURRENCY TRANSLATION (if needed)                         │
│     ├── Translate foreign subsidiary financials              │
│     └── Calculate translation adjustments                    │
│                                                              │
│  3. INTERCOMPANY ELIMINATIONS                                │
│     ├── Identify intercompany balances                       │
│     ├── Apply elimination rules                              │
│     └── Generate elimination entries                         │
│                                                              │
│  4. NCI CALCULATIONS                                         │
│     ├── Calculate NCI in net assets                          │
│     └── Allocate NCI share of profit/loss                    │
│                                                              │
│  5. GOODWILL & INVESTMENT ELIMINATION                        │
│     ├── Eliminate parent investment                          │
│     ├── Eliminate subsidiary equity                          │
│     └── Calculate goodwill/bargain purchase                  │
│                                                              │
│  6. PRODUCE CONSOLIDATED RESULT                              │
│     └── Aggregated balances + adjustments                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Related Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - Overall system architecture
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
- [Nexus Packages Reference](../../docs/NEXUS_PACKAGES_REFERENCE.md) - All available packages

---

## License

MIT License - See [LICENSE](LICENSE) for details.
