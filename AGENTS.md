# AGENTS.md

Lean always-loaded Atomy (Nexus) agent rules. Keep short. Expand docs live in `docs/agentic/`.

## Execution Commands

### PHP packages
Run from affected package dir.

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/phpunit tests/Unit/Services/MyServiceTest.php
./vendor/bin/phpunit --filter testMyMethod
./vendor/bin/phpstan analyse src --level=max
```

### Atomy-Q
Main SaaS app.

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

- PHP: `declare(strict_types=1);` ∀ files.
- Architecture: L1 `packages/` pure PHP; L2 `orchestrators/` coordinate via own interfaces; L3 `adapters/` framework OK.
- SRP: domain logic belongs in L1 packages; cross-domain orchestration in L2; adapter layer ≠ dumping ground.
- Reuse: check `docs/project/NEXUS_PACKAGES_REFERENCE.md` before new capability. Update when L1 package changes or new package added.
- DI: constructor injection + specific interfaces. Generic `object` ⊥.
- Boundaries: `Illuminate\*`/`Symfony\*` ⊥ in L1/L2. Direct L1 imports in L2 ⊥.
- Contracts: define interface before implementation, usually `src/Contracts/`.
- Failure: domain exceptions only. Synthetic success/fake IDs/generic `\Exception` ⊥.
- Repositories: use `*QueryInterface` + `*PersistInterface` where CQRS fits.

## Critical Constraints

- Multi-tenancy: scope reads/writes by `tenantId` at query root.
- Cross-tenant: 404 for missing + wrong tenant; existence leak ⊥.
- Mutations: multi-write flow → single transaction boundary.
- Side effects: dispatch succeeds before success-state persist, unless outbox owns delivery.
- PATCH: omitted field ≠ explicit `null`.
- Numeric/array safety: guard divide-by-zero, empty arrays, unnormalized indexes.

## Working Defaults

- Discovery first: map relevant files/packages only. Propose L1/L2/L3 extraction when domain logic can be reused.
- Minimal context: targeted files + diffs. Avoid whole modules/long docs unless needed.
- Bugs: reproduce with test before fix when feasible.
- Docs: update affected `IMPLEMENTATION_SUMMARY.md` when behavior/public contracts change.
- Imports: explicit imports; group built-ins, PSR, Nexus, local; sort inside groups.

## Code Review Requirement

If `code-reviewer` subagent reviews `apps/atomy-q/` changes, use `docs/superpowers/CODE_REVIEW_GUIDELINES_ATOMY_Q.md` and append `### Atomy-Q guideline pass`.

## On-Demand Guidance

- Agentic workflow + failure patterns: `docs/agentic/AGENTIC_CODING_GUIDELINES.md`
- Loopback learning process: `docs/agentic/AGENT_LEARNINGS.md`
- Architecture: `docs/project/ARCHITECTURE.md`
- Naming: `docs/project/NAMING_CONVENTIONS.md`
- Package reference: `docs/project/NEXUS_PACKAGES_REFERENCE.md`

