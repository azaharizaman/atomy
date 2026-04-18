# Alpha Scope

## Supported Alpha Flow

The Atomy-Q alpha scope is one live buyer-side workflow:

1. Register a tenant company or log in as a tenant user.
2. Create, list, and open RFQs.
3. Add RFQ line items and schedule fields.
4. Invite vendors.
5. Upload or record vendor quote submissions.
6. Normalize quote source lines through the supported deterministic alpha intelligence path.
7. Resolve normalization blockers.
8. Freeze a final comparison run.
9. Review comparison matrix and evidence.
10. Create and sign off an award.
11. Read the decision trail for comparison and award activity.

## Applications In Scope

- `apps/atomy-q/API`
  Laravel API for auth, RFQ lifecycle, quote intake, normalization, comparison, awards, decision trail, and minimal users/roles.
- `apps/atomy-q/WEB`
  Next.js frontend for the same alpha workflow with live-mode fail-loud behavior.

## Nexus Dependencies In Scope

Layer 1:
- `packages/Sourcing`
- `packages/Vendor`
- `packages/Identity`
- `packages/Tenant`
- `packages/Idempotency`
- `packages/PolicyEngine`
- `packages/Notifier`

Layer 2:
- `orchestrators/SourcingOperations`
- `orchestrators/QuoteIngestion`
- `orchestrators/QuotationIntelligence`
- `orchestrators/IdentityOperations`
- `orchestrators/TenantOperations`
- `orchestrators/ApprovalOperations`
- `orchestrators/ProjectManagementOperations`

Layer 3:
- `adapters/Laravel/Sourcing`
- `adapters/Laravel/Vendor`
- `adapters/Laravel/Identity`
- `adapters/Laravel/Tenant`
- `adapters/Laravel/Idempotency`
- `adapters/Laravel/Notifier`
- Atomy-Q API app-local adapters in `apps/atomy-q/API/app`

## Alpha Integrity Rules

Alpha is blocked if the supported flow requires:
- `NEXT_PUBLIC_USE_MOCKS=true`
- seed fallback in live mode
- hardcoded success payloads
- manual database edits to advance the workflow
