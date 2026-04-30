<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use Nexus\InsightOperations\Contracts\ReportingFactsPortInterface;
use Nexus\InsightOperations\Coordinators\ReportingInsightCoordinator;
use Nexus\InsightOperations\DTOs\MetricFactDto;
use Nexus\InsightOperations\DTOs\ReportingFactsDto;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/CoordinatorFakes.php';

final class ReportingInsightCoordinatorTest extends TestCase
{
    public function test_show_returns_report_facts_and_missing_cached_artifact(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new ReportingInsightCoordinator(
            new ReportingFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->show('tenant-a', 'report_kpis')->toResponseArray();

        self::assertSame(0, $provider->calls);
        self::assertSame('report_kpis', $result['data']['subject_type']);
        self::assertSame('unavailable', $result['data']['ai_summary']['status']);
        self::assertSame(['no_cached_ai_artifact'], $result['data']['ai_summary']['reason_codes']);
    }

    public function test_generate_uses_subject_specific_facts(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new ReportingInsightCoordinator(
            new ReportingFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'report_spend_trend', 'actor-1')->toResponseArray();

        self::assertSame(1, $provider->calls);
        self::assertSame('reporting_ai_summary', $provider->featureKey);
        self::assertSame('actor-1', $provider->actorId);
        self::assertSame('report_spend_trend', $provider->subjectType);
        self::assertSame('report_spend_trend', $provider->facts['subject_type']);
        self::assertSame('report_spend_trend', $result['data']['subject_type']);
        self::assertSame(51000.00, $result['data']['total_spend']);
        self::assertTrue($result['data']['ai_summary']['available']);
        self::assertSame(hash('sha256', 'actor-1'), $result['data']['ai_summary']['provenance']['actor_hash']);
    }

    public function test_generate_does_not_call_provider_when_reporting_ai_is_unavailable(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new ReportingInsightCoordinator(
            new ReportingFactsFake(),
            new InMemoryArtifactCache(),
            new UnavailableAiFake(['ai_unavailable']),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'report_spend_by_category', 'actor-1')->toResponseArray();

        self::assertSame(0, $provider->calls);
        self::assertSame('report_spend_by_category', $result['data']['subject_type']);
        self::assertSame(['ai_unavailable'], $result['data']['ai_summary']['reason_codes']);
    }

    public function test_generate_returns_unavailable_when_provider_throws(): void
    {
        $provider = new FailingNarrativePort();
        $coordinator = new ReportingInsightCoordinator(
            new ReportingFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'report_kpis', 'actor-1')->toResponseArray();

        self::assertSame(1, $provider->calls);
        self::assertFalse($result['data']['ai_summary']['available']);
        self::assertSame(['provider_unavailable'], $result['data']['ai_summary']['reason_codes']);
        self::assertSame(51000.00, $result['data']['total_spend']);
    }
}

final class ReportingFactsFake implements ReportingFactsPortInterface
{
    public function factsForTenant(string $tenantId, string $subjectType): ReportingFactsDto
    {
        return new ReportingFactsDto(
            subjectType: $subjectType,
            metrics: [
                new MetricFactDto('total_spend', 51000.00),
                new MetricFactDto('awarded_rfqs', 4),
            ],
            series: [
                ['period' => '2026-04', 'spend' => 51000.00],
            ],
            rows: [
                ['category' => 'Logistics', 'spend' => 21000.00],
            ],
        );
    }
}
