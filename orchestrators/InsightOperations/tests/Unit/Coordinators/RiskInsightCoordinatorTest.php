<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\InsightOperations\Coordinators\RiskInsightCoordinator;
use Nexus\InsightOperations\Services\FactHasher;

require_once __DIR__ . "/CoordinatorFakes.php";

final class RiskInsightCoordinatorTest extends TestCase
{
    public function test_show_returns_risk_items_and_manual_review_state_without_cache(): void
    {
        $factsPort = new RiskFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new RiskInsightCoordinator(
            $factsPort,
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
            new FactHasher(),
        );

        $result = $coordinator->show("tenant-a", "rfq-1")->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertSame("rfq-1", $result["data"]["rfq_id"]);
        self::assertCount(1, $result["data"]["risk_items"]);
        self::assertSame(1, $result["data"]["manual_review"]["pending_items"]);
        self::assertFalse($result["data"]["ai_insights"]["available"]);
        self::assertSame(
            ["no_cached_ai_artifact"],
            $result["data"]["ai_insights"]["reason_codes"],
        );
    }

    public function test_generate_uses_risk_items_as_source_facts(): void
    {
        $factsPort = new RiskFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new RiskInsightCoordinator(
            $factsPort,
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
            new FactHasher(),
        );

        $result = $coordinator
            ->generate("tenant-a", "rfq-1", "actor-1")
            ->toResponseArray();

        self::assertSame(1, $narrativePort->calls);
        self::assertSame("rfq-1", $narrativePort->facts["rfq_id"]);
        self::assertSame("actor-1", $narrativePort->actorId);

        self::assertTrue($result["data"]["ai_insights"]["available"]);
        self::assertSame(
            "Generated AI narrative.",
            $result["data"]["ai_insights"]["payload"]["summary"],
        );
    }

    public function test_generate_returns_source_facts_unavailable_when_no_risk_items_exist(): void
    {
        $factsPort = new EmptyRiskFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingNarrativePort();

        $coordinator = new RiskInsightCoordinator(
            $factsPort,
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
            new FactHasher(),
        );

        $result = $coordinator
            ->generate("tenant-a", "rfq-1", "actor-1")
            ->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertSame([], $result["data"]["risk_items"]);
        self::assertSame(0, $result["data"]["manual_review"]["pending_items"]);
        self::assertSame(
            ["source_facts_unavailable"],
            $result["data"]["ai_insights"]["reason_codes"],
        );
    }
}
