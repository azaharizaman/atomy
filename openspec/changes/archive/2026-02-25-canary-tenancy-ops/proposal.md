## Why

Currently, the `TenancyOperations` orchestrator and the `Tenant` atomic package lack a dedicated, real-world integration testbed within a Symfony environment. A canary application is needed to verify the end-to-end wiring, dependency injection, and functional correctness of tenant lifecycle operations (onboarding, activation, deactivation) in a clean, isolated Symfony CLI context. This ensures the architectural boundaries (Layer 1, 2, 3) are respected and functional before broader adoption.

## What Changes

- **New Application**: Creation of `apps/canary-tenancy`, a Symfony CLI application.
- **Service Wiring**: Implementation of Symfony service configuration (YAML/PHP) to wire `TenancyOperations` orchestrator with its required adapters and the `Tenant` package.
- **CLI Commands**: Addition of CLI commands in the canary app to trigger tenant operations (e.g., `tenant:onboard`, `tenant:status`).
- **Package Refinement**: Potential modifications to `orchestrators/TenantOperations` and `packages/Tenant` to improve testability or fix wiring issues discovered during canary development.

## Capabilities

### New Capabilities
- `tenancy-orchestration-testing`: Provides the ability to execute and verify multi-tenant lifecycle operations via a CLI interface, ensuring the orchestrator correctly coordinates between atomic packages and adapters.

### Modified Capabilities
- None.

## Impact

- **New Directory**: `apps/canary-tenancy/`
- **Orchestrator**: `orchestrators/TenantOperations` (Potential interface or implementation refinements)
- **Package**: `packages/Tenant` (Potential refinements for better L3 integration)
- **Testing**: Provides a template for future canary applications testing other orchestrators.
