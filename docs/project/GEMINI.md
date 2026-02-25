# GEMINI FOUNDATIONAL MANDATES & AGENTIC GUIDELINES

This document serves as the absolute source of truth for Gemini CLI agents working on the Atomy (Nexus) project. These mandates take precedence over general defaults.

## 🏗️ Core Architecture & Standards

All development MUST strictly adhere to the project's **Three-Layer Architecture** and **Coding Standards**.

- **Source of Truth**: Refer to [ARCHITECTURE.md](ARCHITECTURE.md) for detailed architectural rules, coding standards (PHP 8.3), and security guidelines.
- **Package Reference**: Consult [NEXUS_PACKAGES_REFERENCE.md](NEXUS_PACKAGES_REFERENCE.md) before implementing any feature to prevent duplication.

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

## 👥 Multi-Agent Coordination

Nexus is a large system. Agents should follow these coordination patterns:

- **Architect Agent**: Responsible for design, interface definitions, and enforcing boundaries.
- **Developer Agent**: Responsible for implementation and logic. Follows Architect's contracts.
- **QA Agent**: Responsible for testing, verification, and regression analysis.
- **Maintenance Agent**: Handles dependency updates, documentation syncing, and refactoring.

## 🚫 Common Anti-Patterns to Avoid

### Type Safety
- **AVOID**: Using generic `object` or `?object` for dependencies. Use specific interfaces.

### PHP 8.3 Readonly
- **AVOID**: Mutable arrays in readonly classes. Use `ArrayObject` for internal mutation if necessary, or native arrays for true immutability.
- **AVOID**: Using `private readonly` on promoted properties (readonly implies private).

### Method Implementation
- **AVOID**: Methods that ignore passed parameters.
- **AVOID**: Delete methods that only log and don't remove from storage.

### Algorithms
- **AVOID**: Division without zero-check.
- **AVOID**: Index access without normalization (use modulo).

### Multi-Tenancy
- **AVOID**: Ignoring tenant ID in queries.

### Exception Handling
- **AVOID**: Returning synthetic IDs (e.g., `QUO-' . uniqid()`) instead of throwing exceptions when a service is unavailable.

## ✅ Quality Checklist
- [ ] All dependencies use specific interface types (not `object`)
- [ ] All classes use `readonly` when appropriate (PHP 8.3+)
- [ ] All methods actually apply their parameters
- [ ] All delete methods remove from storage
- [ ] Division operations have zero guards
- [ ] Array indices are normalized with modulo
- [ ] Tenant filtering is applied in multi-tenant contexts
- [ ] Failures throw domain exceptions, not return synthetic values
