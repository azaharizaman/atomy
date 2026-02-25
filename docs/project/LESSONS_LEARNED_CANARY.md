# Project-Wide Replication: Lessons from the Tenant Canary Exercise

This document outlines the architectural patterns and best practices discovered during the "Tenant Canary" refactoring. These should be replicated across all Orchestrators and Packages in the Nexus ecosystem.

## 1. The Adapter Interface Pattern (Orchestrator Level)

**Problem:** Orchestrators often define internal dependency interfaces (e.g., `TenantCreatorInterface`) inline or in a scattered way, making it difficult for Layer 3 developers to know what they need to implement.

**Pattern:**
- Always define "Adapter" interfaces in the Orchestrator's `src/Contracts/` namespace.
- Name them `{Domain}{Action}AdapterInterface.php`.
- These interfaces bridge the gap between the Orchestrator's stateless logic and the L1/L3 implementation.

**Benefit:** Clear "Snapping" points for framework-specific integrations (Symfony, Laravel).

## 2. Standardized Operation Results

**Problem:** Services and Orchestrators returning inconsistent DTOs (e.g., some use `success` vs `passed`, some use `errors` vs `issues`).

**Pattern:**
- Use `Nexus\Common\Contracts\OperationResultInterface`.
- Every "Result" DTO must implement `isSuccess()`, `getMessage()`, `getData()`, and `getIssues()`.
- This allows generic UI/CLI handlers to process any orchestrator response without custom mapping logic.

**Benefit:** Decoupled presentation layer (CLI, API, Filament) and predictable developer experience.

## 3. The Minimum Viable Domain (MVD) Registry

**Problem:** Difficulty in knowing if a domain (e.g., Finance, Tenant) is "fully implemented" and ready for use in a new application.

**Pattern:**
- Every domain must have a `DOMAIN_MVD.md` documentation in `docs/project/domains/`.
- This document lists the mandatory L1 interfaces and L2 adapters that MUST be bound in the DI container.
- Implement a `*ReadinessChecker` service in the domain's orchestrator to programmatically verify these bindings.

**Benefit:** Rapid onboarding and automated environment diagnostics.

## 4. Layer 2 as the "Sovereign Workflow"

**Problem:** Temptation to put orchestration logic (coordinating multiple L1 packages) in L3 Controllers or Jobs.

**Pattern:**
- Move all cross-package logic to Layer 2 Orchestrators.
- L3 should ONLY contain configuration, infrastructure implementations (Repositories), and delivery mechanism (Controllers).
- The "Canary App" proved that if L2 is robust, the L3 "Adapter" layer remains lean and framework-portable.

---

**Status:** These patterns are currently enforced in `Nexus\TenantOperations`. New domains must adopt these patterns during their next refactor cycle.
