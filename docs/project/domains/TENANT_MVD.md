# Tenant Domain: Minimum Viable Domain (MVD)

This document defines the minimum set of interfaces and configurations required for a Layer 3 application (Adapter) to be considered "Tenant Ready" within the Nexus ecosystem.

## 📦 Required Layer 1 Interfaces (Persistence)

For the `Nexus\Tenant` package to function, the following must be implemented in L3:

- `Nexus\Tenant\Contracts\TenantRepositoryInterface`: Source of truth for tenant lookups.
- `Nexus\Tenant\Contracts\TenantPersistenceInterface`: Write model for tenant lifecycle.
- `Nexus\Tenant\Contracts\TenantInterface`: The data entity (usually implemented by an Eloquent/Doctrine model).

## 🧩 Required Orchestrator Adapters (Layer 2)

The `TenantOperations` orchestrator requires the following L3 adapters to be bound in the DI container:

- `Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface`
- `Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface`
- `Nexus\TenantOperations\Contracts\CompanyCreatorAdapterInterface`
- `Nexus\TenantOperations\Contracts\SettingsInitializerAdapterInterface`
- `Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface`
- `Nexus\TenantOperations\Contracts\AuditLoggerAdapterInterface`

## ⚙️ Mandatory Configurations

- `tenant.status`: Valid statuses must be `pending`, `active`, `suspended`, `archived`.
- `tenant.default_plan`: A fallback plan for new onboarding.

## 🔍 Readiness Check

The `TenantReadinessChecker` service in `TenantOperations` can be used to programmatically verify if the current application environment satisfies these requirements.
