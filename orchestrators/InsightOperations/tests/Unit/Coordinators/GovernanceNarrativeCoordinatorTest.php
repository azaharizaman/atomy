<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\InsightOperations\Coordinators\GovernanceNarrativeCoordinator;

require_once __DIR__ . "/CoordinatorFakes.php";

final class GovernanceNarrativeCoordinatorTest extends TestCase
{
    public function test_show_returns_governance_facts_and_cached_narrative(): void
    {
        $factsPort = new GovernanceFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingGovernanceNarrativePort();

        $coordinator = new GovernanceNarrativeCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
        );

        $result = $coordinator->show("tenant-a", "vendor-1")->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertSame("vendor-1", $result["data"]["vendor_id"]);
        self::assertCount(1, $result["data"]["evidence"]);
        self::assertFalse($result["data"]["ai_narrative"]["available"]);
    }

    public function test_generate_uses_sanitized_governance_context(): void
    {
        $factsPort = new GovernanceFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new AvailableAiFake();
        $narrativePort = new TrackingGovernanceNarrativePort();

        $coordinator = new GovernanceNarrativeCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
        );

        $result = $coordinator
            ->generate("tenant-a", "vendor-1", "actor-1")
            ->toResponseArray();

        self::assertSame(1, $narrativePort->calls);
        self::assertSame(
            "vendor-1",
            $narrativePort->facts["vendor_id_hash"] ===
            hash("sha256", "vendor-1")
                ? "vendor-1"
                : "wrong",
        );

        // Assert sanitization: evidence note and actor name should be missing or hashed
        $evidence = $narrativePort->facts["evidence"][0];
        self::assertArrayNotHasKey("notes", $evidence);
        self::assertArrayNotHasKey("id", $evidence);
        self::assertSame("compliance", $evidence["domain"]);

        self::assertTrue($result["data"]["ai_narrative"]["available"]);
        self::assertSame(
            "Generated AI governance narrative.",
            $result["data"]["ai_narrative"]["payload"]["summary"],
        );
    }

    public function test_generate_keeps_facts_when_ai_is_unavailable(): void
    {
        $factsPort = new GovernanceFactsFake();
        $cachePort = new InMemoryArtifactCache();
        $availabilityPort = new UnavailableAiFake(["auth_failed"]);
        $narrativePort = new TrackingGovernanceNarrativePort();

        $coordinator = new GovernanceNarrativeCoordinator(
            $factsPort,
            $cachePort,
            $availabilityPort,
            $narrativePort,
        );

        $result = $coordinator
            ->generate("tenant-a", "vendor-1", "actor-1")
            ->toResponseArray();

        self::assertSame(0, $narrativePort->calls);
        self::assertSame("vendor-1", $result["data"]["vendor_id"]);
        self::assertFalse($result["data"]["ai_narrative"]["available"]);
        self::assertSame(
            ["auth_failed"],
            $result["data"]["ai_narrative"]["reason_codes"],
        );
    }
}
