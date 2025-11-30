# Integration Guide: Accounting

Complete integration examples for Laravel and Symfony frameworks.

See `ACCOUNTING_IMPLEMENTATION_SUMMARY.md` in root `docs/` folder for comprehensive application layer implementation with:
- Database migrations for 3 tables
- Eloquent models (FinancialStatement, PeriodClose, ConsolidationEntry)
- Repository implementations
- Service provider configuration

---

## Quick Laravel Setup

1. Create migrations (3 tables)
2. Create Eloquent models implementing package interfaces
3. Create repository implementations
4. Bind in service provider
5. Use AccountingManager in services

**Full examples available in:** `/docs/ACCOUNTING_IMPLEMENTATION_SUMMARY.md` (Application Layer section)

---

## Quick Symfony Setup

1. Create Doctrine entities
2. Create repository implementations
3. Configure services.yaml
4. Use AccountingManager in controllers/services

---

For complete integration guide with code examples, see ACCOUNTING_IMPLEMENTATION_SUMMARY.md.
