# Changelog

All notable changes to the Canary Atomy API app will be documented in this file.

## [2026-03-01] - Identity Operations Implementation

### Added
- **Identity Management**: Full implementation of `IdentityOperations` orchestrator capabilities.
  - New API Resources: `/auth/login`, `/auth/refresh`, `/auth/logout`.
  - Operations: Authenticate (JWT issuance), Token Refresh, Session Validation.
  - Lifecycle Management: `/users/{id}/suspend`, `/users/{id}/activate`.
- **RBAC Foundation**: 
  - Implementation of `UserPermissionAdapter` for role and permission checking.
  - Role-based access control readiness with cross-tenant permission inheritance support.
- **MFA Readiness**: 
  - Implementation of `MfaAdapter` (mock) for MFA enrollment and verification.
  - Support for MFA-enabled flag in feature flags.
- **Adapters**:
  - Implemented `AuthenticatorAdapter` using Symfony's `UserPasswordHasherInterface`.
  - Implemented `TokenManagerAdapter` using `lcobucci/jwt` for secure token management and PSR-6 cache for session storage.
  - Implemented `UserContextAdapter` for unified user context across operations.
  - Implemented `UserOnboardingAdapter` for user creation and tenant assignment.
  - Implemented `UserStateManagerAdapter` for lifecycle transitions.
  - Implemented `UserAuditLoggerAdapter` for security auditing.
- **Test Data**:
  - Extended `AppFixtures` with diverse users across multiple tenants (Tony Stark, Bruce Wayne, etc.).
  - Added user statuses (Active, Pending Activation, Suspended) for testing lifecycle management.

### Changed
- Updated `nexus.yaml` with service mappings for `IdentityOperations` and related adapters.
- Fixed `orchestrators/IdentityOperations` bugs where service implementations did not match contract interfaces.
- Fixed `Setting` entity ORM mapping to match the database schema (`key` column).

### Security
- Centralized authentication with JWT issuance.
- Multi-factor authentication readiness.
- Auditing of all security-sensitive operations (login, logout, password change, status change).

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
