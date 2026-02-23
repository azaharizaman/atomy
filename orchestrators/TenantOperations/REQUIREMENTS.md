# TenantOperations Orchestrator - Requirements

> **Status:** Requirements Draft  
> **Version:** 1.0.0  
> **Date:** 2026-02-22

---

## 1. Executive Summary

### 1.1 Purpose

The **TenantOperations** orchestrator coordinates multi-tenant lifecycle management across foundation packages within the Nexus ERP system. As a non-business operational orchestrator (Layer-2), it provides infrastructure-level services that enable other operational orchestrators to function correctly in a multi-tenant environment.

### 1.2 Scope

TenantOperations manages:
- Tenant onboarding and lifecycle (create, suspend, activate, archive)
- Tenant-specific settings initialization
- Feature flag configuration per tenant
- Company structure setup per tenant
- Tenant activity audit trails
- Tenant impersonation for support operations

### 1.3 Value to Other Orchestrators

| Orchestrator | Value Provided |
|--------------|-----------------|
| **FinanceOperations** | Validates tenant has proper licensing for financial modules; ensures fiscal periods are configured |
| **HumanResourceOperations** | Validates organizational chart exists for tenant; ensures employee data isolation |
| **AccountingOperations** | Validates tenant hasChartOfAccount configured; ensures period closure permissions |
| **SalesOperations** | Validates tenant has sales configuration; ensures territory assignments |
| **ProcurementOperations** | Validates tenant has supplier management enabled; ensures approval workflows |
| **SupplyChainOperations** | Validates tenant has warehouse configurations; ensures inventory isolation |
| **CRMOperations** | Validates tenant has CRM modules enabled; ensures customer data isolation |
| **ComplianceOperations** | Validates tenant compliance requirements; ensures data residency settings |

---

## 2. Functional Requirements

### 2.1 Tenant Onboarding

#### FR-TO-001: Create New Tenant
The system MUST support creating a new tenant with the following attributes:
- Unique tenant identifier (UUID)
- Tenant code (business-facing identifier)
- Tenant name
- Domain/subdomain
- Status (active, suspended, archived)
- Created timestamp
- Owner/primary admin user

#### FR-TO-002: Initialize Tenant Settings
The system MUST initialize tenant-specific settings:
- Default currency
- Default language/locale
- Timezone
- Date format
- Fiscal year configuration

#### FR-TO-003: Configure Feature Flags
The system MUST configure default feature flags for new tenants:
- Module enablement (Finance, HR, Sales, etc.)
- Feature toggles based on tenant plan
- Beta features allocation

#### FR-TO-004: Setup Company Structure
The system MUST create default company structure:
- Default company entity
- Default department
- Default office location

#### FR-TO-005: Onboarding Validation
The system MUST validate prerequisites before onboarding:
- Tenant code uniqueness
- Domain uniqueness
- Valid admin user credentials
- Required settings availability

### 2.2 Tenant Lifecycle Management

#### FR-TO-010: Suspend Tenant
The system MUST support suspending a tenant:
- Disable all tenant user access
- Preserve tenant data
- Disable scheduled jobs
- Send notification to tenant admin

#### FR-TO-011: Activate Tenant
The system MUST support activating a suspended tenant:
- Re-enable user access
- Resume scheduled jobs
- Send notification to tenant admin

#### FR-TO-012: Archive Tenant
The system MUST support archiving an inactive tenant:
- Move data to archive storage
- Remove active configuration
- Retain audit trails for compliance

#### FR-TO-013: Delete Tenant
The system MUST support permanent tenant deletion:
- Verify no active subscriptions
- Export data for retention (if required)
- Permanent data removal

### 2.3 Tenant Impersonation

#### FR-TO-020: Start Impersonation
The system MUST support administrator impersonation:
- Verify administrator has impersonation permission
- Create impersonation session
- Log original admin identity
- Log impersonation start event

#### FR-TO-021: End Impersonation
The system MUST support ending impersonation:
- Restore original admin session
- Log impersonation end event
- Log all actions taken during impersonation

#### FR-TO-022: Impersonation Audit
The system MUST audit all impersonation actions:
- Record which admin impersonated which tenant
- Record all actions performed during impersonation
- Generate audit report for compliance

### 2.4 Tenant Validation (For Other Orchestrators)

#### FR-TO-030: Validate Tenant Active
Other orchestrators MUST be able to validate:
- Tenant is active (not suspended/archived)
- Tenant has valid subscription
- Tenant is not locked

#### FR-TO-031: Validate Tenant Modules
Other orchestrators MUST be able to validate:
- Required modules are enabled for tenant
- Required features are enabled for tenant
- User has access to required modules

#### FR-TO-032: Validate Tenant Configuration
Other orchestrators MUST be able to validate:
- Required settings are configured
- Company structure exists
- Fiscal periods are configured

### 2.5 Cross-Tenant Operations

#### FR-TO-040: Tenant Isolation Verification
The system MUST verify tenant data isolation:
- Ensure queries only return current tenant data
- Verify no cross-tenant data leakage
- Validate tenant context is always set

#### FR-TO-041: Tenant Context Management
The system MUST manage tenant context:
- Set tenant context for request lifecycle
- Clear tenant context at request end
- Handle tenant context in background jobs

---

## 3. Non-Functional Requirements

### 3.1 Performance

- Tenant lookup MUST complete within 50ms
- Tenant onboarding workflow MUST complete within 5 seconds
- Tenant impersonation start MUST complete within 100ms
- Tenant validation checks MUST be cached for performance

### 3.2 Scalability

- Support at least 1000 tenants per installation
- Support concurrent operations across multiple tenants
- Horizontal scaling support for tenant operations

### 3.3 Security

- Tenant data isolation MUST be enforced at database level
- Impersonation MUST require elevated permissions
- All tenant operations MUST be audit-logged
- Tenant API keys MUST be encrypted at rest

### 3.4 Reliability

- Tenant operations MUST be transactional
- Failed operations MUST be rollback-safe
- Tenant context MUST never leak between requests

---

## 4. Package Dependencies

### 4.1 Direct Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `nexus/tenant` | *@dev | Core tenant management |
| `nexus/setting` | *@dev | Tenant-specific settings |
| `nexus/feature-flags` | *@dev | Feature flag management |
| `nexus/backoffice` | *@dev | Company structure |
| `nexus/audit-logger` | *@dev | Audit trail logging |
| `nexus/identity` | *@dev | User authentication/authorization |
| `nexus/common` | *@dev | Shared utilities |

### 4.2 Dependency Flow

```
TenantOperations (L2)
    │
    ├──► Uses: Tenant Interfaces (via Adapter)
    ├──► Uses: Setting Interfaces (via Adapter)
    ├──► Uses: FeatureFlags Interfaces (via Adapter)
    ├──► Uses: Backoffice Interfaces (via Adapter)
    ├──► Uses: AuditLogger Interfaces (via Adapter)
    └──► Uses: Identity Interfaces (via Adapter)
```

---

## 5. ERP System Comparison

### 5.1 SAP S/4HANA

| Capability | SAP S/4HANA | TenantOperations | Notes |
|------------|-------------|------------------|-------|
| Multi-tenancy | ✅ Full | ✅ Implemented | SAP uses isolated tenant databases |
| Tenant onboarding | ✅ Via IMG | ✅ Via Workflow | Similar automated flow |
| Configuration templates | ✅ SAP Best Practices | ✅ Default configs | We provide sensible defaults |
| Audit trails | ✅ Audit Log | ✅ AuditLogger | Full action tracking |
| Impersonation | ✅ SU01 | ✅ Supported | Support admin debugging |

### 5.2 Oracle Cloud ERP

| Capability | Oracle Cloud | TenantOperations | Notes |
|------------|--------------|------------------|-------|
| Multi-tenancy | ✅ SaaS | ✅ Implemented | Similar isolation model |
| Setup data migration | ✅ Template-based | ✅ Default configs | Template-driven setup |
| Role assignments | ✅ Role provisioning | ✅ Via Identity | Role-based access |
| Compliance reporting | ✅ Embedded | ✅ Via AuditLogger | Full audit trail |

### 5.3 Microsoft Dynamics 365

| Capability | Dynamics 365 | TenantOperations | Notes |
|------------|--------------|------------------|-------|
| Multi-tenancy | ✅ Dataverse | ✅ Implemented | Similar tenant isolation |
| Organization hierarchy | ✅ Business Unit | ✅ Backoffice | Company structure |
| Impersonation | ✅ Admin mode | ✅ Supported | Support debugging |
| Audit | ✅ Dataverse Audit | ✅ AuditLogger | Full history |

### 5.4 Odoo

| Capability | Odoo | TenantOperations | Notes |
|-----------|------|------------------|-------|
| Multi-company | ✅ Database separation | ✅ Multi-tenancy | More granular isolation |
| Company-dependent | ✅ All documents | ✅ Tenant-scoped | Data isolation |
| Inter-company | ✅ Advanced | ✅ Not in scope | Business feature |

---

## 6. Coordinators and Workflows

### 6.1 Coordinators

| Coordinator | Responsibility | Key Methods |
|------------|----------------|-------------|
| `TenantOnboardingCoordinator` | Orchestrates new tenant creation | `onboard()`, `validatePrerequisites()` |
| `TenantLifecycleCoordinator` | Manages tenant state transitions | `suspend()`, `activate()`, `archive()`, `delete()` |
| `TenantImpersonationCoordinator` | Handles admin impersonation | `startImpersonation()`, `endImpersonation()` |
| `TenantValidationCoordinator` | Validates tenant for other orchestrators | `validateActive()`, `validateModules()`, `validateConfig()` |

### 6.2 DataProviders

| DataProvider | Purpose |
|--------------|---------|
| `TenantContextDataProvider` | Aggregates tenant context from multiple packages |
| `TenantSettingsDataProvider` | Retrieves tenant-specific settings |
| `TenantFeatureDataProvider` | Retrieves tenant feature flags |
| `TenantAuditDataProvider` | Retrieves tenant audit history |

### 6.3 Rules

| Rule | Purpose |
|------|---------|
| `TenantCodeUniqueRule` | Validates tenant code uniqueness |
| `TenantDomainUniqueRule` | Validates tenant domain uniqueness |
| `TenantActiveRule` | Validates tenant is active |
| `TenantModulesEnabledRule` | Validates required modules are enabled |
| `ImpersonationAllowedRule` | Validates admin can impersonate |

### 6.4 Services

| Service | Purpose |
|---------|---------|
| `TenantOnboardingService` | Handles complex onboarding logic |
| `TenantLifecycleService` | Handles state transitions |
| `TenantImpersonationService` | Handles impersonation sessions |
| `TenantValidationService` | Handles validation logic |

---

## 7. Interface Contracts

### 7.1 TenantOnboardingCoordinatorInterface

```php
interface TenantOnboardingCoordinatorInterface
{
    /**
     * Onboard a new tenant with all required configurations.
     */
    public function onboard(TenantOnboardingRequest $request): TenantOnboardingResult;

    /**
     * Validate prerequisites before onboarding.
     */
    public function validatePrerequisites(TenantOnboardingRequest $request): ValidationResult;
}
```

### 7.2 TenantLifecycleCoordinatorInterface

```php
interface TenantLifecycleCoordinatorInterface
{
    /**
     * Suspend a tenant.
     */
    public function suspend(TenantSuspendRequest $request): TenantSuspendResult;

    /**
     * Activate a suspended tenant.
     */
    public function activate(TenantActivateRequest $request): TenantActivateResult;

    /**
     * Archive a tenant.
     */
    public function archive(TenantArchiveRequest $request): TenantArchiveResult;

    /**
     * Delete a tenant permanently.
     */
    public function delete(TenantDeleteRequest $request): TenantDeleteResult;
}
```

### 7.3 TenantValidationCoordinatorInterface

```php
interface TenantValidationCoordinatorInterface
{
    /**
     * Validate tenant is active and has valid subscription.
     */
    public function validateActive(string $tenantId): TenantValidationResult;

    /**
     * Validate tenant has required modules enabled.
     */
    public function validateModules(ModulesValidationRequest $request): TenantValidationResult;

    /**
     * Validate tenant has required configurations.
     */
    public function validateConfiguration(ConfigurationValidationRequest $request): TenantValidationResult;
}
```

---

## 8. Acceptance Criteria

### 8.1 Tenant Onboarding

- [ ] Can create tenant with unique code and domain
- [ ] Default settings are initialized
- [ ] Feature flags are configured based on plan
- [ ] Company structure is created
- [ ] Validation prevents duplicate tenant codes
- [ ] Validation prevents duplicate domains

### 8.2 Tenant Lifecycle

- [ ] Can suspend tenant and disable access
- [ ] Can activate suspended tenant
- [ ] Can archive tenant and preserve data
- [ ] Notifications are sent on state changes

### 8.3 Impersonation

- [ ] Admin can start impersonation with permission
- [ ] All actions during impersonation are logged
- [ ] Impersonation can be ended by admin or timeout
- [ ] Original admin identity is preserved

### 8.4 Validation for Other Orchestrators

- [ ] FinanceOperations can validate tenant active
- [ ] HumanResourceOperations can validate organizational chart exists
- [ ] AccountingOperations can validate fiscal periods configured
- [ ] All validations are cached for performance

---

## 9. Future Considerations

### 9.1 Phase 2 Enhancements

- Tenant data export/import
- Tenant migration between environments
- Tenant usage analytics
- Multi-region tenant deployment

### 9.2 Future Integrations

- Tenant-specific theming/branding
- Tenant-specific workflow customization
- Tenant-specific approval hierarchies

---

## Appendix A: DTO Definitions

### TenantOnboardingRequest

```php
readonly class TenantOnboardingRequest
{
    public string $tenantCode;
    public string $tenantName;
    public string $domain;
    public string $adminEmail;
    public string $adminPassword;
    public string $plan; // 'starter', 'professional', 'enterprise'
    public ?string $currency;
    public ?string $timezone;
    public ?string $language;
}
```

### TenantOnboardingResult

```php
readonly class TenantOnboardingResult
{
    public bool $success;
    public ?string $tenantId;
    public ?string $adminUserId;
    public ?string $companyId;
    public array $issues = [];
    public ?string $message;
}
```

---

*Document Version: 1.0.0*  
*Last Updated: 2026-02-22*
