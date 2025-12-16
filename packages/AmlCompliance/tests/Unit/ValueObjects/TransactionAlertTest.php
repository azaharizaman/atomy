<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\ValueObjects;

use Nexus\AmlCompliance\ValueObjects\TransactionAlert;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransactionAlert::class)]
final class TransactionAlertTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $triggeredAt = new \DateTimeImmutable();

        $alert = new TransactionAlert(
            type: 'structuring',
            severity: 'high',
            message: 'Potential structuring detected',
            transactionId: 'txn-123',
            amount: 9500.00,
            currency: 'USD',
            triggeredAt: $triggeredAt,
            evidence: ['key' => 'value'],
        );

        $this->assertSame('structuring', $alert->type);
        $this->assertSame('high', $alert->severity);
        $this->assertSame('Potential structuring detected', $alert->message);
        $this->assertSame('txn-123', $alert->transactionId);
        $this->assertSame(9500.00, $alert->amount);
        $this->assertSame('USD', $alert->currency);
        $this->assertSame($triggeredAt, $alert->triggeredAt);
        $this->assertSame(['key' => 'value'], $alert->evidence);
    }

    public function test_structuring_factory_creates_alert(): void
    {
        $alert = TransactionAlert::structuring(
            cumulativeAmount: 9800.00,
            transactionCount: 5,
            threshold: 10000.00,
            currency: 'USD',
        );

        $this->assertSame(TransactionAlert::TYPE_STRUCTURING, $alert->type);
        $this->assertSame(TransactionMonitoringResult::SEVERITY_HIGH, $alert->severity);
        $this->assertSame(9800.00, $alert->amount);
        $this->assertSame('USD', $alert->currency);
        $this->assertStringContainsString('structuring', strtolower($alert->message));
        $this->assertArrayHasKey('transaction_count', $alert->evidence);
        $this->assertArrayHasKey('cumulative_amount', $alert->evidence);
        $this->assertArrayHasKey('threshold', $alert->evidence);
    }

    public function test_velocity_spike_factory_creates_alert(): void
    {
        $alert = TransactionAlert::velocitySpike(
            currentVolume: 50000.00,
            averageVolume: 10000.00,
            percentageIncrease: 400.0,
            period: 'daily',
        );

        $this->assertSame(TransactionAlert::TYPE_VELOCITY, $alert->type);
        $this->assertSame(50000.00, $alert->amount);
        $this->assertArrayHasKey('current_volume', $alert->evidence);
        $this->assertArrayHasKey('average_volume', $alert->evidence);
        $this->assertArrayHasKey('percentage_increase', $alert->evidence);
        $this->assertArrayHasKey('period', $alert->evidence);
    }

    public function test_velocity_spike_sets_severity_based_on_increase(): void
    {
        // 500%+ → CRITICAL
        $critical = TransactionAlert::velocitySpike(60000, 10000, 500);
        $this->assertSame(TransactionMonitoringResult::SEVERITY_CRITICAL, $critical->severity);

        // 300%+ → HIGH
        $high = TransactionAlert::velocitySpike(40000, 10000, 300);
        $this->assertSame(TransactionMonitoringResult::SEVERITY_HIGH, $high->severity);

        // 150%+ → MEDIUM
        $medium = TransactionAlert::velocitySpike(25000, 10000, 150);
        $this->assertSame(TransactionMonitoringResult::SEVERITY_MEDIUM, $medium->severity);

        // Below 150% → LOW
        $low = TransactionAlert::velocitySpike(12000, 10000, 20);
        $this->assertSame(TransactionMonitoringResult::SEVERITY_LOW, $low->severity);
    }

    public function test_geographic_anomaly_factory_creates_alert(): void
    {
        $alert = TransactionAlert::geographicAnomaly(
            transactionId: 'txn-123',
            country: 'RU',
            reason: 'High-risk jurisdiction',
        );

        $this->assertSame(TransactionAlert::TYPE_GEOGRAPHIC, $alert->type);
        $this->assertSame('txn-123', $alert->transactionId);
        $this->assertArrayHasKey('country', $alert->evidence);
        $this->assertArrayHasKey('reason', $alert->evidence);
    }

    public function test_large_amount_factory_creates_alert(): void
    {
        $alert = TransactionAlert::largeAmount(
            transactionId: 'txn-123',
            amount: 15000.00,
            threshold: 10000.00,
            currency: 'USD',
        );

        $this->assertSame(TransactionAlert::TYPE_AMOUNT, $alert->type);
        $this->assertSame('txn-123', $alert->transactionId);
        $this->assertSame(15000.00, $alert->amount);
        $this->assertSame('USD', $alert->currency);
    }

    public function test_counterparty_risk_factory_creates_alert(): void
    {
        $alert = TransactionAlert::counterpartyRisk(
            transactionId: 'txn-123',
            counterpartyId: 'counterparty-456',
            reason: 'Sanctioned entity',
            amount: 5000.00,
            currency: 'USD',
        );

        $this->assertSame(TransactionAlert::TYPE_COUNTERPARTY, $alert->type);
        $this->assertArrayHasKey('counterparty_id', $alert->evidence);
        $this->assertArrayHasKey('reason', $alert->evidence);
    }

    public function test_threshold_breach_factory_creates_alert(): void
    {
        $alert = TransactionAlert::thresholdBreach(
            transactionId: 'txn-123',
            amount: 12000.00,
            thresholdType: 'CTR',
            currency: 'USD',
        );

        $this->assertSame(TransactionAlert::TYPE_THRESHOLD, $alert->type);
        $this->assertArrayHasKey('threshold_type', $alert->evidence);
    }

    public function test_dormancy_reactivation_factory_creates_alert(): void
    {
        $alert = TransactionAlert::dormancyReactivation(
            transactionId: 'txn-123',
            dormantDays: 365,
            amount: 50000.00,
            currency: 'USD',
        );

        $this->assertSame(TransactionAlert::TYPE_DORMANCY, $alert->type);
        $this->assertArrayHasKey('dormant_days', $alert->evidence);
        $this->assertArrayHasKey('reactivation_amount', $alert->evidence);
    }

    public function test_from_array_creates_alert(): void
    {
        $alert = TransactionAlert::fromArray([
            'type' => 'structuring',
            'severity' => 'high',
            'message' => 'Test alert',
            'transaction_id' => 'txn-123',
            'amount' => 5000.00,
            'currency' => 'USD',
            'triggered_at' => 'now',
            'evidence' => ['key' => 'value'],
        ]);

        $this->assertSame('structuring', $alert->type);
        $this->assertSame('high', $alert->severity);
        $this->assertSame('Test alert', $alert->message);
        $this->assertSame('txn-123', $alert->transactionId);
    }

    public function test_is_high_severity(): void
    {
        $high = new TransactionAlert(
            type: 'test',
            severity: TransactionMonitoringResult::SEVERITY_HIGH,
            message: 'Test',
        );

        $this->assertTrue($high->isHighSeverity());
    }

    public function test_is_critical(): void
    {
        $critical = new TransactionAlert(
            type: 'test',
            severity: TransactionMonitoringResult::SEVERITY_CRITICAL,
            message: 'Test',
        );

        $this->assertTrue($critical->isCritical());
    }

    public function test_to_array_returns_structured_data(): void
    {
        $alert = TransactionAlert::structuring(9500, 5, 10000, 'USD');

        $array = $alert->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('severity', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertArrayHasKey('evidence', $array);
    }

    public function test_type_constants_exist(): void
    {
        $this->assertSame('structuring', TransactionAlert::TYPE_STRUCTURING);
        $this->assertSame('velocity', TransactionAlert::TYPE_VELOCITY);
        $this->assertSame('geographic', TransactionAlert::TYPE_GEOGRAPHIC);
        $this->assertSame('amount', TransactionAlert::TYPE_AMOUNT);
        $this->assertSame('counterparty', TransactionAlert::TYPE_COUNTERPARTY);
        $this->assertSame('pattern', TransactionAlert::TYPE_PATTERN);
        $this->assertSame('threshold', TransactionAlert::TYPE_THRESHOLD);
        $this->assertSame('dormancy', TransactionAlert::TYPE_DORMANCY);
    }
}
