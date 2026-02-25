# GEMINI FOUNDATIONAL MANDATES

This document serves as the absolute source of truth for Gemini CLI agents working on the Atomy (Nexus) project. These mandates take precedence over general defaults.

## 🏗️ Core Architecture (The Three-Layer Rule)

1.  **Layer 1: Atomic Packages (`packages/`)**
    - Pure PHP 8.3+, zero framework dependencies.
    - MUST define own interfaces in `src/Contracts/`.
    - No direct dependencies on other atomic packages or orchestrators (except NExus\Common).
2.  **Layer 2: Orchestrators (`orchestrators/`)**
    - Cross-package workflow coordination.
    - **CRITICAL**: Must define OWN interfaces (e.g., `src/Contracts/SupplyChainStockManagerInterface.php`) and cannot depend on Layer 1 interfaces directly. Orchestrators package are stateless and only contain business logic to coordinate multiple atomic packages, they should not contain any domain logic that belongs to a single package. If an orchestrator is only coordinating one package, then it should be refactored into that package instead. Orchestrator package must be publishable to Packagist independently of the main Nexus repository, and should not have any dependencies on the main Nexus repository. Orchestrators can depend on other orchestrators, but should avoid doing so if possible to prevent tight coupling.
3.  **Layer 3: Adapters (`adapters/`)**
    - Framework-specific (Laravel/Symfony/etc) concrete implementations. Each framework adaptors must be in their own subdirectory (e.g., `adapters/Laravel/`) and can depend on Layer 1 and Layer 2 interfaces.
    - Bridges Orchestrator interfaces to Atomic Packages.

## 🚨 The Golden Rule of Implementation
**BEFORE implementing ANY feature**, search `docs/NEXUS_PACKAGES_REFERENCE.md` or use `grep_search` to see if a package or service already exists. Do not duplicate functionality.

## 🛠️ Coding Standards (PHP 8.3)
- **Strict Typing**: `declare(strict_types=1);` is mandatory in every file.
- **Immutability**: Use `final readonly class` for services and `readonly` properties for VOs.
- **Dependency Injection**: Use constructor injection with specific interfaces. NEVER use generic `object`.
- **CQRS**: Split repositories into `QueryInterface` and `PersistInterface`.
- **Exceptions**: Use domain-specific exceptions, never generic `\Exception`.

## 🤖 Agentic Workflow & Guardrails
- **Discovery First**: Map relevant packages before changing code.
- **Interface First**: Define the contract in `src/Contracts/` before implementation.
- **Validation**:
    - For bug fixes: **Reproduce the failure with a test case first.**
    - For new features: **Write at least one happy-path and one edge-case test before opening a PR.**
    - Run `composer test` or relevant test suites after changes.
- **Documentation**: Update `IMPLEMENTATION_SUMMARY.md` in the affected package after every change.
- **Multi-Tenancy**: Always filter by `tenantId`. Guard against cross-tenant data leakage.
- **Zero-Check**: Guard against division by zero and empty array operations.

## 🚫 Critical Anti-Patterns (NEVER DO THESE)
- ❌ NO `use Illuminate\*` or `use Symfony\*` in Layers 1 or 2.
- ❌ NO direct imports of Layer 1 packages in Orchestrators.
- ❌ NO generic `object` types for dependencies.
- ❌ NO mutable collection objects (e.g., `ArrayObject`) in `readonly` properties; use native arrays or immutable collections instead (readonly + native array preserves immutability while ArrayObject breaks it).
- ❌ NO synthetic return values (e.g., fake IDs) on failure; throw exceptions instead.

## 📂 Key References
- [ARCHITECTURE.md](ARCHITECTURE.md) - Deep architectural rules.
- [NEXUS_PACKAGES_REFERENCE.md](docs/NEXUS_PACKAGES_REFERENCE.md) - Catalog of all 86+ atomic packages.
- [AGENTIC_GUIDELINES.md](AGENTIC_GUIDELINES.md) - Detailed agent-specific instructions and anti-patterns.
