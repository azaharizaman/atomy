# Nexus\ChartOfAccount

**Framework-Agnostic Chart of Accounts Management Package**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\ChartOfAccount` is an **atomic package** that provides framework-agnostic chart of accounts management for general ledger systems. This package focuses exclusively on account structure, hierarchy, and master data management.

As an atomic package, it:
- **Owns the Truth** - Defines AccountInterface and account structure
- **Is Framework-Agnostic** - Pure PHP with no database or framework dependencies
- **Is Contract-Driven** - All persistence via repository interfaces

## Installation

```bash
composer require nexus/chart-of-account
```

## Features

- ✅ Hierarchical chart of accounts with unlimited depth
- ✅ Five standard account types (Asset, Liability, Equity, Revenue, Expense)
- ✅ Debit/Credit normal balance awareness
- ✅ Header (group) accounts vs. postable (leaf) accounts
- ✅ Account activation/deactivation without deletion
- ✅ Flexible account code formats (numeric, alphanumeric, dot/dash-separated)
- ✅ Account code value object with validation
- ✅ Parent-child type inheritance validation

## Quick Start

```php
use Nexus\ChartOfAccount\Contracts\AccountManagerInterface;
use Nexus\ChartOfAccount\Enums\AccountType;

// Inject the manager (via DI container)
public function __construct(
    private readonly AccountManagerInterface $accountManager
) {}

// Create an account
$account = $this->accountManager->createAccount([
    'code' => '1000',
    'name' => 'Cash and Cash Equivalents',
    'type' => AccountType::Asset->value,
    'is_header' => true,
]);

// Create a child account
$childAccount = $this->accountManager->createAccount([
    'code' => '1000-001',
    'name' => 'Petty Cash',
    'type' => AccountType::Asset->value,
    'parent_id' => $account->getId(),
    'is_header' => false,
]);

// Find accounts
$account = $this->accountManager->findByCode('1000');
$children = $this->accountManager->findChildren($account->getId());
```

## Architecture

```
src/
├── Contracts/           # Interfaces
│   ├── AccountInterface.php
│   ├── AccountQueryInterface.php
│   ├── AccountPersistInterface.php
│   └── AccountManagerInterface.php
├── Enums/
│   └── AccountType.php
├── ValueObjects/
│   └── AccountCode.php
├── Services/
│   └── AccountManager.php
└── Exceptions/
    ├── ChartOfAccountException.php
    ├── AccountNotFoundException.php
    ├── DuplicateAccountCodeException.php
    ├── InvalidAccountException.php
    └── AccountHasChildrenException.php
```

## Contracts

### `AccountInterface`

Defines the structure of a chart of accounts entry.

```php
interface AccountInterface
{
    public function getId(): string;
    public function getCode(): string;
    public function getName(): string;
    public function getType(): AccountType;
    public function getParentId(): ?string;
    public function isHeader(): bool;
    public function isActive(): bool;
    public function getDescription(): ?string;
    public function getCreatedAt(): DateTimeImmutable;
    public function getUpdatedAt(): DateTimeImmutable;
}
```

### `AccountQueryInterface`

Read operations for accounts (CQRS Query model).

```php
interface AccountQueryInterface
{
    public function find(string $id): ?AccountInterface;
    public function findByCode(string $code): ?AccountInterface;
    public function findAll(array $filters = []): array;
    public function findChildren(string $parentId): array;
    public function findByType(AccountType $type): array;
    public function codeExists(string $code, ?string $excludeId = null): bool;
}
```

### `AccountPersistInterface`

Write operations for accounts (CQRS Command model).

```php
interface AccountPersistInterface
{
    public function save(AccountInterface $account): AccountInterface;
    public function delete(string $id): void;
}
```

## Account Types

| Type | Normal Balance | Balance Sheet? | Example Accounts |
|------|---------------|----------------|------------------|
| Asset | Debit | Yes | Cash, Inventory, Equipment |
| Liability | Credit | Yes | Accounts Payable, Loans |
| Equity | Credit | Yes | Common Stock, Retained Earnings |
| Revenue | Credit | No | Sales Revenue, Service Income |
| Expense | Debit | No | Wages, Rent, Utilities |

## Value Objects

### `AccountCode`

Immutable value object for account codes with validation.

```php
use Nexus\ChartOfAccount\ValueObjects\AccountCode;

$code = AccountCode::fromString('1000-001');
$code->getValue();      // '1000-001'
$code->getLevel();      // 1 (based on dash separators)
$code->getParent();     // AccountCode('1000')
$code->isParentOf($child); // bool
```

## Business Rules

1. **Account Codes Must Be Unique** - Within tenant scope
2. **Type Inheritance** - Child accounts inherit parent's root type
3. **Header Accounts Not Postable** - Only leaf accounts can have journal entries
4. **Deletion Prevention** - Accounts with children or transactions cannot be deleted
5. **Activation/Deactivation** - Preserves history without deletion

## Integration

This package is consumed by:
- `Nexus\JournalEntry` - For account validation during posting
- `Nexus\AccountingOperations` - For coordinated financial workflows

## Related Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - System architecture
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
- [REQUIREMENTS.md](REQUIREMENTS.md) - Package requirements

## License

MIT License - See [LICENSE](LICENSE) for details.
