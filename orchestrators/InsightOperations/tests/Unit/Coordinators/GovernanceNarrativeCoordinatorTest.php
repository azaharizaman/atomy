<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use Nexus\InsightOperations\Contracts\GovernanceFactsPortInterface;
use Nexus\InsightOperations\Coordinators\GovernanceNarrativeCoordinator;
use Nexus\InsightOperations\DTOs\GovernanceFactsDto;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/CoordinatorFakes.php';

final class GovernanceNarrativeCoordinatorTest extends TestCase
{
    public function test_show_returns_governance_facts_and_cached_narrative(): void
    {
        $cache = new InMemoryArtifactCache();
        $provider = new TrackingNarrativePort();
        $coordinator = new GovernanceNarrativeCoordinator(
            new GovernanceFactsFake(),
            $cache,
            new AvailableAiFake(),
            $provider,
        );

        $generated = $coordinator->generate('tenant-a', 'vendor-1', 'actor-1')->toResponseArray();
        $shown = $coordinator->show('tenant-a', 'vendor-1')->toResponseArray();

        self::assertSame(1, $provider->calls);
        self::assertTrue($generated['data']['ai_narrative']['available']);
        self::assertTrue($shown['data']['ai_narrative']['available']);
        self::assertSame($generated['data']['ai_narrative']['source_facts_hash'], $shown['data']['ai_narrative']['source_facts_hash']);
        self::assertSame('vendor-1', $shown['data']['vendor_id']);
    }

    public function test_generate_uses_sanitized_governance_context(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new GovernanceNarrativeCoordinator(
            new GovernanceFactsFake(),
            new InMemoryArtifactCache(),
            new AvailableAiFake(),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'vendor-1', 'actor-1')->toResponseArray();

        $json = json_encode($provider->facts, JSON_THROW_ON_ERROR);

        self::assertSame('governance_ai_narrative', $provider->featureKey);
        self::assertSame('actor-1', $provider->actorId);
        self::assertSame('vendor_governance', $provider->subjectType);
        self::assertSame(hash('sha256', 'actor-1'), $result['data']['ai_narrative']['provenance']['actor_hash']);
        self::assertSame('insurance', $provider->facts['evidence'][0]['type']);
        self::assertSame('compliance', $provider->facts['evidence'][0]['domain']);
        self::assertSame('accepted', $provider->facts['evidence'][0]['status']);
        self::assertSame('compliance', $provider->facts['findings'][0]['domain']);
        self::assertSame('high', $provider->facts['findings'][0]['severity']);
        self::assertSame('open', $provider->facts['findings'][0]['status']);
        self::assertArrayHasKey('actor_name_hash', $provider->facts['evidence'][0]);
        self::assertArrayHasKey('actor_email_hash', $provider->facts['evidence'][0]);
        self::assertArrayHasKey('actor_phone_hash', $provider->facts['evidence'][0]);
        self::assertArrayHasKey('actor_name_hash', $provider->facts['findings'][0]);
        self::assertArrayHasKey('actor_email_hash', $provider->facts['findings'][0]);
        self::assertStringNotContainsString('evidence-raw-id', $json);
        self::assertStringNotContainsString('finding-raw-id', $json);
        self::assertStringNotContainsString('Raw note should not leave system', $json);
        self::assertStringNotContainsString('reviewer@example.com', $json);
        self::assertStringNotContainsString('+60123456789', $json);
        self::assertStringNotContainsString('Jane Reviewer', $json);
    }

    public function test_generate_keeps_facts_when_ai_is_unavailable(): void
    {
        $provider = new TrackingNarrativePort();
        $coordinator = new GovernanceNarrativeCoordinator(
            new GovernanceFactsFake(),
            new InMemoryArtifactCache(),
            new UnavailableAiFake(['ai_disabled']),
            $provider,
        );

        $result = $coordinator->generate('tenant-a', 'vendor-1', 'actor-1')->toResponseArray();

        self::assertSame(0, $provider->calls);
        self::assertSame('vendor-1', $result['data']['vendor_id']);
        self::assertCount(1, $result['data']['evidence']);
        self::assertSame(['ai_disabled'], $result['data']['ai_narrative']['reason_codes']);
    }
}

final class GovernanceFactsFake implements GovernanceFactsPortInterface
{
    public function factsForVendor(string $tenantId, string $vendorId): GovernanceFactsDto
    {
        return new GovernanceFactsDto(
            vendorId: $vendorId,
            evidence: [
                [
                    'id' => 'evidence-raw-id',
                    'type' => 'insurance',
                    'domain' => 'compliance',
                    'status' => 'accepted',
                    'severity' => 'low',
                    'issued_at' => '2026-04-01',
                    'expires_at' => '2026-12-31',
                    'score' => 92,
                    'warning_flags' => ['expires_within_year'],
                    'notes' => 'Raw note should not leave system',
                    'actor_name' => 'Jane Reviewer',
                    'actor_email' => 'reviewer@example.com',
                    'actor_phone' => '+60123456789',
                ],
            ],
            findings: [
                [
                    'id' => 'finding-raw-id',
                    'domain' => 'compliance',
                    'type' => 'missing_document',
                    'severity' => 'high',
                    'status' => 'open',
                    'opened_at' => '2026-04-05',
                    'score' => 41,
                    'warning_flags' => ['manual_review_required'],
                    'raw_notes' => 'Internal finding note.',
                    'actor_name' => 'Jane Reviewer',
                    'actor_email' => 'reviewer@example.com',
                ],
            ],
            scores: [
                'compliance_health' => 71,
                'risk_watch' => 29,
            ],
            warningFlags: ['manual_review_required'],
            sanctionsScreenings: [
                [
                    'status' => 'manual_review_required',
                    'screened_at' => '2026-04-06',
                    'score' => null,
                ],
            ],
            dueDiligenceStatus: 'in_review',
            evidenceFreshness: [
                'oldest_accepted_evidence_at' => '2026-04-01',
            ],
        );
    }
}
