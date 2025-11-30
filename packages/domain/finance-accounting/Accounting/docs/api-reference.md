# API Reference: Accounting

## Overview

The Accounting package provides **10 interfaces**, **4 core engines**, **8 value objects**, **4 enums**, and **6 exceptions** for comprehensive financial reporting and accounting operations.

**Full API documentation is available in the source code via comprehensive docblocks (34% comment ratio).**

See:
- `src/Contracts/` - All 10 interfaces
- `src/Services/AccountingManager.php` - 15 public APIs
- `src/Core/Engine/` - 4 core engines
- `src/Core/ValueObjects/` - 8 immutable value objects
- `src/Core/Enums/` - 4 native PHP enums
- `src/Exceptions/` - 6 domain exceptions

---

## Quick Reference

### Primary Service

**AccountingManager** - `Nexus\Accounting\Services\AccountingManager`

15 Public APIs for:
- Financial statement generation (3 methods)
- Period close operations (3 methods)
- Consolidation (2 methods)
- Variance analysis (1 method)
- Export and reporting (6 methods)

### Core Interfaces

1. **StatementBuilderInterface** - Statement generation engine
2. **ConsolidationEngineInterface** - Multi-entity consolidation
3. **PeriodCloseServiceInterface** - Period close operations
4. **VarianceCalculatorInterface** - Budget variance analysis
5. **StatementRepositoryInterface** - Statement persistence
6. **FinancialStatementInterface** - Statement contract
7. **BalanceSheetInterface** - Balance sheet contract
8. **IncomeStatementInterface** - Income statement contract
9. **CashFlowStatementInterface** - Cash flow statement contract
10. **ComplianceTemplateInterface** - Compliance formatting

---

For complete API documentation with parameters, return types, and exceptions, refer to source code docblocks in `packages/Accounting/src/`.
