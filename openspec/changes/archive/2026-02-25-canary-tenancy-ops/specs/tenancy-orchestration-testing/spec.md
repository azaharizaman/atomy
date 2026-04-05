## ADDED Requirements

### Requirement: Tenant Onboarding CLI Command
The Canary App SHALL provide a CLI command `tenant:onboard` to trigger the tenant onboarding process using the `TenantOnboardingCoordinatorInterface`.

#### Scenario: Successful onboarding via CLI
- **WHEN** user executes `bin/console tenant:onboard --code="ACME" --name="Acme Corp" --email="admin@acme.com" --password="password" --domain="acme.nexus.test" --plan="starter"`
- **THEN** the system SHALL call `TenantOnboardingCoordinatorInterface::onboard` with the provided details
- **AND** display a success message with the generated tenant ID

### Requirement: Tenant Status CLI Command
The Canary App SHALL provide a CLI command `tenant:status` to check the current readiness and state of a tenant.

#### Scenario: Check tenant readiness
- **WHEN** user executes `bin/console tenant:status --code="ACME"`
- **THEN** the system SHALL call `TenantReadinessChecker` (or relevant service) to verify the tenant's configuration
- **AND** display the readiness status (e.g., READY, INCOMPLETE, SUSPENDED)

### Requirement: Service Wiring in Symfony
The Canary App SHALL successfully wire the `TenancyOperations` orchestrator and its dependencies in a Symfony-compatible service container.

#### Scenario: Verify orchestrator injection
- **WHEN** the Canary App container is compiled
- **THEN** the `TenantOnboardingCoordinatorInterface` SHALL be resolvable and injected with a concrete implementation from Layer 2
- **AND** all required adapters (Layer 3) SHALL be correctly injected into the orchestrator
