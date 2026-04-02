# SourcingOperations (Nexus)

Layer 2 orchestrator for RFQ lifecycle coordination in the sourcing domain.

## Scope

- **In scope:** Tenant-scoped RFQ duplicate, draft save, bulk action, status transition, and invitation reminder coordination.
- **Out of scope:** Eloquent, Laravel controllers, HTTP validation, and synthetic response generation.

## Depends On

| Package | Role |
|---------|------|
| `nexus/sourcing` | RFQ lifecycle vocabulary, bulk-action allowlist, and transition policy contract |
| `nexus/vendor` | Shared vendor-related contracts used by the sourcing domain |

## Testing

```bash
cd orchestrators/SourcingOperations
composer install
./vendor/bin/phpunit
```

## Notes

- All coordinator methods require tenant-scoped ports.
- Duplicate copies the RFQ core record and line items only in the current Alpha slice.
- Bulk actions are limited to the Layer 1 allowlist (`close`, `cancel`).
- Invitation reminders must return real tenant-scoped results, not synthetic placeholders.
