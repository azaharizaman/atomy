<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\InsightOperations\Coordinators\DashboardInsightCoordinator;
use Nexus\InsightOperations\Services\FactHasher;
use Psr\Log\NullLogger;

require_once __DIR__ . "/CoordinatorFakes.php";

final class DashboardInsightCoordinatorTest extends TestCase
{
    public function test_show_returns_facts_and_no_cached_artifact_without_provider_call(): void
    {
        $factsPort = new DashboardFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new DashboardInsightCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
            new NullLogger(),
            new FactHasher(),
        );

        $result = $coordinator->show("tenant-a")->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertSame(3, $result["data"]["active_rfqs"]);
        self::assertSame(2, $result["data"]["pending_approvals"]);
        self::assertSame(12500.5, $result["data"]["total_savings"]);
        self::assertSame(9, $result["data"]["avg_cycle_time_days"]);
        self::assertFalse($result["data"]["ai_summary"]["available"]);
        self::assertSame(
            ["no_cached_ai_artifact"],
            $result["data"]["ai_summary"]["reason_codes"],
        );
    }

    public function test_generate_uses_real_facts_and_stores_artifact(): void
    {
        $factsPort = new DashboardFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new DashboardInsightCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
            new NullLogger(),
            new FactHasher(),
        );

        $result = $coordinator
            ->generate("tenant-a", "actor-1")
            ->toResponseArray();

        self::assertSame(1, $narrativePort->calls);
        self::assertSame("tenant-a", $narrativePort->tenantId);
        self::assertSame("actor-1", $narrativePort->actorId);
        self::assertSame(3, $narrativePort->facts["active_rfqs"]);
        self::assertSame(2, $narrativePort->facts["pending_approvals"]);

        self::assertTrue($result["data"]["ai_summary"]["available"]);
        self::assertSame(
            "Generated AI narrative.",
            $result["data"]["ai_summary"]["payload"]["summary"],
        );
        self::assertSame(
            "actor-1",
            $result["data"]["ai_summary"]["provenance"]["actor_id"],
        );

        // Verify cache
        $cached = $cachePort->get(array_key_first($cachePort->stored));
        self::assertNotNull($cached);
        self::assertTrue($cached->available);
    }

    public function test_generate_returns_unavailable_when_ai_feature_is_unavailable(): void
    {
        $factsPort = new DashboardFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new UnavailableAiFake(["quota_exhausted"]);
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new DashboardInsightCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
            new NullLogger(),
            new FactHasher(),
        );

        $result = $coordinator
            ->generate("tenant-a", "actor-1")
            ->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertFalse($result["data"]["ai_summary"]["available"]);
        self::assertSame(
            ["quota_exhausted"],
            $result["data"]["ai_summary"]["reason_codes"],
        );
    }

    public function test_generate_returns_unavailable_artifact_when_provider_fails(): void
    {
        $factsPort = new DashboardFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new FailingNarrativePort();

        $coordinator = new DashboardInsightCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
            new NullLogger(),
            new FactHasher(),
        );

        $result = $coordinator
            ->generate("tenant-a", "actor-1")
            ->toResponseArray();

        self::assertSame(1, $narrativePort->calls);
        self::assertFalse($result["data"]["ai_summary"]["available"]);
        self::assertSame(
            ["provider_unavailable"],
            $result["data"]["ai_summary"]["reason_codes"],
        );
    }
}
