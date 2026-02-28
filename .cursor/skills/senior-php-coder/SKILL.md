---
name: senior-php-coder
description: Writes PHP 8.3+ code following Nexus coding standards: strict types, readonly classes, constructor injection, PSR conventions. Use when writing or refactoring PHP in packages, orchestrators, or adapters.
---

# Senior PHP Coder

## Mandatory Standards

- `declare(strict_types=1);` in every file
- PSR-4 autoloading
- Constructor injection only; depend on interfaces
- `final readonly class` for services
- Native enums instead of constants
- Domain-specific exceptions (never throw generic `\Exception`)

## Class Conventions

```php
// Service
final readonly class FeatureFlagManager
{
    public function __construct(
        private FlagRepositoryInterface $repository,
        private FlagEvaluatorInterface $evaluator,
    ) {}
}

// DTO
final readonly class LaborHealthDTO
{
    public function __construct(
        public string $projectId,
        public float $healthPercentage,
        public Money $budgeted,
        public Money $actual,
    ) {}
}

// Value Object
final readonly class Money
{
    public function __construct(
        public readonly string $amount,
        public readonly string $currency,
    ) {}
}
```

## Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Interface | `{Name}Interface` | `StockManagerInterface` |
| Query repo | `{Entity}QueryInterface` | `UserQueryInterface` |
| Persist repo | `{Entity}PersistInterface` | `UserPersistInterface` |
| Service | `{Domain}Manager` | `FeatureFlagManager` |
| Value object | `{Concept}` | `Money`, `EvaluationContext` |
| Exception | `{Error}Exception` | `InvalidFlagDefinitionException` |

## Package Structure

```
packages/{Package}/src/
├── Contracts/
├── Services/
├── ValueObjects/
└── Exceptions/
```

## Orchestrator Structure

```
orchestrators/{Name}/src/
├── Contracts/
├── Coordinators/
├── DataProviders/
├── Rules/
├── Services/
├── Workflows/
├── DTOs/
└── Exceptions/
```

## Anti-Patterns

- No `new` for dependencies – inject via constructor
- No static methods for business logic
- No mutable public properties – use readonly
- No mixing framework code in packages (packages are pure PHP)

## Reference

- `docs/project/ARCHITECTURE.md` – Section 6: Coding Standards
