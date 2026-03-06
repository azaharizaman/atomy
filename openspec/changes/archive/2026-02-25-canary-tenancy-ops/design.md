## Context

The `TenancyOperations` orchestrator (Layer 2) requires several adapters (Layer 3) to function, including `TenantCreatorAdapterInterface`, `AdminCreatorAdapterInterface`, `CompanyCreatorAdapterInterface`, etc. To verify its correctness in a Symfony environment, we need a canary application that provides these implementations and wires them together.

## Goals / Non-Goals

**Goals:**
- Scaffolding a clean Symfony CLI application in `apps/canary-tenancy`.
- Providing concrete, lightweight implementations of required Layer 3 adapters for `TenancyOperations`.
- Demonstrating successful dependency injection of the orchestrator into Symfony CLI commands.
- Verifying the `TenantOnboardingRequest` flow from CLI to the orchestrator.

**Non-Goals:**
- Implementing a full-featured web UI for tenancy.
- setting up a production-grade database cluster (SQLite will be used for simplicity in the canary if possible, or simple in-memory storage for mocks).
- Full integration with all other orchestrators (only `TenancyOperations` is the focus).

## Decisions

### 1. Application Structure
We will use the Symfony CLI Skeleton to minimize overhead. The app will be located in `apps/canary-tenancy`.

### 2. Adapter Implementation
Instead of complex database-backed adapters, the canary will implement "Logging" or "In-Memory" versions of the adapters in `apps/canary-tenancy/src/Adapters`. This allows us to verify that the orchestrator calls the correct methods with the correct data without needing a full infrastructure setup.

### 3. Service Configuration
We will use Symfony's `services.yaml` to manually wire the orchestrator. This serves as a "blueprint" for how other Symfony-based apps (like `atomy-api`) should wire these services.

### 4. CLI over Web
A CLI interface is chosen for the canary because it allows for rapid testing and easier automation of verification steps without the complexities of HTTP, routing, and middleware.

## Risks / Trade-offs

- **[Risk] Mocked Adapters hide real DB issues** → **Mitigation**: The canary's primary goal is *orchestration and wiring*. DB-specific issues should be handled by unit/integration tests within the `Tenant` package and its specific DB adapters.
- **[Risk] Symfony vs Laravel differences** → **Mitigation**: Using a Symfony canary ensures our Layer 1 and Layer 2 packages remain truly framework-agnostic, as mandated by the Three-Layer Architecture.
