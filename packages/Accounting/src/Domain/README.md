# Domain Layer: Accounting Package

**Purpose:** Contains the core business logic, entities, value objects, events, contracts, services, and policies for the Accounting domain.

This layer represents "THE TRUTH" - pure business logic with no framework dependencies.

---

## Components

### Entities

| Entity | Purpose | Key Properties |
|--------|---------|----------------|
| `FinancialStatement` | Base class for all financial statements | period, generatedAt, preparedBy |
| `BalanceSheet` | Assets = Liabilities + Equity statement | assets, liabilities, equity, sections |
| `IncomeStatement` | Revenue - Expenses = Net Income | revenue, expenses, netIncome, sections |
| `CashFlowStatement` | Operating, investing, financing activities | operatingCash, investingCash, financingCash |
| `TrialBalance` | Debit/Credit balance verification | accounts, totalDebits, totalCredits |
| `StatementSection` | Grouping of line items | name, lineItems, subtotal |
| `StatementLineItem` | Individual line in statement | accountCode, description, amount |

### Value Objects

| Value Object | Purpose | Properties |
|--------------|---------|------------|
| `Period` | Fiscal period representation | startDate, endDate, periodType |
| `PeriodType` | Enum for period types | MONTHLY, QUARTERLY, ANNUAL, CUSTOM |
| `Money` | Monetary value with currency | amount, currency |
| `AccountBalance` | Account with balance | accountId, accountCode, accountName, debit, credit, balance |
| `StatementMetadata` | Statement generation info | version, approvedBy, approvedAt, notes |
| `ConsolidationEntity` | Entity in consolidation | entityId, name, currency, ownershipPercentage |

### Domain Events

| Event | Trigger | Purpose |
|-------|---------|---------|
| `FinancialStatementGeneratedEvent` | Statement created | Notify subscribers of new statement |
| `PeriodClosedEvent` | Period closed | Trigger end-of-period processes |
| `ConsolidationCompletedEvent` | Consolidation done | Notify completion of multi-entity consolidation |
| `VarianceDetectedEvent` | Budget variance | Alert on significant variance |

### Contracts (Repository Interfaces)

| Interface | Purpose |
|-----------|---------|
| `AccountingPeriodQueryInterface` | Read accounting period data |
| `AccountingPeriodPersistInterface` | Write accounting period data |
| `FinancialStatementQueryInterface` | Query financial statements |
| `FinancialStatementPersistInterface` | Persist financial statements |
| `ConsolidationQueryInterface` | Query consolidation data |
| `ConsolidationPersistInterface` | Persist consolidation data |

### Domain Services

| Service | Purpose |
|---------|---------|
| `BalanceSheetGenerator` | Generate balance sheet from trial balance |
| `IncomeStatementGenerator` | Generate income statement |
| `CashFlowStatementGenerator` | Generate cash flow statement |
| `TrialBalanceCalculator` | Calculate trial balance from GL |
| `FinancialRatioCalculator` | Calculate financial ratios |
| `IntercompanyEliminator` | Eliminate intercompany transactions |

### Policies

| Policy | Purpose |
|--------|---------|
| `PeriodClosePolicy` | Rules for closing accounting periods |
| `StatementApprovalPolicy` | Rules for statement approval workflow |
| `ConsolidationPolicy` | Rules for multi-entity consolidation |

---

## Architectural Rules

1. **No Framework Dependencies:** This layer MUST NOT use any framework-specific code
2. **Pure PHP 8.3+:** Use modern PHP features (readonly, enums, match)
3. **Immutable Entities:** All entities should be immutable where possible
4. **Interface-Driven:** All external dependencies defined via interfaces
5. **Event-Driven:** State changes emit domain events

---

## Dependencies

- `Nexus\Common` - Common value objects and contracts
- `Nexus\Finance` - General ledger integration (via interfaces)
- `Nexus\Period` - Fiscal period management (via interfaces)
