# Requirements: ChartOfAccount (Atomic Package Layer)

**Package:** `Nexus\ChartOfAccount`  
**Version:** 1.0  
**Last Updated:** November 26, 2025  
**Total Requirements:** 35 (Refined from Nexus\Finance split)

---

## Package Boundary Definition

This requirements document defines the **atomic package layer** for `Nexus\ChartOfAccount` - a stateless, framework-agnostic chart of accounts management engine.

### What Belongs in Package Layer (This Document)

- ✅ **Account Structure**: Hierarchical chart of accounts with unlimited depth
- ✅ **Account Type Management**: Five standard types (Asset, Liability, Equity, Revenue, Expense)
- ✅ **Account Code Validation**: Format validation via AccountCode value object
- ✅ **Account Lifecycle**: Activation, deactivation, hierarchy management
- ✅ **Entity Contracts**: AccountInterface, AccountQueryInterface, AccountPersistInterface
- ✅ **Business Rules**: Account code uniqueness, parent-child type inheritance, header/postable distinction

### What Does NOT Belong in This Package

- ❌ **Journal Entries**: Belongs in `Nexus\JournalEntry` package
- ❌ **Balance Calculation**: Belongs in `Nexus\JournalEntry` package
- ❌ **Posting Engine**: Belongs in `Nexus\JournalEntry` package
- ❌ **Multi-currency Support**: Money/ExchangeRate belong in `Nexus\JournalEntry`
- ❌ **Database Schema**: Migrations, indexes, foreign keys (application layer)
- ❌ **API Endpoints**: REST routes, controllers (application layer)
- ❌ **Orchestration**: Workflow approval, notifications (orchestrator layer)

### Architectural References

- **Core Principles**: `.github/copilot-instructions.md`
- **Architecture Guidelines**: `ARCHITECTURE.md`
- **Package Reference**: `docs/NEXUS_PACKAGES_REFERENCE.md`

---

## Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| **ARCHITECTURAL REQUIREMENTS** |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1001 | Package MUST be framework-agnostic with zero dependencies on Laravel, Symfony, or any web framework | composer.json, src/ | ⏳ Pending | Validate no Illuminate\* imports | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1002 | Package composer.json MUST require only: php:^8.3 and nexus/common | composer.json | ⏳ Pending | Minimal dependencies | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1003 | All entity data structures MUST be defined via interfaces (AccountInterface) | Contracts/ | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1004 | All persistence operations MUST use CQRS repository interfaces (AccountQueryInterface, AccountPersistInterface) | Contracts/ | ⏳ Pending | Separate read/write | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1005 | Business logic MUST be concentrated in service layer (AccountManager) with readonly injected dependencies | Services/ | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1006 | Account codes MUST use AccountCode Value Object (readonly, format validation) | ValueObjects/AccountCode.php | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1007 | Account types MUST use native PHP enum (Asset, Liability, Equity, Revenue, Expense) with debit/credit normal indicator methods | Enums/AccountType.php | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1008 | All files MUST use declare(strict_types=1) and constructor property promotion with readonly modifiers | src/ | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1009 | Package MUST be stateless - no session state, no class-level mutable properties, all state externalized via repository interfaces | src/ | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Architectural | ARC-COA-1010 | All domain exceptions MUST extend base ChartOfAccountException with factory methods for context-rich error creation | Exceptions/ | ⏳ Pending | - | 2025-11-26 |
| **BUSINESS RULES** |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1001 | Account codes MUST be unique within tenant scope (validated by repository layer) | Services/AccountManager.php | ⏳ Pending | Repository validates uniqueness | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1002 | Header accounts (isHeader() = true) represent groupings and cannot have transactions posted | Contracts/AccountInterface.php | ⏳ Pending | Used by JournalEntry package | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1003 | Only leaf accounts (isHeader() = false) are postable - exposed via isPostable() method | Contracts/AccountInterface.php | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1004 | Account deletion MUST be prevented if account has child accounts (hasChildren() = true) | Services/AccountManager.php | ⏳ Pending | Validate before delete | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1005 | Chart of Accounts MUST support unlimited hierarchical depth with parent-child relationships | Contracts/AccountInterface.php | ⏳ Pending | Repository manages tree | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1006 | Each account MUST belong to exactly one of 5 account types (Asset, Liability, Equity, Revenue, Expense) | Enums/AccountType.php | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1007 | Child accounts MUST inherit parent account's root type (cannot mix Asset children under Liability parent) | Services/AccountManager.php | ⏳ Pending | Validate on create | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1008 | Account activation/deactivation MUST preserve account history without deletion | Contracts/AccountInterface.php | ⏳ Pending | isActive() boolean flag | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1009 | Account codes MUST support flexible formats (numeric, alphanumeric, dot-separated, dash-separated) via AccountCode value object | ValueObjects/AccountCode.php | ⏳ Pending | Regex validation | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1010 | Account type changes MUST be prohibited once account has been created (type is immutable) | Services/AccountManager.php | ⏳ Pending | Prevent type change | 2025-11-26 |
| `Nexus\ChartOfAccount` | Business Rule | BUS-COA-1011 | Header status changes MUST be prohibited if account has child accounts | Services/AccountManager.php | ⏳ Pending | Validate header change | 2025-11-26 |
| **FUNCTIONAL CAPABILITIES** |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1001 | AccountManager MUST provide createAccount() method accepting code, name, type, and optional parent ID | Services/AccountManager.php | ⏳ Pending | Factory method | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1002 | AccountManager MUST provide updateAccount() method for modifying account properties | Services/AccountManager.php | ⏳ Pending | Validates constraints | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1003 | AccountManager MUST provide findById() method returning AccountInterface | Services/AccountManager.php | ⏳ Pending | Throws if not found | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1004 | AccountManager MUST provide findByCode() method returning AccountInterface | Services/AccountManager.php | ⏳ Pending | Throws if not found | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1005 | AccountManager MUST provide getAccounts() method with filtering by type, active status, and parent | Services/AccountManager.php | ⏳ Pending | Repository query | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1006 | AccountManager MUST provide getChildren() method returning child accounts of a parent | Services/AccountManager.php | ⏳ Pending | Repository query | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1007 | AccountManager MUST provide activateAccount() method to re-enable inactive accounts | Services/AccountManager.php | ⏳ Pending | Sets isActive = true | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1008 | AccountManager MUST provide deactivateAccount() method to disable accounts without deletion | Services/AccountManager.php | ⏳ Pending | Sets isActive = false | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1009 | AccountManager MUST provide deleteAccount() method with validation for children | Services/AccountManager.php | ⏳ Pending | Throws if has children | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1010 | AccountInterface MUST provide isHeader() method indicating if account is a grouping account | Contracts/AccountInterface.php | ⏳ Pending | Boolean flag | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1011 | AccountInterface MUST provide isPostable() method indicating if account can receive transactions | Contracts/AccountInterface.php | ⏳ Pending | Inverse of isHeader | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1012 | AccountInterface MUST provide isActive() method for activation status | Contracts/AccountInterface.php | ⏳ Pending | Boolean flag | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1013 | AccountInterface MUST provide getType() method returning AccountType enum | Contracts/AccountInterface.php | ⏳ Pending | - | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1014 | AccountType enum MUST provide isDebitNormal() method (true for Asset/Expense, false for Liability/Equity/Revenue) | Enums/AccountType.php | ⏳ Pending | Business logic method | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1015 | AccountType enum MUST provide isBalanceSheetAccount() method (true for Asset/Liability/Equity) | Enums/AccountType.php | ⏳ Pending | Classification method | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1016 | AccountQueryInterface MUST provide find(), findByCode(), findAll(), findChildren(), findByType() methods | Contracts/AccountQueryInterface.php | ⏳ Pending | Read operations | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1017 | AccountQueryInterface MUST provide codeExists() method for uniqueness validation | Contracts/AccountQueryInterface.php | ⏳ Pending | Validation helper | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1018 | AccountQueryInterface MUST provide hasChildren() method for hierarchy validation | Contracts/AccountQueryInterface.php | ⏳ Pending | Validation helper | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1019 | AccountPersistInterface MUST provide save() and delete() methods | Contracts/AccountPersistInterface.php | ⏳ Pending | Write operations | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1020 | AccountCode value object MUST provide getLevel() method for hierarchy depth | ValueObjects/AccountCode.php | ⏳ Pending | Based on separators | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1021 | AccountCode value object MUST provide getParent() method returning parent code | ValueObjects/AccountCode.php | ⏳ Pending | Hierarchy navigation | 2025-11-26 |
| `Nexus\ChartOfAccount` | Functional | FUN-COA-1022 | AccountCode value object MUST provide isParentOf() method for hierarchy validation | ValueObjects/AccountCode.php | ⏳ Pending | Relationship check | 2025-11-26 |
| **VALIDATION & ERROR HANDLING** |
| `Nexus\ChartOfAccount` | Validation | VAL-COA-1001 | AccountNotFoundException MUST include searched account ID or code in exception message | Exceptions/AccountNotFoundException.php | ⏳ Pending | Factory methods | 2025-11-26 |
| `Nexus\ChartOfAccount` | Validation | VAL-COA-1002 | DuplicateAccountCodeException MUST include conflicting account code in exception message | Exceptions/DuplicateAccountCodeException.php | ⏳ Pending | Factory method | 2025-11-26 |
| `Nexus\ChartOfAccount` | Validation | VAL-COA-1003 | InvalidAccountException MUST include specific validation failure reason | Exceptions/InvalidAccountException.php | ⏳ Pending | Multiple factory methods | 2025-11-26 |
| `Nexus\ChartOfAccount` | Validation | VAL-COA-1004 | AccountHasChildrenException MUST indicate account has child accounts preventing deletion | Exceptions/AccountHasChildrenException.php | ⏳ Pending | Factory method | 2025-11-26 |
| `Nexus\ChartOfAccount` | Validation | VAL-COA-1005 | All validation MUST occur in service layer before repository persistence calls | Services/ | ⏳ Pending | Fail-fast design | 2025-11-26 |
| **ALGORITHMIC COMPLEXITY** |
| `Nexus\ChartOfAccount` | Algorithmic | ALG-COA-1001 | Account hierarchy traversal MUST achieve O(log k) average complexity for k accounts using indexed parent references | Contracts/AccountQueryInterface.php | ⏳ Pending | Repository optimization | 2025-11-26 |
| `Nexus\ChartOfAccount` | Algorithmic | ALG-COA-1002 | Account code uniqueness check MUST achieve O(1) complexity via indexed lookup | Contracts/AccountQueryInterface.php | ⏳ Pending | Repository indexed | 2025-11-26 |
| **INTEGRATION CONTRACTS** |
| `Nexus\ChartOfAccount` | Integration | INT-COA-1001 | MAY inject AuditLogManagerInterface (from Nexus\AuditLogger) for optional audit trail of COA changes | Services/AccountManager.php | ⏳ Pending | Optional nullable dep | 2025-11-26 |
| `Nexus\ChartOfAccount` | Integration | INT-COA-1002 | MUST expose AccountQueryInterface for consumption by Nexus\JournalEntry package | Contracts/AccountQueryInterface.php | ⏳ Pending | Public interface | 2025-11-26 |
| `Nexus\ChartOfAccount` | Integration | INT-COA-1003 | MUST define AccountManagerInterface as public API for consumption by orchestrators | Contracts/AccountManagerInterface.php | ⏳ Pending | Package contract | 2025-11-26 |

---

## Notes

### Package Split from Nexus\Finance

This package was split from the original `Nexus\Finance` package following the Interface Segregation Principle (ISP). The split separates:

1. **`Nexus\ChartOfAccount`** (this package): Account structure, hierarchy, master data management
2. **`Nexus\JournalEntry`**: Journal entries, posting engine, balance calculation, multi-currency

### Consumer Packages

This package is consumed by:
- `Nexus\JournalEntry` - For account validation during posting
- `Nexus\AccountingOperations` - For coordinated financial workflows

### External Transaction Validation

Note: The validation for "account has transactions" is NOT in this package. This package provides `AccountHasTransactionsException` but the actual transaction count check must be performed by the consuming application or the `Nexus\JournalEntry` package via integration.

---

**Document Version**: 1.0  
**Creation Date**: November 26, 2025  
**Maintained By**: Nexus Architecture Team  
**Compliance**: Atomic Package Layer Standards
