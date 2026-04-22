<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\VendorGovernanceScoreService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VendorGovernanceScoreServiceTest extends TestCase
{
    #[Test]
    public function scoresFreshEvidenceWithoutOpenFindingsAsHealthy(): void
    {
        $service = new VendorGovernanceScoreService();
        $now = CarbonImmutable::parse('2026-04-22T00:00:00Z');

        $summary = $service->summarize([
            [
                'domain' => 'ESG',
                'type' => 'sustainability_disclosure',
                'observed_at' => '2026-03-01T00:00:00Z',
                'expires_at' => '2027-03-01T00:00:00Z',
                'review_status' => 'reviewed',
            ],
            [
                'domain' => 'Compliance',
                'type' => 'sanctions_screening',
                'observed_at' => '2026-04-01T00:00:00Z',
                'expires_at' => '2026-10-01T00:00:00Z',
                'review_status' => 'reviewed',
            ],
        ], [], $now);

        $this->assertSame([
            'esg_score' => 100,
            'compliance_health_score' => 100,
            'risk_watch_score' => 100,
            'evidence_freshness_score' => 100,
        ], $summary['scores']);
        $this->assertSame([], $summary['warning_flags']);
    }

    #[Test]
    public function scoresExpiredEvidenceAndOpenSevereFindingsWithWarnings(): void
    {
        $service = new VendorGovernanceScoreService();
        $now = CarbonImmutable::parse('2026-04-22T00:00:00Z');

        $summary = $service->summarize([
            [
                'domain' => 'Compliance',
                'type' => 'iso_certificate',
                'observed_at' => '2024-03-01T00:00:00Z',
                'expires_at' => '2026-01-01T00:00:00Z',
                'review_status' => 'pending',
            ],
        ], [
            [
                'domain' => 'Risk',
                'severity' => 'High',
                'status' => 'Open',
                'remediation_due_at' => '2026-04-01T00:00:00Z',
            ],
        ], $now);

        $this->assertLessThan(100, $summary['scores']['compliance_health_score']);
        $this->assertLessThan(100, $summary['scores']['risk_watch_score']);
        $this->assertLessThan(100, $summary['scores']['evidence_freshness_score']);
        $this->assertContains('esg_review_stale', $summary['warning_flags']);
        $this->assertContains('sanctions_check_due', $summary['warning_flags']);
        $this->assertContains('compliance_document_expired', $summary['warning_flags']);
        $this->assertContains('open_severe_risk_finding', $summary['warning_flags']);
        $this->assertContains('remediation_overdue', $summary['warning_flags']);
    }
}
