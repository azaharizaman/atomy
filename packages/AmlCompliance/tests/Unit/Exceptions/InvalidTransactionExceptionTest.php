<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Exceptions;

use Nexus\AmlCompliance\Exceptions\InvalidTransactionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidTransactionException::class)]
final class InvalidTransactionExceptionTest extends TestCase
{
    public function test_structuring_detected_factory(): void
    {
        $exception = InvalidTransactionException::structuringDetected(
            partyId: 'party-123',
            totalAmount: 45000.0,
            transactionCount: 5,
            threshold: 10000.0
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('5 transactions', $exception->getMessage());
        $this->assertSame(3001, $exception->getCode());
    }

    public function test_velocity_spike_factory(): void
    {
        $exception = InvalidTransactionException::velocitySpike(
            partyId: 'party-123',
            currentCount: 50,
            averageCount: 10.0,
            multiplier: 5.0
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('velocity spike', $exception->getMessage());
        $this->assertSame(3002, $exception->getCode());
    }

    public function test_geographic_anomaly_factory(): void
    {
        $exception = InvalidTransactionException::geographicAnomaly(
            partyId: 'party-123',
            transactionId: 'txn-456',
            countryCode: 'KP',
            reason: 'Sanctioned country'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('txn-456', $exception->getMessage());
        $this->assertStringContainsString('KP', $exception->getMessage());
        $this->assertSame(3003, $exception->getCode());
    }

    public function test_high_risk_counterparty_factory(): void
    {
        $exception = InvalidTransactionException::highRiskCounterparty(
            partyId: 'party-123',
            transactionId: 'txn-456',
            counterpartyId: 'counterparty-789',
            reason: 'Known shell company'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('counterparty-789', $exception->getMessage());
        $this->assertSame(3004, $exception->getCode());
    }

    public function test_threshold_breach_factory(): void
    {
        $exception = InvalidTransactionException::thresholdBreach(
            partyId: 'party-123',
            transactionId: 'txn-456',
            amount: 50000.0,
            threshold: 10000.0,
            thresholdType: 'CTR'
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('50000.00', $exception->getMessage());
        $this->assertSame(3005, $exception->getCode());
    }

    public function test_dormancy_reactivation_factory(): void
    {
        $exception = InvalidTransactionException::dormancyReactivation(
            partyId: 'party-123',
            dormantDays: 365,
            transactionAmount: 25000.0
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('365 days', $exception->getMessage());
        $this->assertSame(3006, $exception->getCode());
    }

    public function test_round_amount_pattern_factory(): void
    {
        $exception = InvalidTransactionException::roundAmountPattern(
            partyId: 'party-123',
            roundAmountCount: 8,
            totalCount: 10,
            percentage: 80.0
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('8 of 10', $exception->getMessage());
        $this->assertSame(3007, $exception->getCode());
    }

    public function test_layering_detected_factory(): void
    {
        $exception = InvalidTransactionException::layeringDetected(
            partyId: 'party-123',
            layerCount: 5,
            entityChain: ['entity-1', 'entity-2', 'entity-3']
        );

        $this->assertStringContainsString('party-123', $exception->getMessage());
        $this->assertStringContainsString('5 layers', $exception->getMessage());
        $this->assertSame(3008, $exception->getCode());
    }
}
