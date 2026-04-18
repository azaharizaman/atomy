# 03 Domains

This directory documents the product domains used by Atomy-Q.

## Domain Documentation Standard

Each domain should be documented with the same file set and section order:

- `overview.md` - purpose, scope, core entities, inputs, outputs, and dependencies
- `workflows.md` - operational flows, written as numbered workflows with triggers, steps, outputs, failure handling, and involved domains
- `entities.md` - business entities with property tables, relationships, business rules, and related docs
- `lifecycle.md` - state model and transitions, or an explicit note explaining why the domain does not have a meaningful lifecycle machine yet

Use the tenant-and-company docs as the canonical layout for structure and tone:

- start with the business purpose
- describe the current implementation surface, not just the ideal model
- separate business behavior from persistence details
- cross-link related docs inside the same domain folder
- note stubbed or deferred behavior explicitly instead of implying it is already finished
- if a domain does not have a real lifecycle machine, keep `lifecycle.md` as a short, honest note that says so instead of inventing one

## Current Domain Set

| Domain | Overview | Workflows | Entities | Lifecycle | Notes |
|---|---|---|---|---|---|
| Approvals | [overview](./approvals/overview.md) | [workflows](./approvals/workflows.md) | [entities](./approvals/entities.md) | [lifecycle](./approvals/lifecycle.md) | RFQ-facing approval queue and decision history. |
| Auth | [overview](./auth/overview.md) | [workflows](./auth/workflows.md) | [entities](./auth/entities.md) | [lifecycle](./auth/lifecycle.md) | Company registration, login, MFA, refresh, logout. |
| Awards | [overview](./awards/overview.md) | [workflows](./awards/workflows.md) | [entities](./awards/entities.md) | [lifecycle](./awards/lifecycle.md) | Final award, debrief, protest, and signoff. |
| Comparison | [overview](./comparison/overview.md) | [workflows](./comparison/workflows.md) | [entities](./comparison/entities.md) | [lifecycle](./comparison/lifecycle.md) | Preview/final comparison runs and frozen snapshots. |
| Normalization | [overview](./normalization/overview.md) | [workflows](./normalization/workflows.md) | [entities](./normalization/entities.md) | [lifecycle](./normalization/lifecycle.md) | Source-line mapping, overrides, conflicts, readiness. |
| Projects and Tasks | [overview](./projects-and-tasks/overview.md) | [workflows](./projects-and-tasks/workflows.md) | [entities](./projects-and-tasks/entities.md) | [lifecycle](./projects-and-tasks/lifecycle.md) | Feature-flagged project/task management surface. |
| Quote Intake | [overview](./quote-intake/overview.md) | [workflows](./quote-intake/workflows.md) | [entities](./quote-intake/entities.md) | [lifecycle](./quote-intake/lifecycle.md) | Upload, parse, reparse, and quote readiness. |
| RFQ | [overview](./rfq/overview.md) | [workflows](./rfq/workflows.md) | [entities](./rfq/entities.md) | [lifecycle](./rfq/lifecycle.md) | Core sourcing record, line items, invitations, and draft/save. |
| Tenant and Company | [overview](./tenant-and-company/overview.md) | [workflows](./tenant-and-company/workflows.md) | [entities](./tenant-and-company/entities.md) | [lifecycle](./tenant-and-company/lifecycle.md) | Workspace bootstrap and first admin session. |
| Users and Roles | [overview](./users-and-roles/overview.md) | [workflows](./users-and-roles/workflows.md) | [entities](./users-and-roles/entities.md) | [lifecycle](./users-and-roles/lifecycle.md) | Tenant user admin surface with deferred governance routes. |
| Vendors | [overview](./vendors/overview.md) | [workflows](./vendors/workflows.md) | [entities](./vendors/entities.md) | [lifecycle](./vendors/lifecycle.md) | Buyer-side vendor reference data and derived metrics. |

## Supporting References

- [Cross-domain rules](./CROSS_DOMAIN_RULES.md)
- [Domain map](./DOMAIN_MAP.md)
- [Glossary](./GLOSSARY.md)
