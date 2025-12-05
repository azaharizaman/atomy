# Nexus\JournalEntry - Requirements Specification

## Package Overview

**Package Name:** `Nexus\JournalEntry`  
**Namespace:** `Nexus\JournalEntry`  
**Purpose:** Atomic package for double-entry journal entry management  
**Layer:** Atomic Package (`packages/`)

## Scope Definition

### In Scope
- Journal entry creation, posting, and reversal
- Double-entry bookkeeping enforcement (debits = credits)
- Journal entry line item management
- Entry status lifecycle (Draft → Posted → Reversed)
- Ledger posting and balance queries
- Multi-currency support with exchange rates
- Journal entry number generation patterns

### Out of Scope
- Chart of Accounts management (use `Nexus\ChartOfAccount`)
- Financial statement generation (orchestrator responsibility)
- Period close operations (orchestrator responsibility)
- Sub-ledger reconciliation (orchestrator responsibility)
- Budget management (use `Nexus\Budget`)

---

## Requirements

### 1. Journal Entry Core

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| JE-CORE-001 | Create draft journal entries with header and line items | Must Have | In Progress |
| JE-CORE-002 | Validate double-entry balance (total debits = total credits) | Must Have | Complete |
| JE-CORE-003 | Post journal entries to general ledger | Must Have | In Progress |
| JE-CORE-004 | Reverse posted journal entries with offsetting entry | Must Have | In Progress |
| JE-CORE-005 | Delete draft journal entries only | Must Have | In Progress |
| JE-CORE-006 | Prevent modification of posted entries | Must Have | In Progress |
| JE-CORE-007 | Support journal entry attachments via Storage integration | Should Have | Planned |
| JE-CORE-008 | Track source system and document for each entry | Should Have | In Progress |

### 2. Entry Status Lifecycle

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| JE-STATUS-001 | Draft: Allow creation and editing | Must Have | In Progress |
| JE-STATUS-002 | Posted: Immutable, allow reversal only | Must Have | In Progress |
| JE-STATUS-003 | Reversed: Immutable, historical record | Must Have | In Progress |
| JE-STATUS-004 | Validate status transitions | Must Have | In Progress |
| JE-STATUS-005 | Track status change timestamps and actors | Should Have | Planned |

### 3. Line Item Management

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| JE-LINE-001 | Each line references an account ID | Must Have | In Progress |
| JE-LINE-002 | Each line has debit OR credit amount (not both) | Must Have | In Progress |
| JE-LINE-003 | Support line-level descriptions/memos | Should Have | In Progress |
| JE-LINE-004 | Support cost center allocation per line | Should Have | Planned |
| JE-LINE-005 | Support department allocation per line | Should Have | Planned |
| JE-LINE-006 | Minimum 2 lines per entry | Must Have | Complete |

### 4. Multi-Currency Support

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| JE-FX-001 | Support transaction currency per entry | Should Have | In Progress |
| JE-FX-002 | Store exchange rate at posting time | Should Have | In Progress |
| JE-FX-003 | Calculate functional currency amounts | Should Have | Planned |
| JE-FX-004 | Store both transaction and functional amounts | Should Have | Planned |
| JE-FX-005 | Support currency gain/loss recognition | Could Have | Planned |

### 5. Ledger Posting

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| JE-LEDGER-001 | Update account balances on posting | Must Have | In Progress |
| JE-LEDGER-002 | Query account balance as of date | Must Have | In Progress |
| JE-LEDGER-003 | Generate trial balance | Must Have | In Progress |
| JE-LEDGER-004 | Support period-based balance queries | Should Have | Planned |
| JE-LEDGER-005 | Track transaction history per account | Should Have | Planned |

### 6. Validation Rules

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| JE-VAL-001 | Validate debits equal credits | Must Have | Complete |
| JE-VAL-002 | Validate account exists (via contract) | Must Have | In Progress |
| JE-VAL-003 | Validate account allows posting (not header) | Must Have | In Progress |
| JE-VAL-004 | Validate account is active | Should Have | In Progress |
| JE-VAL-005 | Validate posting date within open period (via contract) | Should Have | Planned |

### 7. Integration Contracts

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| JE-INT-001 | Define AccountValidatorInterface for CoA integration | Must Have | In Progress |
| JE-INT-002 | Define PeriodValidatorInterface for Period integration | Should Have | Planned |
| JE-INT-003 | Define SequencingInterface for entry numbering | Should Have | Planned |
| JE-INT-004 | Define ExchangeRateProviderInterface for FX | Should Have | Planned |
| JE-INT-005 | Define EventDispatcherInterface for domain events | Should Have | Planned |

---

## Domain Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `JournalEntryCreatedEvent` | Entry created in draft | On create |
| `JournalEntryPostedEvent` | Entry posted to ledger | On post |
| `JournalEntryReversedEvent` | Entry reversed | On reverse |
| `JournalEntryDeletedEvent` | Draft entry deleted | On delete |
| `LedgerBalanceChangedEvent` | Account balance updated | On post/reverse |

---

## Package Dependencies

### Required
- PHP ^8.3
- PSR-3 Logger (psr/log)

### Optional (via Interface)
- `Nexus\ChartOfAccount` - Account validation
- `Nexus\Period` - Period validation
- `Nexus\Sequencing` - Entry number generation
- `Nexus\Currency` - Exchange rate provider
- `Nexus\AuditLogger` - Audit trail

---

## Metrics

| Metric | Target | Current |
|--------|--------|---------|
| Requirements Complete | 100% | 40% |
| Test Coverage | >80% | 0% |
| API Documentation | 100% | 30% |
