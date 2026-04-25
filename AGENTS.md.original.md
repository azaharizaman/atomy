# AGENTS.md

Lean, always-loaded instructions for Coding CLI agents working on Atomy (Nexus).
Keep this file short. Move expandable guidance to `docs/agentic/`.

## Execution Commands

### PHP packages
Run from the affected package directory.

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/phpunit tests/Unit/Services/MyServiceTest.php
./vendor/bin/phpunit --filter testMyMethod
./vendor/bin/phpstan analyse src --level=max
```

### Atomy-Q
Main SaaS application for this repo

#### Atomy-Q Backend (API)
Run from `qpps/atomy-q/API`.

Serve
```bash
php artisan serve
```
Test
```bash
composer install
#Refresh migration with seed. DB in dev mode always - Disregard data loss
php artisan migrate:fresh --seed 
php artisan test
#Run specific test example
php artisan test --filter ProjectAclTest 
```


#### Atomy-Q Frontend (WEB)
Run from `apps/atomy-q/WEB`.

```bash
npm install
npm run dev
npm run build
npm run lint
npm run test:unit
npx vitest run src/hooks/useMyHook.test.ts
npm run test:e2e
npm run generate:api
```

### Root E2E

```bash
npm run test:e2e
npm run test:e2e:laravel
npm run test:e2e:api
npm run test:e2e:report
```

## Core Mandates

- Strict typing: every PHP file must declare `strict_types=1`.
- Architecture: Layer 1 `packages/` is pure PHP; Layer 2 `orchestrators/` coordinates through its own interfaces; Layer 3 `adapters/` may use frameworks.
- Single Responsibility / Segregation of Responsibility: Business/Singular Domain Responsibility must be delegated to appropriate Layer 1 `packages/`, either existing or create new. Promote Re-Use for this and future projects. Create new or reuse Layer 2 `orchestrators/` for cross domain ochestration logic. Do not lump all responsibility to application/adapter layer. Refer to `docs/project/NEXUS_PACKAGES_REFERENCE.md`. Update `docs/project/NEXUS_PACKAGES_REFERENCE.md` when layer 1 packages are modified or new package created.
- Dependency rule: constructor DI with specific interfaces. Do not use generic `object` dependencies.
- Boundary rule: no `Illuminate\*` or `Symfony\*` in Layers 1 or 2. Do not import Layer 1 directly into Layer 2.
- Contract rule: define interfaces before implementation, usually under `src/Contracts/`.
- Failure rule: throw domain-specific exceptions. Do not return synthetic success values, fake IDs, or generic `\Exception`.
- Repository rule: split CQRS persistence into `*QueryInterface` and `*PersistInterface` where applicable.

## Critical Constraints

- Multi-tenancy: always scope queries and writes by `tenantId` at the query root.
- Cross-tenant access: return 404 for both "not found" and "wrong tenant"; do not leak resource existence.
- Mutations: multi-write workflows need one transaction boundary.
- Side effects: persist state only after external dispatch succeeds unless the design explicitly requires an outbox.
- PATCH semantics: distinguish omitted fields from explicit `null` clears.
- Numeric/array safety: guard division by zero, empty arrays, and unnormalized indexes.

## Working Defaults

- Discovery first: map only the relevant files/packages before editing. Propose new Layer 1, 2 or 3 packages/orchestrators/adaptors where domain logic can be extracted to promote reuse.
- Minimal context: read targeted files and diffs; avoid loading whole modules or long docs unless needed.
- Validation first for bugs: reproduce with a test before fixing when feasible.
- Documentation: update the affected package `IMPLEMENTATION_SUMMARY.md` when behavior or public contracts change.
- Imports: use explicit imports; group built-ins, PSR, Nexus packages, then local project imports; sort within groups.

## Code Review Requirement

When dispatching the `code-reviewer` subagent for changes touching `apps/atomy-q/`, the reviewer must use `docs/superpowers/CODE_REVIEW_GUIDELINES_ATOMY_Q.md` and append `### Atomy-Q guideline pass` to the review.

## On-Demand Guidance

- Agentic workflow and failure patterns: `docs/agentic/AGENTIC_CODING_GUIDELINES.md`
- Loopback learning process: `docs/agentic/AGENT_LEARNINGS.md`
- Architecture: `docs/project/ARCHITECTURE.md`
- Naming: `docs/project/NAMING_CONVENTIONS.md`
- Package reference: `docs/project/NEXUS_PACKAGES_REFERENCE.md`

