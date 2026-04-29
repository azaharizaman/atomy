<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use Nexus\InsightOperations\Contracts\RiskInsightFactsPortInterface;
use Nexus\InsightOperations\Coordinators\RiskInsightCoordinator;
use Nexus\InsightOperations\DTOs\RiskInsightFactsDto;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/CoordinatorFakes.php';

final class RiskInsightCoordinatorTest extends TestCase
{
    public function test_show_returns_risk_items_and_manual_review_state_without_cache(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new RiskInsightCoordinator(
            new RiskFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->show('tenant-a', 'rfq-1')->toResponseArray();

        self::assertSame(0, $provider->calls);
        self::assertCount(2, $result['data']['risk_items']);
        self::assertSame(2, $result['data']['manual_review']['pending_items']);
        self::assertSame(['no_cached_ai_artifact'], $result['data']['ai_insights']['reason_codes']);
    }

    public function test_generate_uses_risk_items_as_source_facts(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new RiskInsightCoordinator(
            new RiskFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'rfq-1', 'actor-1')->toResponseArray();

        self::assertSame(1, $provider->calls);
        self::assertSame('rfq_ai_insights', $provider->featureKey);
        self::assertSame('actor-1', $provider->actorId);
        self::assertSame('rfq', $provider->subjectType);
        self::assertCount(2, $provider->facts['risk_items']);
        self::assertCount(2, $result['data']['items']);
        self::assertSame('deadline', $provider->facts['risk_items'][0]['domain']);
        self::assertSame(2, $result['data']['manual_review']['pending_items']);
        self::assertTrue($result['data']['ai_insights']['available']);
        self::assertSame(hash('sha256', 'actor-1'), $result['data']['ai_insights']['provenance']['actor_hash']);
    }

    public function test_generate_returns_source_facts_unavailable_when_no_risk_items_exist(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new RiskInsightCoordinator(
            new EmptyRiskFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'rfq-1', 'actor-1')->toResponseArray();

        self::assertSame(0, $provider->calls);
        self::assertSame([], $result['data']['risk_items']);
        self::assertSame(0, $result['data']['manual_review']['pending_items']);
        self::assertSame(['source_facts_unavailable'], $result['data']['ai_insights']['reason_codes']);
    }
}

final class RiskFactsFake implements RiskInsightFactsPortInterface
{
    public function factsForRfq(string $tenantId, string $rfqId): RiskInsightFactsDto
    {
        return new RiskInsightFactsDto(
            rfqId: $rfqId,
            riskItems: [
                [
                    'domain' => 'deadline',
                    'severity' => 'high',
                    'status' => 'open',
                    'title' => 'Submission deadline is near.',
                    'source' => 'rfq_deadline',
                ],
                [
                    'domain' => 'vendor_compliance',
                    'severity' => 'medium',
                    'status' => 'open',
                    'title' => 'Selected vendor has an open finding.',
                    'source' => 'vendor_finding',
                ],
            ],
        );
    }
}

final class EmptyRiskFactsFake implements RiskInsightFactsPortInterface
{
    public function factsForRfq(string $tenantId, string $rfqId): RiskInsightFactsDto
    {
        return new RiskInsightFactsDto(rfqId: $rfqId, riskItems: []);
    }
}
