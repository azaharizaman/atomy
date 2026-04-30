# Atomy-Q AI Insights And Governance Functional Reality Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Plan 5 AI insights, reporting summaries, RFQ risk insighting, and vendor governance narratives alpha-real by moving coordination into `InsightOperations`, replacing hardcoded facts with tenant-scoped facts, adding WEB generation actions, and removing fake governance/risk outcomes.

**Architecture:** This plan is split into five testable parts because the implementation crosses orchestrator contracts, Laravel adapters/controllers, governance truthfulness, WEB generation UX, and contract/docs closure. Each part leaves the previous part testable: Part 1 is pure orchestrator PHPUnit, Part 2 wires dashboard/report API facts through the orchestrator, Part 3 closes risk/governance/sanctions truthfulness, Part 4 adds WEB generation controls over the stable API, and Part 5 closes OpenAPI/generated-client/docs/verification.

**Tech Stack:** PHP 8.3, Laravel, Eloquent, Nexus Layer 2 orchestrators, React/TypeScript, TanStack Query, PHPUnit, Vitest, OpenAPI generated client.

---

## Source Documents

- Spec: `docs/superpowers/specs/2026-04-30-atomy-q-ai-insights-governance-functional-reality-design.md`
- AI runtime architecture: `docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md`
- Plan 5 baseline: `docs/superpowers/plans/2026-04-23-atomy-q-ai-insights-governance-and-reporting.md`
- Architecture rules: `docs/project/ARCHITECTURE.md`
- Package reference: `docs/project/NEXUS_PACKAGES_REFERENCE.md`

## Split Rationale

This work is too large for one implementation slice because it touches four user-facing API areas, one shared WEB component, multiple hooks/pages, provider artifact semantics, and orchestrator boundaries. The split is valid because every part has an independent verification gate:

1. `InsightOperations` contracts and coordinator tests pass without Laravel.
2. Dashboard/report API tests pass using Laravel adapters and fake provider ports.
3. RFQ risk/governance/sanctions API tests pass using deterministic records.
4. WEB unit tests pass using mocked API responses and AI status payloads.
5. OpenAPI/generated client/docs verification passes after response contracts stabilize.

Do not start Part 2 before Part 1 is green. Do not start Part 4 before the API paths used by the hooks are green.

## File Structure

### Part 1: Orchestrator Functional Reality Core

- Create: `orchestrators/InsightOperations/src/Contracts/AiArtifactCachePortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/AiAvailabilityPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/DashboardFactsPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/GovernanceFactsPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/InsightNarrativePortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/ReportingFactsPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/RiskInsightFactsQueryInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/RiskInsightFactsCommandInterface.php`
- Create: `orchestrators/InsightOperations/src/Coordinators/DashboardInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/src/Coordinators/GovernanceNarrativeCoordinator.php`
- Create: `orchestrators/InsightOperations/src/Coordinators/ReportingInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/src/Coordinators/RiskInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/src/DTOs/AiArtifactDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/AiArtifactProvenanceDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/DashboardFactsDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/GovernanceFactsDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/InsightResultDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/MetricFactDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/ReportingFactsDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/RiskInsightFactsDto.php`
- Create: `orchestrators/InsightOperations/src/Services/FactHasher.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/DashboardInsightCoordinatorTest.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/ReportingInsightCoordinatorTest.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/RiskInsightCoordinatorTest.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/GovernanceNarrativeCoordinatorTest.php`
- Modify: `orchestrators/InsightOperations/IMPLEMENTATION_SUMMARY.md`

### Part 2: Dashboard And Reporting API Functional Facts

- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/CacheAiArtifactStore.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/DashboardFactsAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/InsightAiAvailabilityAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/ProviderInsightNarrativeAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/ReportingFactsAdapter.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ReportController.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

### Part 3: RFQ Risk, Governance, And Sanctions Truthfulness

- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/GovernanceFactsAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/ProviderGovernanceNarrativeAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/RiskInsightFactsAdapter.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RiskComplianceController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RiskComplianceAiInsightsApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorGovernanceApiTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

### Part 4: WEB Generation UX

- Modify: `apps/atomy-q/WEB/src/components/ai/ai-narrative-panel.tsx`
- Modify: `apps/atomy-q/WEB/src/components/ai/ai-narrative-panel.test.tsx`
- Modify: `apps/atomy-q/WEB/src/hooks/use-ai-narrative-summary.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-dashboard-ai-summary.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-reporting-ai-summary.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-governance.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/reporting/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.tsx`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

### Part 5: Contract, Generated Client, Documentation, Final Verification

- Modify: `apps/atomy-q/API/openapi/openapi.json`
- Modify generated client files under `apps/atomy-q/WEB/src/generated/api/` through `npm run generate:api`
- Modify: `docs/superpowers/plans/2026-04-23-atomy-q-ai-insights-governance-and-reporting.md`
- Modify: `docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md`
- Modify affected `IMPLEMENTATION_SUMMARY.md` files if not already updated in Parts 1-4

## Part 1: Orchestrator Functional Reality Core

### Task 1.1: Add Fact And Artifact DTOs

**Files:**
- Create: `orchestrators/InsightOperations/src/DTOs/MetricFactDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/AiArtifactProvenanceDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/AiArtifactDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/InsightResultDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/DashboardFactsDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/ReportingFactsDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/RiskInsightFactsDto.php`
- Create: `orchestrators/InsightOperations/src/DTOs/GovernanceFactsDto.php`
- Test: DTO assertions inside coordinator tests in Tasks 1.3-1.6

- [ ] **Step 1: Create DTO classes with strict serialization contracts**

Use `declare(strict_types=1);` in every PHP file. Use `final readonly class` for each DTO. Keep every DTO framework-free.

Required DTO shape:

```php
namespace Nexus\InsightOperations\DTOs;

final readonly class MetricFactDto
{
    public function __construct(
        public string $key,
        public mixed $value,
        public string $status = 'available',
        public ?string $reasonCode = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'status' => $this->status,
            'reason_code' => $this->reasonCode,
        ];
    }
}
```

`AiArtifactDto::unavailable()` must return an artifact with `available=false`, `status='unavailable'`, `payload=null`, and the supplied reason code. `InsightResultDto::toResponseArray()` must merge deterministic facts and artifact fields into the same shape the existing controllers return: `data.<facts>` plus `data.ai_summary`, `data.ai_insights`, or `data.ai_narrative`.

- [ ] **Step 2: Run syntax checks**

Run:

```bash
cd orchestrators/InsightOperations
find src/DTOs -name '*.php' -print0 | xargs -0 -n1 php -l
```

Expected: every file reports `No syntax errors detected`.

### Task 1.2: Add Ports And Fact Hasher

**Files:**
- Create: `orchestrators/InsightOperations/src/Contracts/AiArtifactCachePortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/AiAvailabilityPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/DashboardFactsPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/GovernanceFactsPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/InsightNarrativePortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/ReportingFactsPortInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/RiskInsightFactsQueryInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/RiskInsightFactsCommandInterface.php`
- Create: `orchestrators/InsightOperations/src/Services/FactHasher.php`

- [ ] **Step 1: Define interfaces**

Use these method signatures:

```php
interface DashboardFactsPortInterface
{
    public function factsForTenant(string $tenantId): DashboardFactsDto;
}

interface ReportingFactsPortInterface
{
    public function factsForTenant(string $tenantId, string $subjectType): ReportingFactsDto;
}

interface RiskInsightFactsQueryInterface
{
    public function factsForRfq(string $tenantId, string $rfqId): RiskInsightFactsDto;
}

interface RiskInsightFactsCommandInterface
{
    public function escalate(string $tenantId, string $rfqId, string $itemId): void;
    public function resolveAsException(string $tenantId, string $rfqId, string $itemId, string $actorId): void;
}

interface GovernanceFactsPortInterface
{
    public function factsForVendor(string $tenantId, string $vendorId): GovernanceFactsDto;
}

interface AiAvailabilityPortInterface
{
    public function isFeatureAvailable(string $featureKey): bool;

    /** @return list<string> */
    public function reasonCodes(string $featureKey): array;
}

interface InsightNarrativePortInterface
{
    /** @param array<string, mixed> $facts */
    public function generate(string $featureKey, string $tenantId, string $subjectType, string $actorId, array $facts): AiArtifactDto;
}

interface AiArtifactCachePortInterface
{
    public function get(string $cacheKey): ?AiArtifactDto;

    public function put(string $cacheKey, AiArtifactDto $artifact, int $ttlSeconds): void;
}
```

- [ ] **Step 2: Implement deterministic hashing**

`FactHasher::hash(array $facts): string` must sort associative keys recursively before JSON encoding. This prevents cache misses from key-order drift.

- [ ] **Step 3: Run syntax checks**

Run:

```bash
cd orchestrators/InsightOperations
find src/Contracts src/Services -name '*.php' -print0 | xargs -0 -n1 php -l
```

Expected: every file reports `No syntax errors detected`.

### Task 1.3: Dashboard Coordinator

**Files:**
- Create: `orchestrators/InsightOperations/src/Coordinators/DashboardInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/DashboardInsightCoordinatorTest.php`

- [ ] **Step 1: Write failing tests**

Test these cases:

```php
public function test_show_returns_facts_and_no_cached_artifact_without_provider_call(): void
public function test_generate_uses_real_facts_and_stores_artifact(): void
public function test_generate_returns_unavailable_when_ai_feature_is_unavailable(): void
```

Use fake ports that return `active_rfqs=3`, `pending_approvals=2`, `total_savings=12500.50`, and `avg_cycle_time_days=9`. Assert the generated narrative source facts contain those values and never zero placeholders.

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit tests/Unit/Coordinators/DashboardInsightCoordinatorTest.php
```

Expected: FAIL because `DashboardInsightCoordinator` does not exist.

- [ ] **Step 3: Implement coordinator**

`show(string $tenantId): InsightResultDto` must fetch facts and cached artifact only. `generate(string $tenantId, string $actorId): InsightResultDto` must fetch facts, check `dashboard_ai_summary`, call `InsightNarrativePortInterface`, cache by `tenantId + featureKey + sourceFactsHash`, and return facts plus artifact.

- [ ] **Step 4: Run tests to verify pass**

Run the same PHPUnit command.

Expected: PASS.

### Task 1.4: Reporting Coordinator

**Files:**
- Create: `orchestrators/InsightOperations/src/Coordinators/ReportingInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/ReportingInsightCoordinatorTest.php`

- [ ] **Step 1: Write failing tests**

Test these cases:

```php
public function test_show_returns_report_facts_and_missing_cached_artifact(): void
public function test_generate_uses_subject_specific_facts(): void
public function test_generate_does_not_call_provider_when_reporting_ai_is_unavailable(): void
```

Use subject types `report_kpis`, `report_spend_trend`, and `report_spend_by_category`. Assert the provider receives the same subject type requested by the controller path.

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit tests/Unit/Coordinators/ReportingInsightCoordinatorTest.php
```

Expected: FAIL because `ReportingInsightCoordinator` does not exist.

- [ ] **Step 3: Implement coordinator**

Use feature key `reporting_ai_summary`. Use the same cache and unavailable semantics as dashboard, but include the subject type in the cache key.

- [ ] **Step 4: Run tests to verify pass**

Run the same PHPUnit command.

Expected: PASS.

### Task 1.5: Risk Insight Coordinator

**Files:**
- Create: `orchestrators/InsightOperations/src/Coordinators/RiskInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/RiskInsightCoordinatorTest.php`

- [ ] **Step 1: Write failing tests**

Test these cases:

```php
public function test_show_returns_risk_items_and_manual_review_state_without_cache(): void
public function test_generate_uses_risk_items_as_source_facts(): void
public function test_generate_returns_source_facts_unavailable_when_no_risk_items_exist(): void
```

The no-risk-items case must return deterministic facts and an unavailable artifact with reason code `source_facts_unavailable`, not a provider call.

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit tests/Unit/Coordinators/RiskInsightCoordinatorTest.php
```

Expected: FAIL because `RiskInsightCoordinator` does not exist.

- [ ] **Step 3: Implement coordinator**

Use feature key `rfq_ai_insights`. Include `manual_review.pending_items=count(risk_items)` in the result.

- [ ] **Step 4: Run tests to verify pass**

Run the same PHPUnit command.

Expected: PASS.

### Task 1.6: Governance Narrative Coordinator

**Files:**
- Create: `orchestrators/InsightOperations/src/Coordinators/GovernanceNarrativeCoordinator.php`
- Create: `orchestrators/InsightOperations/tests/Unit/Coordinators/GovernanceNarrativeCoordinatorTest.php`

- [ ] **Step 1: Write failing tests**

Test these cases:

```php
public function test_show_returns_governance_facts_and_cached_narrative(): void
public function test_generate_uses_sanitized_governance_context(): void
public function test_generate_keeps_facts_when_ai_is_unavailable(): void
```

Assert the provider context includes evidence type/domain/status and finding domain/severity/status, but excludes evidence id, finding id, raw notes, email, phone, and plain actor names.

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit tests/Unit/Coordinators/GovernanceNarrativeCoordinatorTest.php
```

Expected: FAIL because `GovernanceNarrativeCoordinator` does not exist.

- [ ] **Step 3: Implement coordinator**

Use feature key `governance_ai_narrative`. Add a private sanitizer or small service inside the coordinator that hashes actor fields and strips raw identifiers before provider invocation.

- [ ] **Step 4: Run tests to verify pass**

Run the same PHPUnit command.

Expected: PASS.

### Task 1.7: Part 1 Verification And Commit

- [ ] **Step 1: Run full orchestrator tests**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit
```

Expected: PASS.

- [ ] **Step 2: Update implementation summary**

Update `orchestrators/InsightOperations/IMPLEMENTATION_SUMMARY.md` with:

- New Plan 5 coordinator interfaces.
- Fact-hash cache behavior.
- AI narrative non-authority boundary.
- Verification command and result.

- [ ] **Step 3: Commit Part 1**

Run:

```bash
git add orchestrators/InsightOperations
git commit -m "feat: add insight operations AI narrative coordinators"
```

## Part 2: Dashboard And Reporting API Functional Facts

### Task 2.1: Write Failing API Tests For Real Facts

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php`

- [ ] **Step 1: Add seeded dashboard fact test**

Add a test that creates three tenant RFQs and one cross-tenant RFQ:

- Tenant RFQ statuses: `draft`, `published`, `awarded`.
- Cross-tenant RFQ status: `published`.
- One tenant approval with `status='pending'`.
- One tenant quote submission in intake.
- One tenant award in flight if the current schema supports it.

Assert `/api/v1/dashboard/kpis` returns tenant-only counts. The expected assertion must not be `0` for active RFQs.

- [ ] **Step 2: Add seeded reporting fact test**

Add a test that creates RFQs with `estimated_value` and `savings_percentage`. Assert `/api/v1/reports/kpis` returns tenant-only `active_rfqs`, nonzero `total_spend`, and computed `savings` when source fields exist.

- [ ] **Step 3: Run tests to verify failure**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php
```

Expected: FAIL because controllers still return hardcoded zeros and empty arrays.

### Task 2.2: Add API Adapters And Bindings

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/CacheAiArtifactStore.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/DashboardFactsAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/InsightAiAvailabilityAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/ProviderInsightNarrativeAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/ReportingFactsAdapter.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Implement `DashboardFactsAdapter`**

Use Eloquent query roots scoped by `tenant_id`. Source models:

- `App\Models\Rfq`
- `App\Models\Approval`
- `App\Models\QuoteSubmission`
- `App\Models\Award`
- `App\Models\VendorFinding`

Return explicit metric facts. If a source model/table cannot produce a metric, return a `MetricFactDto` with `status='not_available'` and `reasonCode='source_domain_not_implemented'`.

- [ ] **Step 2: Implement `ReportingFactsAdapter`**

Support these subject types:

- `report_kpis`
- `report_spend_trend`
- `report_spend_by_category`

Use tenant-scoped RFQs and awards as the minimum alpha fact source. Empty series must mean no tenant rows; unimplemented data must be marked unavailable.

- [ ] **Step 3: Implement AI availability and narrative adapters**

`InsightAiAvailabilityAdapter` wraps the existing `InteractsWithAiAvailability` logic through the runtime coordinator or status provider already bound in the API. `ProviderInsightNarrativeAdapter` wraps `ProviderInsightClientInterface` and maps provider payloads into `AiArtifactDto` with provenance.

- [ ] **Step 4: Bind interfaces in `AppServiceProvider`**

Bind every new `Nexus\InsightOperations\Contracts\*` interface to the Laravel adapter. Bind coordinators as concrete classes. Do not bind provider clients inside the orchestrator package.

- [ ] **Step 5: Run syntax checks**

Run:

```bash
cd apps/atomy-q/API
find app/Adapters/InsightOperations -name '*.php' -print0 | xargs -0 -n1 php -l
php -l app/Providers/AppServiceProvider.php
```

Expected: no syntax errors.

### Task 2.3: Refactor Dashboard And Report Controllers

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ReportController.php`

- [ ] **Step 1: Replace direct provider calls**

Remove direct use of:

- `ProviderInsightClientInterface`
- `InsightSummaryRequest`
- `Cache` inside these controllers
- private provider payload/cache/hash helpers inside these controllers

Inject:

- `Nexus\InsightOperations\Coordinators\DashboardInsightCoordinator`
- `Nexus\InsightOperations\Coordinators\ReportingInsightCoordinator`

- [ ] **Step 2: Preserve response routes**

`GET /dashboard/kpis` calls `DashboardInsightCoordinator::show($tenantId)`.

`POST /dashboard/kpis/generate` calls `DashboardInsightCoordinator::generate($tenantId, $userId)`.

`GET /reports/kpis` calls `ReportingInsightCoordinator::show($tenantId, 'report_kpis')`.

`POST /reports/kpis/generate` calls `ReportingInsightCoordinator::generate($tenantId, $userId, 'report_kpis')`.

Use equivalent subject types for spend trend and spend by category.

- [ ] **Step 3: Keep non-AI report endpoints stable**

Do not change report schedules/runs/export behavior in this task except to remove fake data if the endpoint is touched by tests.

### Task 2.4: Part 2 Verification And Commit

- [ ] **Step 1: Run dashboard/report API tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php
```

Expected: PASS.

- [ ] **Step 2: Run orchestrator tests to catch boundary regression**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit
```

Expected: PASS.

- [ ] **Step 3: Update API summary**

Update `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` with dashboard/report fact adapter behavior, orchestrator delegation, and verification results.

- [ ] **Step 4: Commit Part 2**

Run:

```bash
git add apps/atomy-q/API orchestrators/InsightOperations
git commit -m "feat: route dashboard reporting AI through insight operations"
```

## Part 3: RFQ Risk, Governance, And Sanctions Truthfulness

### Task 3.1: Write Failing RFQ Risk Tests

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RiskComplianceAiInsightsApiTest.php`

- [ ] **Step 1: Add realistic risk item test**

Create an RFQ with:

- `submission_deadline` in the past or within a warning window.
- At least one invited or selected vendor with an open high-severity `VendorFinding`.
- At least one quote submission not ready for comparison if the current schema supports readiness.

Assert `/api/v1/risk-items?rfqId=<id>` returns at least one item with:

- `domain`
- `severity`
- `status`
- `title`
- `source`
- `source_id`

- [ ] **Step 2: Add generate test**

Bind `rfq_ai_insights` as available and fake the provider. Assert `POST /api/v1/risk-items/generate` sends non-empty risk facts and stores/returns an available artifact.

- [ ] **Step 3: Run test to verify failure**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/RiskComplianceAiInsightsApiTest.php
```

Expected: FAIL because `loadRiskItems()` returns `[]`.

### Task 3.2: Implement Risk Fact Adapter And Controller Delegation

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/RiskInsightFactsAdapter.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RiskComplianceController.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Implement tenant-scoped RFQ risk facts**

Build risk items from available current models:

- RFQ deadline risk from `Rfq::submission_deadline` and `Rfq::closing_date`.
- Vendor governance risk from `VendorFinding` records for vendors associated with the RFQ through selected vendors, invitations, or quote submissions.
- Quote intake readiness risk from `QuoteSubmission` status fields.

Every query must start with tenant id and RFQ id. Wrong tenant remains `404`.

- [ ] **Step 2: Refactor controller**

Remove `loadRiskItems()` from `RiskComplianceController`. Inject `RiskInsightCoordinator`. `index()` calls `show($tenantId, $rfqId)`. `generate()` calls `generate($tenantId, $rfqId, $userId)`.

- [ ] **Step 3: Run risk tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/RiskComplianceAiInsightsApiTest.php
```

Expected: PASS.

### Task 3.3: Write Failing Governance/Sanctions Tests

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorGovernanceApiTest.php`

- [ ] **Step 1: Add sanctions truthfulness test**

Add a test for a vendor with no sanctions provider and no manual evidence. Assert a read/history endpoint does not claim a completed screening. Expected response should expose no history and a manual-required or unconfigured state, depending on the endpoint shape chosen in implementation.

- [ ] **Step 2: Add governance narrative orchestrator delegation test**

Use a fake provider and assert the generated `source_facts` excludes raw evidence ids, finding ids, raw notes, plain actor names, emails, and phone numbers.

- [ ] **Step 3: Run tests to verify failure**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/VendorGovernanceApiTest.php
```

Expected: FAIL for delegation/sanctions truthfulness until adapters/controllers are refactored.

### Task 3.4: Implement Governance Adapter And Controller Delegation

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/GovernanceFactsAdapter.php`
- Create: `apps/atomy-q/API/app/Adapters/InsightOperations/ProviderGovernanceNarrativeAdapter.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Implement governance facts adapter**

Move fact loading logic out of `VendorGovernanceController` into `GovernanceFactsAdapter`. Preserve current deterministic behavior:

- `VendorEvidence`
- `VendorFinding`
- `VendorGovernanceScoreService`
- warning flags
- due-diligence status
- sanctions history

- [ ] **Step 2: Refactor controller narrative generation**

`VendorGovernanceController::show()` delegates narrative read to `GovernanceNarrativeCoordinator::show($tenantId, $vendorId)`.

`VendorGovernanceController::generate()` delegates to `GovernanceNarrativeCoordinator::generate($tenantId, $vendorId, $userId)`.

The controller can still own due-diligence patching and manual evidence creation because those are Laravel write endpoints, but it must not assemble provider prompt context.

- [ ] **Step 3: Make sanctions semantics truthful**

For manual sanctions screening, keep `screening_status='completed'` only when a new `VendorEvidence` record is created or an idempotent replay returns the previously created evidence.

For history/read behavior with no records, return `history=[]` plus a metadata field such as:

```json
{
  "screening_status": "manual_review_required",
  "reason_code": "sanctions_provider_not_configured"
}
```

Do not return static completed/no-match status for a vendor when no real or manual screening occurred.

- [ ] **Step 4: Run governance tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/VendorGovernanceApiTest.php
```

Expected: PASS.

### Task 3.5: Part 3 Verification And Commit

- [ ] **Step 1: Run focused API tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/RiskComplianceAiInsightsApiTest.php tests/Feature/Api/V1/VendorGovernanceApiTest.php
```

Expected: PASS.

- [ ] **Step 2: Run orchestrator tests**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit
```

Expected: PASS.

- [ ] **Step 3: Update API summary**

Update `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` with RFQ risk fact sources, governance delegation, and sanctions truthfulness semantics.

- [ ] **Step 4: Commit Part 3**

Run:

```bash
git add apps/atomy-q/API orchestrators/InsightOperations
git commit -m "feat: make RFQ risk and governance AI facts truthful"
```

## Part 4: WEB Generation UX

### Task 4.1: Extend Shared Narrative Hook

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-ai-narrative-summary.ts`

- [ ] **Step 1: Add mutation support**

Extend `useAiNarrativeSummary()` options:

```ts
options?: {
  enabled?: boolean;
  queryKey?: readonly unknown[];
  generatePath?: string;
}
```

Return:

```ts
generate: (() => void) | null;
isGenerating: boolean;
generateError: Error | null;
canGenerate: boolean;
```

Use `useMutation` to call `api.post(generatePath)`. On success, normalize the generated response and update the existing query cache for the query key.

- [ ] **Step 2: Keep read behavior unchanged**

The hook must still fetch via `api.get(path)` and normalize `ai_summary`, `ai_insights`, or `ai_narrative` containers.

### Task 4.2: Extend AiNarrativePanel

**Files:**
- Modify: `apps/atomy-q/WEB/src/components/ai/ai-narrative-panel.tsx`
- Modify: `apps/atomy-q/WEB/src/components/ai/ai-narrative-panel.test.tsx`

- [ ] **Step 1: Write failing component tests**

Add tests:

```ts
it('renders a generate button when generation is available')
it('disables generate when canGenerate is false')
it('calls onGenerate when the button is clicked')
it('keeps unavailable callout scoped to the panel')
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:unit -- src/components/ai/ai-narrative-panel.test.tsx
```

Expected: FAIL because generation props do not exist.

- [ ] **Step 3: Implement panel props**

Add props:

```ts
onGenerate?: () => void;
isGenerating?: boolean;
canGenerate?: boolean;
generateLabel?: string;
```

Render the button in `SectionCard.actions` when `onGenerate` exists and the feature is not hidden. Label states:

- Default: `Generate`
- Generating: `Generating...`
- Available summary exists: `Regenerate`

- [ ] **Step 4: Run component tests**

Run the same Vitest command.

Expected: PASS.

### Task 4.3: Wire Dashboard And Reporting Generation

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-dashboard-ai-summary.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-reporting-ai-summary.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/reporting/page.tsx`

- [ ] **Step 1: Add generate paths**

`useDashboardAiSummary()` passes `generatePath: '/dashboard/kpis/generate'`.

`useReportingAiSummary()` passes `generatePath: '/reports/kpis/generate'` for the current reporting page.

- [ ] **Step 2: Pass generation props into panels**

Dashboard and reporting pages pass `onGenerate`, `isGenerating`, `canGenerate`, and `generateLabel`.

- [ ] **Step 3: Run focused tests**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:unit -- src/components/ai/ai-narrative-panel.test.tsx
```

Expected: PASS.

### Task 4.4: Wire RFQ Risk And Vendor Governance Generation

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-governance.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.tsx`

- [ ] **Step 1: Add vendor governance mutation**

Add `useGenerateVendorGovernanceNarrative(vendorId: string)` in `use-vendor-governance.ts`. It must call:

```ts
api.post(`/vendors/${encodeURIComponent(vendorId)}/governance/generate`)
```

On success, update `['vendors', vendorId, 'governance']` query data using `normalizeVendorGovernancePayload`.

- [ ] **Step 2: Add RFQ risk generation path**

If the RFQ risk page already uses `useAiNarrativeSummary`, pass `generatePath: '/risk-items/generate'` with the RFQ id in the request payload. If the page owns a bespoke query, add a mutation that posts `{ rfq_id: rfqId }` and refreshes the risk query.

- [ ] **Step 3: Pass generation props**

Vendor detail, vendor ESG/compliance, and RFQ risk pages pass generation props into `AiNarrativePanel`.

- [ ] **Step 4: Run WEB tests**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:unit -- src/components/ai/ai-narrative-panel.test.tsx
```

Expected: PASS.

### Task 4.5: Part 4 Verification And Commit

- [ ] **Step 1: Run WEB unit tests**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:unit -- src/components/ai/ai-narrative-panel.test.tsx src/hooks/use-ai-status.test.ts
```

Expected: PASS.

- [ ] **Step 2: Run WEB build**

Run:

```bash
cd apps/atomy-q/WEB
npm run build
```

Expected: PASS.

- [ ] **Step 3: Update WEB summary**

Update `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` with shared generation UX, mutation paths, and verification results.

- [ ] **Step 4: Commit Part 4**

Run:

```bash
git add apps/atomy-q/WEB
git commit -m "feat: add AI narrative generation controls"
```

## Part 5: Contract, Generated Client, Documentation, Final Verification

### Task 5.1: Update OpenAPI And Generated Client

**Files:**
- Modify: `apps/atomy-q/API/openapi/openapi.json`
- Modify generated files under `apps/atomy-q/WEB/src/generated/api/`

- [ ] **Step 1: Update OpenAPI response schemas**

Ensure dashboard/report/risk/governance schemas include:

- `source_facts_hash`
- `reason_codes`
- `provenance`
- `screening_status` and `reason_code` for sanctions history/read paths when no screening evidence exists

- [ ] **Step 2: Validate OpenAPI JSON**

Run:

```bash
jq empty apps/atomy-q/API/openapi/openapi.json
```

Expected: no output and exit code 0.

- [ ] **Step 3: Regenerate WEB client**

Run:

```bash
cd apps/atomy-q/WEB
npm run generate:api
```

Expected: command exits 0 and generated files update only from OpenAPI changes.

### Task 5.2: Update AI Plan Docs And Summaries

**Files:**
- Modify: `docs/superpowers/plans/2026-04-23-atomy-q-ai-insights-governance-and-reporting.md`
- Modify: `docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md`
- Modify: `orchestrators/InsightOperations/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Link the corrective plan**

In Plan 5, add a note that the alpha corrective implementation is specified by:

```text
docs/superpowers/specs/2026-04-30-atomy-q-ai-insights-governance-functional-reality-design.md
docs/superpowers/plans/2026-04-30-atomy-q-ai-insights-governance-functional-reality.md
```

- [ ] **Step 2: Update launch hardening dependencies**

In Plan 6, add Plan 5 verification evidence requirements:

- Real dashboard/report facts verified from seeded tenant data.
- RFQ risk items are not empty stubs.
- Sanctions endpoints do not fake completed/no-match outcomes.
- WEB generation buttons are capability-gated.

- [ ] **Step 3: Confirm summaries include commands and contract changes**

Each `IMPLEMENTATION_SUMMARY.md` touched by Parts 1-4 must include exact commands run and outcomes. Any changes to API response envelopes, AI artifact structure, or orchestrator interfaces must be explicitly recorded.

### Task 5.3: Final Verification Matrix

- [ ] **Step 1: Run orchestrator gate**

Run:

```bash
cd orchestrators/InsightOperations
./vendor/bin/phpunit
```

Expected: PASS.

- [ ] **Step 2: Run API focused gate**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php tests/Feature/Api/V1/RiskComplianceAiInsightsApiTest.php tests/Feature/Api/V1/VendorGovernanceApiTest.php
```

Expected: PASS.

- [ ] **Step 3: Run API full test gate if focused gate passes**

Run:

```bash
cd apps/atomy-q/API
php artisan test
```

Expected: PASS.

- [ ] **Step 4: Run WEB focused gate**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:unit -- src/components/ai/ai-narrative-panel.test.tsx src/hooks/use-ai-status.test.ts
```

Expected: PASS.

- [ ] **Step 5: Run WEB release gate**

Run:

```bash
cd apps/atomy-q/WEB
npm run build
```

Expected: PASS.

- [ ] **Step 6: Confirm no controller-side Plan 5 provider orchestration remains**

Run:

```bash
grep -R "ProviderInsightClientInterface\\|ProviderGovernanceClientInterface\\|InsightSummaryRequest\\|GovernanceNarrativeRequest" -n apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/ReportController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/RiskComplianceController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php
```

Expected: no matches.

### Task 5.4: Final Commit

- [ ] **Step 1: Review diff**

Run:

```bash
git status --short
git diff --stat
```

Expected: only files in this plan are changed, plus generated client files from OpenAPI generation.

- [ ] **Step 2: Commit Part 5**

Run:

```bash
git add docs/superpowers/plans apps/atomy-q/API/openapi/openapi.json apps/atomy-q/WEB/src/generated/api orchestrators/InsightOperations/IMPLEMENTATION_SUMMARY.md apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
git commit -m "docs: close AI insights governance implementation contract"
```

## Plan Completion Criteria

- `InsightOperations` owns Plan 5 coordination.
- Dashboard/report facts are tenant-scoped and not hardcoded zeros.
- RFQ risk items come from deterministic data or explicit unavailable source facts.
- Governance narrative uses sanitized evidence/findings/scores and never becomes source of truth.
- Sanctions screening does not fake completed/no-match outcomes.
- WEB users can trigger generation through shared narrative controls.
- OpenAPI and generated client match final API response contracts.
- Focused orchestrator, API, WEB, and build gates pass.
