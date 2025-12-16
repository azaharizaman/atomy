<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\ValueObjects;

use Nexus\AmlCompliance\ValueObjects\TransactionAlert;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransactionMonitoringResult::class)]
final class TransactionMonitoringResultTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $analyzedAt = new \DateTimeImmutable();
        $periodStart = new \DateTimeImmutable('-30 days');
        $periodEnd = new \DateTimeImmutable();

        $result = new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: true,
            riskScore: 75,
            reasons: ['Structuring detected'],
            alerts: [],
            patterns: ['round_amounts'],
            analyzedAt: $analyzedAt,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            transactionCount: 50,
            totalVolume: 100000.00,
            metadata: ['key' => 'value'],
        );

        $this->assertSame('party-123', $result->partyId);
        $this->assertTrue($result->isSuspicious);
        $this->assertSame(75, $result->riskScore);
        $this->assertSame(['Structuring detected'], $result->reasons);
        $this->assertSame(['round_amounts'], $result->patterns);
        $this->assertSame($analyzedAt, $result->analyzedAt);
        $this->assertSame($periodStart, $result->periodStart);
        $this->assertSame($periodEnd, $result->periodEnd);
        $this->assertSame(50, $result->transactionCount);
        $this->assertSame(100000.00, $result->totalVolume);
    }

    public function test_constructor_throws_for_negative_risk_score(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: false,
            riskScore: -1,
            reasons: [],
            alerts: [],
            patterns: [],
            analyzedAt: new \DateTimeImmutable(),
        );
    }

    public function test_constructor_throws_for_risk_score_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: false,
            riskScore: 101,
            reasons: [],
            alerts: [],
            patterns: [],
            analyzedAt: new \DateTimeImmutable(),
        );
    }

    public function test_clean_factory_creates_non_suspicious_result(): void
    {
        $result = TransactionMonitoringResult::clean('party-123', 10, 5000.00);

        $this->assertSame('party-123', $result->partyId);
        $this->assertFalse($result->isSuspicious);
        $this->assertSame(0, $result->riskScore);
        $this->assertEmpty($result->reasons);
        $this->assertEmpty($result->alerts);
        $this->assertEmpty($result->patterns);
        $this->assertSame(10, $result->transactionCount);
        $this->assertSame(5000.00, $result->totalVolume);
    }

    public function test_from_array_creates_instance(): void
    {
        $data = [
            'party_id' => 'party-123',
            'is_suspicious' => true,
            'risk_score' => 60,
            'reasons' => ['High velocity'],
            'alerts' => [],
            'patterns' => ['velocity_spike'],
            'analyzed_at' => '2024-01-15T10:00:00+00:00',
            'transaction_count' => 25,
            'total_volume' => 50000.00,
        ];

        $result = TransactionMonitoringResult::fromArray($data);

        $this->assertSame('party-123', $result->partyId);
        $this->assertTrue($result->isSuspicious);
        $this->assertSame(60, $result->riskScore);
    }

    public function test_should_consider_sar(): void
    {
        // High risk + suspicious → should consider SAR
        $suspicious = new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: true,
            riskScore: 75,
            reasons: ['Structuring'],
            alerts: [],
            patterns: [],
            analyzedAt: new \DateTimeImmutable(),
        );

        $this->assertTrue($suspicious->shouldConsiderSar());

        // Clean result → should not consider SAR
        $clean = TransactionMonitoringResult::clean('party-123');

        $this->assertFalse($clean->shouldConsiderSar());
    }

    public function test_get_highest_severity_returns_null_when_no_alerts(): void
    {
        $result = TransactionMonitoringResult::clean('party-123');

        $this->assertNull($result->getHighestSeverity());
    }

    public function test_get_highest_severity_returns_correct_severity(): void
    {
        $alerts = [
            new TransactionAlert(
                type: 'test1',
                severity: TransactionMonitoringResult::SEVERITY_LOW,
                message: 'Low alert',
            ),
            new TransactionAlert(
                type: 'test2',
                severity: TransactionMonitoringResult::SEVERITY_CRITICAL,
                message: 'Critical alert',
            ),
            new TransactionAlert(
                type: 'test3',
                severity: TransactionMonitoringResult::SEVERITY_MEDIUM,
                message: 'Medium alert',
            ),
        ];

        $result = new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: true,
            riskScore: 80,
            reasons: ['Multiple alerts'],
            alerts: $alerts,
            patterns: [],
            analyzedAt: new \DateTimeImmutable(),
        );

        $this->assertSame(TransactionMonitoringResult::SEVERITY_CRITICAL, $result->getHighestSeverity());
    }

    public function test_has_critical_alerts(): void
    {
        $alerts = [
            new TransactionAlert(
                type: 'test',
                severity: TransactionMonitoringResult::SEVERITY_CRITICAL,
                message: 'Critical',
            ),
        ];

        $result = new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: true,
            riskScore: 90,
            reasons: [],
            alerts: $alerts,
            patterns: [],
            analyzedAt: new \DateTimeImmutable(),
        );

        $this->assertTrue($result->hasCriticalAlerts());
    }

    public function test_get_alerts_by_type(): void
    {
        $alerts = [
            TransactionAlert::structuring(9500, 5, 10000),
            TransactionAlert::velocitySpike(50000, 10000, 400),
            TransactionAlert::structuring(9800, 6, 10000),
        ];

        $result = new TransactionMonitoringResult(
            partyId: 'party-123',
            isSuspicious: true,
            riskScore: 80,
            reasons: [],
            alerts: $alerts,
            patterns: [],
            analyzedAt: new \DateTimeImmutable(),
        );

        $structuringAlerts = $result->getAlertsByType(TransactionAlert::TYPE_STRUCTURING);

        $this->assertCount(2, $structuringAlerts);
    }

    public function test_to_array_returns_structured_data(): void
    {
        $result = TransactionMonitoringResult::clean('party-123', 10, 5000);

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('party_id', $array);
        $this->assertArrayHasKey('is_suspicious', $array);
        $this->assertArrayHasKey('risk_score', $array);
        $this->assertArrayHasKey('reasons', $array);
        $this->assertArrayHasKey('alerts', $array);
        $this->assertArrayHasKey('patterns', $array);
        $this->assertArrayHasKey('transaction_count', $array);
        $this->assertArrayHasKey('total_volume', $array);
    }

    public function test_severity_constants_exist(): void
    {
        $this->assertSame('low', TransactionMonitoringResult::SEVERITY_LOW);
        $this->assertSame('medium', TransactionMonitoringResult::SEVERITY_MEDIUM);
        $this->assertSame('high', TransactionMonitoringResult::SEVERITY_HIGH);
        $this->assertSame('critical', TransactionMonitoringResult::SEVERITY_CRITICAL);
    }
}
