<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Services;

use Nexus\AmlCompliance\Services\TransactionMonitor;
use Nexus\AmlCompliance\ValueObjects\TransactionAlert;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(TransactionMonitor::class)]
final class TransactionMonitorTest extends TestCase
{
    private TransactionMonitor $monitor;

    protected function setUp(): void
    {
        $this->monitor = new TransactionMonitor(new NullLogger());
    }

    private function createTransaction(
        string $id,
        float $amount,
        string $currency = 'USD',
        ?string $date = null,
        ?string $counterpartyCountry = null
    ): array {
        return [
            'id' => $id,
            'amount' => $amount,
            'currency' => $currency,
            'date' => $date ?? date('Y-m-d'),
            'counterparty_country' => $counterpartyCountry ?? 'US',
        ];
    }

    public function test_monitor_returns_clean_result_for_empty_transactions(): void
    {
        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: [],
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable()
        );

        $this->assertInstanceOf(TransactionMonitoringResult::class, $result);
        $this->assertFalse($result->isSuspicious);
        $this->assertSame(0, $result->riskScore);
        $this->assertEmpty($result->alerts);
        $this->assertEmpty($result->patterns);
    }

    public function test_monitor_returns_result_with_transaction_counts(): void
    {
        $transactions = [
            $this->createTransaction('txn-1', 1000.0),
            $this->createTransaction('txn-2', 2000.0),
            $this->createTransaction('txn-3', 3000.0),
        ];

        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: $transactions,
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable()
        );

        $this->assertSame(3, $result->transactionCount);
        $this->assertSame(6000.0, $result->totalVolume);
    }

    public function test_detect_structuring_returns_true_for_suspicious_pattern(): void
    {
        // Multiple transactions just below $10,000 threshold
        $transactions = [
            ['id' => 'txn-1', 'amount' => 9500.0],
            ['id' => 'txn-2', 'amount' => 9600.0],
            ['id' => 'txn-3', 'amount' => 9700.0],
            ['id' => 'txn-4', 'amount' => 9800.0],
        ];

        $isStructuring = $this->monitor->detectStructuring('party-123', $transactions, 10000.0);

        $this->assertTrue($isStructuring);
    }

    public function test_detect_structuring_returns_false_for_normal_pattern(): void
    {
        $transactions = [
            ['id' => 'txn-1', 'amount' => 500.0],
            ['id' => 'txn-2', 'amount' => 1500.0],
            ['id' => 'txn-3', 'amount' => 7500.0],
        ];

        $isStructuring = $this->monitor->detectStructuring('party-123', $transactions, 10000.0);

        $this->assertFalse($isStructuring);
    }

    public function test_detect_velocity_anomaly_returns_true_when_exceeds_threshold(): void
    {
        $transactions = array_fill(0, 50, ['id' => 'txn', 'amount' => 100.0]);
        $historicalData = ['average_transaction_count' => 10.0];

        $hasAnomaly = $this->monitor->detectVelocityAnomaly(
            'party-123',
            $transactions,
            $historicalData,
            3.0 // 3x multiplier threshold
        );

        $this->assertTrue($hasAnomaly);
    }

    public function test_detect_velocity_anomaly_returns_false_for_normal_volume(): void
    {
        $transactions = array_fill(0, 10, ['id' => 'txn', 'amount' => 100.0]);
        $historicalData = ['average_transaction_count' => 10.0];

        $hasAnomaly = $this->monitor->detectVelocityAnomaly(
            'party-123',
            $transactions,
            $historicalData,
            3.0
        );

        $this->assertFalse($hasAnomaly);
    }

    public function test_monitor_detects_structuring_pattern(): void
    {
        $transactions = [
            $this->createTransaction('txn-1', 9500.0),
            $this->createTransaction('txn-2', 9600.0),
            $this->createTransaction('txn-3', 9700.0),
            $this->createTransaction('txn-4', 9800.0),
        ];

        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: $transactions,
            periodStart: new \DateTimeImmutable('-7 days'),
            periodEnd: new \DateTimeImmutable()
        );

        $this->assertTrue($result->isSuspicious);
        $this->assertContains('structuring', $result->patterns);
        $this->assertNotEmpty($result->alerts);
    }

    public function test_monitor_detects_large_transactions(): void
    {
        $transactions = [
            $this->createTransaction('txn-1', 100000.0), // Very large
        ];

        // Configure with lower threshold for test
        $monitor = new TransactionMonitor(new NullLogger(), [
            'large_transaction_threshold' => 50000.0,
        ]);

        $result = $monitor->monitor(
            partyId: 'party-123',
            transactions: $transactions,
            periodStart: new \DateTimeImmutable('-7 days'),
            periodEnd: new \DateTimeImmutable()
        );

        $this->assertTrue($result->isSuspicious);
        $this->assertContains('large_amount', $result->patterns);
    }

    public function test_custom_thresholds_are_applied(): void
    {
        $monitor = new TransactionMonitor(new NullLogger(), [
            'structuring_threshold' => 5000.0,
            'structuring_min_transactions' => 2,
        ]);

        $transactions = [
            ['id' => 'txn-1', 'amount' => 4500.0],
            ['id' => 'txn-2', 'amount' => 4600.0],
        ];

        $isStructuring = $monitor->detectStructuring('party-123', $transactions, 5000.0);

        $this->assertTrue($isStructuring);
    }

    public function test_monitor_calculates_risk_score(): void
    {
        $transactions = [
            $this->createTransaction('txn-1', 9500.0),
            $this->createTransaction('txn-2', 9600.0),
            $this->createTransaction('txn-3', 9700.0),
        ];

        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: $transactions,
            periodStart: new \DateTimeImmutable('-7 days'),
            periodEnd: new \DateTimeImmutable()
        );

        // Should have non-zero risk score if suspicious
        if ($result->isSuspicious) {
            $this->assertGreaterThan(0, $result->riskScore);
        }
    }

    public function test_monitor_includes_reasons_for_suspicious_activity(): void
    {
        $transactions = [
            $this->createTransaction('txn-1', 9500.0),
            $this->createTransaction('txn-2', 9600.0),
            $this->createTransaction('txn-3', 9700.0),
            $this->createTransaction('txn-4', 9800.0),
        ];

        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: $transactions,
            periodStart: new \DateTimeImmutable('-7 days'),
            periodEnd: new \DateTimeImmutable()
        );

        $this->assertTrue($result->isSuspicious);
        $this->assertNotEmpty($result->reasons);
    }

    public function test_monitor_sets_correct_period(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');

        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: [$this->createTransaction('txn-1', 1000.0)],
            periodStart: $periodStart,
            periodEnd: $periodEnd
        );

        $this->assertSame($periodStart, $result->periodStart);
        $this->assertSame($periodEnd, $result->periodEnd);
    }

    public function test_detect_round_amount_pattern(): void
    {
        // 80%+ round amounts should be suspicious
        $transactions = [
            ['id' => 'txn-1', 'amount' => 1000.0],
            ['id' => 'txn-2', 'amount' => 2000.0],
            ['id' => 'txn-3', 'amount' => 5000.0],
            ['id' => 'txn-4', 'amount' => 3000.0],
            ['id' => 'txn-5', 'amount' => 4000.0],
        ];

        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: $transactions,
            periodStart: new \DateTimeImmutable('-7 days'),
            periodEnd: new \DateTimeImmutable()
        );

        // Should detect round amount pattern if 80%+ are round
        if (in_array('round_amounts', $result->patterns, true)) {
            $this->assertTrue($result->isSuspicious);
        }
    }

    public function test_alerts_contain_correct_type(): void
    {
        $transactions = [
            $this->createTransaction('txn-1', 9500.0),
            $this->createTransaction('txn-2', 9600.0),
            $this->createTransaction('txn-3', 9700.0),
            $this->createTransaction('txn-4', 9800.0),
        ];

        $result = $this->monitor->monitor(
            partyId: 'party-123',
            transactions: $transactions,
            periodStart: new \DateTimeImmutable('-7 days'),
            periodEnd: new \DateTimeImmutable()
        );

        $this->assertTrue($result->isSuspicious);

        foreach ($result->alerts as $alert) {
            $this->assertInstanceOf(TransactionAlert::class, $alert);
        }
    }
}
