# tenancy-orchestration-laravel-testing Specification

## Purpose
TBD - created by archiving change canary-tenancy-laravel. Update Purpose after archive.
## Requirements
### Requirement: Tenant Onboarding Web Interface
The Laravel Canary App SHALL provide a web form at `/tenants/onboard` to trigger the tenant onboarding process using the `TenantOnboardingCoordinatorInterface`.

#### Scenario: Successful onboarding via Web
- **WHEN** user submits the onboarding form with valid data (code, name, email, password, domain, plan)
- **THEN** the system SHALL call `TenantOnboardingCoordinatorInterface::onboard`
- **AND** redirect to a success page displaying the generated tenant ID

### Requirement: Tenant Status Dashboard
The Laravel Canary App SHALL provide a dashboard at `/tenants/status` to visualize the readiness of the system and individual tenant states.

#### Scenario: View system readiness
- **WHEN** user visits `/tenants/status`
- **THEN** the system SHALL call `TenantReadinessChecker::check()`
- **AND** display a list of required adapters and their binding status (READY/MISSING)

### Requirement: Laravel Service Provider Wiring
The Laravel Canary App SHALL successfully wire the `TenancyOperations` orchestrator and its required adapters in a Laravel Service Provider.

#### Scenario: Verify service resolution
- **WHEN** a request is made to the onboarding form
- **THEN** Laravel's Service Container SHALL successfully resolve `TenantOnboardingCoordinatorInterface` with all its dependencies correctly injected.

