# GEMINI FOUNDATIONAL MANDATES & AGENTIC GUIDELINES

This document serves as the absolute source of truth for Gemini CLI agents working on the Atomy (Nexus) project. These mandates take precedence over general defaults.

## 🏗️ Core Architecture & Standards

All development MUST strictly adhere to the project's **Three-Layer Architecture** and **Coding Standards**.

- **Source of Truth**: Refer to [ARCHITECTURE.md](docs/project/ARCHITECTURE.md) for detailed architectural rules, coding standards (PHP 8.3), and security guidelines.
- **Package Reference**: Consult [NEXUS_PACKAGES_REFERENCE.md](docs/project/NEXUS_PACKAGES_REFERENCE.md) before implementing any feature to prevent duplication.

### Quick Mandates:
- **Strict Typing**: `declare(strict_types=1);` is mandatory in every file.
- **Immutability**: Use `final readonly class` for services and `readonly` properties for VOs.
- **Dependency Injection**: Constructor injection with specific interfaces. NEVER use generic `object`.
- **Three-Layer Rule**:
    - **Layer 1 (packages/)**: Pure PHP, no framework dependencies.
    - **Layer 2 (orchestrators/)**: Cross-package coordination, own interfaces, stateless.
    - **Layer 3 (adapters/)**: Framework-specific implementation (Laravel).
- **Anti-Patterns**: NO `use Illuminate\*` in Layers 1 or 2. NO direct imports of L1 in L2. NO synthetic return values on failure.

## 🤖 Agentic Workflow & Tool Engagement

- **Discovery First**: Map relevant packages before changing code. Use `grep_search` and `ls -R`.
- **Interface First**: Define the contract in `src/Contracts/` before implementation.
- **Validation**: Reproduce bugs with a test case first. Write happy-path and edge-case tests for new features.
- **Documentation**: Update `IMPLEMENTATION_SUMMARY.md` in the affected package after every change.
- **Multi-Tenancy**: Always filter by `tenantId`. Guard against cross-tenant data leakage.
- **Zero-Check**: Guard against division by zero and empty array operations.

## 🔍 Code review (superpowers: requesting-code-review)

When dispatching the **code-reviewer** subagent for changes that touch **`apps/atomy-q/`**, the reviewer **must** walk the checklist in [`docs/superpowers/CODE_REVIEW_GUIDELINES_ATOMY_Q.md`](docs/superpowers/CODE_REVIEW_GUIDELINES_ATOMY_Q.md) and append **`### Atomy-Q guideline pass`** to the review. The superpowers template **`requesting-code-review/code-reviewer.md`** (in your skills install) embeds this requirement.

---

## Multi-Agent Coordination

Nexus is a large system. Agents should follow these coordination patterns:

- **Architect Agent**: Responsible for design, interface definitions, and enforcing boundaries.
- **Developer Agent**: Responsible for implementation and logic. Follows Architect's contracts.
- **QA Agent**: Responsible for testing, verification, and regression analysis.
- **Maintenance Agent**: Handles dependency updates, documentation syncing, and refactoring.

## Naming Conventions

Refer to [NAMING_CONVENTIONS.md](NAMING_CONVENTIONS.md) for detailed naming standards across the codebase.
