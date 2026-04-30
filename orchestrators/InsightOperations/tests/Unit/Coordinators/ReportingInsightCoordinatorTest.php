<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\InsightOperations\Coordinators\ReportingInsightCoordinator;

require_once __DIR__ . "/CoordinatorFakes.php";

final class ReportingInsightCoordinatorTest extends TestCase
{
    public function test_show_returns_report_facts_and_missing_cached_artifact(): void
    {
        $factsPort = new ReportingFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new ReportingInsightCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
        );

        $result = $coordinator
            ->show("tenant-a", "report_kpis")
            ->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertSame("report_kpis", $result["data"]["subject_type"]);
        self::assertSame(51000.0, $result["data"]["total_spend"]);
        self::assertSame(4, $result["data"]["active_rfqs"]);
        self::assertSame(2500.0, $result["data"]["savings"]);
        self::assertFalse($result["data"]["ai_summary"]["available"]);
        self::assertSame(
            ["no_cached_ai_artifact"],
            $result["data"]["ai_summary"]["reason_codes"],
        );
    }

    public function test_generate_uses_subject_specific_facts(): void
    {
        $factsPort = new ReportingFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new ReportingInsightCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
        );

        $result = $coordinator
            ->generate("tenant-a", "report_spend_trend", "actor-1")
            ->toResponseArray();

        self::assertSame(1, $narrativePort->calls);
        self::assertSame("report_spend_trend", $narrativePort->subjectType);
        self::assertSame("actor-1", $narrativePort->actorId);
        self::assertSame(51000.0, $narrativePort->facts["total_spend"]);

        self::assertTrue($result["data"]["ai_summary"]["available"]);
        self::assertSame(
            "Generated AI narrative.",
            $result["data"]["ai_summary"]["payload"]["summary"],
        );
    }

    public function test_generate_does_not_call_provider_when_reporting_ai_is_unavailable(): void
    {
        $factsPort = new ReportingFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new UnavailableAiFake(["auth_failed"]);
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new ReportingInsightCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
        );

        $result = $coordinator
            ->generate("tenant-a", "report_kpis", "actor-1")
            ->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertFalse($result["data"]["ai_summary"]["available"]);
        self::assertSame(
            ["auth_failed"],
            $result["data"]["ai_summary"]["reason_codes"],
        );
    }
}
