# Atomy-Q AI Provider Check Command Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `php artisan atomy:ai-provider-check`, a safe-by-default operator command for Atomy-Q AI provider readiness with explicit `--deep` contract verification.

**Architecture:** Add small Atomy-Q application-layer services under `App\Services\Ai` for structured readiness results and reusable provider contract verification. The new console command formats those results for humans or JSON, while existing AI adapters continue to own provider config, probes, transport, and runtime status. Deep checks reuse the representative contract call logic by moving it out of `AiVerifyContractsCommand` into a service consumed by both commands.

**Tech Stack:** Laravel Artisan commands, PHPUnit feature/unit tests, existing Atomy-Q AI contracts, `Nexus\MachineLearning` endpoint value objects, `Nexus\IntelligenceOperations` status DTOs.

---

## File Structure

- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderCheckSeverity.php`
  - String constants for `ok`, `warning`, `failed`, `skipped`, `unknown`.
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderCheckFinding.php`
  - Immutable DTO for operator findings.
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderEndpointCheck.php`
  - Immutable DTO for each endpoint group result.
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderReadinessResult.php`
  - Immutable aggregate DTO with JSON serialization.
- Create: `apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationResult.php`
  - Immutable DTO for one deep contract check.
- Create: `apps/atomy-q/API/app/Services/Ai/Contracts/ProviderContractVerifierInterface.php`
  - Contract for representative provider contract verification.
- Create: `apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationService.php`
  - Moves representative contract calls out of `AiVerifyContractsCommand`.
- Create: `apps/atomy-q/API/app/Services/Ai/Contracts/AiProviderReadinessCheckerInterface.php`
  - Contract for command-facing readiness checks.
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderReadinessChecker.php`
  - Safe default readiness aggregation and optional deep checks.
- Create: `apps/atomy-q/API/app/Console/Commands/AiProviderCheckCommand.php`
  - Artisan command `atomy:ai-provider-check`.
- Modify: `apps/atomy-q/API/app/Console/Commands/AiVerifyContractsCommand.php`
  - Delegate deep contract verification to `ProviderContractVerificationService`.
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
  - Bind the command-facing service contracts to concrete implementations.
- Modify: `apps/atomy-q/API/tests/Feature/Console/AiConsoleCommandsTest.php`
  - Add feature coverage for the new command and preserve existing verify-contract behavior.
- Create: `apps/atomy-q/API/tests/Unit/Services/AiProviderReadinessCheckerTest.php`
  - Unit coverage for severity rollup, safe default behavior, filtering, and sanitization.
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - Document the new operator command.

## Task 1: Add Readiness Result DTOs

**Files:**
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderCheckSeverity.php`
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderCheckFinding.php`
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderEndpointCheck.php`
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderReadinessResult.php`
- Test: `apps/atomy-q/API/tests/Unit/Services/AiProviderReadinessCheckerTest.php`

- [ ] **Step 1: Create the initial failing DTO unit test**

Add this test class:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Ai\AiProviderCheckFinding;
use App\Services\Ai\AiProviderCheckSeverity;
use App\Services\Ai\AiProviderEndpointCheck;
use App\Services\Ai\AiProviderReadinessResult;
use Tests\TestCase;

final class AiProviderReadinessCheckerTest extends TestCase
{
    public function testReadinessResultRollsUpFailedSeverityAndSerializesPayload(): void
    {
        $result = new AiProviderReadinessResult(
            checkedAt: '2026-05-02T00:00:00+00:00',
            mode: 'provider',
            provider: 'openrouter',
            deep: false,
            endpointGroups: [
                new AiProviderEndpointCheck(
                    endpointGroup: 'document',
                    configured: true,
                    enabled: true,
                    endpointUri: 'https://openrouter.ai/api/v1/chat/completions',
                    probeHealth: 'unavailable',
                    latencyMs: 120,
                    severity: AiProviderCheckSeverity::FAILED,
                    reasonCodes: ['health_probe_failed'],
                    diagnostics: ['provider_name' => 'openrouter'],
                ),
            ],
            operatorFindings: [
                new AiProviderCheckFinding(
                    severity: AiProviderCheckSeverity::WARNING,
                    area: 'security',
                    message: 'Endpoint [document] uses plain HTTP outside local development.',
                    endpointGroup: 'document',
                    reasonCode: 'plain_http_endpoint',
                ),
            ],
            publishedAlerts: [],
        );

        self::assertSame(AiProviderCheckSeverity::FAILED, $result->exitSeverity());

        $payload = $result->toArray();
        self::assertSame('provider', $payload['mode']);
        self::assertSame('openrouter', $payload['provider']);
        self::assertFalse($payload['deep']);
        self::assertSame(AiProviderCheckSeverity::FAILED, $payload['global_status']);
        self::assertSame(AiProviderCheckSeverity::FAILED, $payload['exit_severity']);
        self::assertSame('document', $payload['endpoint_groups'][0]['endpoint_group']);
        self::assertSame('security', $payload['operator_findings'][0]['area']);
    }
}
```

- [ ] **Step 2: Run the failing test**

Run:

```bash
cd apps/atomy-q/API
php artisan test --filter AiProviderReadinessCheckerTest
```

Expected: FAIL because `AiProviderReadinessResult` and related DTOs do not exist.

- [ ] **Step 3: Implement `AiProviderCheckSeverity`**

Create `apps/atomy-q/API/app/Services/Ai/AiProviderCheckSeverity.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class AiProviderCheckSeverity
{
    public const OK = 'ok';
    public const WARNING = 'warning';
    public const FAILED = 'failed';
    public const SKIPPED = 'skipped';
    public const UNKNOWN = 'unknown';

    public static function rank(string $severity): int
    {
        return match ($severity) {
            self::FAILED => 50,
            self::WARNING => 40,
            self::UNKNOWN => 30,
            self::SKIPPED => 20,
            self::OK => 10,
            default => 0,
        };
    }

    public static function worse(string $left, string $right): string
    {
        return self::rank($left) >= self::rank($right) ? $left : $right;
    }
}
```

- [ ] **Step 4: Implement `AiProviderCheckFinding`**

Create `apps/atomy-q/API/app/Services/Ai/AiProviderCheckFinding.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class AiProviderCheckFinding
{
    public function __construct(
        public string $severity,
        public string $area,
        public string $message,
        public ?string $endpointGroup = null,
        public ?string $reasonCode = null,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'area' => $this->area,
            'message' => $this->message,
            'endpoint_group' => $this->endpointGroup,
            'reason_code' => $this->reasonCode,
        ];
    }
}
```

- [ ] **Step 5: Implement `AiProviderEndpointCheck`**

Create `apps/atomy-q/API/app/Services/Ai/AiProviderEndpointCheck.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class AiProviderEndpointCheck
{
    /**
     * @param list<string> $reasonCodes
     * @param array<string, scalar|null> $diagnostics
     */
    public function __construct(
        public string $endpointGroup,
        public bool $configured,
        public bool $enabled,
        public ?string $endpointUri,
        public ?string $probeHealth,
        public ?int $latencyMs,
        public string $severity,
        public array $reasonCodes,
        public array $diagnostics = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'endpoint_group' => $this->endpointGroup,
            'configured' => $this->configured,
            'enabled' => $this->enabled,
            'endpoint_uri' => $this->endpointUri,
            'probe_health' => $this->probeHealth,
            'latency_ms' => $this->latencyMs,
            'severity' => $this->severity,
            'reason_codes' => $this->reasonCodes,
            'diagnostics' => $this->diagnostics,
        ];
    }
}
```

- [ ] **Step 6: Implement `AiProviderReadinessResult`**

Create `apps/atomy-q/API/app/Services/Ai/AiProviderReadinessResult.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class AiProviderReadinessResult
{
    /**
     * @param list<AiProviderEndpointCheck> $endpointGroups
     * @param list<AiProviderCheckFinding> $operatorFindings
     * @param list<array<string, mixed>> $publishedAlerts
     */
    public function __construct(
        public string $checkedAt,
        public string $mode,
        public string $provider,
        public bool $deep,
        public array $endpointGroups,
        public array $operatorFindings,
        public array $publishedAlerts = [],
    ) {
    }

    public function exitSeverity(): string
    {
        $severity = AiProviderCheckSeverity::OK;

        foreach ($this->endpointGroups as $endpointGroup) {
            $severity = AiProviderCheckSeverity::worse($severity, $endpointGroup->severity);
        }

        foreach ($this->operatorFindings as $finding) {
            $severity = AiProviderCheckSeverity::worse($severity, $finding->severity);
        }

        return $severity;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $exitSeverity = $this->exitSeverity();

        return [
            'checked_at' => $this->checkedAt,
            'mode' => $this->mode,
            'provider' => $this->provider,
            'global_status' => $exitSeverity,
            'deep' => $this->deep,
            'endpoint_groups' => array_map(
                static fn (AiProviderEndpointCheck $endpointGroup): array => $endpointGroup->toArray(),
                $this->endpointGroups,
            ),
            'operator_findings' => array_map(
                static fn (AiProviderCheckFinding $finding): array => $finding->toArray(),
                $this->operatorFindings,
            ),
            'published_alerts' => $this->publishedAlerts,
            'exit_severity' => $exitSeverity,
        ];
    }
}
```

- [ ] **Step 7: Verify DTO tests pass**

Run:

```bash
cd apps/atomy-q/API
php artisan test --filter AiProviderReadinessCheckerTest
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add apps/atomy-q/API/app/Services/Ai/AiProviderCheckSeverity.php \
  apps/atomy-q/API/app/Services/Ai/AiProviderCheckFinding.php \
  apps/atomy-q/API/app/Services/Ai/AiProviderEndpointCheck.php \
  apps/atomy-q/API/app/Services/Ai/AiProviderReadinessResult.php \
  apps/atomy-q/API/tests/Unit/Services/AiProviderReadinessCheckerTest.php
git commit -m "feat: add ai provider readiness result objects"
```

## Task 2: Extract Deep Contract Verification Service

**Files:**
- Create: `apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationResult.php`
- Create: `apps/atomy-q/API/app/Services/Ai/Contracts/ProviderContractVerifierInterface.php`
- Create: `apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationService.php`
- Modify: `apps/atomy-q/API/app/Console/Commands/AiVerifyContractsCommand.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Test: `apps/atomy-q/API/tests/Feature/Console/AiConsoleCommandsTest.php`

- [ ] **Step 1: Add a feature test proving the existing command still delegates all endpoint groups**

In `apps/atomy-q/API/tests/Feature/Console/AiConsoleCommandsTest.php`, keep `testAiVerifyContractsCommandRunsAllEndpointGroups` and add this assertion to the end of that test after `assertExitCode(0)` once the service is introduced:

```php
// The command should continue to print the same operator-facing success lines
// after the representative contract logic is moved into a reusable service.
```

This step intentionally preserves the current feature test as the behavior lock. The failing behavior will appear after the command constructor is changed before the service is registered.

- [ ] **Step 2: Implement `ProviderContractVerificationResult`**

Create `apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationResult.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class ProviderContractVerificationResult
{
    /**
     * @param list<string> $reasonCodes
     */
    public function __construct(
        public string $endpointGroup,
        public string $severity,
        public bool $verified,
        public array $reasonCodes,
        public ?string $message = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'endpoint_group' => $this->endpointGroup,
            'severity' => $this->severity,
            'verified' => $this->verified,
            'reason_codes' => $this->reasonCodes,
            'message' => $this->message,
        ];
    }
}
```

- [ ] **Step 3: Implement `ProviderContractVerificationService`**
- [ ] **Step 3: Implement `ProviderContractVerifierInterface`**

Create `apps/atomy-q/API/app/Services/Ai/Contracts/ProviderContractVerifierInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

use App\Services\Ai\ProviderContractVerificationResult;

interface ProviderContractVerifierInterface
{
    /**
     * @param list<string> $endpointGroups
     * @return list<ProviderContractVerificationResult>
     */
    public function verify(array $endpointGroups, string $tenantId, string $rfqId): array;

    /**
     * @param list<string> $endpointGroups
     */
    public function assertEndpointGroups(array $endpointGroups): void;
}
```

- [ ] **Step 4: Implement `ProviderContractVerificationService`**

Create `apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationService.php` by moving the existing verifier closures from `AiVerifyContractsCommand` into this service:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\Contracts\ProviderNormalizationClientInterface;
use App\Adapters\Ai\Contracts\ProviderSourcingRecommendationClientInterface;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Adapters\Ai\Exceptions\AiTransportFailedException;
use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;
use App\Adapters\Ai\Exceptions\AiTransportUnavailableException;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use InvalidArgumentException;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;
use Throwable;

final readonly class ProviderContractVerificationService implements ProviderContractVerifierInterface
{
    public const ENDPOINT_GROUPS = [
        'document',
        'normalization',
        'sourcing_recommendation',
        'comparison_award',
        'insight',
        'governance',
    ];

    public function __construct(
        private ProviderDocumentIntelligenceClientInterface $documentClient,
        private ProviderNormalizationClientInterface $normalizationClient,
        private ProviderSourcingRecommendationClientInterface $recommendationClient,
        private ComparisonAwardAiClientInterface $comparisonAwardClient,
        private ProviderInsightClientInterface $insightClient,
        private ProviderGovernanceClientInterface $governanceClient,
    ) {
    }

    /**
     * @param list<string> $endpointGroups
     * @return list<ProviderContractVerificationResult>
     */
    public function verify(array $endpointGroups, string $tenantId, string $rfqId): array
    {
        $this->assertEndpointGroups($endpointGroups);

        $results = [];
        foreach ($endpointGroups as $endpointGroup) {
            $results[] = $this->verifyOne($endpointGroup, $tenantId, $rfqId);
        }

        return $results;
    }

    /**
     * @param list<string> $endpointGroups
     */
    public function assertEndpointGroups(array $endpointGroups): void
    {
        $unsupported = array_values(array_diff($endpointGroups, self::ENDPOINT_GROUPS));
        if ($unsupported !== []) {
            throw new InvalidArgumentException('Unsupported endpoint group(s): ' . implode(', ', $unsupported));
        }
    }

    private function verifyOne(string $endpointGroup, string $tenantId, string $rfqId): ProviderContractVerificationResult
    {
        try {
            $result = $this->invoke($endpointGroup, $tenantId, $rfqId);
        } catch (AiTransportInvalidResponseException $exception) {
            return $this->failed($endpointGroup, 'provider_invalid_payload', $exception);
        } catch (AiTransportUnavailableException $exception) {
            return $this->failed($endpointGroup, 'provider_unavailable', $exception);
        } catch (AiTransportFailedException $exception) {
            return $this->failed($endpointGroup, 'provider_request_failed', $exception);
        } catch (Throwable $exception) {
            return $this->failed($endpointGroup, 'provider_contract_failed', $exception);
        }

        if (! is_array($result) || array_is_list($result)) {
            return new ProviderContractVerificationResult(
                endpointGroup: $endpointGroup,
                severity: AiProviderCheckSeverity::FAILED,
                verified: false,
                reasonCodes: ['provider_invalid_payload'],
                message: 'Provider contract verification returned an invalid payload for ' . $endpointGroup,
            );
        }

        return new ProviderContractVerificationResult(
            endpointGroup: $endpointGroup,
            severity: AiProviderCheckSeverity::OK,
            verified: true,
            reasonCodes: ['provider_available'],
            message: 'Verified provider contract for ' . $endpointGroup,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function invoke(string $endpointGroup, string $tenantId, string $rfqId): array
    {
        return match ($endpointGroup) {
            'document' => $this->documentClient->extract([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'document_id' => 'plan6-doc',
                'filename' => 'plan6.pdf',
                'mime_type' => 'application/pdf',
            ]),
            'normalization' => $this->normalizationClient->suggest([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'source_lines' => [['id' => 'src-1', 'text' => 'Plan 6 source line']],
            ]),
            'sourcing_recommendation' => $this->recommendationClient->enrich(
                new VendorRecommendationRequest(
                    tenantId: $tenantId,
                    rfqId: $rfqId,
                    categories: ['services'],
                    description: 'Operational hardening contract verification',
                    geography: 'MY',
                    spendBand: 'mid',
                    lineItemSummary: ['Line-item summary'],
                    candidates: [],
                ),
                [
                    new VendorRecommendationScoredCandidate(
                        vendorId: 'vendor-1',
                        vendorName: 'Vendor 1',
                        fitScore: 80,
                        confidenceBand: 'high',
                        recommendedReasonSummary: 'Meets deterministic fit checks.',
                        deterministicReasons: ['coverage'],
                    ),
                ],
            ),
            'comparison_award' => $this->comparisonAwardClient->comparisonOverlay(new ComparisonOverlayRequest(
                tenantId: $tenantId,
                rfqId: $rfqId,
                mode: 'preview',
                comparison: ['summary' => 'Plan 6 comparison contract verification'],
                snapshot: null,
            ))->payload,
            'insight' => $this->insightClient->summarize(new InsightSummaryRequest(
                featureKey: 'dashboard_ai_summary',
                tenantId: $tenantId,
                subjectType: 'dashboard_kpis',
                facts: ['active_rfqs' => 0],
            )),
            'governance' => $this->governanceClient->narrate(new GovernanceNarrativeRequest(
                featureKey: 'governance_ai_narrative',
                tenantId: $tenantId,
                vendorId: 'vendor-1',
                facts: ['summary_scores' => ['compliance' => 90], 'warning_flags' => []],
            )),
            default => throw new InvalidArgumentException('Unsupported endpoint group: ' . $endpointGroup),
        };
    }

    private function failed(string $endpointGroup, string $reasonCode, Throwable $exception): ProviderContractVerificationResult
    {
        return new ProviderContractVerificationResult(
            endpointGroup: $endpointGroup,
            severity: AiProviderCheckSeverity::FAILED,
            verified: false,
            reasonCodes: [$reasonCode],
            message: $exception->getMessage(),
        );
    }
}
```

- [ ] **Step 5: Refactor `AiVerifyContractsCommand` to use the service contract**

Replace the current client-heavy constructor and verifier closure logic with this shape:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\AiProviderCheckSeverity;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use App\Services\Ai\ProviderContractVerificationService;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class AiVerifyContractsCommand extends Command
{
    protected $signature = 'atomy:ai-verify-contracts
        {--endpoint-group=* : Restrict verification to one or more endpoint groups}
        {--tenant-id=plan6-tenant : Tenant identifier used in sample payloads}
        {--rfq-id=plan6-rfq : RFQ identifier used in sample payloads}';

    protected $description = 'Run provider contract verification requests for every configured Atomy-Q AI endpoint group.';

    public function __construct(
        private readonly ProviderContractVerifierInterface $verificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = trim((string) $this->option('tenant-id'));
        $rfqId = trim((string) $this->option('rfq-id'));
        if ($tenantId === '') {
            $this->error('The --tenant-id option must be non-empty.');

            return self::FAILURE;
        }

        if ($rfqId === '') {
            $this->error('The --rfq-id option must be non-empty.');

            return self::FAILURE;
        }

        try {
            $endpointGroups = $this->requestedEndpointGroups();
            $results = $this->verificationService->verify($endpointGroups, $tenantId, $rfqId);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $exitCode = self::SUCCESS;
        foreach ($results as $result) {
            if ($result->severity === AiProviderCheckSeverity::OK) {
                $this->info($result->message ?? 'Verified provider contract for ' . $result->endpointGroup);
                continue;
            }

            $this->error($result->message ?? 'Provider contract verification failed for ' . $result->endpointGroup);
            $exitCode = self::FAILURE;
        }

        return $exitCode;
    }

    /**
     * @return list<string>
     */
    private function requestedEndpointGroups(): array
    {
        $requested = $this->option('endpoint-group');
        if (! is_array($requested) || $requested === []) {
            return ProviderContractVerificationService::ENDPOINT_GROUPS;
        }

        $filtered = array_values(array_filter(array_map(
            static function (mixed $value): ?string {
                if (! is_string($value)) {
                    return null;
                }

                $normalized = trim($value);

                return $normalized !== '' ? $normalized : null;
            },
            $requested,
        )));

        return $filtered === [] ? ProviderContractVerificationService::ENDPOINT_GROUPS : $filtered;
    }
}
```

- [ ] **Step 6: Bind `ProviderContractVerifierInterface`**

Add these imports to `apps/atomy-q/API/app/Providers/AppServiceProvider.php`:

```php
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use App\Services\Ai\ProviderContractVerificationService;
```

Add this singleton binding near the existing AI bindings:

```php
$this->app->singleton(
    ProviderContractVerifierInterface::class,
    ProviderContractVerificationService::class,
);
```

- [ ] **Step 7: Run existing console command tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Console/AiConsoleCommandsTest.php
```

Expected: PASS, including existing `atomy:ai-verify-contracts` output assertions.

- [ ] **Step 8: Commit**

```bash
git add apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationResult.php \
  apps/atomy-q/API/app/Services/Ai/Contracts/ProviderContractVerifierInterface.php \
  apps/atomy-q/API/app/Services/Ai/ProviderContractVerificationService.php \
  apps/atomy-q/API/app/Console/Commands/AiVerifyContractsCommand.php \
  apps/atomy-q/API/app/Providers/AppServiceProvider.php \
  apps/atomy-q/API/tests/Feature/Console/AiConsoleCommandsTest.php
git commit -m "refactor: extract ai provider contract verification"
```

## Task 3: Implement Safe Provider Readiness Checker

**Files:**
- Create: `apps/atomy-q/API/app/Services/Ai/AiProviderReadinessChecker.php`
- Create: `apps/atomy-q/API/app/Services/Ai/Contracts/AiProviderReadinessCheckerInterface.php`
- Modify: `apps/atomy-q/API/tests/Unit/Services/AiProviderReadinessCheckerTest.php`

- [ ] **Step 1: Add failing unit tests for safe default checks**

Append these tests and helper classes to `AiProviderReadinessCheckerTest`:

```php
public function testSafeCheckMarksOffModeEndpointsAsSkippedWithoutDeepVerifier(): void
{
    $checker = new AiProviderReadinessChecker(
        endpointRegistry: new FakeEndpointRegistry('off', null),
        healthProbe: new FakeHealthProbe(),
        runtimeStatus: FakeRuntimeStatus::withMode('off'),
        contractVerifier: new FailingContractVerifier(),
    );

    $result = $checker->check(
        endpointGroups: ['document'],
        deep: false,
        publishAlerts: false,
        tenantId: 'plan6-tenant',
        rfqId: 'plan6-rfq',
    );

    self::assertSame(AiProviderCheckSeverity::SKIPPED, $result->endpointGroups[0]->severity);
    self::assertSame(['ai_disabled_by_config'], $result->endpointGroups[0]->reasonCodes);
}

public function testSafeCheckReportsMissingProviderEndpointAsFailed(): void
{
    $checker = new AiProviderReadinessChecker(
        endpointRegistry: new FakeEndpointRegistry('provider', null),
        healthProbe: new FakeHealthProbe(),
        runtimeStatus: FakeRuntimeStatus::withMode('provider'),
        contractVerifier: new FailingContractVerifier(),
    );

    $result = $checker->check(
        endpointGroups: ['document'],
        deep: false,
        publishAlerts: false,
        tenantId: 'plan6-tenant',
        rfqId: 'plan6-rfq',
    );

    self::assertSame(AiProviderCheckSeverity::FAILED, $result->endpointGroups[0]->severity);
    self::assertSame(['endpoint_not_configured'], $result->endpointGroups[0]->reasonCodes);
}

public function testSafeCheckUsesProbeHealthAndWarnsAboutPlainHttp(): void
{
    $config = new \Nexus\MachineLearning\ValueObjects\AiEndpointConfig(
        endpointGroup: \Nexus\MachineLearning\Enums\AiEndpointGroup::DOCUMENT,
        providerName: 'openrouter',
        endpointUri: 'http://provider.example.test/ai',
        timeoutSeconds: 10,
        enabled: true,
        metadata: ['auth_token' => 'secret-token', 'retry_attempts' => 1, 'retry_backoff_ms' => 0],
    );

    $checker = new AiProviderReadinessChecker(
        endpointRegistry: new FakeEndpointRegistry('provider', $config),
        healthProbe: new FakeHealthProbe(\Nexus\MachineLearning\Enums\AiHealth::HEALTHY),
        runtimeStatus: FakeRuntimeStatus::withMode('provider'),
        contractVerifier: new FailingContractVerifier(),
    );

    $result = $checker->check(
        endpointGroups: ['document'],
        deep: false,
        publishAlerts: false,
        tenantId: 'plan6-tenant',
        rfqId: 'plan6-rfq',
    );

    self::assertSame(AiProviderCheckSeverity::OK, $result->endpointGroups[0]->severity);
    self::assertSame('plain_http_endpoint', $result->operatorFindings[0]->reasonCode);
    self::assertStringNotContainsString('secret-token', json_encode($result->toArray(), JSON_THROW_ON_ERROR));
}
```

Add these imports to the existing import block at the top of the test file:

```php
use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\MachineLearning\Contracts\AiHealthProbeInterface;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Nexus\MachineLearning\ValueObjects\AiEndpointHealthSnapshot as RuntimeEndpointHealthSnapshot;
```

Add these private helper classes at the bottom of the test file after the test class:

```php
final readonly class FakeEndpointRegistry implements AiEndpointRegistryInterface
{
    public function __construct(private string $mode, private ?AiEndpointConfig $config)
    {
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function providerName(): string
    {
        return 'openrouter';
    }

    public function endpointGroups(): array
    {
        return ['document'];
    }

    public function endpointConfig(string $endpointGroup): ?AiEndpointConfig
    {
        return $endpointGroup === 'document' ? $this->config : null;
    }
}

final readonly class FakeHealthProbe implements AiHealthProbeInterface
{
    public function __construct(private AiHealth $health = AiHealth::UNAVAILABLE)
    {
    }

    public function probe(AiEndpointConfig $endpointConfig): RuntimeEndpointHealthSnapshot
    {
        return new RuntimeEndpointHealthSnapshot(
            endpointGroup: $endpointConfig->endpointGroup,
            health: $this->health,
            checkedAt: new \DateTimeImmutable('2026-05-02T00:00:00+00:00'),
            reasonCodes: [$this->health === AiHealth::HEALTHY ? 'provider_available' : 'health_probe_failed'],
            latencyMs: 25,
            diagnostics: ['provider_name' => $endpointConfig->providerName],
        );
    }
}

final readonly class FakeRuntimeStatus implements AiRuntimeStatusInterface
{
    public function __construct(private AiStatusSnapshot $snapshot)
    {
    }

    public static function withMode(string $mode): self
    {
        return new self(new AiStatusSnapshot(
            mode: $mode,
            globalHealth: $mode === AiStatusSchema::MODE_PROVIDER ? AiStatusSchema::HEALTH_HEALTHY : AiStatusSchema::HEALTH_DISABLED,
            capabilityDefinitions: [],
            capabilityStatuses: [],
            endpointGroupHealthSnapshots: [],
            reasonCodes: [],
            generatedAt: new \DateTimeImmutable('2026-05-02T00:00:00+00:00'),
        ));
    }

    public function snapshot(): AiStatusSnapshot
    {
        return $this->snapshot;
    }

    public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
    {
        return null;
    }

    public function providerName(): string
    {
        return 'openrouter';
    }
}

final class FailingContractVerifier implements ProviderContractVerifierInterface
{
    public function verify(array $endpointGroups, string $tenantId, string $rfqId): array
    {
        throw new \RuntimeException('Deep verifier should not run during safe checks.');
    }

    public function assertEndpointGroups(array $endpointGroups): void
    {
    }
}
```

- [ ] **Step 2: Run the failing checker tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test --filter AiProviderReadinessCheckerTest
```

Expected: FAIL because `AiProviderReadinessChecker` does not exist.

- [ ] **Step 3: Implement `AiProviderReadinessCheckerInterface`**

Create `apps/atomy-q/API/app/Services/Ai/Contracts/AiProviderReadinessCheckerInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiProviderReadinessResult;

interface AiProviderReadinessCheckerInterface
{
    /**
     * @param list<string> $endpointGroups
     */
    public function check(
        array $endpointGroups,
        bool $deep,
        bool $publishAlerts,
        string $tenantId,
        string $rfqId,
    ): AiProviderReadinessResult;
}
```

- [ ] **Step 4: Implement `AiProviderReadinessChecker`**

Create `apps/atomy-q/API/app/Services/Ai/AiProviderReadinessChecker.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Services\Ai\Contracts\AiOperationalAlertPublisherInterface;
use App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use DateTimeImmutable;
use DateTimeZone;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\MachineLearning\Contracts\AiHealthProbeInterface;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Throwable;

final readonly class AiProviderReadinessChecker implements AiProviderReadinessCheckerInterface
{
    public function __construct(
        private AiEndpointRegistryInterface $endpointRegistry,
        private AiHealthProbeInterface $healthProbe,
        private AiRuntimeStatusInterface $runtimeStatus,
        private ProviderContractVerifierInterface $contractVerifier,
        private ?AiOperationalAlertPublisherInterface $alertPublisher = null,
    ) {
    }

    /**
     * @param list<string> $endpointGroups
     */
    public function check(
        array $endpointGroups,
        bool $deep,
        bool $publishAlerts,
        string $tenantId,
        string $rfqId,
    ): AiProviderReadinessResult {
        $checkedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $mode = $this->endpointRegistry->mode();
        $selectedGroups = $endpointGroups === [] ? $this->endpointRegistry->endpointGroups() : $endpointGroups;
        $endpointChecks = [];
        $findings = [];

        foreach ($selectedGroups as $endpointGroup) {
            $config = $this->endpointRegistry->endpointConfig($endpointGroup);
            $endpointChecks[] = $this->checkEndpoint($endpointGroup, $mode, $config);
            array_push($findings, ...$this->configurationFindings($endpointGroup, $mode, $config));
        }

        if ($deep) {
            foreach ($this->contractVerifier->verify($selectedGroups, $tenantId, $rfqId) as $deepResult) {
                $findings[] = new AiProviderCheckFinding(
                    severity: $deepResult->severity,
                    area: 'deep_contract',
                    message: $deepResult->message ?? 'Provider contract verification completed for ' . $deepResult->endpointGroup,
                    endpointGroup: $deepResult->endpointGroup,
                    reasonCode: $deepResult->reasonCodes[0] ?? null,
                );
            }
        }

        $publishedAlerts = [];
        if ($publishAlerts && $this->alertPublisher instanceof AiOperationalAlertPublisherInterface) {
            $publishedAlerts = $this->alertPublisher->publishSnapshot($this->runtimeStatus->snapshot());
        }

        return new AiProviderReadinessResult(
            checkedAt: $checkedAt->format(DATE_ATOM),
            mode: $mode,
            provider: $this->endpointRegistry->providerName(),
            deep: $deep,
            endpointGroups: $endpointChecks,
            operatorFindings: $findings,
            publishedAlerts: $publishedAlerts,
        );
    }

    private function checkEndpoint(string $endpointGroup, string $mode, ?AiEndpointConfig $config): AiProviderEndpointCheck
    {
        if ($mode === AiStatusSchema::MODE_OFF) {
            return $this->skippedEndpoint($endpointGroup, ['ai_disabled_by_config']);
        }

        if ($mode === AiStatusSchema::MODE_DETERMINISTIC) {
            return $this->skippedEndpoint($endpointGroup, ['deterministic_fallback_mode']);
        }

        if ($config === null) {
            return new AiProviderEndpointCheck(
                endpointGroup: $endpointGroup,
                configured: false,
                enabled: false,
                endpointUri: null,
                probeHealth: null,
                latencyMs: null,
                severity: AiProviderCheckSeverity::FAILED,
                reasonCodes: ['endpoint_not_configured'],
                diagnostics: [],
            );
        }

        if (! $config->enabled) {
            return new AiProviderEndpointCheck(
                endpointGroup: $endpointGroup,
                configured: true,
                enabled: false,
                endpointUri: $config->endpointUri,
                probeHealth: AiStatusSchema::HEALTH_DISABLED,
                latencyMs: null,
                severity: AiProviderCheckSeverity::SKIPPED,
                reasonCodes: ['endpoint_disabled_by_config'],
                diagnostics: ['provider_name' => $config->providerName],
            );
        }

        try {
            $snapshot = $this->healthProbe->probe($config);
        } catch (Throwable) {
            return new AiProviderEndpointCheck(
                endpointGroup: $endpointGroup,
                configured: true,
                enabled: true,
                endpointUri: $config->endpointUri,
                probeHealth: AiStatusSchema::HEALTH_UNAVAILABLE,
                latencyMs: null,
                severity: AiProviderCheckSeverity::FAILED,
                reasonCodes: ['health_probe_failed'],
                diagnostics: ['provider_name' => $config->providerName],
            );
        }

        return new AiProviderEndpointCheck(
            endpointGroup: $endpointGroup,
            configured: true,
            enabled: true,
            endpointUri: $config->endpointUri,
            probeHealth: $snapshot->health->value,
            latencyMs: $snapshot->latencyMs,
            severity: $this->severityForHealth($snapshot->health),
            reasonCodes: $snapshot->reasonCodes,
            diagnostics: $snapshot->diagnostics,
        );
    }

    /**
     * @param list<string> $reasonCodes
     */
    private function skippedEndpoint(string $endpointGroup, array $reasonCodes): AiProviderEndpointCheck
    {
        return new AiProviderEndpointCheck(
            endpointGroup: $endpointGroup,
            configured: false,
            enabled: false,
            endpointUri: null,
            probeHealth: AiStatusSchema::HEALTH_DISABLED,
            latencyMs: null,
            severity: AiProviderCheckSeverity::SKIPPED,
            reasonCodes: $reasonCodes,
            diagnostics: [],
        );
    }

    private function severityForHealth(AiHealth $health): string
    {
        return match ($health) {
            AiHealth::HEALTHY => AiProviderCheckSeverity::OK,
            AiHealth::DEGRADED => AiProviderCheckSeverity::WARNING,
            AiHealth::UNAVAILABLE => AiProviderCheckSeverity::FAILED,
            AiHealth::DISABLED => AiProviderCheckSeverity::SKIPPED,
        };
    }

    /**
     * @return list<AiProviderCheckFinding>
     */
    private function configurationFindings(string $endpointGroup, string $mode, ?AiEndpointConfig $config): array
    {
        if ($mode !== AiStatusSchema::MODE_PROVIDER || $config === null) {
            return [];
        }

        $findings = [];
        $authToken = $config->metadata['auth_token'] ?? null;
        if (! is_string($authToken) || trim($authToken) === '') {
            $findings[] = new AiProviderCheckFinding(
                severity: AiProviderCheckSeverity::WARNING,
                area: 'auth',
                message: 'Endpoint [' . $endpointGroup . '] has no configured provider token.',
                endpointGroup: $endpointGroup,
                reasonCode: 'missing_auth_token',
            );
        }

        if ($this->usesPlainHttpOutsideLocal($config->endpointUri)) {
            $findings[] = new AiProviderCheckFinding(
                severity: AiProviderCheckSeverity::WARNING,
                area: 'security',
                message: 'Endpoint [' . $endpointGroup . '] uses plain HTTP outside local development.',
                endpointGroup: $endpointGroup,
                reasonCode: 'plain_http_endpoint',
            );
        }

        if ($config->timeoutSeconds <= 2) {
            $findings[] = new AiProviderCheckFinding(
                severity: AiProviderCheckSeverity::WARNING,
                area: 'timeout',
                message: 'Endpoint [' . $endpointGroup . '] timeout is very low for provider calls.',
                endpointGroup: $endpointGroup,
                reasonCode: 'low_timeout_seconds',
            );
        }

        return $findings;
    }

    private function usesPlainHttpOutsideLocal(string $uri): bool
    {
        $parts = parse_url($uri);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'http'
            && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
```

- [ ] **Step 5: Run checker tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test --filter AiProviderReadinessCheckerTest
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/app/Services/Ai/AiProviderReadinessChecker.php \
  apps/atomy-q/API/app/Services/Ai/Contracts/AiProviderReadinessCheckerInterface.php \
  apps/atomy-q/API/tests/Unit/Services/AiProviderReadinessCheckerTest.php
git commit -m "feat: add safe ai provider readiness checker"
```

## Task 4: Add `atomy:ai-provider-check` Command

**Files:**
- Create: `apps/atomy-q/API/app/Console/Commands/AiProviderCheckCommand.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify: `apps/atomy-q/API/tests/Feature/Console/AiConsoleCommandsTest.php`

- [ ] **Step 1: Add failing console feature tests**

Add these tests to `AiConsoleCommandsTest`:

```php
public function testAiProviderCheckCommandEmitsJsonSafeDefault(): void
{
    $this->app->instance(\App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface::class, new readonly class implements \App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface {
        public function check(array $endpointGroups, bool $deep, bool $publishAlerts, string $tenantId, string $rfqId): \App\Services\Ai\AiProviderReadinessResult
        {
            \PHPUnit\Framework\Assert::assertFalse($deep);
            \PHPUnit\Framework\Assert::assertFalse($publishAlerts);

            return new \App\Services\Ai\AiProviderReadinessResult(
                checkedAt: '2026-05-02T00:00:00+00:00',
                mode: 'provider',
                provider: 'openrouter',
                deep: false,
                endpointGroups: [],
                operatorFindings: [],
                publishedAlerts: [],
            );
        }
    });

    $this->artisan('atomy:ai-provider-check --json')
        ->expectsOutputToContain('"provider": "openrouter"')
        ->expectsOutputToContain('"deep": false')
        ->assertExitCode(0);
}

public function testAiProviderCheckCommandFailsOnWarningWhenRequested(): void
{
    $this->app->instance(\App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface::class, new readonly class implements \App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface {
        public function check(array $endpointGroups, bool $deep, bool $publishAlerts, string $tenantId, string $rfqId): \App\Services\Ai\AiProviderReadinessResult
        {
            return new \App\Services\Ai\AiProviderReadinessResult(
                checkedAt: '2026-05-02T00:00:00+00:00',
                mode: 'provider',
                provider: 'openrouter',
                deep: false,
                endpointGroups: [],
                operatorFindings: [
                    new \App\Services\Ai\AiProviderCheckFinding(
                        severity: \App\Services\Ai\AiProviderCheckSeverity::WARNING,
                        area: 'auth',
                        message: 'Endpoint [document] has no configured provider token.',
                        endpointGroup: 'document',
                        reasonCode: 'missing_auth_token',
                    ),
                ],
                publishedAlerts: [],
            );
        }
    });

    $this->artisan('atomy:ai-provider-check --fail-on=warning')
        ->expectsOutputToContain('Endpoint [document] has no configured provider token.')
        ->assertExitCode(1);
}
```

- [ ] **Step 2: Run failing console tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Console/AiConsoleCommandsTest.php --filter AiProviderCheck
```

Expected: FAIL because `atomy:ai-provider-check` is not registered.

- [ ] **Step 3: Implement the command**

Create `apps/atomy-q/API/app/Console/Commands/AiProviderCheckCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\AiProviderCheckSeverity;
use App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final class AiProviderCheckCommand extends Command
{
    protected $signature = 'atomy:ai-provider-check
        {--endpoint-group=* : Restrict checks to one or more endpoint groups}
        {--deep : Run representative provider contract calls}
        {--json : Emit the readiness result as JSON}
        {--fail-on= : Exit non-zero for warning when set to warning}
        {--publish-alerts : Publish degraded/unavailable alerts}
        {--tenant-id=plan6-tenant : Tenant identifier used for deep sample payloads}
        {--rfq-id=plan6-rfq : RFQ identifier used for deep sample payloads}';

    protected $description = 'Check configured Atomy-Q AI provider readiness without deep provider calls unless requested.';

    public function __construct(
        private readonly AiProviderReadinessCheckerInterface $checker,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = trim((string) $this->option('tenant-id'));
        $rfqId = trim((string) $this->option('rfq-id'));
        if ($tenantId === '' || $rfqId === '') {
            $this->error('The --tenant-id and --rfq-id options must be non-empty.');

            return self::FAILURE;
        }

        try {
            $result = $this->checker->check(
                endpointGroups: $this->requestedEndpointGroups(),
                deep: $this->option('deep') === true,
                publishAlerts: $this->option('publish-alerts') === true,
                tenantId: $tenantId,
                rfqId: $rfqId,
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json') === true) {
            try {
                $this->line((string) json_encode($result->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            } catch (JsonException $exception) {
                report($exception);
                $this->error('Failed to encode AI provider check payload.');

                return self::FAILURE;
            }
        } else {
            $this->renderHumanOutput($result->toArray());
        }

        return $this->exitCode($result->exitSeverity());
    }

    /**
     * @return list<string>
     */
    private function requestedEndpointGroups(): array
    {
        $requested = $this->option('endpoint-group');
        if (! is_array($requested)) {
            return [];
        }

        $endpointGroups = array_values(array_filter(array_map(
            static function (mixed $value): ?string {
                if (! is_string($value)) {
                    return null;
                }

                $normalized = trim($value);

                return $normalized === '' ? null : $normalized;
            },
            $requested,
        )));

        $unsupported = array_values(array_diff($endpointGroups, AiStatusSchema::endpointGroups()));
        if ($unsupported !== []) {
            throw new InvalidArgumentException('Unsupported endpoint group(s): ' . implode(', ', $unsupported));
        }

        return $endpointGroups;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderHumanOutput(array $payload): void
    {
        $this->info(sprintf(
            'AI provider check mode=%s provider=%s status=%s',
            (string) $payload['mode'],
            (string) $payload['provider'],
            (string) $payload['global_status'],
        ));

        $this->table(
            ['Endpoint Group', 'Configured', 'Enabled', 'Probe Health', 'Latency', 'Severity', 'Reason Codes'],
            array_map(
                static fn (array $endpoint): array => [
                    $endpoint['endpoint_group'],
                    $endpoint['configured'] ? 'yes' : 'no',
                    $endpoint['enabled'] ? 'yes' : 'no',
                    $endpoint['probe_health'] ?? '-',
                    $endpoint['latency_ms'] ?? '-',
                    $endpoint['severity'],
                    implode(',', $endpoint['reason_codes']),
                ],
                $payload['endpoint_groups'],
            ),
        );

        foreach ($payload['operator_findings'] as $finding) {
            $this->line(sprintf(
                '[%s] %s: %s',
                $finding['severity'],
                $finding['area'],
                $finding['message'],
            ));
        }
    }

    private function exitCode(string $severity): int
    {
        if ($severity === AiProviderCheckSeverity::FAILED) {
            return self::FAILURE;
        }

        return $severity === AiProviderCheckSeverity::WARNING && $this->option('fail-on') === 'warning'
            ? self::FAILURE
            : self::SUCCESS;
    }
}
```

- [ ] **Step 4: Bind the service contracts in `AppServiceProvider`**

Add these imports to `apps/atomy-q/API/app/Providers/AppServiceProvider.php` if they are not already present:

```php
use App\Services\Ai\AiProviderReadinessChecker;
use App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface;
```

Add this singleton binding near the existing AI bindings:

```php
$this->app->singleton(
    AiProviderReadinessCheckerInterface::class,
    AiProviderReadinessChecker::class,
);
```

- [ ] **Step 5: Run command feature tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test tests/Feature/Console/AiConsoleCommandsTest.php --filter AiProviderCheck
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/app/Console/Commands/AiProviderCheckCommand.php \
  apps/atomy-q/API/app/Providers/AppServiceProvider.php \
  apps/atomy-q/API/tests/Feature/Console/AiConsoleCommandsTest.php
git commit -m "feat: add ai provider check artisan command"
```

## Task 5: Validate Integration, Docs, and Full Test Slice

**Files:**
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Verify: all files touched by Tasks 1-4

- [ ] **Step 1: Update implementation summary**

Add this bullet to the AI/provider operations section of `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`:

```markdown
- Added `php artisan atomy:ai-provider-check` for safe-by-default AI provider readiness checks. The command reports endpoint configuration, health probes, operator findings, JSON output, warning-sensitive exit codes, optional alert publishing, and explicit `--deep` contract verification.
```

- [ ] **Step 2: Run focused tests**

Run:

```bash
cd apps/atomy-q/API
php artisan test --filter AiProviderReadinessCheckerTest
php artisan test tests/Feature/Console/AiConsoleCommandsTest.php
```

Expected: both commands PASS.

- [ ] **Step 3: Run the command manually in safe JSON mode**

Run:

```bash
cd apps/atomy-q/API
php artisan atomy:ai-provider-check --json
```

Expected: exits `0` or `1` based on local AI config, prints valid JSON with keys `checked_at`, `mode`, `provider`, `global_status`, `deep`, `endpoint_groups`, `operator_findings`, `published_alerts`, and `exit_severity`. It must not print auth token values.

- [ ] **Step 4: Run the command manually with endpoint filtering**

Run:

```bash
cd apps/atomy-q/API
php artisan atomy:ai-provider-check --endpoint-group=document
```

Expected: output includes the `document` endpoint group only.

- [ ] **Step 5: Run the command manually in deep mode only when using fake/local provider config**

Run only against fake/local provider config:

```bash
cd apps/atomy-q/API
php artisan atomy:ai-provider-check --deep --endpoint-group=insight
```

Expected: command invokes the representative contract verifier for `insight` and reports a deep contract finding. Do not run this against a paid provider during routine implementation verification.

- [ ] **Step 6: Run lint/static checks for changed app code**

Run:

```bash
cd apps/atomy-q/API
./vendor/bin/phpstan analyse app/Console/Commands app/Services/Ai --level=max
```

Expected: PASS.

- [ ] **Step 7: Commit docs and any integration fixes**

```bash
git add apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/API/app/Console/Commands/AiProviderCheckCommand.php \
  apps/atomy-q/API/app/Console/Commands/AiVerifyContractsCommand.php \
  apps/atomy-q/API/app/Services/Ai \
  apps/atomy-q/API/tests/Feature/Console/AiConsoleCommandsTest.php \
  apps/atomy-q/API/tests/Unit/Services/AiProviderReadinessCheckerTest.php
git commit -m "docs: document ai provider check command"
```

## Self-Review

- Spec coverage: The plan covers safe default checks, `--deep`, endpoint filtering, JSON output, warning-sensitive exit behavior, alert publication, deep contract reuse, no secret printing, docs, and tests.
- Scope: The plan stays inside `apps/atomy-q/API` and uses existing Atomy-Q AI contracts. It does not move provider HTTP concerns into L1 packages.
- Placeholder scan: No open placeholders are intentionally left for implementers. Provider-status URL and readiness scoring remain future extensions from the spec and are not part of this implementation plan.
- Type consistency: DTO class names, command option names, service method names, and severity constants are consistent across tasks.
