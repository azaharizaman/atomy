# Documentation Refactor Plan

## Current Problem
The root directory is cluttered with overlapping documentation:
- `README.md`: General overview + package list.
- `ARCHITECTURE.md`: Technical deep dive into monorepo structure and atomicity.
- `SYSTEM_DESIGN_AND_PHILOSOPHY.md`: Focuses on the "Advanced Orchestrator Pattern".
- `CODING_GUIDELINES.md`: PHP standards, repository design, and DI rules.
- `DEVELOPER_GUIDELINES.md`: Empty/Redundant.
- `docs/NEXUS_SYSTEM_OVERVIEW.md`: Large reference document for AI assistants that overlaps with all the above.

## Proposed Strategy
1. **Consolidate** all core architectural and coding principles into `ARCHITECTURE.md`.
2. **Simplify** `README.md` to be a high-level entry point, installation guide, and link-hub.
3. **Eliminate** redundant files: `SYSTEM_DESIGN_AND_PHILOSOPHY.md`, `CODING_GUIDELINES.md`, `DEVELOPER_GUIDELINES.md`.
4. **Synchronize** with `docs/NEXUS_SYSTEM_OVERVIEW.md` ensuring it remains the "single source of truth" for AI assistants while keeping root docs readable for humans.

## File-Specific Changes

### 1. `README.md`
- High-level value proposition.
- 3-Layer Architecture summary (brief).
- Installation and Quick Start.
- Links to detailed guides (Architecture, API, Package Reference).

### 2. `ARCHITECTURE.md` (The "Nexus Bible")
- **Part 1: The Three Layers** (Packages, Orchestrators, Adapters).
- **Part 2: Package Atomicity** (Pure logic, stateless, contract-driven).
- **Part 3: Advanced Orchestrator Pattern** (Coordinators, DataProviders, Rules, Services).
- **Part 4: Coding Guidelines** (PHP 8.3+, Repository pattern/CQRS, DI, Strict Types).
- **Part 5: Security & Authorization** (Nexus\Identity, RBAC/ABAC).

### 3. Cleanup
- `rm SYSTEM_DESIGN_AND_PHILOSOPHY.md`
- `rm CODING_GUIDELINES.md`
- `rm DEVELOPER_GUIDELINES.md`
- `rm ARCHITECTURE.md` (temporary for complete rewrite)
