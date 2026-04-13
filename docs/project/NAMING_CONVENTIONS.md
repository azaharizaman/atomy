
# Naming Conventions

This document outlines the naming conventions for all aspects of the codebase, ensuring consistency and adherence to the project's Three-Layer Architecture.

## General Principles

*   **Clarity and Conciseness:** Names should be descriptive but not overly verbose.
*   **Consistency:** Adhere strictly to the defined conventions.
*   **Readability:** Use clear language and avoid cryptic abbreviations.
*   **PHP Standards:** Follow PSR standards where applicable (e.g., camelCase for methods/variables, PascalCase for classes/interfaces).
*   **`declare(strict_types=1);`:** Mandatory in every file as per project guidelines.

---

## Layer 1: packages/ (Pure PHP, framework-agnostic)

### Classes

*   **Services:** PascalCase. Suffix with `Service` (e.g., `AccountConsolidationService`).
*   **Value Objects (VOs):** PascalCase, often singular nouns representing a concept (e.g., `Money`, `TenantId`, `PeriodId`). MUST be `final readonly class`.
*   **Enums:** PascalCase, often plural or representing a set of values (e.g., `Status`, `SubledgerType`).
*   **Contracts:** PascalCase. Suffix with `Interface` or `Contract` (e.g., `AccountConsolidationInterface`, `SubledgerTypeInterface`).
*   **Exceptions:** PascalCase. Suffix with `Exception` (e.g., `InvalidArgumentException`, `DomainException`). Domain-specific exceptions should be prefixed with relevant domain or package name (e.g., `Nexus\FinanceOperations\Exceptions\InvalidRuleContextException`).
*   **Rules:** PascalCase. Suffix with `Rule` (e.g., `BudgetAvailableRule`, `GLAccountMappingRule`).
*   **Data Transfer Objects (DTOs):** PascalCase. Suffix with `DTO`, `Request`, or `Result` (e.g., `ConsistencyCheckRequest`, `BudgetCheckResult`).
*   **Repositories:** PascalCase. Suffix with `Repository` (e.g., `VendorRepository`). These are for data persistence and operations. Note: For read-only query operations, prefer defining separate `QueryInterface` interfaces (see Interfaces section).

### Interfaces

*   **Services:** PascalCase. Suffix with `ServiceInterface` (e.g., `AccountConsolidationServiceInterface`).
*   **Queries:** PascalCase. Suffix with `QueryInterface` (e.g., `GLAccountQueryInterface`, `InvoiceDuplicateQueryInterface`).
*   **Persistence/Repositories:** PascalCase. Suffix with `RepositoryInterface` or `PersistenceInterface` (e.g., `VendorRepositoryInterface`).
*   **Commands/Actions:** PascalCase. Suffix with `CommandInterface` or `ActionInterface` (e.g., `ProcessPaymentCommandInterface`).
*   **Contracts:** PascalCase. Suffix with `Contract` or `Interface` (e.g., `RuleContextInterface`).

### Methods

*   **Public Methods:** camelCase. Use imperative mood for actions (e.g., `checkAvailability()`, `calculateVariances()`, `createAccrual()`).
*   **Private/Protected Methods:** camelCase. Use for helper logic within the class.
*   **Getters:** camelCase. Prefix with `get` (e.g., `getTenantId()`, `getAmount()`).
*   **Setters:** camelCase. Prefix with `set` (e.g., `setApprovalStatus()`). Prefer immutability where possible, using `with...` methods for value objects or DTOs (e.g., `withStatus()`, `withAmount()`). Immutable setters must follow the `with...` pattern, be named after the modified field, accept the new value as the sole/primary argument, and return a new instance of the object rather than mutating `this`. For boolean fields, consistency is key—use either `withIs...` or `with...` consistently across the DTO/VO.
*   **Boolean Checkers:** camelCase. Prefix with `is` or `has` (e.g., `isOpen()`, `hasVariance()`).
*   **Factory Methods:** camelCase. Prefix with `create` or `for` for static factory methods (e.g., `RuleContext::forBudgetAvailability()`).

### Variables

*   **Local Variables:** camelCase. Descriptive.
*   **Constants:** SCREAMING_SNAKE_CASE (e.g., `self::DEFAULT_TOLERANCE_PERCENT`).

---

## Layer 2: orchestrators/ (Cross-package coordination, stateless)

### Classes

*   **Coordinators:** PascalCase. Suffix with `Coordinator` (e.g., `BudgetTrackingCoordinator`, `GLPostingCoordinator`). Should direct flow, not execute business logic.
*   **Rules:** PascalCase. Suffix with `Rule` (e.g., `BudgetAvailableRule`, `GLAccountMappingRule`). Implementations of contracts from Layer 1.
*   **DTOs/Requests/Results:** PascalCase. Suffix with `DTO`, `Request`, or `Result` (e.g., `ConsistencyCheckRequest`, `BudgetCheckResult`). Used for data transfer between layers or within orchestrator logic.
*   **Enums:** PascalCase, often plural or representing a set of values (e.g., `SubledgerType`, `PaymentBatchStatus`).
*   **Exceptions:** PascalCase. Suffix with `Exception` (e.g., `InvalidRuleContextException`). Domain-specific exceptions.

### Interfaces

*   **Coordinators:** PascalCase. Suffix with `CoordinatorInterface` (e.g., `BudgetTrackingCoordinatorInterface`).
*   **Rules:** PascalCase. Suffix with `RuleInterface` (e.g., `BudgetAvailableRuleInterface`).
*   **Events:** PascalCase. Suffix with `Event` (e.g., `BudgetExceededEvent`).

### Methods

*   **Public Methods:** camelCase. Imperative mood for actions (e.g., `checkBudgetAvailable()`, `calculateVariances()`, `createAccrual()`).
*   **Internal/Protected Methods:** camelCase. Use for helper logic within the coordinator.

### Variables

*   **Local Variables:** camelCase. Descriptive.

---

## Layer 3: adapters/ (Framework-specific implementation)

### Classes

*   **Framework-Specific Implementations:** PascalCase. Suffix indicates framework or role (e.g., `Laravel\Eloquent\Model`, `Symfony\Controller`, `Doctrine\Repository`).
*   **Controllers:** PascalCase. Suffix with `Controller` (e.g., `InvoiceController`).
*   **Repositories (Framework-specific):** PascalCase. Suffix with `Repository` (e.g., `EloquentVendorRepository`). Implement interfaces from Layer 1 or 2.
*   **Services (Framework-specific):** PascalCase. Suffix with `Service` if they offer framework-specific functionality not covered by Layer 1/2 services.
*   **Event Listeners/Subscribers:** PascalCase. Suffix with `Listener` or `Subscriber` (e.g., `PaymentBatchCreatedListener`).

### Interfaces

*   May implement framework interfaces or extend Layer 1/2 interfaces.

### Methods

*   **Public Methods:** camelCase. Follow framework conventions (e.g., controller actions, repository methods).
*   **Framework-Specific Methods:** camelCase. As required by the framework.

### Variables

*   **Local Variables:** camelCase. Descriptive.

---

## Common Elements Across Layers

*   **Enums:** PascalCase, e.g., `SubledgerType`, `PaymentBatchStatus`.
*   **Interfaces:** PascalCase, suffix `Interface` or `Contract`, e.g., `RuleContextInterface`, `DiscountCalculationServiceInterface`.
*   **Traits:** PascalCase, suffix `Trait`, e.g., `TenantAwareTrait`.
*   **File Names:** Use PascalCase for files named after a single primary class/interface (e.g., `GrIrAccrualService.php`, `RuleContextInterface.php`). Use snake_case for directories (e.g., `procurement-operations/`).
*   **Namespaces:** PascalCase. Follow the pattern `Nexus\PackageName\SubNamespace` (e.g., `Nexus\FinanceOperations\Coordinators`).
*   **Test files and methods:** Test files must end in `Test.php` (e.g., `StockManagerTest.php`). Test classes must end in `Test`. Test method names must use snake_case with `it_` prefix (e.g., `it_calculates_value_scores_correctly()`, `it_returns_empty_array_when_vendors_list_is_empty()`). Avoid PascalCase or camelCase prefixes like `testItCalculateValueScoresCorrectly` or `TestItCalculateValueScoresCorrectly`.
*   **Abstract/Base classes:** Prefix with `Abstract` or `Base` (e.g., `AbstractFinanceWorkflow`, `BaseRepository`).
*   **Configuration files:** snake_case for filenames (e.g., `payment_config.php`, `database.php`).
*   **Properties:** camelCase.
