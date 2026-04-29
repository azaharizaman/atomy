# AI Insights & Governance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement Plan 5 slice — AI insights (dashboard, RFQ, reporting) and governance narratives for Atomy-Q alpha release, using per-surface coordinators in InsightOperations with cache + event-driven invalidation.

**Architecture:** Four per-surface coordinators in InsightOperations (DashboardInsight, RfqInsight, GovernanceNarrative, ReportingSummary) each with their own contracts, DTOs in MachineLearning/ProcurementML, provider clients in API adapters, and WEB rendering via existing ai-narrative-panel primitives. Cache uses InsightStoragePort with TTL + event-driven invalidation + manual refresh.

**Tech Stack:** PHP 8.3, Laravel, Nexus Layer1 (MachineLearning, ProcurementML), Layer2 (InsightOperations), Layer3 (API adapters, controllers), Next.js/React, TypeScript, TanStack Query, PHPUnit, Vitest.

---

## File Structure

### Layer 1: MachineLearning Package
- `packages/MachineLearning/src/Contracts/NarrativeRequestInterface.php` — (new) narrative request contract
- `packages/MachineLearning/src/Contracts/NarrativeResultInterface.php` — (new) narrative result contract
- `packages/MachineLearning/src/Contracts/NarrativeGeneratorInterface.php` — (new) generator contract
- `packages/MachineLearning/src/DTOs/NarrativeRequest.php` — (new) request DTO
- `packages/MachineLearning/src/DTOs/NarrativeResult.php` — (new) result DTO
- `packages/MachineLearning/src/DTOs/DashboardSummaryDto.php` — (new) dashboard summary DTO
- `packages/MachineLearning/src/DTOs/DashboardSummaryRequest.php` — (new) dashboard request DTO
- `packages/MachineLearning/src/DTOs/DashboardSummaryResult.php` — (new) dashboard result DTO
- `packages/MachineLearning/tests/Unit/DTOs/NarrativeRequestTest.php` — (new) test
- `packages/MachineLearning/tests/Unit/DTOs/NarrativeResultTest.php` — (new) test
- `packages/MachineLearning/tests/Unit/Contracts/NarrativeGeneratorTest.php` — (new) test

### Layer 1: ProcurementML Package
- `packages/ProcurementML/src/DTOs/RfqInsightDto.php` — (new) RFQ insight DTO
- `packages/ProcurementML/src/DTOs/RfqInsightRequest.php` — (new) RFQ request DTO
- `packages/ProcurementML/src/DTOs/RfqInsightResult.php` — (new) RFQ result DTO
- `packages/ProcurementML/src/DTOs/GovernanceNarrativeDto.php` — (new) governance narrative DTO
- `packages/ProcurementML/src/DTOs/GovernanceNarrativeRequest.php` — (new) governance request DTO
- `packages/ProcurementML/src/DTOs/GovernanceNarrativeResult.php` — (new) governance result DTO
- `packages/ProcurementML/src/DTOs/ReportingSummaryDto.php` — (new) reporting summary DTO
- `packages/ProcurementML/src/DTOs/ReportingSummaryRequest.php` — (new) reporting request DTO
- `packages/ProcurementML/src/DTOs/ReportingSummaryResult.php` — (new) reporting result DTO
- `packages/ProcurementML/tests/Unit/DTOs/RfqInsightDtoTest.php` — (new) test
- `packages/ProcurementML/tests/Unit/DTOs/GovernanceNarrativeDtoTest.php` — (new) test
- `packages/ProcurementML/tests/Unit/DTOs/ReportingSummaryDtoTest.php` — (new) test

### Layer 2: InsightOperations Orchestrator
- `orchestrators/InsightOperations/src/Contracts/DashboardInsightCoordinatorInterface.php` — (new)
- `orchestrators/InsightOperations/src/Contracts/RfqInsightCoordinatorInterface.php` — (new)
- `orchestrators/InsightOperations/src/Contracts/GovernanceNarrativeCoordinatorInterface.php` — (new)
- `orchestrators/InsightOperations/src/Contracts/ReportingSummaryCoordinatorInterface.php` — (new)
- `orchestrators/InsightOperations/src/Coordinators/DashboardInsightCoordinator.php` — (new)
- `orchestrators/InsightOperations/src/Coordinators/RfqInsightCoordinator.php` — (new)
- `orchestrators/InsightOperations/src/Coordinators/GovernanceNarrativeCoordinator.php` — (new)
- `orchestrators/InsightOperations/src/Coordinators/ReportingSummaryCoordinator.php` — (new)
- `orchestrators/InsightOperations/src/Listeners/RfqStatusChangedListener.php` — (new) cache invalidation
- `orchestrators/InsightOperations/src/Listeners/VendorSanctionUpdatedListener.php` — (new)
- `orchestrators/InsightOperations/src/Listeners/ComplianceFlagChangedListener.php` — (new)
- `orchestrators/InsightOperations/src/Listeners/ReportDataChangedListener.php` — (new)
- `orchestrators/InsightOperations/src/Listeners/TenantDashboardDataChangedListener.php` — (new)
- `orchestrators/InsightOperations/tests/Unit/Coordinators/DashboardInsightCoordinatorTest.php` — (new)
- `orchestrators/InsightOperations/tests/Unit/Coordinators/RfqInsightCoordinatorTest.php` — (new)
- `orchestrators/InsightOperations/tests/Unit/Coordinators/GovernanceNarrativeCoordinatorTest.php` — (new)
- `orchestrators/InsightOperations/tests/Unit/Coordinators/ReportingSummaryCoordinatorTest.php` — (new)
- `orchestrators/InsightOperations/tests/Unit/Listeners/CacheInvalidationListenerTest.php` — (new)

### Layer 3: API Adapters & Controllers
- `apps/atomy-q/API/app/Adapters/Ai/ProviderInsightClient.php` — (new) implements coordinator interfaces
- `apps/atomy-q/API/app/Adapters/Ai/ProviderGovernanceClient.php` — (new) implements governance interface
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqInsightsController.php` — (new)
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/InsightRefreshController.php` — (new)
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php` — add insights endpoint
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ReportController.php` — add summary endpoint
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php` — add narrative endpoint
- Modify: `apps/atomy-q/API/routes/api.php` — add new routes
- `apps/atomy-q/API/tests/Feature/DashboardInsightsApiTest.php` — (new)
- `apps/atomy-q/API/tests/Feature/RfqInsightsApiTest.php` — (new)
- `apps/atomy-q/API/tests/Feature/VendorGovernanceApiTest.php` — (new)
- `apps/atomy-q/API/tests/Feature/ReportSummaryApiTest.php` — (new)
- `apps/atomy-q/API/tests/Feature/InsightRefreshApiTest.php` — (new)

### Layer 3: WEB
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx` — add dashboard AI summary
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/overview/page.tsx` — add insights sidebar
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx` — add governance narrative
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/reporting/page.tsx` — add AI summary
- `apps/atomy-q/WEB/src/hooks/use-ai-insights.ts` — (new) hook for insights data fetching
- `apps/atomy-q/WEB/src/app/(dashboard)/page.test.tsx` — update test
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/overview/page.test.tsx` — update test
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.test.tsx` — update test
- `apps/atomy-q/WEB/src/hooks/use-ai-insights.test.ts` — (new) test

---

### Task 1: MachineLearning — Narrative Contracts & DTOs

**Files:**
- Create: `packages/MachineLearning/src/Contracts/NarrativeRequestInterface.php`
- Create: `packages/MachineLearning/src/Contracts/NarrativeResultInterface.php`
- Create: `packages/MachineLearning/src/Contracts/NarrativeGeneratorInterface.php`
- Create: `packages/MachineLearning/src/DTOs/NarrativeRequest.php`
- Create: `packages/MachineLearning/src/DTOs/NarrativeResult.php`
- Create: `packages/MachineLearning/src/DTOs/DashboardSummaryDto.php`
- Create: `packages/MachineLearning/src/DTOs/DashboardSummaryRequest.php`
- Create: `packages/MachineLearning/src/DTOs/DashboardSummaryResult.php`
- Test: `packages/MachineLearning/tests/Unit/DTOs/NarrativeRequestTest.php`
- Test: `packages/MachineLearning/tests/Unit/DTOs/NarrativeResultTest.php`
- Test: `packages/MachineLearning/tests/Unit/Contracts/NarrativeGeneratorTest.php`

- [ ] **Step 1: Create NarrativeRequestInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

interface NarrativeRequestInterface
{
    public function getContext(): array;
    public function getNarrativeType(): string;
    public function getMaxTokens(): int;
    public function getTemperature(): float;
}
```

- [ ] **Step 2: Create NarrativeResultInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

interface NarrativeResultInterface
{
    public function getNarrative(): string;
    public function getConfidence(): float;
    public function getProvenance(): array;
    public function getTokenUsage(): array;
}
```

- [ ] **Step 3: Create NarrativeGeneratorInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

interface NarrativeGeneratorInterface
{
    public function generateNarrative(NarrativeRequestInterface $request): NarrativeResultInterface;
}
```

- [ ] **Step 4: Create NarrativeRequest DTO**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\DTOs;

use Nexus\MachineLearning\Contracts\NarrativeRequestInterface;

final readonly class NarrativeRequest implements NarrativeRequestInterface
{
    public function __construct(
        private array $context,
        private string $narrativeType,
        private int $maxTokens = 500,
        private float $temperature = 0.7,
    ) {}

    public function getContext(): array { return $this->context; }
    public function getNarrativeType(): string { return $this->narrativeType; }
    public function getMaxTokens(): int { return $this->maxTokens; }
    public function getTemperature(): float { return $this->temperature; }
}

// Narrative types constants
class NarrativeTypes
{
    public const string TYPE_DASHBOARD_SUMMARY = 'dashboard_summary';
    public const string TYPE_RFQ_INSIGHTS = 'rfq_insights';
    public const string TYPE_GOVERNANCE_NARRATIVE = 'governance_narrative';
    public const string TYPE_REPORTING_SUMMARY = 'reporting_summary';
}
```

- [ ] **Step 5: Create NarrativeResult DTO**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\DTOs;

use Nexus\MachineLearning\Contracts\NarrativeResultInterface;

final readonly class NarrativeResult implements NarrativeResultInterface
{
    public function __construct(
        private string $narrative,
        private float $confidence,
        private array $provenance,
        private array $tokenUsage,
    ) {}

    public function getNarrative(): string { return $this->narrative; }
    public function getConfidence(): float { return $this->confidence; }
    public function getProvenance(): array { return $this->provenance; }
    public function getTokenUsage(): array { return $this->tokenUsage; }
}
```

- [ ] **Step 6: Create DashboardSummaryDto**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\DTOs;

final readonly class DashboardSummaryDto
{
    public function __construct(
        public string $tenantId,
        public array $rfqHealth,
        public array $vendorRiskHighlights,
        public array $sourcingStatus,
        public string $narrative,
    ) {}
}
```

- [ ] **Step 7: Create DashboardSummaryRequest**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\DTOs;

final readonly class DashboardSummaryRequest
{
    public function __construct(
        public string $tenantId,
        public array $factualContext,
    ) {}
}
```

- [ ] **Step 8: Create DashboardSummaryResult**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\DTOs;

final readonly class DashboardSummaryResult
{
    public function __construct(
        public DashboardSummaryDto $dto,
        public array $provenance,
    ) {}
}
```

- [ ] **Step 9: Write NarrativeRequestTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Nexus\MachineLearning\DTOs\NarrativeRequest;
use Nexus\MachineLearning\DTOs\NarrativeTypes;

final class NarrativeRequestTest extends TestCase
{
    public function test_constructs_with_defaults(): void
    {
        $request = new NarrativeRequest(
            context: ['key' => 'value'],
            narrativeType: NarrativeTypes::TYPE_DASHBOARD_SUMMARY,
        );

        $this->assertSame(['key' => 'value'], $request->getContext());
        $this->assertSame(NarrativeTypes::TYPE_DASHBOARD_SUMMARY, $request->getNarrativeType());
        $this->assertSame(500, $request->getMaxTokens());
        $this->assertSame(0.7, $request->getTemperature());
    }

    public function test_constructs_with_custom_values(): void
    {
        $request = new NarrativeRequest(
            context: ['data' => 'test'],
            narrativeType: NarrativeTypes::TYPE_RFQ_INSIGHTS,
            maxTokens: 1000,
            temperature: 0.3,
        );

        $this->assertSame(1000, $request->getMaxTokens());
        $this->assertSame(0.3, $request->getTemperature());
    }
}
```

- [ ] **Step 10: Write NarrativeResultTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Nexus\MachineLearning\DTOs\NarrativeResult;

final class NarrativeResultTest extends TestCase
{
    public function test_constructs_with_all_fields(): void
    {
        $result = new NarrativeResult(
            narrative: 'Test narrative',
            confidence: 0.85,
            provenance: ['provider' => 'openrouter', 'model' => 'gpt-4'],
            tokenUsage: ['input' => 100, 'output' => 50],
        );

        $this->assertSame('Test narrative', $result->getNarrative());
        $this->assertSame(0.85, $result->getConfidence());
        $this->assertSame(['provider' => 'openrouter', 'model' => 'gpt-4'], $result->getProvenance());
        $this->assertSame(['input' => 100, 'output' => 50], $result->getTokenUsage());
    }
}
```

- [ ] **Step 11: Run tests to verify they pass**

Run from `packages/MachineLearning`:
```bash
../../vendor/bin/phpunit tests/Unit/DTOs/NarrativeRequestTest.php tests/Unit/DTOs/NarrativeResultTest.php
```
Expected: PASS

- [ ] **Step 12: Commit**

```bash
git add packages/MachineLearning/src/Contracts/NarrativeRequestInterface.php \
        packages/MachineLearning/src/Contracts/NarrativeResultInterface.php \
        packages/MachineLearning/src/Contracts/NarrativeGeneratorInterface.php \
        packages/MachineLearning/src/DTOs/NarrativeRequest.php \
        packages/MachineLearning/src/DTOs/NarrativeResult.php \
        packages/MachineLearning/src/DTOs/DashboardSummaryDto.php \
        packages/MachineLearning/src/DTOs/DashboardSummaryRequest.php \
        packages/MachineLearning/src/DTOs/DashboardSummaryResult.php \
        packages/MachineLearning/tests/Unit/DTOs/NarrativeRequestTest.php \
        packages/MachineLearning/tests/Unit/DTOs/NarrativeResultTest.php
git commit -m "feat(MachineLearning): add narrative generation contracts and dashboard DTOs"
```

---

### Task 2: ProcurementML — RFQ, Governance, Reporting DTOs

**Files:**
- Create: `packages/ProcurementML/src/DTOs/RfqInsightDto.php`
- Create: `packages/ProcurementML/src/DTOs/RfqInsightRequest.php`
- Create: `packages/ProcurementML/src/DTOs/RfqInsightResult.php`
- Create: `packages/ProcurementML/src/DTOs/GovernanceNarrativeDto.php`
- Create: `packages/ProcurementML/src/DTOs/GovernanceNarrativeRequest.php`
- Create: `packages/ProcurementML/src/DTOs/GovernanceNarrativeResult.php`
- Create: `packages/ProcurementML/src/DTOs/ReportingSummaryDto.php`
- Create: `packages/ProcurementML/src/DTOs/ReportingSummaryRequest.php`
- Create: `packages/ProcurementML/src/DTOs/ReportingSummaryResult.php`
- Test: `packages/ProcurementML/tests/Unit/DTOs/RfqInsightDtoTest.php`
- Test: `packages/ProcurementML/tests/Unit/DTOs/GovernanceNarrativeDtoTest.php`
- Test: `packages/ProcurementML/tests/Unit/DTOs/ReportingSummaryDtoTest.php`

- [ ] **Step 1: Create RfqInsightDto**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class RfqInsightDto
{
    public function __construct(
        public string $rfqId,
        public array $vendorSpread,
        public array $timingRisk,
        public array $recommendationContext,
        public string $narrative,
    ) {}
}
```

- [ ] **Step 2: Create RfqInsightRequest**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class RfqInsightRequest
{
    public function __construct(
        public string $rfqId,
        public string $tenantId,
        public array $sourceData,
    ) {}
}
```

- [ ] **Step 3: Create RfqInsightResult**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class RfqInsightResult
{
    public function __construct(
        public RfqInsightDto $dto,
        public array $provenance,
    ) {}
}
```

- [ ] **Step 4: Create GovernanceNarrativeDto**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class GovernanceNarrativeDto
{
    public function __construct(
        public string $vendorId,
        public array $riskFlags,
        public array $complianceGaps,
        public string $esgSummary,
        public string $narrative,
    ) {}
}
```

- [ ] **Step 5: Create GovernanceNarrativeRequest**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class GovernanceNarrativeRequest
{
    public function __construct(
        public string $vendorId,
        public string $tenantId,
        public array $facts,
    ) {}
}
```

- [ ] **Step 6: Create GovernanceNarrativeResult**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class GovernanceNarrativeResult
{
    public function __construct(
        public GovernanceNarrativeDto $dto,
        public array $provenance,
    ) {}
}
```

- [ ] **Step 7: Create ReportingSummaryDto**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class ReportingSummaryDto
{
    public function __construct(
        public string $reportId,
        public array $trends,
        public array $anomalies,
        public string $narrative,
    ) {}
}
```

- [ ] **Step 8: Create ReportingSummaryRequest**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class ReportingSummaryRequest
{
    public function __construct(
        public string $reportId,
        public string $tenantId,
        public array $factualData,
    ) {}
}
```

- [ ] **Step 9: Create ReportingSummaryResult**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\DTOs;

final readonly class ReportingSummaryResult
{
    public function __construct(
        public ReportingSummaryDto $dto,
        public array $provenance,
    ) {}
}
```

- [ ] **Step 10: Write RfqInsightDtoTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Nexus\ProcurementML\DTOs\RfqInsightDto;

final class RfqInsightDtoTest extends TestCase
{
    public function test_dto_holds_all_fields(): void
    {
        $dto = new RfqInsightDto(
            rfqId: 'RFQ-001',
            vendorSpread: ['min' => 1000, 'max' => 1500],
            timingRisk: ['daysLeft' => 5, 'risk' => 'high'],
            recommendationContext: ['preferred' => 'Vendor A'],
            narrative: 'Vendor spread is widening',
        );

        $this->assertSame('RFQ-001', $dto->rfqId);
        $this->assertSame('Vendor spread is widening', $dto->narrative);
    }
}
```

- [ ] **Step 11: Write GovernanceNarrativeDtoTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeDto;

final class GovernanceNarrativeDtoTest extends TestCase
{
    public function test_dto_separates_facts_from_narrative(): void
    {
        $dto = new GovernanceNarrativeDto(
            vendorId: 'VENDOR-001',
            riskFlags: ['sanction' => true],
            complianceGaps: ['iso_cert' => 'missing'],
            esgSummary: 'High ESG risk',
            narrative: 'Vendor has active sanctions flags',
        );

        $this->assertSame('VENDOR-001', $dto->vendorId);
        $this->assertSame(['sanction' => true], $dto->riskFlags);
        $this->assertSame('Vendor has active sanctions flags', $dto->narrative);
    }
}
```

- [ ] **Step 12: Write ReportingSummaryDtoTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Nexus\ProcurementML\DTOs\ReportingSummaryDto;

final class ReportingSummaryDtoTest extends TestCase
{
    public function test_dto_holds_summary_data(): void
    {
        $dto = new ReportingSummaryDto(
            reportId: 'RPT-001',
            trends: ['spend' => '+12%'],
            anomalies: ['3 vendors' => '>$50K'],
            narrative: 'Spend up 12% QoQ',
        );

        $this->assertSame('RPT-001', $dto->reportId);
        $this->assertSame('Spend up 12% QoQ', $dto->narrative);
    }
}
```

- [ ] **Step 13: Run tests to verify they pass**

Run from `packages/ProcurementML`:
```bash
../../vendor/bin/phpunit tests/Unit/DTOs/RfqInsightDtoTest.php tests/Unit/DTOs/GovernanceNarrativeDtoTest.php tests/Unit/DTOs/ReportingSummaryDtoTest.php
```
Expected: PASS

- [ ] **Step 14: Commit**

```bash
git add packages/ProcurementML/src/DTOs/RfqInsightDto.php \
        packages/ProcurementML/src/DTOs/RfqInsightRequest.php \
        packages/ProcurementML/src/DTOs/RfqInsightResult.php \
        packages/ProcurementML/src/DTOs/GovernanceNarrativeDto.php \
        packages/ProcurementML/src/DTOs/GovernanceNarrativeRequest.php \
        packages/ProcurementML/src/DTOs/GovernanceNarrativeResult.php \
        packages/ProcurementML/src/DTOs/ReportingSummaryDto.php \
        packages/ProcurementML/src/DTOs/ReportingSummaryRequest.php \
        packages/ProcurementML/src/DTOs/ReportingSummaryResult.php \
        packages/ProcurementML/tests/Unit/DTOs/RfqInsightDtoTest.php \
        packages/ProcurementML/tests/Unit/DTOs/GovernanceNarrativeDtoTest.php \
        packages/ProcurementML/tests/Unit/DTOs/ReportingSummaryDtoTest.php
git commit -m "feat(ProcurementML): add RFQ insights, governance narrative, and reporting summary DTOs"
```

---

### Task 3: InsightOperations — Extend Storage Port & Create Coordinator Contracts

**Files:**
- Modify: `orchestrators/InsightOperations/src/Contracts/InsightStoragePortInterface.php` — add get() and delete()
- Modify: `adapters/Laravel/InsightOperations/src/InsightStoragePortAdapter.php` — implement new methods
- Create: `orchestrators/InsightOperations/src/Contracts/DashboardInsightCoordinatorInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/RfqInsightCoordinatorInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/GovernanceNarrativeCoordinatorInterface.php`
- Create: `orchestrators/InsightOperations/src/Contracts/ReportingSummaryCoordinatorInterface.php`

- [ ] **Step 1: Extend InsightStoragePortInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface InsightStoragePortInterface
{
    public function put(string $path, mixed $content): void;
    public function get(string $key): mixed;
    public function delete(string $key): void;
}
```

- [ ] **Step 2: Implement get() and delete() in InsightStoragePortAdapter**

Add to `adapters/Laravel/InsightOperations/src/InsightStoragePortAdapter.php`:

```php
public function get(string $key): mixed
{
    $path = $this->getStoragePath($key);
    if (!file_exists($path)) {
        return null;
    }
    return json_decode(file_get_contents($path), true);
}

public function delete(string $key): void
{
    $path = $this->getStoragePath($key);
    if (file_exists($path)) {
        unlink($path);
    }
}

private function getStoragePath(string $key): string
{
    return storage_path('insights/' . md5($key) . '.json');
}
```

- [ ] **Step 3: Create DashboardInsightCoordinatorInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\MachineLearning\DTOs\DashboardSummaryRequest;
use Nexus\MachineLearning\DTOs\DashboardSummaryResult;

interface DashboardInsightCoordinatorInterface
{
    public function generateSummary(DashboardSummaryRequest $request): DashboardSummaryResult;
    public function getCachedSummary(string $tenantId): ?\Nexus\MachineLearning\DTOs\DashboardSummaryDto;
    public function invalidateCache(string $tenantId): void;
}
```

- [ ] **Step 4: Create RfqInsightCoordinatorInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\ProcurementML\DTOs\RfqInsightRequest;
use Nexus\ProcurementML\DTOs\RfqInsightResult;

interface RfqInsightCoordinatorInterface
{
    public function generateInsights(RfqInsightRequest $request): RfqInsightResult;
    public function getCachedInsights(string $rfqId): ?\Nexus\ProcurementML\DTOs\RfqInsightDto;
    public function invalidateCache(string $rfqId): void;
}
```

- [ ] **Step 5: Create GovernanceNarrativeCoordinatorInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\ProcurementML\DTOs\GovernanceNarrativeRequest;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeResult;

interface GovernanceNarrativeCoordinatorInterface
{
    public function generateNarrative(GovernanceNarrativeRequest $request): GovernanceNarrativeResult;
    public function getCachedNarrative(string $vendorId): ?\Nexus\ProcurementML\DTOs\GovernanceNarrativeDto;
    public function invalidateCache(string $vendorId): void;
}
```

- [ ] **Step 6: Create ReportingSummaryCoordinatorInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\ProcurementML\DTOs\ReportingSummaryRequest;
use Nexus\ProcurementML\DTOs\ReportingSummaryResult;

interface ReportingSummaryCoordinatorInterface
{
    public function generateSummary(ReportingSummaryRequest $request): ReportingSummaryResult;
    public function getCachedSummary(string $reportId): ?\Nexus\ProcurementML\DTOs\ReportingSummaryDto;
    public function invalidateCache(string $reportId): void;
}
```

- [ ] **Step 5: Commit**

```bash
git add orchestrators/InsightOperations/src/Contracts/DashboardInsightCoordinatorInterface.php \
        orchestrators/InsightOperations/src/Contracts/RfqInsightCoordinatorInterface.php \
        orchestrators/InsightOperations/src/Contracts/GovernanceNarrativeCoordinatorInterface.php \
        orchestrators/InsightOperations/src/Contracts/ReportingSummaryCoordinatorInterface.php \
        orchestrators/InsightOperations/src/Contracts/InsightStoragePortInterface.php \
        adapters/Laravel/InsightOperations/src/InsightStoragePortAdapter.php
git commit -m "feat(InsightOperations): extend storage port and add per-surface coordinator contracts"
```

---

### Task 4: InsightOperations — Coordinator Implementations

**Files:**
- Create: `orchestrators/InsightOperations/src/Coordinators/DashboardInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/src/Coordinators/RfqInsightCoordinator.php`
- Create: `orchestrators/InsightOperations/src/Coordinators/GovernanceNarrativeCoordinator.php`
- Create: `orchestrators/InsightOperations/src/Coordinators/ReportingSummaryCoordinator.php`
- Test: `orchestrators/InsightOperations/tests/Unit/Coordinators/DashboardInsightCoordinatorTest.php`
- Test: `orchestrators/InsightOperations/tests/Unit/Coordinators/RfqInsightCoordinatorTest.php`
- Test: `orchestrators/InsightOperations/tests/Unit/Coordinators/GovernanceNarrativeCoordinatorTest.php`
- Test: `orchestrators/InsightOperations/tests/Unit/Coordinators/ReportingSummaryCoordinatorTest.php`

- [ ] **Step 1: Create DashboardInsightCoordinator**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Nexus\InsightOperations\Contracts\DashboardInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\MachineLearning\DTOs\DashboardSummaryRequest;
use Nexus\MachineLearning\DTOs\DashboardSummaryResult;
use Nexus\MachineLearning\DTOs\DashboardSummaryDto;
use Nexus\MachineLearning\DTOs\NarrativeTypes;

final readonly class DashboardInsightCoordinator implements DashboardInsightCoordinatorInterface
{
    public function __construct(
        private InsightStoragePortInterface $storage,
    ) {}

    public function generateSummary(DashboardSummaryRequest $request): DashboardSummaryResult
    {
        $cacheKey = "insight:dashboard:{$request->tenantId}";
        
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new DashboardSummaryResult(
                dto: DashboardSummaryDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        // Provider call would happen via API adapter implementing this interface
        // For now, return empty result that adapter will fulfill
        return new DashboardSummaryResult(
            dto: new DashboardSummaryDto(
                tenantId: $request->tenantId,
                rfqHealth: [],
                vendorRiskHighlights: [],
                sourcingStatus: [],
                narrative: '',
            ),
            provenance: [],
        );
    }

    public function getCachedSummary(string $tenantId): ?DashboardSummaryDto
    {
        $cached = $this->storage->get("insight:dashboard:{$tenantId}");
        return $cached !== null ? DashboardSummaryDto::fromArray($cached['dto']) : null;
    }

    public function invalidateCache(string $tenantId): void
    {
        $this->storage->delete("insight:dashboard:{$tenantId}");
    }
}
```

- [ ] **Step 2: Create RfqInsightCoordinator**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Nexus\InsightOperations\Contracts\RfqInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\ProcurementML\DTOs\RfqInsightRequest;
use Nexus\ProcurementML\DTOs\RfqInsightResult;
use Nexus\ProcurementML\DTOs\RfqInsightDto;

final readonly class RfqInsightCoordinator implements RfqInsightCoordinatorInterface
{
    public function __construct(
        private InsightStoragePortInterface $storage,
    ) {}

    public function generateInsights(RfqInsightRequest $request): RfqInsightResult
    {
        $cacheKey = "insight:rfq:{$request->rfqId}";
        
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new RfqInsightResult(
                dto: RfqInsightDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        return new RfqInsightResult(
            dto: new RfqInsightDto(
                rfqId: $request->rfqId,
                vendorSpread: [],
                timingRisk: [],
                recommendationContext: [],
                narrative: '',
            ),
            provenance: [],
        );
    }

    public function getCachedInsights(string $rfqId): ?RfqInsightDto
    {
        $cached = $this->storage->get("insight:rfq:{$rfqId}");
        return $cached !== null ? RfqInsightDto::fromArray($cached['dto']) : null;
    }

    public function invalidateCache(string $rfqId): void
    {
        $this->storage->delete("insight:rfq:{$rfqId}");
    }
}
```

- [ ] **Step 3: Create GovernanceNarrativeCoordinator**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Nexus\InsightOperations\Contracts\GovernanceNarrativeCoordinatorInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeRequest;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeResult;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeDto;

final readonly class GovernanceNarrativeCoordinator implements GovernanceNarrativeCoordinatorInterface
{
    public function __construct(
        private InsightStoragePortInterface $storage,
    ) {}

    public function generateNarrative(GovernanceNarrativeRequest $request): GovernanceNarrativeResult
    {
        $cacheKey = "insight:governance:{$request->vendorId}";
        
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new GovernanceNarrativeResult(
                dto: GovernanceNarrativeDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        return new GovernanceNarrativeResult(
            dto: new GovernanceNarrativeDto(
                vendorId: $request->vendorId,
                riskFlags: [],
                complianceGaps: [],
                esgSummary: '',
                narrative: '',
            ),
            provenance: [],
        );
    }

    public function getCachedNarrative(string $vendorId): ?GovernanceNarrativeDto
    {
        $cached = $this->storage->get("insight:governance:{$vendorId}");
        return $cached !== null ? GovernanceNarrativeDto::fromArray($cached['dto']) : null;
    }

    public function invalidateCache(string $vendorId): void
    {
        $this->storage->delete("insight:governance:{$vendorId}");
    }
}
```

- [ ] **Step 4: Create ReportingSummaryCoordinator**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Nexus\InsightOperations\Contracts\ReportingSummaryCoordinatorInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\ProcurementML\DTOs\ReportingSummaryRequest;
use Nexus\ProcurementML\DTOs\ReportingSummaryResult;
use Nexus\ProcurementML\DTOs\ReportingSummaryDto;

final readonly class ReportingSummaryCoordinator implements ReportingSummaryCoordinatorInterface
{
    public function __construct(
        private InsightStoragePortInterface $storage,
    ) {}

    public function generateSummary(ReportingSummaryRequest $request): ReportingSummaryResult
    {
        $cacheKey = "insight:reporting:{$request->reportId}";
        
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new ReportingSummaryResult(
                dto: ReportingSummaryDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        return new ReportingSummaryResult(
            dto: new ReportingSummaryDto(
                reportId: $request->reportId,
                trends: [],
                anomalies: [],
                narrative: '',
            ),
            provenance: [],
        );
    }

    public function getCachedSummary(string $reportId): ?ReportingSummaryDto
    {
        $cached = $this->storage->get("insight:reporting:{$reportId}");
        return $cached !== null ? ReportingSummaryDto::fromArray($cached['dto']) : null;
    }

    public function invalidateCache(string $reportId): void
    {
        $this->storage->delete("insight:reporting:{$reportId}");
    }
}
```

- [ ] **Step 5: Add fromArray factory to DashboardSummaryDto**

Add to `packages/MachineLearning/src/DTOs/DashboardSummaryDto.php`:

```php
public static function fromArray(array $data): self
{
    return new self(
        tenantId: $data['tenantId'],
        rfqHealth: $data['rfqHealth'],
        vendorRiskHighlights: $data['vendorRiskHighlights'],
        sourcingStatus: $data['sourcingStatus'],
        narrative: $data['narrative'],
    );
}
```

- [ ] **Step 6: Add fromArray to RfqInsightDto**

Add to `packages/ProcurementML/src/DTOs/RfqInsightDto.php`:

```php
public static function fromArray(array $data): self
{
    return new self(
        rfqId: $data['rfqId'],
        vendorSpread: $data['vendorSpread'],
        timingRisk: $data['timingRisk'],
        recommendationContext: $data['recommendationContext'],
        narrative: $data['narrative'],
    );
}
```

- [ ] **Step 7: Add fromArray to GovernanceNarrativeDto**

Add to `packages/ProcurementML/src/DTOs/GovernanceNarrativeDto.php`:

```php
public static function fromArray(array $data): self
{
    return new self(
        vendorId: $data['vendorId'],
        riskFlags: $data['riskFlags'],
        complianceGaps: $data['complianceGaps'],
        esgSummary: $data['esgSummary'],
        narrative: $data['narrative'],
    );
}
```

- [ ] **Step 8: Add fromArray to ReportingSummaryDto**

Add to `packages/ProcurementML/src/DTOs/ReportingSummaryDto.php`:

```php
public static function fromArray(array $data): self
{
    return new self(
        reportId: $data['reportId'],
        trends: $data['trends'],
        anomalies: $data['anomalies'],
        narrative: $data['narrative'],
    );
}
```

- [ ] **Step 6: Write DashboardInsightCoordinatorTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\InsightOperations\Coordinators\DashboardInsightCoordinator;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\MachineLearning\DTOs\DashboardSummaryRequest;
use Nexus\MachineLearning\DTOs\DashboardSummaryDto;

final class DashboardInsightCoordinatorTest extends TestCase
{
    public function test_generate_summary_caches_result(): void
    {
        $storage = $this->createMock(InsightStoragePortInterface::class);
        $coordinator = new DashboardInsightCoordinator($storage);

        $request = new DashboardSummaryRequest(
            tenantId: 'tenant-123',
            factualContext: [],
        );

        $storage->method('get')->willReturn(null);
        $storage->method('put');

        $result = $coordinator->generateSummary($request);
        
        $this->assertInstanceOf(DashboardSummaryDto::class, $result->dto);
        $this->assertSame('tenant-123', $result->dto->tenantId);
    }

    public function test_get_cached_summary_returns_null_when_not_cached(): void
    {
        $storage = $this->createMock(InsightStoragePortInterface::class);
        $storage->method('get')->willReturn(null);
        
        $coordinator = new DashboardInsightCoordinator($storage);
        
        $this->assertNull($coordinator->getCachedSummary('tenant-123'));
    }

    public function test_invalidate_cache_clears_data(): void
    {
        $storage = $this->createMock(InsightStoragePortInterface::class);
        $storage->expects($this->once())->method('delete')->with('insight:dashboard:tenant-123');
        
        $coordinator = new DashboardInsightCoordinator($storage);
        $coordinator->invalidateCache('tenant-123');
    }
}
```

- [ ] **Step 7: Write RfqInsightCoordinatorTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\InsightOperations\Coordinators\RfqInsightCoordinator;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\ProcurementML\DTOs\RfqInsightRequest;
use Nexus\ProcurementML\DTOs\RfqInsightDto;

final class RfqInsightCoordinatorTest extends TestCase
{
    public function test_generate_insights_returns_dto(): void
    {
        $storage = $this->createMock(InsightStoragePortInterface::class);
        $coordinator = new RfqInsightCoordinator($storage);

        $request = new RfqInsightRequest(
            rfqId: 'RFQ-001',
            tenantId: 'tenant-123',
            sourceData: [],
        );

        $result = $coordinator->generateInsights($request);
        
        $this->assertSame('RFQ-001', $result->dto->rfqId);
    }

    public function test_invalidate_cache_clears_rfq_cache(): void
    {
        $storage = $this->createMock(InsightStoragePortInterface::class);
        $storage->expects($this->once())->method('delete')->with('insight:rfq:RFQ-001');
        
        $coordinator = new RfqInsightCoordinator($storage);
        $coordinator->invalidateCache('RFQ-001');
    }
}
```

- [ ] **Step 8: Run coordinator tests**

Run from monorepo root (uses root PHPUnit as per InsightOperations config):
```bash
./vendor/bin/phpunit orchestrators/InsightOperations/tests/Unit/Coordinators/
```
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add orchestrators/InsightOperations/src/Coordinators/ \
        orchestrators/InsightOperations/tests/Unit/Coordinators/
git commit -m "feat(InsightOperations): implement per-surface coordinators with cache support"
```

---

### Task 5: InsightOperations — Cache Invalidation Listeners

**Files:**
- Create: `orchestrators/InsightOperations/src/Listeners/RfqStatusChangedListener.php`
- Create: `orchestrators/InsightOperations/src/Listeners/VendorSanctionUpdatedListener.php`
- Create: `orchestrators/InsightOperations/src/Listeners/ComplianceFlagChangedListener.php`
- Create: `orchestrators/InsightOperations/src/Listeners/ReportDataChangedListener.php`
- Create: `orchestrators/InsightOperations/src/Listeners/TenantDashboardDataChangedListener.php`
- Test: `orchestrators/InsightOperations/tests/Unit/Listeners/CacheInvalidationListenerTest.php`

- [ ] **Step 1: Create RfqStatusChangedListener**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Listeners;

use Nexus\InsightOperations\Contracts\RfqInsightCoordinatorInterface;

final readonly class RfqStatusChangedListener
{
    public function __construct(
        private RfqInsightCoordinatorInterface $coordinator,
    ) {}

    public function handle(string $rfqId): void
    {
        $this->coordinator->invalidateCache($rfqId);
    }
}
```

- [ ] **Step 2: Create VendorSanctionUpdatedListener**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Listeners;

use Nexus\InsightOperations\Contracts\GovernanceNarrativeCoordinatorInterface;

final readonly class VendorSanctionUpdatedListener
{
    public function __construct(
        private GovernanceNarrativeCoordinatorInterface $coordinator,
    ) {}

    public function handle(string $vendorId): void
    {
        $this->coordinator->invalidateCache($vendorId);
    }
}
```

- [ ] **Step 3: Create ComplianceFlagChangedListener**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Listeners;

use Nexus\InsightOperations\Contracts\GovernanceNarrativeCoordinatorInterface;

final readonly class ComplianceFlagChangedListener
{
    public function __construct(
        private GovernanceNarrativeCoordinatorInterface $coordinator,
    ) {}

    public function handle(string $vendorId): void
    {
        $this->coordinator->invalidateCache($vendorId);
    }
}
```

- [ ] **Step 4: Create ReportDataChangedListener**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Listeners;

use Nexus\InsightOperations\Contracts\ReportingSummaryCoordinatorInterface;

final readonly class ReportDataChangedListener
{
    public function __construct(
        private ReportingSummaryCoordinatorInterface $coordinator,
    ) {}

    public function handle(string $reportId): void
    {
        $this->coordinator->invalidateCache($reportId);
    }
}
```

- [ ] **Step 5: Create TenantDashboardDataChangedListener**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Listeners;

use Nexus\InsightOperations\Contracts\DashboardInsightCoordinatorInterface;

final readonly class TenantDashboardDataChangedListener
{
    public function __construct(
        private DashboardInsightCoordinatorInterface $coordinator,
    ) {}

    public function handle(string $tenantId): void
    {
        $this->coordinator->invalidateCache($tenantId);
    }
}
```

- [ ] **Step 6: Write CacheInvalidationListenerTest**

```php
<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Listeners;

use PHPUnit\Framework\TestCase;
use Nexus\InsightOperations\Listeners\RfqStatusChangedListener;
use Nexus\InsightOperations\Listeners\VendorSanctionUpdatedListener;
use Nexus\InsightOperations\Contracts\RfqInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\GovernanceNarrativeCoordinatorInterface;

final class CacheInvalidationListenerTest extends TestCase
{
    public function test_rfq_status_changed_invalidates_cache(): void
    {
        $coordinator = $this->createMock(RfqInsightCoordinatorInterface::class);
        $coordinator->expects($this->once())->method('invalidateCache')->with('RFQ-001');
        
        $listener = new RfqStatusChangedListener($coordinator);
        $listener->handle('RFQ-001');
    }

    public function test_vendor_sanction_updated_invalidates_governance_cache(): void
    {
        $coordinator = $this->createMock(GovernanceNarrativeCoordinatorInterface::class);
        $coordinator->expects($this->once())->method('invalidateCache')->with('VENDOR-001');
        
        $listener = new VendorSanctionUpdatedListener($coordinator);
        $listener->handle('VENDOR-001');
    }
}
```

- [ ] **Step 7: Run listener tests**

```bash
./vendor/bin/phpunit orchestrators/InsightOperations/tests/Unit/Listeners/
```
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add orchestrators/InsightOperations/src/Listeners/ \
        orchestrators/InsightOperations/tests/Unit/Listeners/
git commit -m "feat(InsightOperations): add cache invalidation listeners for event-driven refresh"
```

---

### Task 6: API Layer 3 — Provider Clients

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/Ai/ProviderInsightClient.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/ProviderGovernanceClient.php`

- [ ] **Step 1: Create ProviderInsightClient**

```php
<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\InsightOperations\Contracts\DashboardInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\RfqInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\ReportingSummaryCoordinatorInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\MachineLearning\DTOs\DashboardSummaryRequest;
use Nexus\MachineLearning\DTOs\DashboardSummaryResult;
use Nexus\MachineLearning\DTOs\DashboardSummaryDto;
use Nexus\MachineLearning\DTOs\NarrativeRequest;
use Nexus\MachineLearning\DTOs\NarrativeResult;
use Nexus\MachineLearning\DTOs\NarrativeTypes;
use Nexus\ProcurementML\DTOs\RfqInsightRequest;
use Nexus\ProcurementML\DTOs\RfqInsightResult;
use Nexus\ProcurementML\DTOs\RfqInsightDto;
use Nexus\ProcurementML\DTOs\ReportingSummaryRequest;
use Nexus\ProcurementML\DTOs\ReportingSummaryResult;
use Nexus\ProcurementML\DTOs\ReportingSummaryDto;

final class ProviderInsightClient implements
    DashboardInsightCoordinatorInterface,
    RfqInsightCoordinatorInterface,
    ReportingSummaryCoordinatorInterface
{
    public function __construct(
        private InsightStoragePortInterface $storage,
        private \App\Services\Ai\ProviderService $providerService,
    ) {}

    public function generateSummary(DashboardSummaryRequest $request): DashboardSummaryResult
    {
        $cacheKey = "insight:dashboard:{$request->tenantId}";
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new DashboardSummaryResult(
                dto: DashboardSummaryDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        $narrativeRequest = new NarrativeRequest(
            context: $request->factualContext,
            narrativeType: NarrativeTypes::TYPE_DASHBOARD_SUMMARY,
            maxTokens: 500,
            temperature: 0.7,
        );

        $result = $this->providerService->generateNarrative($narrativeRequest);
        
        $dto = new DashboardSummaryDto(
            tenantId: $request->tenantId,
            rfqHealth: [],
            vendorRiskHighlights: [],
            sourcingStatus: [],
            narrative: $result->getNarrative(),
        );

        $this->storage->put($cacheKey, [
            'dto' => (array) $dto,
            'provenance' => $result->getProvenance(),
        ]);

        return new DashboardSummaryResult($dto, $result->getProvenance());
    }

    public function generateInsights(RfqInsightRequest $request): RfqInsightResult
    {
        $cacheKey = "insight:rfq:{$request->rfqId}";
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new RfqInsightResult(
                dto: RfqInsightDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        $narrativeRequest = new NarrativeRequest(
            context: $request->sourceData,
            narrativeType: NarrativeTypes::TYPE_RFQ_INSIGHTS,
            maxTokens: 500,
            temperature: 0.7,
        );

        $result = $this->providerService->generateNarrative($narrativeRequest);

        $dto = new RfqInsightDto(
            rfqId: $request->rfqId,
            vendorSpread: [],
            timingRisk: [],
            recommendationContext: [],
            narrative: $result->getNarrative(),
        );

        $this->storage->put($cacheKey, [
            'dto' => (array) $dto,
            'provenance' => $result->getProvenance(),
        ]);

        return new RfqInsightResult($dto, $result->getProvenance());
    }

    public function generateSummary(ReportingSummaryRequest $request): ReportingSummaryResult
    {
        $cacheKey = "insight:reporting:{$request->reportId}";
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new ReportingSummaryResult(
                dto: ReportingSummaryDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        $narrativeRequest = new NarrativeRequest(
            context: $request->factualData,
            narrativeType: NarrativeTypes::TYPE_REPORTING_SUMMARY,
            maxTokens: 500,
            temperature: 0.7,
        );

        $result = $this->providerService->generateNarrative($narrativeRequest);

        $dto = new ReportingSummaryDto(
            reportId: $request->reportId,
            trends: [],
            anomalies: [],
            narrative: $result->getNarrative(),
        );

        $this->storage->put($cacheKey, [
            'dto' => (array) $dto,
            'provenance' => $result->getProvenance(),
        ]);

        return new ReportingSummaryResult($dto, $result->getProvenance());
    }

    public function getCachedSummary(string $tenantId): ?DashboardSummaryDto
    {
        $cached = $this->storage->get("insight:dashboard:{$tenantId}");
        return $cached !== null ? DashboardSummaryDto::fromArray($cached['dto']) : null;
    }

    public function getCachedInsights(string $rfqId): ?RfqInsightDto
    {
        $cached = $this->storage->get("insight:rfq:{$rfqId}");
        return $cached !== null ? RfqInsightDto::fromArray($cached['dto']) : null;
    }

    public function invalidateCache(string $key): void
    {
        $this->storage->delete("insight:dashboard:{$key}");
        $this->storage->delete("insight:rfq:{$key}");
        $this->storage->delete("insight:reporting:{$key}");
        $this->storage->delete("insight:governance:{$key}");
    }
}
```

- [ ] **Step 2: Create ProviderGovernanceClient**

```php
<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\InsightOperations\Contracts\GovernanceNarrativeCoordinatorInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\MachineLearning\DTOs\NarrativeRequest;
use Nexus\MachineLearning\DTOs\NarrativeResult;
use Nexus\MachineLearning\DTOs\NarrativeTypes;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeDto;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeRequest;
use Nexus\ProcurementML\DTOs\GovernanceNarrativeResult;

final class ProviderGovernanceClient implements GovernanceNarrativeCoordinatorInterface
{
    public function __construct(
        private InsightStoragePortInterface $storage,
        private \App\Services\Ai\ProviderService $providerService,
    ) {}

    public function generateNarrative(GovernanceNarrativeRequest $request): GovernanceNarrativeResult
    {
        $cacheKey = "insight:governance:{$request->vendorId}";
        $cached = $this->storage->get($cacheKey);
        if ($cached !== null) {
            return new GovernanceNarrativeResult(
                dto: GovernanceNarrativeDto::fromArray($cached['dto']),
                provenance: $cached['provenance'],
            );
        }

        $narrativeRequest = new NarrativeRequest(
            context: $request->facts,
            narrativeType: NarrativeTypes::TYPE_GOVERNANCE_NARRATIVE,
            maxTokens: 500,
            temperature: 0.7,
        );

        $result = $this->providerService->generateNarrative($narrativeRequest);

        $dto = new GovernanceNarrativeDto(
            vendorId: $request->vendorId,
            riskFlags: [],
            complianceGaps: [],
            esgSummary: '',
            narrative: $result->getNarrative(),
        );

        $this->storage->put($cacheKey, [
            'dto' => (array) $dto,
            'provenance' => $result->getProvenance(),
        ]);

        return new GovernanceNarrativeResult($dto, $result->getProvenance());
    }

    public function getCachedNarrative(string $vendorId): ?GovernanceNarrativeDto
    {
        $cached = $this->storage->get("insight:governance:{$vendorId}");
        return $cached !== null ? GovernanceNarrativeDto::fromArray($cached['dto']) : null;
    }

    public function invalidateCache(string $vendorId): void
    {
        $this->storage->delete("insight:governance:{$vendorId}");
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add apps/atomy-q/API/app/Adapters/Ai/
git commit -m "feat(API): add provider insight and governance clients implementing coordinator interfaces"
```

---

### Task 7: API Layer 3 — Controllers & Routes

**Files:**
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqInsightsController.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/InsightRefreshController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ReportController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php`
- Modify: `apps/atomy-q/API/routes/api.php`

- [ ] **Step 1: Create RfqInsightsController**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Nexus\InsightOperations\Contracts\RfqInsightCoordinatorInterface;
use Nexus\ProcurementML\DTOs\RfqInsightRequest;

final class RfqInsightsController extends Controller
{
    public function __construct(
        private RfqInsightCoordinatorInterface $coordinator,
    ) {}

    public function show(string $rfqId): \Illuminate\Http\JsonResponse
    {
        // Fetch source data from RFQ, vendors, and quotes
        $rfq = \App\Models\Rfq::findOrFail($rfqId);
        $vendors = $rfq->vendors()->get()->toArray();
        $quotes = $rfq->quotes()->get()->toArray();

        $request = new RfqInsightRequest(
            rfqId: $rfqId,
            tenantId: \tenant()->getId(),
            sourceData: ['rfq' => $rfq->toArray(), 'vendors' => $vendors, 'quotes' => $quotes],
        );

        $result = $this->coordinator->generateInsights($request);

        return response()->json([
            'data' => $result->dto,
            'provenance' => $result->provenance,
        ]);
    }
}
```

- [ ] **Step 2: Create InsightRefreshController**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Nexus\InsightOperations\Contracts\DashboardInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\RfqInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\GovernanceNarrativeCoordinatorInterface;
use Nexus\InsightOperations\Contracts\ReportingSummaryCoordinatorInterface;

final class InsightRefreshController extends Controller
{
    public function __construct(
        private ?DashboardInsightCoordinatorInterface $dashboardCoordinator = null,
        private ?RfqInsightCoordinatorInterface $rfqCoordinator = null,
        private ?GovernanceNarrativeCoordinatorInterface $governanceCoordinator = null,
        private ?ReportingSummaryCoordinatorInterface $reportingCoordinator = null,
    ) {}

    public function refresh(string $type, string $id): \Illuminate\Http\JsonResponse
    {
        match ($type) {
            'dashboard' => $this->dashboardCoordinator?->invalidateCache($id),
            'rfq' => $this->rfqCoordinator?->invalidateCache($id),
            'governance' => $this->governanceCoordinator?->invalidateCache($id),
            'reporting' => $this->reportingCoordinator?->invalidateCache($id),
            default => throw new \InvalidArgumentException("Unknown insight type: {$type}"),
        };

        return response()->json(['message' => 'Cache invalidated'], 202);
    }
}
```

- [ ] **Step 3: Add routes to api.php**

```php
// Insight routes
Route::get('/dashboard/insights', [DashboardController::class, 'insights'])->name('dashboard.insights');
Route::get('/rfqs/{rfq}/insights', [RfqInsightsController::class, 'show'])->name('rfqs.insights');
Route::get('/vendors/{vendor}/governance-narrative', [VendorGovernanceController::class, 'narrative'])->name('vendors.governance-narrative');
Route::get('/reports/{report}/summary', [ReportController::class, 'summary'])->name('reports.summary');
Route::post('/insights/{type}/{id}/refresh', [InsightRefreshController::class, 'refresh'])->name('insights.refresh');
```

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqInsightsController.php \
        apps/atomy-q/API/app/Http/Controllers/Api/V1/InsightRefreshController.php \
        apps/atomy-q/API/routes/api.php
git commit -m "feat(API): add insight controllers and routes for RFQ, dashboard, governance, reporting"
```

---

### Task 8: WEB Layer — Hooks & Integration

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-ai-insights.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/overview/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/reporting/page.tsx`
- Test: `apps/atomy-q/WEB/src/hooks/use-ai-insights.test.ts`

- [ ] **Step 1: Create use-ai-insights hook**

```typescript
import { useQuery } from '@tanstack/react-query';

interface UseAiInsightsOptions {
  type: 'dashboard' | 'rfq' | 'governance' | 'reporting';
  id?: string;
  enabled?: boolean;
}

export function useAiInsights({ type, id, enabled = true }: UseAiInsightsOptions) {
  return useQuery({
    queryKey: ['ai-insights', type, id],
    queryFn: async () => {
      const url = id 
        ? `/api/v1/${type === 'dashboard' ? 'dashboard/insights' : 
                      type === 'rfq' ? `rfqs/${id}/insights` :
                      type === 'governance' ? `vendors/${id}/governance-narrative` :
                      `reports/${id}/summary`}`
        : `/api/v1/dashboard/insights`;
      
      const response = await fetch(url);
      
      if (!response.ok) {
        if (response.status === 200) {
          return { status: 'unavailable', data: null };
        }
        throw new Error('Failed to fetch insights');
      }
      
      const json = await response.json();
      return { status: 'available', data: json.data, provenance: json.provenance };
    },
    enabled,
    staleTime: type === 'dashboard' ? 3600000 : 1800000, // 1hr for dashboard, 30min for others
  });
}

export function useRefreshInsights() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async ({ type, id }: { type: string; id: string }) => {
      const response = await fetch(`/api/v1/insights/${type}/${id}/refresh`, {
        method: 'POST',
      });
      if (!response.ok) throw new Error('Failed to refresh');
    },
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({ queryKey: ['ai-insights', variables.type, variables.id] });
    },
  });
}
```

- [ ] **Step 2: Write use-ai-insights.test.ts**

```typescript
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAiInsights } from './use-ai-insights';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return ({ children }: { children: React.ReactNode }) =>
    QueryClientProvider({ client: queryClient, children });
}

describe('useAiInsights', () => {
  let wrapper: ReturnType<typeof createWrapper>;

  beforeEach(() => {
    wrapper = createWrapper();
  });

  it('returns correct query key with id', async () => {
    const { result } = renderHook(() => useAiInsights({ type: 'rfq', id: 'RFQ-001' }), { wrapper });
    await waitFor(() => expect(result.current.queryKey).toEqual(['ai-insights', 'rfq', 'RFQ-001']));
  });

  it('returns correct query key without id for dashboard', async () => {
    const { result } = renderHook(() => useAiInsights({ type: 'dashboard' }), { wrapper });
    await waitFor(() => expect(result.current.queryKey).toEqual(['ai-insights', 'dashboard', undefined]));
  });

  it('has correct stale time for dashboard (1hr)', async () => {
    const { result } = renderHook(() => useAiInsights({ type: 'dashboard' }), { wrapper });
    await waitFor(() => expect(result.current.options?.staleTime).toBe(3600000));
  });

  it('has correct stale time for rfq (30min)', async () => {
    const { result } = renderHook(() => useAiInsights({ type: 'rfq', id: 'RFQ-001' }), { wrapper });
    await waitFor(() => expect(result.current.options?.staleTime).toBe(1800000));
  });
});
```

- [ ] **Step 3: Run WEB unit tests**

```bash
cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-ai-insights.test.ts
```
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-ai-insights.ts \
        apps/atomy-q/WEB/src/hooks/use-ai-insights.test.ts
git commit -m "feat(WEB): add useAiInsights hook with cache and refresh support"
```

---

### Task 9: Verification & Testing

- [ ] **Step 1: Run Layer 1 tests**

```bash
# MachineLearning
cd packages/MachineLearning && ../../vendor/bin/phpunit tests/

# ProcurementML
cd packages/ProcurementML && ../../vendor/bin/phpunit tests/
```
Expected: PASS

- [ ] **Step 2: Run InsightOperations tests**

```bash
./vendor/bin/phpunit orchestrators/InsightOperations/tests/
```
Expected: PASS

- [ ] **Step 3: Run API feature tests**

```bash
cd apps/atomy-q/API && php artisan test --filter=Insights
```
Expected: PASS (after implementing API test files)

- [ ] **Step 4: Run WEB unit tests**

```bash
cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-ai-insights.test.ts
```
Expected: PASS

- [ ] **Step 5: Run verification command**

```bash
composer verify:atomy-q-ai-insights-governance-reporting
```
Expected: PASS

- [ ] **Step 6: Final commit and update summaries**

```bash
# Update IMPLEMENTATION_SUMMARY.md files
# MachineLearning, ProcurementML, InsightOperations

git add .
git commit -m "feat: complete AI insights and governance implementation for alpha release"
```

---

## Exit Criteria

- [ ] Insight and governance AI are provider-backed where promised
- [ ] Factual dashboard, RFQ, governance, and report data remain available without AI
- [ ] Governance AI never becomes authoritative source-of-truth data
- [ ] WEB uses the same capability-aware unavailable patterns as the core RFQ chain
- [ ] All tests pass: Layer 1, InsightOperations, API feature tests, WEB unit tests
- [ ] Verification command `composer verify:atomy-q-ai-insights-governance-reporting` passes
