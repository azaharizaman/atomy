# Nexus Dependencies

This file is the canonical Atomy-Q dependency map to Nexus first-party packages, orchestrators, and adapters.

## Core Dependencies By Layer

### Layer 1 Packages

- `packages/Sourcing`
  RFQ lifecycle, quote, comparison, and award domain primitives.
- `packages/Vendor`
  Vendor profile contracts used by buyer-side vendor workflows.
- `packages/Identity`
  Auth, sessions, RBAC, MFA, and user lifecycle primitives.
- `packages/Tenant`
  Tenant lifecycle and tenant context primitives.
- `packages/Idempotency`
  Mutation safety for write paths.
- `packages/PolicyEngine`
  Operational approval policy logic.
- `packages/Notifier`
  Notification abstractions used in workflow delivery.
- `packages/Document`
  Quote-upload and related document primitives where needed.
- `packages/MachineLearning`
  Shared intelligence-facing primitives used by normalization-related work.
- `packages/Project`
- `packages/Task`

### Layer 2 Orchestrators

- `orchestrators/SourcingOperations`
- `orchestrators/QuoteIngestion`
- `orchestrators/QuotationIntelligence`
- `orchestrators/IdentityOperations`
- `orchestrators/TenantOperations`
- `orchestrators/ApprovalOperations`
- `orchestrators/ProjectManagementOperations`
- `orchestrators/SettingsManagement` when configuration/read surfaces require it

### Layer 3 Adapters

- `adapters/Laravel/Sourcing`
- `adapters/Laravel/Vendor`
- `adapters/Laravel/Identity`
- `adapters/Laravel/Tenant`
- `adapters/Laravel/Idempotency`
- `adapters/Laravel/Notifier`
- `adapters/Laravel/ApprovalOperations`
- `adapters/Laravel/ProjectManagementOperations`

## Atomy-Q App-Local Integration

The Atomy-Q API also owns app-local Laravel integration in:
- `apps/atomy-q/API/app/Http/Controllers/Api/V1`
- `apps/atomy-q/API/app/Providers`
- `apps/atomy-q/API/app/Adapters`
- `apps/atomy-q/API/app/Services`

The WEB application consumes the API through:
- generated API client in `apps/atomy-q/WEB/src/generated/api`
- live transport helpers in `apps/atomy-q/WEB/src/lib`
- TanStack Query hooks in `apps/atomy-q/WEB/src/hooks`
