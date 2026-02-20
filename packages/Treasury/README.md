# Treasury Package

Treasury management package for cash flow forecasting, investment management, and approval workflows.

## Purpose

The Treasury package provides a comprehensive domain model and service layer for managing treasury operations within the Nexus platform. It handles policy management, authorization limits, and approval workflows for financial transactions.

## Key Features

- **Treasury Policy Management**: Create and manage treasury policies with configurable thresholds
- **Authorization Limits**: Define spending limits per user or role with currency support
- **Approval Workflows**: Submit, approve, or reject transactions requiring authorization
- **Multi-Currency Support**: All monetary values support multiple currencies
- **Status Tracking**: Comprehensive status management for policies and approvals

## Installation

```bash
composer require nexus/treasury
```

### Requirements

- PHP ^8.3
- nexus/common ^1.0

## Usage

### Creating a Treasury Policy

```php
use Nexus\Treasury\Services\TreasuryManager;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;

// Create policy data
$policyData = TreasuryPolicyData::fromArray([
    'name' => 'Corporate Treasury Policy',
    'description' => 'Main treasury policy for corporate operations',
    'minimum_cash_balance' => 100000.00,
    'minimum_cash_balance_currency' => 'USD',
    'maximum_single_transaction' => 500000.00,
    'maximum_single_transaction_currency' => 'USD',
    'approval_required' => true,
    'approval_threshold' => 50000.00,
    'approval_threshold_currency' => 'USD',
]);

// Create policy via TreasuryManager
$policy = $treasuryManager->createPolicy($tenantId, $policyData);
echo $policy->getId(); // TRS-POL-XXXXX
```

### Managing Authorization Limits

```php
use Nexus\Treasury\ValueObjects\AuthorizationLimit;

// Create an authorization limit for a user
$limit = new AuthorizationLimit(
    userId: 'user-123',
    roleId: null,
    amount: 25000.00,
    currency: 'USD',
    transactionType: 'payment'
);

$authLimit = $treasuryManager->createAuthorizationLimit($tenantId, $limit);

// Check if transaction requires approval
$requiresApproval = $treasuryManager->requiresApproval(
    $tenantId,
    30000.00,
    'USD'
);
```

### Approval Workflow

```php
// Submit a transaction for approval
$approval = $treasuryManager->submitForApproval(
    $tenantId,
    'payment',
    75000.00,
    'USD',
    'Vendor payment for services',
    'user-123'
);

// Get pending approvals for a user
$pendingApprovals = $treasuryManager->getPendingApprovals('approver-456');

// Approve the transaction
$approved = $treasuryManager->approveTransaction(
    $approval->getId(),
    'approver-456',
    'Approved - within budget'
);

// Or reject the transaction
$rejected = $treasuryManager->rejectTransaction(
    $approval->getId(),
    'approver-456',
    'Insufficient budget allocation'
);
```

## Interfaces

### Core Interfaces

| Interface | Description |
|-----------|-------------|
| [`TreasuryManagerInterface`](src/Contracts/TreasuryManagerInterface.php) | Main orchestrator for treasury operations |
| [`TreasuryPolicyInterface`](src/Contracts/TreasuryPolicyInterface.php) | Treasury policy entity contract |
| [`TreasuryApprovalInterface`](src/Contracts/TreasuryApprovalInterface.php) | Treasury approval entity contract |
| [`AuthorizationLimitInterface`](src/Contracts/AuthorizationLimitInterface.php) | Authorization limit entity contract |

### Repository Interfaces

| Interface | Description |
|-----------|-------------|
| [`TreasuryPolicyRepositoryInterface`](src/Contracts/TreasuryPolicyRepositoryInterface.php) | Combined repository for policy persistence |
| [`TreasuryPolicyQueryRepositoryInterface`](src/Contracts/TreasuryPolicyQueryRepositoryInterface.php) | Read operations for policies |
| [`TreasuryPolicyPersistRepositoryInterface`](src/Contracts/TreasuryPolicyPersistRepositoryInterface.php) | Write operations for policies |
| [`TreasuryApprovalRepositoryInterface`](src/Contracts/TreasuryApprovalRepositoryInterface.php) | Combined repository for approval persistence |
| [`TreasuryApprovalQueryRepositoryInterface`](src/Contracts/TreasuryApprovalQueryRepositoryInterface.php) | Read operations for approvals |
| [`TreasuryApprovalPersistRepositoryInterface`](src/Contracts/TreasuryApprovalPersistRepositoryInterface.php) | Write operations for approvals |
| [`AuthorizationLimitRepositoryInterface`](src/Contracts/AuthorizationLimitRepositoryInterface.php) | Combined repository for limit persistence |
| [`AuthorizationLimitQueryRepositoryInterface`](src/Contracts/AuthorizationLimitQueryRepositoryInterface.php) | Read operations for limits |
| [`AuthorizationLimitPersistRepositoryInterface`](src/Contracts/AuthorizationLimitPersistRepositoryInterface.php) | Write operations for limits |

## Models

| Model | Description |
|-------|-------------|
| [`TreasuryPolicy`](src/Models/TreasuryPolicy.php) | Immutable treasury policy entity |
| [`TreasuryApproval`](src/Models/TreasuryApproval.php) | Immutable approval entity |
| [`AuthorizationLimit`](src/Models/AuthorizationLimit.php) | Immutable authorization limit entity |

## Value Objects

| Value Object | Description |
|--------------|-------------|
| [`TreasuryPolicyData`](src/ValueObjects/TreasuryPolicyData.php) | Data transfer object for policy creation/update |
| [`AuthorizationLimit`](src/ValueObjects/AuthorizationLimit.php) | Data transfer object for limit creation |

## Enums

| Enum | Cases | Description |
|------|-------|-------------|
| [`ApprovalStatus`](src/Enums/ApprovalStatus.php) | PENDING, APPROVED, REJECTED, CANCELLED, EXPIRED, REQUIRES_REVIEW | Status of approval requests |
| [`TreasuryStatus`](src/Enums/TreasuryStatus.php) | ACTIVE, INACTIVE, PENDING, SUSPENDED, CLOSED | Status of treasury policies |
| [`InvestmentType`](src/Enums/InvestmentType.php) | MONEY_MARKET, TERM_DEPOSIT, TREASURY_BILL, COMMERCIAL_PAPER, FIXED_DEPOSIT, OVERNIGHT | Types of short-term investments |
| [`InvestmentStatus`](src/Enums/InvestmentStatus.php) | PENDING, ACTIVE, MATURED, CANCELLED | Status of investments |
| [`ForecastScenario`](src/Enums/ForecastScenario.php) | OPTIMISTIC, BASE, PESSIMISTIC | Cash flow forecast scenarios |

## Exceptions

| Exception | Description |
|-----------|-------------|
| [`TreasuryException`](src/Exceptions/TreasuryException.php) | Base exception for treasury operations |
| [`TreasuryPolicyNotFoundException`](src/Exceptions/TreasuryPolicyNotFoundException.php) | Thrown when policy is not found |
| [`LiquidityPoolNotFoundException`](src/Exceptions/LiquidityPoolNotFoundException.php) | Thrown when liquidity pool is not found |

## Architecture

This package follows the Atomic Architecture pattern:

- **Contracts**: Interface definitions for domain contracts
- **Models**: Immutable entity implementations
- **ValueObjects**: Data transfer objects for operations
- **Services**: Business logic and orchestration
- **Repositories**: In-memory implementations for testing
- **Enums**: Type-safe enumerations
- **Exceptions**: Domain-specific exceptions

## Testing

```bash
# Run tests
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

## License

MIT License
