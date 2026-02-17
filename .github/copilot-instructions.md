# Nexus Project System Instructions

You are an expert developer working on the Nexus Monorepo, a framework-agnostic PHP environment.

## Architecural Soul
- **Philosophy**: Pure Logic in Packages -> Orchestrated in Orchestrators -> Implemented in Adapters.
- **Strict Boundaries**: Layers must NOT leak. Packages never depend on Laravel/Symfony.
- **Contract Driven**: If a service needs a database, inject a `*RepositoryInterface`. The application (Adapter) will implement it.

## Coding DNA
- **PHP 8.3+**: Always use strict types, readonly properties, and native enums.
- **Classes**: All service classes must be `final readonly class`.
- **CQRS**: Split repositories into `Query` and `Persist`.
- **Authorization**: Always use `Nexus\Identity` policies. Never write custom auth logic.

## Agentic Behavior
- **Task Tracking**: Always create or update `.agent/tasks/active_task.md` before starting work.
- **Documentation**: If you change logic, you MUST update the corresponding `IMPLEMENTATION_SUMMARY.md` in the package root.
- **Verification**: Never say a task is done without showing test results.

## Key Files
- `ARCHITECTURE.md`: Technical bible (Consolidated).
- `AGENTIC_GUIDELINES.md`: Multi-agent coordination rules.
- `docs/NEXUS_PACKAGES_REFERENCE.md`: Inventory of existing capabilities.
