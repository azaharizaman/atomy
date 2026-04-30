<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use Nexus\InsightOperations\Contracts\DashboardFactsPortInterface;
use Nexus\InsightOperations\Coordinators\DashboardInsightCoordinator;
use Nexus\InsightOperations\DTOs\DashboardFactsDto;
use Nexus\InsightOperations\DTOs\MetricFactDto;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/CoordinatorFakes.php';

final class DashboardInsightCoordinatorTest extends TestCase
{
    public function test_show_returns_facts_and_no_cached_artifact_without_provider_call(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new DashboardInsightCoordinator(
            new DashboardFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->show('tenant-a')->toResponseArray();

        self::assertSame(0, $provider->calls);
        self::assertSame('unavailable', $result['data']['ai_summary']['status']);
        self::assertSame(['no_cached_ai_artifact'], $result['data']['ai_summary']['reason_codes']);
        self::assertSame(3, $this->metricValue($result['data']['metrics'], 'active_rfqs'));
        self::assertSame(2, $this->metricValue($result['data']['metrics'], 'pending_approvals'));
        self::assertSame(12500.50, $this->metricValue($result['data']['metrics'], 'total_savings'));
        self::assertSame(9, $this->metricValue($result['data']['metrics'], 'avg_cycle_time_days'));
        self::assertSame(3, $result['data']['active_rfqs']);
        self::assertSame(2, $result['data']['pending_approvals']);
    }

    public function test_generate_uses_real_facts_and_stores_artifact(): void
    {
        $cache = new InMemoryArtifactCache();
        $provider = new TrackingNarrativePort();
        $coordinator = new DashboardInsightCoordinator(
            new DashboardFactsFake(),
            $cache,
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'actor-1')->toResponseArray();

        self::assertSame(1, $provider->calls);
        self::assertSame('dashboard_ai_summary', $provider->featureKey);
        self::assertSame('tenant-a', $provider->tenantId);
        self::assertSame('actor-1', $provider->actorId);
        self::assertSame('dashboard_kpis', $provider->subjectType);
        self::assertSame(3, $this->metricValue($provider->facts['metrics'], 'active_rfqs'));
        self::assertSame(2, $this->metricValue($provider->facts['metrics'], 'pending_approvals'));
        self::assertSame(12500.50, $this->metricValue($provider->facts['metrics'], 'total_savings'));
        self::assertSame(9, $this->metricValue($provider->facts['metrics'], 'avg_cycle_time_days'));
        self::assertNotSame(0, $this->metricValue($provider->facts['metrics'], 'active_rfqs'));
        self::assertCount(1, $cache->stored);
        self::assertTrue($result['data']['ai_summary']['available']);
        self::assertNotNull($result['data']['ai_summary']['source_facts_hash']);
        self::assertSame(hash('sha256', 'actor-1'), $result['data']['ai_summary']['provenance']['actor_hash']);
        self::assertSame($result['data']['ai_summary']['source_facts_hash'], $result['data']['ai_summary']['provenance']['input_hash']);
        self::assertSame(
            $result['data']['ai_summary']['source_facts_hash'],
            array_values($cache->stored)[0]->sourceFactsHash,
        );
    }

    public function test_generate_returns_unavailable_when_ai_feature_is_unavailable(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new DashboardInsightCoordinator(
            new DashboardFactsFake(),
            new InMemoryArtifactCache(),
            new UnavailableAiFake(['ai_disabled']),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'actor-1')->toResponseArray();

        self::assertSame(0, $provider->calls);
        self::assertFalse($result['data']['ai_summary']['available']);
        self::assertSame('unavailable', $result['data']['ai_summary']['status']);
        self::assertSame(['ai_disabled'], $result['data']['ai_summary']['reason_codes']);
    }

    public function test_generate_returns_unavailable_when_provider_throws(): void
    {
        $provider = new FailingNarrativePort();
        $coordinator = new DashboardInsightCoordinator(
            new DashboardFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'actor-1')->toResponseArray();

        self::assertSame(1, $provider->calls);
        self::assertFalse($result['data']['ai_summary']['available']);
        self::assertSame(['provider_unavailable'], $result['data']['ai_summary']['reason_codes']);
        self::assertSame(3, $result['data']['active_rfqs']);
    }

    /**
     * @param array<int, array<string, mixed>> $metrics
     */
    private function metricValue(array $metrics, string $key): mixed
    {
        foreach ($metrics as $metric) {
            if (($metric['key'] ?? null) === $key) {
                return $metric['value'] ?? null;
            }
        }

        self::fail(sprintf('Metric [%s] was not found.', $key));
    }
}

final class DashboardFactsFake implements DashboardFactsPortInterface
{
    public function factsForTenant(string $tenantId): DashboardFactsDto
    {
        return new DashboardFactsDto(
            metrics: [
                new MetricFactDto('active_rfqs', 3),
                new MetricFactDto('pending_approvals', 2),
                new MetricFactDto('total_savings', 12500.50),
                new MetricFactDto('avg_cycle_time_days', 9),
            ],
            recentActivity: [
                ['type' => 'rfq_published', 'count' => 1],
            ],
            riskAlerts: [
                ['severity' => 'high', 'count' => 1],
            ],
        );
    }
}
