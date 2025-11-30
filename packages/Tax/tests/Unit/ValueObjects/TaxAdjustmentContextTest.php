<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Enums\TaxType;
use Nexus\Tax\ValueObjects\TaxAdjustmentContext;
use PHPUnit\Framework\TestCase;

final class TaxAdjustmentContextTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $context = new TaxAdjustmentContext(
            adjustmentId: 'ADJ-001',
            originalTransactionId: 'TXN-001',
            adjustmentDate: new \DateTimeImmutable('2024-02-15'),
            reason: 'Customer refund',
            adjustmentAmount: Money::of('-100.00', 'USD'),
            taxType: TaxType::SalesTax,
            taxCode: 'US-CA-SALES',
            isFullReversal: false,
        );

        $this->assertSame('ADJ-001', $context->adjustmentId);
        $this->assertSame('TXN-001', $context->originalTransactionId);
        $this->assertSame('Customer refund', $context->reason);
        $this->assertSame('-100.00', $context->adjustmentAmount->getAmount());
        $this->assertSame(TaxType::SalesTax, $context->taxType);
        $this->assertFalse($context->isFullReversal);
    }

    public function test_it_validates_empty_adjustment_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Adjustment ID cannot be empty');

        new TaxAdjustmentContext(
            adjustmentId: '',
            originalTransactionId: 'TXN-001',
            adjustmentDate: new \DateTimeImmutable('2024-02-15'),
            reason: 'Customer refund',
            adjustmentAmount: Money::of('-100.00', 'USD'),
        );
    }

    public function test_it_validates_empty_original_transaction_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Original transaction ID cannot be empty');

        new TaxAdjustmentContext(
            adjustmentId: 'ADJ-001',
            originalTransactionId: '',
            adjustmentDate: new \DateTimeImmutable('2024-02-15'),
            reason: 'Customer refund',
            adjustmentAmount: Money::of('-100.00', 'USD'),
        );
    }

    public function test_it_validates_empty_reason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Adjustment reason cannot be empty');

        new TaxAdjustmentContext(
            adjustmentId: 'ADJ-001',
            originalTransactionId: 'TXN-001',
            adjustmentDate: new \DateTimeImmutable('2024-02-15'),
            reason: '',
            adjustmentAmount: Money::of('-100.00', 'USD'),
        );
    }

    public function test_it_handles_full_reversal_flag(): void
    {
        $context = new TaxAdjustmentContext(
            adjustmentId: 'ADJ-002',
            originalTransactionId: 'TXN-002',
            adjustmentDate: new \DateTimeImmutable('2024-02-15'),
            reason: 'Complete reversal',
            adjustmentAmount: Money::of('-107.25', 'USD'),
            isFullReversal: true,
        );

        $this->assertTrue($context->isFullReversal);
    }
}
