<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use Nexus\InsightOperations\Contracts\AiArtifactCachePortInterface;
use Nexus\InsightOperations\Contracts\AiAvailabilityPortInterface;
use Nexus\InsightOperations\Contracts\DashboardFactsPortInterface;
use Nexus\InsightOperations\Contracts\GovernanceFactsPortInterface;
use Nexus\InsightOperations\Contracts\GovernanceNarrativePortInterface;
use Nexus\InsightOperations\Contracts\InsightNarrativePortInterface;
use Nexus\InsightOperations\Contracts\ReportingFactsPortInterface;
use Nexus\InsightOperations\Contracts\RiskInsightFactsCommandInterface;
use Nexus\InsightOperations\Contracts\RiskInsightFactsQueryInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;
use Nexus\InsightOperations\DTOs\DashboardFactsDto;
use Nexus\InsightOperations\DTOs\GovernanceFactsDto;
use Nexus\InsightOperations\DTOs\MetricFactDto;
use Nexus\InsightOperations\DTOs\ReportingFactsDto;
use Nexus\InsightOperations\DTOs\RiskInsightFactsDto;

final class TrackingNarrativePort implements InsightNarrativePortInterface
{
    public int $calls = 0;
    public ?string $featureKey = null;
    public ?string $tenantId = null;
    public ?string $actorId = null;
    public ?string $subjectType = null;
    /** @var array<string, mixed> */
    public array $facts = [];
    public ?AiArtifactDto $artifact = null;

    public function generate(
        string $featureKey,
        string $tenantId,
        string $subjectType,
        string $actorId,
        array $facts,
    ): AiArtifactDto {
        $this->calls++;
        $this->featureKey = $featureKey;
        $this->tenantId = $tenantId;
        $this->actorId = $actorId;
        $this->subjectType = $subjectType;
        $this->facts = $facts;
        $this->artifact = AiArtifactDto::available(
            featureKey: $featureKey,
            capabilityGroup: str_starts_with($featureKey, "governance_")
                ? "governance_intelligence"
                : "insight_intelligence",
            payload: ["summary" => "Generated AI narrative."],
        );

        return $this->artifact;
    }
}

final class TrackingGovernanceNarrativePort implements
    GovernanceNarrativePortInterface
{
    public int $calls = 0;
    public ?string $featureKey = null;
    public ?string $tenantId = null;
    public ?string $actorId = null;
    public ?string $subjectType = null;
    /** @var array<string, mixed> */
    public array $facts = [];
    public ?AiArtifactDto $artifact = null;

    public function generate(
        string $featureKey,
        string $tenantId,
        string $subjectType,
        string $actorId,
        array $facts,
    ): AiArtifactDto {
        $this->calls++;
        $this->featureKey = $featureKey;
        $this->tenantId = $tenantId;
        $this->actorId = $actorId;
        $this->subjectType = $subjectType;
        $this->facts = $facts;
        $this->artifact = AiArtifactDto::available(
            featureKey: $featureKey,
            capabilityGroup: "governance_intelligence",
            payload: ["summary" => "Generated AI governance narrative."],
        );

        return $this->artifact;
    }
}

final class FailingNarrativePort implements InsightNarrativePortInterface
{
    public int $calls = 0;

    public function generate(
        string $featureKey,
        string $tenantId,
        string $subjectType,
        string $actorId,
        array $facts,
    ): AiArtifactDto {
        $this->calls++;

        throw new \DomainException("provider unavailable");
    }
}

final class ReasonedFailingNarrativePort implements InsightNarrativePortInterface
{
    public int $calls = 0;

    public function __construct(private readonly string $reasonCode) {}

    public function generate(
        string $featureKey,
        string $tenantId,
        string $subjectType,
        string $actorId,
        array $facts,
    ): AiArtifactDto {
        $this->calls++;

        throw new class ($this->reasonCode) extends \RuntimeException {
            public function __construct(private readonly string $reasonCode)
            {
                parent::__construct("provider unavailable");
            }

            public function reasonCode(): string
            {
                return $this->reasonCode;
            }
        };
    }
}

final class FailingGovernanceNarrativePort implements
    GovernanceNarrativePortInterface
{
    public int $calls = 0;

    public function generate(
        string $featureKey,
        string $tenantId,
        string $subjectType,
        string $actorId,
        array $facts,
    ): AiArtifactDto {
        $this->calls++;

        throw new \RuntimeException("provider unavailable");
    }
}

final class InMemoryArtifactCache implements AiArtifactCachePortInterface
{
    /** @var array<string, AiArtifactDto> */
    public array $stored = [];

    public function get(string $cacheKey): ?AiArtifactDto
    {
        return $this->stored[$cacheKey] ?? null;
    }

    public function put(
        string $cacheKey,
        AiArtifactDto $artifact,
        int $ttlSeconds,
    ): void {
        $this->stored[$cacheKey] = $artifact;
    }
}

final class AvailableAiFake implements AiAvailabilityPortInterface
{
    public function isFeatureAvailable(string $featureKey): bool
    {
        return true;
    }

    public function reasonCodes(string $featureKey): array
    {
        return [];
    }
}

final readonly class UnavailableAiFake implements AiAvailabilityPortInterface
{
    /**
     * @param list<string> $reasonCodes
     */
    public function __construct(private array $reasonCodes) {}

    public function isFeatureAvailable(string $featureKey): bool
    {
        return false;
    }

    public function reasonCodes(string $featureKey): array
    {
        return $this->reasonCodes;
    }
}

final class DashboardFactsFake implements DashboardFactsPortInterface
{
    public function factsForTenant(string $tenantId): DashboardFactsDto
    {
        return new DashboardFactsDto(
            metrics: [
                new MetricFactDto("active_rfqs", 3),
                new MetricFactDto("pending_approvals", 2),
                new MetricFactDto("total_savings", 12500.5),
                new MetricFactDto("avg_cycle_time_days", 9),
            ],
            recentActivity: [],
            riskAlerts: [],
        );
    }
}

final class ReportingFactsFake implements ReportingFactsPortInterface
{
    public function factsForTenant(
        string $tenantId,
        string $subjectType,
    ): ReportingFactsDto {
        return new ReportingFactsDto(
            subjectType: $subjectType,
            metrics: [
                new MetricFactDto("total_spend", 51000.0),
                new MetricFactDto("active_rfqs", 4),
                new MetricFactDto("savings", 2500.0),
            ],
        );
    }
}

final class RiskFactsFake implements
    RiskInsightFactsQueryInterface,
    RiskInsightFactsCommandInterface
{
    public function factsForRfq(
        string $tenantId,
        string $rfqId,
    ): RiskInsightFactsDto {
        return new RiskInsightFactsDto(
            $rfqId,
            [[
                "domain" => "risk",
                "severity" => "high",
                "status" => "open",
                "title" => "Deadline passed",
                "source" => "rfq_schedule",
                "source_id" => "risk-source-1",
            ]],
            [],
        );
    }

    public function escalate(
        string $tenantId,
        string $rfqId,
        string $itemId,
    ): void {}

    public function resolveAsException(
        string $tenantId,
        string $rfqId,
        string $itemId,
        string $actorId,
    ): void {}
}

final class EmptyRiskFactsFake implements
    RiskInsightFactsQueryInterface,
    RiskInsightFactsCommandInterface
{
    public function factsForRfq(
        string $tenantId,
        string $rfqId,
    ): RiskInsightFactsDto {
        return new RiskInsightFactsDto($rfqId, [], []);
    }

    public function escalate(
        string $tenantId,
        string $rfqId,
        string $itemId,
    ): void {}

    public function resolveAsException(
        string $tenantId,
        string $rfqId,
        string $itemId,
        string $actorId,
    ): void {}
}

final class GovernanceFactsFake implements GovernanceFactsPortInterface
{
    public function factsForVendor(
        string $tenantId,
        string $vendorId,
    ): GovernanceFactsDto {
        return new GovernanceFactsDto(
            vendorId: $vendorId,
            evidence: [
                [
                    "id" => "ev-1",
                    "domain" => "compliance",
                    "type" => "iso_9001",
                    "title" => "ISO 9001 Certificate",
                    "review_status" => "approved",
                    "notes" => "Secret note",
                ],
            ],
            findings: [
                [
                    "id" => "fi-1",
                    "severity" => "medium",
                    "status" => "open",
                    "opened_by" => "User A",
                ],
            ],
            scores: ["esg" => 85],
            warningFlags: [],
            sanctionsScreenings: [],
            dueDiligenceStatus: "current",
            evidenceFreshness: [],
        );
    }
}
