# Changelog

All notable changes to the Canary Atomy API app will be documented in this file.

## [2026-02-28] - Full Orchestrator Implementation

### Added
- **Tenant Management**: Full implementation of `TenantOperations` orchestrator.
  - New API Resource: `/api/tenants`
  - Operations: List, Get, Create (Onboard), Suspend, Activate, Archive, Delete.
  - Orchestration of tenant creation, admin user setup, settings initialization, and feature configuration.
- **Settings Management**: Full implementation of `SettingsManagement` orchestrator.
  - Enhanced `/api/settings`: Support for single update (PATCH) and bulk update (POST).
  - Enhanced `/api/feature-flags`: Support for single update (PATCH) and hierarchy-aware resolution.
- **Fiscal Period Management**:
  - New API Resource: `/api/fiscal-periods`
  - Operations: List, Get, Open, Close.
  - Integration with `SettingsManagement` orchestrator for period state transitions.
- **Adapters**:
  - Implemented `TenantCreatorAdapter`, `AdminCreatorAdapter`, `SettingsInitializerAdapter`, `FeatureConfiguratorAdapter`, `AuditLoggerAdapter`.
  - Implemented `SettingsProvider`, `FeatureFlagProvider`, `FiscalPeriodProvider` for orchestrator-to-package bridging.

### Changed
- Refactored `SettingCollectionProvider` and `FeatureFlagCollectionProvider` to use orchestrators instead of direct package repositories, ensuring proper hierarchy resolution and multi-tenant scoping.
- Updated `nexus.yaml` with comprehensive service mappings for all orchestrators and adapters.

### Security
- Applied strict multi-tenant scoping to all new and updated resources.
- Audited all orchestrator interactions to ensure they follow the project's security mandates.
