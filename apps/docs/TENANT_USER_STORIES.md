# Tenant User Stories Compilation

**Package:** `Nexus\Tenant` v1.1.0  
**Purpose:** User stories for consuming applications implementing multi-tenancy features  
**Generated:** November 28, 2025  
**Scope:** Non-developer actors (excludes Developer user stories)

---

## Overview

This document compiles user stories derived from the `Nexus\Tenant` package capabilities for implementation in consuming applications (e.g., Laravel-based Nexus ERP). The stories are organized by actor type and feature area.

**Package Capabilities:**
- Multi-tenant context management with caching
- 5 tenant identification strategies (domain, subdomain, header, path, session)
- Tenant lifecycle management (create, activate, suspend, reactivate, archive, delete)
- Secure tenant impersonation with audit trails
- Hierarchical tenant support (parent-child relationships)
- Event-driven architecture (9 lifecycle events)
- CQRS-compliant split interfaces

**Actor Types (Excluding Developer):**
- **Platform Administrator** - System-wide platform management
- **Tenant Administrator** - Tenant-level configuration and management
- **Support Staff** - Customer support and tenant assistance
- **Billing Manager** - Subscription and financial operations

---

## User Stories Table

| Story ID | Actor | User Story | Feature Area | Navigation Menu | API Endpoints | Permission/Role | Feature Flags | Test Total | Test Passing |
|----------|-------|------------|--------------|-----------------|---------------|-----------------|---------------|------------|--------------|
| USE-TNT-0001 | Platform Administrator | As a Platform Administrator, I want to create a new tenant organization so that a new customer can start using the platform | Tenant Lifecycle | Platform > Tenants > Create | `POST /api/platform/tenants` | `platform.tenants.create` | `feature.tenant.creation` | 3 | 0 |
| USE-TNT-0002 | Platform Administrator | As a Platform Administrator, I want to view a list of all tenants so that I can manage the platform organizations | Tenant Management | Platform > Tenants > List | `GET /api/platform/tenants` | `platform.tenants.view` | - | 2 | 0 |
| USE-TNT-0003 | Platform Administrator | As a Platform Administrator, I want to search tenants by code, domain, or name so that I can quickly find specific organizations | Tenant Management | Platform > Tenants > Search | `GET /api/platform/tenants?search=` | `platform.tenants.view` | - | 3 | 0 |
| USE-TNT-0004 | Platform Administrator | As a Platform Administrator, I want to view tenant details so that I can see their configuration and status | Tenant Management | Platform > Tenants > {id} > Details | `GET /api/platform/tenants/{id}` | `platform.tenants.view` | - | 2 | 0 |
| USE-TNT-0005 | Platform Administrator | As a Platform Administrator, I want to update tenant information so that I can correct or modify organization details | Tenant Lifecycle | Platform > Tenants > {id} > Edit | `PUT /api/platform/tenants/{id}` | `platform.tenants.update` | - | 3 | 0 |
| USE-TNT-0006 | Platform Administrator | As a Platform Administrator, I want to activate a pending tenant so that the customer can start using the platform | Status Management | Platform > Tenants > {id} > Activate | `POST /api/platform/tenants/{id}/activate` | `platform.tenants.activate` | - | 3 | 0 |
| USE-TNT-0007 | Platform Administrator | As a Platform Administrator, I want to suspend a tenant so that access is temporarily blocked for policy violations or non-payment | Status Management | Platform > Tenants > {id} > Suspend | `POST /api/platform/tenants/{id}/suspend` | `platform.tenants.suspend` | - | 4 | 0 |
| USE-TNT-0008 | Platform Administrator | As a Platform Administrator, I want to provide a reason when suspending a tenant so that the suspension is documented and auditable | Status Management | Platform > Tenants > {id} > Suspend (Modal) | `POST /api/platform/tenants/{id}/suspend` | `platform.tenants.suspend` | - | 2 | 0 |
| USE-TNT-0009 | Platform Administrator | As a Platform Administrator, I want to reactivate a suspended tenant so that the customer can resume using the platform | Status Management | Platform > Tenants > {id} > Reactivate | `POST /api/platform/tenants/{id}/reactivate` | `platform.tenants.reactivate` | - | 3 | 0 |
| USE-TNT-0010 | Platform Administrator | As a Platform Administrator, I want to archive a tenant so that inactive organizations are soft-deleted with retention policy | Status Management | Platform > Tenants > {id} > Archive | `POST /api/platform/tenants/{id}/archive` | `platform.tenants.archive` | - | 3 | 0 |
| USE-TNT-0011 | Platform Administrator | As a Platform Administrator, I want to permanently delete an archived tenant so that data is purged after retention period | Status Management | Platform > Tenants > {id} > Delete | `DELETE /api/platform/tenants/{id}` | `platform.tenants.delete` | `feature.tenant.hard_delete` | 4 | 0 |
| USE-TNT-0012 | Platform Administrator | As a Platform Administrator, I want to restore an archived tenant so that accidentally archived organizations can be recovered | Status Management | Platform > Tenants > {id} > Restore | `POST /api/platform/tenants/{id}/restore` | `platform.tenants.restore` | - | 3 | 0 |
| USE-TNT-0013 | Platform Administrator | As a Platform Administrator, I want to see tenant status history so that I can audit status changes over time | Audit & History | Platform > Tenants > {id} > History | `GET /api/platform/tenants/{id}/history` | `platform.tenants.audit` | `feature.tenant.audit_history` | 2 | 0 |
| USE-TNT-0014 | Platform Administrator | As a Platform Administrator, I want to filter tenants by status so that I can see only active, suspended, or pending organizations | Tenant Management | Platform > Tenants > Filter | `GET /api/platform/tenants?status=` | `platform.tenants.view` | - | 4 | 0 |
| USE-TNT-0015 | Platform Administrator | As a Platform Administrator, I want to see expired trial tenants so that I can follow up on conversion or cleanup | Status Management | Platform > Tenants > Trials > Expired | `GET /api/platform/tenants/trials/expired` | `platform.tenants.view` | `feature.tenant.trials` | 2 | 0 |
| USE-TNT-0016 | Platform Administrator | As a Platform Administrator, I want to assign a custom domain to a tenant so that customers can use their own domain | Domain Management | Platform > Tenants > {id} > Domain | `PUT /api/platform/tenants/{id}/domain` | `platform.tenants.domain` | `feature.tenant.custom_domain` | 4 | 0 |
| USE-TNT-0017 | Platform Administrator | As a Platform Administrator, I want to validate domain uniqueness so that duplicate domains are prevented | Domain Management | Platform > Tenants > Validate Domain | `POST /api/platform/tenants/validate-domain` | `platform.tenants.domain` | - | 2 | 0 |
| USE-TNT-0018 | Platform Administrator | As a Platform Administrator, I want to configure subdomain for a tenant so that customers can access via tenant-specific URL | Domain Management | Platform > Tenants > {id} > Subdomain | `PUT /api/platform/tenants/{id}/subdomain` | `platform.tenants.domain` | `feature.tenant.subdomain` | 3 | 0 |
| USE-TNT-0019 | Platform Administrator | As a Platform Administrator, I want to set up hierarchical tenants so that parent organizations can manage child tenants | Hierarchy | Platform > Tenants > {id} > Children | `POST /api/platform/tenants/{id}/children` | `platform.tenants.hierarchy` | `feature.tenant.hierarchy` | 5 | 0 |
| USE-TNT-0020 | Platform Administrator | As a Platform Administrator, I want to view child tenants of a parent so that I can see the organizational structure | Hierarchy | Platform > Tenants > {id} > Children | `GET /api/platform/tenants/{id}/children` | `platform.tenants.view` | `feature.tenant.hierarchy` | 2 | 0 |
| USE-TNT-0021 | Platform Administrator | As a Platform Administrator, I want to move a tenant to a different parent so that organizational restructuring is supported | Hierarchy | Platform > Tenants > {id} > Move | `PUT /api/platform/tenants/{id}/parent` | `platform.tenants.hierarchy` | `feature.tenant.hierarchy` | 3 | 0 |
| USE-TNT-0022 | Platform Administrator | As a Platform Administrator, I want to see tenant statistics so that I can understand platform usage | Analytics | Platform > Dashboard > Tenant Stats | `GET /api/platform/tenants/statistics` | `platform.analytics.view` | `feature.tenant.analytics` | 3 | 0 |
| USE-TNT-0023 | Platform Administrator | As a Platform Administrator, I want to view tenant quotas so that I can see resource limits per organization | Quotas & Limits | Platform > Tenants > {id} > Quotas | `GET /api/platform/tenants/{id}/quotas` | `platform.tenants.quotas` | `feature.tenant.quotas` | 2 | 0 |
| USE-TNT-0024 | Platform Administrator | As a Platform Administrator, I want to configure tenant quotas so that resource limits are enforced | Quotas & Limits | Platform > Tenants > {id} > Quotas > Edit | `PUT /api/platform/tenants/{id}/quotas` | `platform.tenants.quotas.update` | `feature.tenant.quotas` | 3 | 0 |
| USE-TNT-0025 | Platform Administrator | As a Platform Administrator, I want to set identification strategy for a tenant so that the correct resolution method is used | Configuration | Platform > Tenants > {id} > Settings | `PUT /api/platform/tenants/{id}/identification-strategy` | `platform.tenants.settings` | - | 2 | 0 |
| USE-TNT-0026 | Support Staff | As a Support Staff, I want to impersonate a tenant so that I can see the customer's view for troubleshooting | Impersonation | Support > Impersonate | `POST /api/support/impersonate` | `support.impersonate` | `feature.tenant.impersonation` | 5 | 0 |
| USE-TNT-0027 | Support Staff | As a Support Staff, I want to provide a reason for impersonation so that all impersonation actions are audited | Impersonation | Support > Impersonate (Modal) | `POST /api/support/impersonate` | `support.impersonate` | `feature.tenant.impersonation` | 2 | 0 |
| USE-TNT-0028 | Support Staff | As a Support Staff, I want to stop impersonation so that I return to my original context | Impersonation | Header > Stop Impersonation | `POST /api/support/impersonate/stop` | `support.impersonate` | `feature.tenant.impersonation` | 3 | 0 |
| USE-TNT-0029 | Support Staff | As a Support Staff, I want to see an indicator when impersonating so that I know I'm acting on behalf of a customer | Impersonation | Header > Impersonation Badge | N/A (UI only) | `support.impersonate` | `feature.tenant.impersonation` | 1 | 0 |
| USE-TNT-0030 | Support Staff | As a Support Staff, I want to see the original tenant context during impersonation so that I can quickly reference it | Impersonation | Header > Original Context | `GET /api/support/impersonate/context` | `support.impersonate` | `feature.tenant.impersonation` | 2 | 0 |
| USE-TNT-0031 | Support Staff | As a Support Staff, I want to view impersonation history so that I can see my past support sessions | Audit & History | Support > Impersonation History | `GET /api/support/impersonate/history` | `support.impersonate.history` | `feature.tenant.impersonation` | 2 | 0 |
| USE-TNT-0032 | Support Staff | As a Support Staff, I want to search for a tenant to impersonate so that I can quickly find the customer | Impersonation | Support > Impersonate > Search | `GET /api/support/tenants?search=` | `support.impersonate` | `feature.tenant.impersonation` | 2 | 0 |
| USE-TNT-0033 | Support Staff | As a Support Staff, I want to see tenant suspension reason so that I can explain to customers why access is blocked | Status Management | Support > Tenant Details | `GET /api/support/tenants/{id}` | `support.tenants.view` | - | 1 | 0 |
| USE-TNT-0034 | Support Staff | As a Support Staff, I want to request tenant reactivation so that suspended customers can be escalated for review | Status Management | Support > Request Reactivation | `POST /api/support/tenants/{id}/request-reactivation` | `support.tenants.request` | - | 3 | 0 |
| USE-TNT-0035 | Tenant Administrator | As a Tenant Administrator, I want to view my organization settings so that I can see current configuration | Tenant Settings | Settings > Organization | `GET /api/tenant/settings` | `tenant.settings.view` | - | 2 | 0 |
| USE-TNT-0036 | Tenant Administrator | As a Tenant Administrator, I want to update organization name and details so that our profile is accurate | Tenant Settings | Settings > Organization > Edit | `PUT /api/tenant/settings` | `tenant.settings.update` | - | 3 | 0 |
| USE-TNT-0037 | Tenant Administrator | As a Tenant Administrator, I want to configure timezone and locale so that dates and numbers display correctly | Tenant Settings | Settings > Preferences | `PUT /api/tenant/settings/preferences` | `tenant.settings.update` | - | 2 | 0 |
| USE-TNT-0038 | Tenant Administrator | As a Tenant Administrator, I want to configure default currency so that financial data uses correct currency | Tenant Settings | Settings > Preferences | `PUT /api/tenant/settings/currency` | `tenant.settings.update` | - | 2 | 0 |
| USE-TNT-0039 | Tenant Administrator | As a Tenant Administrator, I want to configure date format so that dates display in our preferred format | Tenant Settings | Settings > Preferences | `PUT /api/tenant/settings/date-format` | `tenant.settings.update` | - | 2 | 0 |
| USE-TNT-0040 | Tenant Administrator | As a Tenant Administrator, I want to view current quota usage so that I can monitor resource consumption | Quotas & Limits | Settings > Usage | `GET /api/tenant/quotas/usage` | `tenant.quotas.view` | `feature.tenant.quotas` | 2 | 0 |
| USE-TNT-0041 | Tenant Administrator | As a Tenant Administrator, I want to receive notifications when approaching quota limits so that I can take action | Quotas & Limits | Notifications | N/A (Push/Email) | `tenant.notifications.view` | `feature.tenant.quotas` | 2 | 0 |
| USE-TNT-0042 | Tenant Administrator | As a Tenant Administrator, I want to request custom domain setup so that we can use our branded URL | Domain Management | Settings > Domain | `POST /api/tenant/domain/request` | `tenant.domain.request` | `feature.tenant.custom_domain` | 3 | 0 |
| USE-TNT-0043 | Tenant Administrator | As a Tenant Administrator, I want to see domain verification status so that I know if our custom domain is active | Domain Management | Settings > Domain | `GET /api/tenant/domain/status` | `tenant.domain.view` | `feature.tenant.custom_domain` | 2 | 0 |
| USE-TNT-0044 | Tenant Administrator | As a Tenant Administrator, I want to manage child organizations so that subsidiary companies can be administered | Hierarchy | Settings > Child Organizations | `GET /api/tenant/children` | `tenant.children.view` | `feature.tenant.hierarchy` | 2 | 0 |
| USE-TNT-0045 | Tenant Administrator | As a Tenant Administrator, I want to create child organization so that subsidiaries can have their own tenant | Hierarchy | Settings > Child Organizations > Create | `POST /api/tenant/children` | `tenant.children.create` | `feature.tenant.hierarchy` | 4 | 0 |
| USE-TNT-0046 | Tenant Administrator | As a Tenant Administrator, I want to inherit settings from parent organization so that consistency is maintained | Hierarchy | Settings > Inherited Settings | `GET /api/tenant/inherited-settings` | `tenant.settings.view` | `feature.tenant.hierarchy` | 2 | 0 |
| USE-TNT-0047 | Tenant Administrator | As a Tenant Administrator, I want to override parent settings where needed so that child organizations can customize | Hierarchy | Settings > Overrides | `PUT /api/tenant/settings/overrides` | `tenant.settings.update` | `feature.tenant.hierarchy` | 3 | 0 |
| USE-TNT-0048 | Billing Manager | As a Billing Manager, I want to view tenant subscription status so that I can verify active subscriptions | Subscription | Billing > Subscription | `GET /api/billing/subscription` | `billing.subscription.view` | - | 2 | 0 |
| USE-TNT-0049 | Billing Manager | As a Billing Manager, I want to see trial expiration date so that I can plan for conversion | Subscription | Billing > Trial Status | `GET /api/billing/trial-status` | `billing.subscription.view` | `feature.tenant.trials` | 2 | 0 |
| USE-TNT-0050 | Billing Manager | As a Billing Manager, I want to upgrade tenant from trial to paid so that the customer can continue using the platform | Subscription | Billing > Upgrade | `POST /api/billing/upgrade` | `billing.subscription.upgrade` | `feature.tenant.trials` | 4 | 0 |
| USE-TNT-0051 | Billing Manager | As a Billing Manager, I want to extend trial period so that prospects have more time to evaluate | Subscription | Billing > Extend Trial | `POST /api/billing/trial/extend` | `billing.trial.extend` | `feature.tenant.trials` | 3 | 0 |
| USE-TNT-0052 | Billing Manager | As a Billing Manager, I want to view suspension reason for billing so that I can resolve payment issues | Status Management | Billing > Suspension Details | `GET /api/billing/suspension-details` | `billing.suspension.view` | - | 2 | 0 |
| USE-TNT-0053 | Billing Manager | As a Billing Manager, I want to request reactivation after payment so that service is restored | Status Management | Billing > Request Reactivation | `POST /api/billing/request-reactivation` | `billing.reactivation.request` | - | 3 | 0 |

---

## Summary Statistics

### By Actor Type
| Actor | Story Count | Percentage |
|-------|-------------|------------|
| Platform Administrator | 25 | 47.2% |
| Support Staff | 9 | 17.0% |
| Tenant Administrator | 13 | 24.5% |
| Billing Manager | 6 | 11.3% |
| **Total** | **53** | **100%** |

### By Feature Area
| Feature Area | Story Count | Priority |
|--------------|-------------|----------|
| Tenant Lifecycle | 3 | Critical |
| Tenant Management | 4 | Critical |
| Status Management | 11 | High |
| Domain Management | 5 | Medium |
| Impersonation | 7 | High |
| Hierarchy | 7 | Medium |
| Tenant Settings | 6 | High |
| Quotas & Limits | 4 | Medium |
| Audit & History | 2 | Medium |
| Analytics | 1 | Low |
| Subscription | 6 | High |
| Configuration | 1 | Low |

### Test Summary
| Metric | Value |
|--------|-------|
| Total Test Cases | 137 |
| Tests Passing | 0 |
| Tests Failing | 0 |
| Coverage | 0% |
| Status | ⚠️ Tests Planned (Phase 4) |

---

## Package Components Used

### Contracts (9 interfaces)
| Interface | Purpose | Used By Stories |
|-----------|---------|-----------------|
| `TenantInterface` | Tenant entity contract | All stories |
| `TenantContextInterface` | Context management | USE-TNT-0026 to USE-TNT-0032 |
| `TenantPersistenceInterface` | Write operations (CQRS) | USE-TNT-0001, USE-TNT-0005 to USE-TNT-0012 |
| `TenantQueryInterface` | Read operations (CQRS) | USE-TNT-0002 to USE-TNT-0004, USE-TNT-0014 |
| `TenantValidationInterface` | Uniqueness validation | USE-TNT-0001, USE-TNT-0017 |
| `CacheRepositoryInterface` | Cache abstraction | All query operations |
| `EventDispatcherInterface` | Lifecycle events | All status change stories |
| `ImpersonationStorageInterface` | External state storage | USE-TNT-0026 to USE-TNT-0031 |
| `TenantRepositoryInterface` | Deprecated (backward compat) | Legacy implementations |

### Services (6 classes)
| Service | Purpose | Used By Stories |
|---------|---------|-----------------|
| `TenantLifecycleService` | CRUD and state management | USE-TNT-0001, USE-TNT-0005 to USE-TNT-0012 |
| `TenantContextManager` | Request-scoped context | All authenticated stories |
| `TenantResolverService` | Multi-strategy identification | USE-TNT-0016 to USE-TNT-0018, USE-TNT-0025 |
| `TenantImpersonationService` | Secure impersonation | USE-TNT-0026 to USE-TNT-0031 |
| `TenantStatusService` | Business logic filtering | USE-TNT-0014, USE-TNT-0015, USE-TNT-0022 |
| `TenantEventDispatcher` | Event dispatching | All status change stories |

### Enums (2 classes)
| Enum | Values | Used By Stories |
|------|--------|-----------------|
| `TenantStatus` | `PENDING`, `ACTIVE`, `TRIAL`, `SUSPENDED`, `ARCHIVED` | USE-TNT-0006 to USE-TNT-0015, USE-TNT-0049 to USE-TNT-0053 |
| `IdentificationStrategy` | `DOMAIN`, `SUBDOMAIN`, `HEADER`, `PATH`, `SESSION` | USE-TNT-0016 to USE-TNT-0018, USE-TNT-0025 |

### Events (9 classes)
| Event | Triggered By | Used By Stories |
|-------|--------------|-----------------|
| `TenantCreatedEvent` | `createTenant()` | USE-TNT-0001 |
| `TenantActivatedEvent` | `activateTenant()` | USE-TNT-0006 |
| `TenantSuspendedEvent` | `suspendTenant()` | USE-TNT-0007 |
| `TenantReactivatedEvent` | `reactivateTenant()` | USE-TNT-0009 |
| `TenantArchivedEvent` | `archiveTenant()` | USE-TNT-0010 |
| `TenantDeletedEvent` | `deleteTenant()` | USE-TNT-0011 |
| `TenantUpdatedEvent` | `updateTenant()` | USE-TNT-0005 |
| `ImpersonationStartedEvent` | `impersonate()` | USE-TNT-0026 |
| `ImpersonationEndedEvent` | `stopImpersonation()` | USE-TNT-0028 |

### Exceptions (8 classes)
| Exception | Scenario | Used By Stories |
|-----------|----------|-----------------|
| `TenantNotFoundException` | Tenant ID not found | USE-TNT-0004, USE-TNT-0005 |
| `TenantContextNotSetException` | No tenant in context | All authenticated stories |
| `TenantSuspendedException` | Access while suspended | USE-TNT-0033, USE-TNT-0052 |
| `DuplicateTenantCodeException` | Code already exists | USE-TNT-0001 |
| `DuplicateTenantDomainException` | Domain already exists | USE-TNT-0016, USE-TNT-0017 |
| `InvalidTenantStatusException` | Invalid status transition | USE-TNT-0006 to USE-TNT-0012 |
| `ImpersonationNotAllowedException` | Impersonation denied | USE-TNT-0026 |
| `InvalidIdentificationStrategyException` | Invalid strategy | USE-TNT-0025 |

### Value Objects (2 classes)
| Value Object | Properties | Used By Stories |
|--------------|------------|-----------------|
| `TenantSettings` | `timezone`, `locale`, `currency`, `dateFormat`, `timeFormat`, `metadata` | USE-TNT-0037 to USE-TNT-0039 |
| `TenantQuota` | `maxUsers`, `maxStorage`, `maxApiCalls`, `metadata` | USE-TNT-0023, USE-TNT-0024, USE-TNT-0040, USE-TNT-0041 |

---

## Feature Flags Reference

| Flag Key | Description | Default | Stories Affected |
|----------|-------------|---------|------------------|
| `feature.tenant.creation` | Enable tenant creation | `true` | USE-TNT-0001 |
| `feature.tenant.hard_delete` | Enable permanent deletion | `false` | USE-TNT-0011 |
| `feature.tenant.audit_history` | Enable audit history | `true` | USE-TNT-0013 |
| `feature.tenant.trials` | Enable trial subscriptions | `true` | USE-TNT-0015, USE-TNT-0049 to USE-TNT-0051 |
| `feature.tenant.custom_domain` | Enable custom domains | `false` | USE-TNT-0016, USE-TNT-0042, USE-TNT-0043 |
| `feature.tenant.subdomain` | Enable subdomain resolution | `true` | USE-TNT-0018 |
| `feature.tenant.hierarchy` | Enable parent-child tenants | `false` | USE-TNT-0019 to USE-TNT-0021, USE-TNT-0044 to USE-TNT-0047 |
| `feature.tenant.analytics` | Enable tenant analytics | `true` | USE-TNT-0022 |
| `feature.tenant.quotas` | Enable quota management | `true` | USE-TNT-0023, USE-TNT-0024, USE-TNT-0040, USE-TNT-0041 |
| `feature.tenant.impersonation` | Enable support impersonation | `true` | USE-TNT-0026 to USE-TNT-0032 |

---

## Permissions Reference

### Platform Administrator Permissions
| Permission | Description | Stories |
|------------|-------------|---------|
| `platform.tenants.create` | Create new tenants | USE-TNT-0001 |
| `platform.tenants.view` | View tenant list and details | USE-TNT-0002 to USE-TNT-0004, USE-TNT-0014, USE-TNT-0015, USE-TNT-0020 |
| `platform.tenants.update` | Update tenant information | USE-TNT-0005 |
| `platform.tenants.activate` | Activate pending tenants | USE-TNT-0006 |
| `platform.tenants.suspend` | Suspend active tenants | USE-TNT-0007, USE-TNT-0008 |
| `platform.tenants.reactivate` | Reactivate suspended tenants | USE-TNT-0009 |
| `platform.tenants.archive` | Archive (soft delete) tenants | USE-TNT-0010 |
| `platform.tenants.restore` | Restore archived tenants | USE-TNT-0012 |
| `platform.tenants.delete` | Permanently delete tenants | USE-TNT-0011 |
| `platform.tenants.audit` | View tenant audit history | USE-TNT-0013 |
| `platform.tenants.domain` | Manage tenant domains | USE-TNT-0016 to USE-TNT-0018 |
| `platform.tenants.hierarchy` | Manage tenant hierarchy | USE-TNT-0019 to USE-TNT-0021 |
| `platform.tenants.quotas` | View tenant quotas | USE-TNT-0023 |
| `platform.tenants.quotas.update` | Update tenant quotas | USE-TNT-0024 |
| `platform.tenants.settings` | Manage tenant settings | USE-TNT-0025 |
| `platform.analytics.view` | View platform analytics | USE-TNT-0022 |

### Support Staff Permissions
| Permission | Description | Stories |
|------------|-------------|---------|
| `support.impersonate` | Impersonate tenant for support | USE-TNT-0026 to USE-TNT-0030, USE-TNT-0032 |
| `support.impersonate.history` | View impersonation history | USE-TNT-0031 |
| `support.tenants.view` | View tenant details for support | USE-TNT-0033 |
| `support.tenants.request` | Request tenant status changes | USE-TNT-0034 |

### Tenant Administrator Permissions
| Permission | Description | Stories |
|------------|-------------|---------|
| `tenant.settings.view` | View organization settings | USE-TNT-0035, USE-TNT-0046 |
| `tenant.settings.update` | Update organization settings | USE-TNT-0036 to USE-TNT-0039, USE-TNT-0047 |
| `tenant.quotas.view` | View quota usage | USE-TNT-0040 |
| `tenant.notifications.view` | View quota notifications | USE-TNT-0041 |
| `tenant.domain.request` | Request custom domain | USE-TNT-0042 |
| `tenant.domain.view` | View domain status | USE-TNT-0043 |
| `tenant.children.view` | View child organizations | USE-TNT-0044 |
| `tenant.children.create` | Create child organizations | USE-TNT-0045 |

### Billing Manager Permissions
| Permission | Description | Stories |
|------------|-------------|---------|
| `billing.subscription.view` | View subscription status | USE-TNT-0048, USE-TNT-0049 |
| `billing.subscription.upgrade` | Upgrade subscription | USE-TNT-0050 |
| `billing.trial.extend` | Extend trial period | USE-TNT-0051 |
| `billing.suspension.view` | View suspension details | USE-TNT-0052 |
| `billing.reactivation.request` | Request reactivation | USE-TNT-0053 |

---

## Navigation Menu Structure

### Platform Administration
```
Platform
├── Dashboard
│   └── Tenant Statistics
├── Tenants
│   ├── List All
│   ├── Create New
│   ├── Search
│   └── Filter by Status
│       ├── Active
│       ├── Pending
│       ├── Trial
│       ├── Suspended
│       └── Archived
└── Trials
    └── Expired Trials
```

### Tenant Details (Platform View)
```
Tenant: {name}
├── Overview
│   ├── Details
│   ├── Status
│   └── History
├── Domain
│   ├── Custom Domain
│   └── Subdomain
├── Hierarchy
│   └── Child Organizations
├── Quotas
│   └── Usage & Limits
├── Settings
│   └── Identification Strategy
└── Actions
    ├── Activate
    ├── Suspend
    ├── Reactivate
    ├── Archive
    ├── Restore
    └── Delete
```

### Support Panel
```
Support
├── Impersonate
│   ├── Search Tenant
│   └── Active Sessions
├── Impersonation History
└── Tenant Details
    └── Request Reactivation
```

### Tenant Settings (Tenant View)
```
Settings
├── Organization
│   ├── Profile
│   └── Edit Details
├── Preferences
│   ├── Timezone & Locale
│   ├── Currency
│   └── Date Format
├── Domain
│   ├── Request Custom Domain
│   └── Verification Status
├── Usage
│   ├── Quota Usage
│   └── Notifications
└── Child Organizations (if enabled)
    ├── List
    ├── Create
    └── Inherited Settings
```

### Billing Panel
```
Billing
├── Subscription
│   ├── Status
│   ├── Trial Status
│   └── Upgrade
├── Trial
│   └── Extend Period
└── Suspension
    ├── Details
    └── Request Reactivation
```

---

## API Endpoints Summary

### Platform Administration Endpoints
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| `GET` | `/api/platform/tenants` | List all tenants | `platform.tenants.view` |
| `POST` | `/api/platform/tenants` | Create tenant | `platform.tenants.create` |
| `GET` | `/api/platform/tenants/{id}` | Get tenant details | `platform.tenants.view` |
| `PUT` | `/api/platform/tenants/{id}` | Update tenant | `platform.tenants.update` |
| `DELETE` | `/api/platform/tenants/{id}` | Delete tenant | `platform.tenants.delete` |
| `POST` | `/api/platform/tenants/{id}/activate` | Activate tenant | `platform.tenants.activate` |
| `POST` | `/api/platform/tenants/{id}/suspend` | Suspend tenant | `platform.tenants.suspend` |
| `POST` | `/api/platform/tenants/{id}/reactivate` | Reactivate tenant | `platform.tenants.reactivate` |
| `POST` | `/api/platform/tenants/{id}/archive` | Archive tenant | `platform.tenants.archive` |
| `POST` | `/api/platform/tenants/{id}/restore` | Restore tenant | `platform.tenants.restore` |
| `GET` | `/api/platform/tenants/{id}/history` | Get audit history | `platform.tenants.audit` |
| `GET` | `/api/platform/tenants/{id}/children` | Get child tenants | `platform.tenants.view` |
| `POST` | `/api/platform/tenants/{id}/children` | Create child tenant | `platform.tenants.hierarchy` |
| `PUT` | `/api/platform/tenants/{id}/parent` | Move to parent | `platform.tenants.hierarchy` |
| `PUT` | `/api/platform/tenants/{id}/domain` | Set custom domain | `platform.tenants.domain` |
| `PUT` | `/api/platform/tenants/{id}/subdomain` | Set subdomain | `platform.tenants.domain` |
| `POST` | `/api/platform/tenants/validate-domain` | Validate domain | `platform.tenants.domain` |
| `GET` | `/api/platform/tenants/{id}/quotas` | Get tenant quotas | `platform.tenants.quotas` |
| `PUT` | `/api/platform/tenants/{id}/quotas` | Update quotas | `platform.tenants.quotas.update` |
| `PUT` | `/api/platform/tenants/{id}/identification-strategy` | Set strategy | `platform.tenants.settings` |
| `GET` | `/api/platform/tenants/statistics` | Get statistics | `platform.analytics.view` |
| `GET` | `/api/platform/tenants/trials/expired` | Get expired trials | `platform.tenants.view` |

### Support Endpoints
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| `POST` | `/api/support/impersonate` | Start impersonation | `support.impersonate` |
| `POST` | `/api/support/impersonate/stop` | Stop impersonation | `support.impersonate` |
| `GET` | `/api/support/impersonate/context` | Get impersonation context | `support.impersonate` |
| `GET` | `/api/support/impersonate/history` | Get impersonation history | `support.impersonate.history` |
| `GET` | `/api/support/tenants` | Search tenants | `support.impersonate` |
| `GET` | `/api/support/tenants/{id}` | Get tenant details | `support.tenants.view` |
| `POST` | `/api/support/tenants/{id}/request-reactivation` | Request reactivation | `support.tenants.request` |

### Tenant Endpoints
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| `GET` | `/api/tenant/settings` | Get organization settings | `tenant.settings.view` |
| `PUT` | `/api/tenant/settings` | Update organization | `tenant.settings.update` |
| `PUT` | `/api/tenant/settings/preferences` | Update preferences | `tenant.settings.update` |
| `PUT` | `/api/tenant/settings/currency` | Update currency | `tenant.settings.update` |
| `PUT` | `/api/tenant/settings/date-format` | Update date format | `tenant.settings.update` |
| `PUT` | `/api/tenant/settings/overrides` | Override parent settings | `tenant.settings.update` |
| `GET` | `/api/tenant/quotas/usage` | Get quota usage | `tenant.quotas.view` |
| `GET` | `/api/tenant/children` | Get child organizations | `tenant.children.view` |
| `POST` | `/api/tenant/children` | Create child organization | `tenant.children.create` |
| `GET` | `/api/tenant/inherited-settings` | Get inherited settings | `tenant.settings.view` |
| `POST` | `/api/tenant/domain/request` | Request custom domain | `tenant.domain.request` |
| `GET` | `/api/tenant/domain/status` | Get domain status | `tenant.domain.view` |

### Billing Endpoints
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| `GET` | `/api/billing/subscription` | Get subscription status | `billing.subscription.view` |
| `GET` | `/api/billing/trial-status` | Get trial status | `billing.subscription.view` |
| `POST` | `/api/billing/upgrade` | Upgrade to paid | `billing.subscription.upgrade` |
| `POST` | `/api/billing/trial/extend` | Extend trial | `billing.trial.extend` |
| `GET` | `/api/billing/suspension-details` | Get suspension details | `billing.suspension.view` |
| `POST` | `/api/billing/request-reactivation` | Request reactivation | `billing.reactivation.request` |

---

## Implementation Priority

### Phase 1: Critical (Must Have)
1. Tenant creation and basic CRUD
2. Status management (activate, suspend, reactivate)
3. Tenant context management
4. Basic tenant resolution (domain/subdomain)

### Phase 2: High Priority
1. Impersonation for support
2. Tenant settings management
3. Trial subscription management
4. Suspension workflow

### Phase 3: Medium Priority
1. Hierarchical tenants
2. Custom domain management
3. Quota management
4. Audit history

### Phase 4: Nice to Have
1. Advanced analytics
2. Bulk operations
3. Tenant backup/restore
4. Advanced reporting

---

## Dependencies

### First-Party Nexus Packages
| Package | Purpose | Stories Affected |
|---------|---------|------------------|
| `Nexus\Domain\Identity` | User authentication and RBAC | All authenticated stories |
| `Nexus\AuditLogger` | Audit trail logging | USE-TNT-0013, USE-TNT-0031 |
| `Nexus\Notifier` | Notification delivery | USE-TNT-0041 |
| `Nexus\FeatureFlags` | Feature flag management | All stories with feature flags |
| `Nexus\Setting` | Application settings | USE-TNT-0035 to USE-TNT-0039 |

### External Integrations
| Integration | Purpose | Stories Affected |
|-------------|---------|------------------|
| Billing Provider (Stripe, etc.) | Subscription management | USE-TNT-0048 to USE-TNT-0053 |
| DNS Provider | Domain verification | USE-TNT-0016, USE-TNT-0042, USE-TNT-0043 |
| Email Service | Notifications | USE-TNT-0041 |

---

## Notes

### Test Coverage Status
The `Nexus\Tenant` package currently has **0% test coverage** (tests planned for Phase 4 of package development). The test totals in the user stories table represent **planned tests** for application-layer implementation.

### Architectural Compliance
- Package follows **ISP** (Interface Segregation Principle) with split interfaces
- Package follows **CQRS** (Command Query Responsibility Segregation)
- All services are **stateless** except `TenantContextManager` (request-scoped)
- Package is **framework-agnostic** with zero Laravel dependencies

### Backward Compatibility
- `TenantRepositoryInterface` is deprecated but maintained for backward compatibility
- Migration path provided in `REFACTORING_SUMMARY.md`
- Planned removal in v2.0 (6 months)

---

**Document Version:** 1.0  
**Last Updated:** November 28, 2025  
**Author:** Nexus Architecture Team
