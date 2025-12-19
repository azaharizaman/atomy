<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Entities\SettlementBatch;
use Nexus\Payment\Enums\SettlementBatchStatus;
use Nexus\Payment\Exceptions\InvalidSettlementBatchStatusException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettlementBatch::class)]
final class SettlementBatchTest extends TestCase
{
    private function createBatch(?string $id = null, array $metadata = []): SettlementBatch
    {
        return SettlementBatch::create(
            id: $id ?? 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'MYR',
            metadata: $metadata
        );
    }

    private function hasPayment(SettlementBatch $batch, string $paymentId): bool
    {
        return in_array($paymentId, $batch->getPaymentIds(), true);
    }

    #[Test]
    public function it_creates_settlement_batch_via_factory(): void
    {
        $batch = SettlementBatch::create(
            id: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'MYR'
        );

        $this->assertSame('batch-001', $batch->getId());
        $this->assertSame('tenant-001', $batch->getTenantId());
        $this->assertSame('stripe', $batch->getProcessorId());
        $this->assertSame('MYR', $batch->getCurrency());
        $this->assertInstanceOf(DateTimeImmutable::class, $batch->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $batch->getOpenedAt());
        $this->assertSame(SettlementBatchStatus::OPEN, $batch->getStatus());
        $this->assertNull($batch->getClosedAt());
    }

    #[Test]
    public function it_starts_with_zero_amounts(): void
    {
        $batch = $this->createBatch();

        $this->assertTrue($batch->getGrossAmount()->isZero());
        $this->assertTrue($batch->getTotalFees()->isZero());
        $this->assertTrue($batch->getNetAmount()->isZero());
        $this->assertSame(0, $batch->getPaymentCount());
        $this->assertEmpty($batch->getPaymentIds());
    }

    #[Test]
    public function it_adds_payment_to_batch(): void
    {
        $batch = $this->createBatch();
        $amount = Money::of(100, 'MYR');
        $fee = Money::of(3, 'MYR');

        $batch->addPayment('payment-001', $amount, $fee);

        $this->assertSame(1, $batch->getPaymentCount());
        $this->assertContains('payment-001', $batch->getPaymentIds());
        $this->assertEqualsWithDelta(100.00, $batch->getGrossAmount()->getAmount(), 0.001);
        $this->assertEqualsWithDelta(3.00, $batch->getTotalFees()->getAmount(), 0.001);
        $this->assertEqualsWithDelta(97.00, $batch->getNetAmount()->getAmount(), 0.001);
    }

    #[Test]
    public function it_adds_multiple_payments(): void
    {
        $batch = $this->createBatch();

        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->addPayment('payment-002', Money::of(200, 'MYR'), Money::of(6, 'MYR'));
        $batch->addPayment('payment-003', Money::of(150, 'MYR'), Money::of('4.50', 'MYR'));

        $this->assertSame(3, $batch->getPaymentCount());
        $this->assertEqualsWithDelta(450.00, $batch->getGrossAmount()->getAmount(), 0.001);
        $this->assertEqualsWithDelta(13.50, $batch->getTotalFees()->getAmount(), 0.001);
        $this->assertEqualsWithDelta(436.50, $batch->getNetAmount()->getAmount(), 0.001);
    }

    #[Test]
    public function it_checks_if_payment_exists_in_batch(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));

        $this->assertTrue($this->hasPayment($batch, 'payment-001'));
        $this->assertFalse($this->hasPayment($batch, 'payment-002'));
    }

    #[Test]
    public function it_ignores_duplicate_payment_add(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        
        // Adding same payment again should be ignored (no exception, no duplicate)
        $batch->addPayment('payment-001', Money::of(50, 'MYR'), Money::of('1.5', 'MYR'));

        $this->assertSame(1, $batch->getPaymentCount());
        $this->assertEqualsWithDelta(100.00, $batch->getGrossAmount()->getAmount(), 0.001);
    }

    #[Test]
    public function it_throws_exception_when_adding_wrong_currency_amount(): void
    {
        $batch = $this->createBatch();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: expected MYR, got USD');

        $batch->addPayment('payment-001', Money::of(100, 'USD'), Money::of(3, 'MYR'));
    }

    #[Test]
    public function it_throws_exception_when_adding_wrong_currency_fee(): void
    {
        $batch = $this->createBatch();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: expected MYR, got USD');

        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'USD'));
    }

    #[Test]
    public function it_throws_exception_when_adding_payment_to_closed_batch(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        $this->expectException(InvalidSettlementBatchStatusException::class);

        $batch->addPayment('payment-002', Money::of(50, 'MYR'), Money::of('1.5', 'MYR'));
    }

    #[Test]
    public function it_closes_batch(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        
        $batch->close();

        $this->assertSame(SettlementBatchStatus::CLOSED, $batch->getStatus());
        $this->assertInstanceOf(DateTimeImmutable::class, $batch->getClosedAt());
        $this->assertFalse($batch->isOpen());
    }

    #[Test]
    public function it_sets_expected_settlement_on_close(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        
        $batch->close();

        // Expected settlement should equal net amount on close
        $expectedAmount = $batch->getExpectedSettlementAmount();
        $this->assertNotNull($expectedAmount);
        $this->assertEqualsWithDelta(97.00, $expectedAmount->getAmount(), 0.001);
    }

    #[Test]
    public function it_throws_exception_when_closing_already_closed_batch(): void
    {
        $batch = $this->createBatch();
        $batch->close();

        $this->expectException(InvalidSettlementBatchStatusException::class);

        $batch->close();
    }

    #[Test]
    public function it_reconciles_batch_without_discrepancy(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        $batch->reconcile(Money::of('97.00', 'MYR'));

        $this->assertSame(SettlementBatchStatus::RECONCILED, $batch->getStatus());
        $actualAmount = $batch->getActualSettlementAmount();
        $this->assertNotNull($actualAmount);
        $this->assertEqualsWithDelta(97.00, $actualAmount->getAmount(), 0.001);
        $this->assertFalse($batch->hasDiscrepancy());
    }

    #[Test]
    public function it_reconciles_batch_with_discrepancy(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        // Processor settled $5 less than expected
        $batch->reconcile(Money::of('92.00', 'MYR'));

        $this->assertSame(SettlementBatchStatus::RECONCILED, $batch->getStatus());
        $this->assertTrue($batch->hasDiscrepancy());
        $discrepancy = $batch->getDiscrepancyAmount();
        $this->assertNotNull($discrepancy);
        $this->assertEqualsWithDelta(-5.00, $discrepancy->getAmount(), 0.001);
    }

    #[Test]
    public function it_reconciles_batch_with_positive_discrepancy(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        // Processor settled $2 more than expected
        $batch->reconcile(Money::of('99.00', 'MYR'));

        $this->assertTrue($batch->hasDiscrepancy());
        $discrepancy = $batch->getDiscrepancyAmount();
        $this->assertNotNull($discrepancy);
        $this->assertEqualsWithDelta(2.00, $discrepancy->getAmount(), 0.001);
    }

    #[Test]
    public function it_reconciles_with_reference(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        $batch->reconcile(Money::of('97.00', 'MYR'), 'SETTLE-REF-12345');

        $this->assertSame('SETTLE-REF-12345', $batch->getProcessorBatchReference());
    }

    #[Test]
    public function it_throws_exception_when_reconciling_open_batch(): void
    {
        $batch = $this->createBatch();

        $this->expectException(InvalidSettlementBatchStatusException::class);

        $batch->reconcile(Money::of('97.00', 'MYR'));
    }

    #[Test]
    public function it_marks_batch_as_disputed(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        $batch->markDisputed('Settlement amount incorrect');

        $this->assertSame(SettlementBatchStatus::DISPUTED, $batch->getStatus());
        $this->assertSame('Settlement amount incorrect', $batch->getMetadata()['dispute_reason']);
        $this->assertArrayHasKey('disputed_at', $batch->getMetadata());
    }

    #[Test]
    public function it_throws_exception_when_disputing_open_batch(): void
    {
        $batch = $this->createBatch();

        $this->expectException(InvalidSettlementBatchStatusException::class);

        $batch->markDisputed('Some reason');
    }

    #[Test]
    public function it_resolves_dispute_by_reconciling(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();
        $batch->markDisputed('Initial dispute');

        // Disputed batches can transition to reconciled
        $batch->reconcile(Money::of('97.00', 'MYR'));

        $this->assertSame(SettlementBatchStatus::RECONCILED, $batch->getStatus());
    }

    #[Test]
    public function it_checks_batch_states(): void
    {
        $batch = $this->createBatch();

        // Open state
        $this->assertTrue($batch->isOpen());
        $this->assertFalse($batch->isReconciled());

        // Closed state
        $batch->close();
        $this->assertFalse($batch->isOpen());
        $this->assertFalse($batch->isReconciled());

        // Reconciled state
        $batch->reconcile(Money::zero('MYR'));
        $this->assertFalse($batch->isOpen());
        $this->assertTrue($batch->isReconciled());
    }

    #[Test]
    public function it_removes_payment_from_batch(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->addPayment('payment-002', Money::of(200, 'MYR'), Money::of(6, 'MYR'));

        $batch->removePayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));

        $this->assertSame(1, $batch->getPaymentCount());
        $this->assertFalse($this->hasPayment($batch, 'payment-001'));
        $this->assertTrue($this->hasPayment($batch, 'payment-002'));
        $this->assertEqualsWithDelta(200.00, $batch->getGrossAmount()->getAmount(), 0.001);
        $this->assertEqualsWithDelta(6.00, $batch->getTotalFees()->getAmount(), 0.001);
        $this->assertEqualsWithDelta(194.00, $batch->getNetAmount()->getAmount(), 0.001);
    }

    #[Test]
    public function it_throws_exception_when_removing_from_closed_batch(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        $this->expectException(InvalidSettlementBatchStatusException::class);

        $batch->removePayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
    }

    #[Test]
    public function it_handles_batch_reference(): void
    {
        $batch = $this->createBatch();
        $this->assertNull($batch->getProcessorBatchReference());

        $batch->setProcessorBatchReference('BATCH-REF-123');

        $this->assertSame('BATCH-REF-123', $batch->getProcessorBatchReference());
    }

    #[Test]
    public function it_handles_settlement_date(): void
    {
        $batch = $this->createBatch();
        $this->assertNull($batch->getSettlementDate());

        $settlementDate = new DateTimeImmutable('2024-01-20');
        $batch->setSettlementDate($settlementDate);

        $this->assertSame($settlementDate, $batch->getSettlementDate());
    }

    #[Test]
    public function it_handles_metadata(): void
    {
        $metadata = ['channel' => 'online', 'region' => 'APAC'];
        $batch = $this->createBatch(metadata: $metadata);

        $this->assertSame($metadata, $batch->getMetadata());
    }

    #[Test]
    public function it_sets_expected_settlement_manually(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));

        // Override expected settlement manually
        $batch->setExpectedSettlement(Money::of('95.00', 'MYR'));

        $expectedAmount = $batch->getExpectedSettlementAmount();
        $this->assertNotNull($expectedAmount);
        $this->assertEqualsWithDelta(95.00, $expectedAmount->getAmount(), 0.001);
    }

    #[Test]
    public function it_throws_exception_when_setting_expected_with_wrong_currency(): void
    {
        $batch = $this->createBatch();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');

        $batch->setExpectedSettlement(Money::of('100.00', 'USD'));
    }

    #[Test]
    public function it_calculates_discrepancy_correctly(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();

        // Before reconciliation, discrepancy is null
        $this->assertNull($batch->getDiscrepancyAmount());

        $batch->reconcile(Money::of('90.00', 'MYR'));

        // Discrepancy = actual - expected = 90 - 97 = -7
        $discrepancy = $batch->getDiscrepancyAmount();
        $this->assertNotNull($discrepancy);
        $this->assertEqualsWithDelta(-7.00, $discrepancy->getAmount(), 0.001);
    }

    #[Test]
    public function it_updates_timestamp_on_modifications(): void
    {
        $batch = $this->createBatch();
        $this->assertNull($batch->getUpdatedAt());

        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));

        $this->assertInstanceOf(DateTimeImmutable::class, $batch->getUpdatedAt());
    }

    #[Test]
    public function it_returns_zero_discrepancy_when_amounts_match(): void
    {
        $batch = $this->createBatch();
        $batch->addPayment('payment-001', Money::of(100, 'MYR'), Money::of(3, 'MYR'));
        $batch->close();
        $batch->reconcile(Money::of('97.00', 'MYR'));

        $discrepancy = $batch->getDiscrepancyAmount();
        
        $this->assertNotNull($discrepancy);
        $this->assertTrue($discrepancy->isZero());
        $this->assertFalse($batch->hasDiscrepancy());
    }

    #[Test]
    public function it_throws_exception_for_lowercase_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be a valid ISO 4217 3-letter code, got: usd');

        SettlementBatch::create(
            id: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'usd'
        );
    }

    #[Test]
    public function it_throws_exception_for_non_three_letter_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be a valid ISO 4217 3-letter code, got: US');

        SettlementBatch::create(
            id: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'US'
        );
    }

    #[Test]
    public function it_throws_exception_for_numeric_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be a valid ISO 4217 3-letter code, got: 123');

        SettlementBatch::create(
            id: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: '123'
        );
    }

    #[Test]
    public function it_throws_exception_for_empty_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be a valid ISO 4217 3-letter code, got: ');

        SettlementBatch::create(
            id: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: ''
        );
    }

    #[Test]
    public function it_throws_exception_for_too_long_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be a valid ISO 4217 3-letter code, got: USDT');

        SettlementBatch::create(
            id: 'batch-001',
            tenantId: 'tenant-001',
            processorId: 'stripe',
            currency: 'USDT'
        );
    }
}
