<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\DTOs\AchBatchResult;
use Nexus\PaymentRails\DTOs\CheckResult;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\DTOs\VirtualCardResult;
use Nexus\PaymentRails\DTOs\WireTransferResult;
use Nexus\PaymentRails\Enums\CheckStatus;
use Nexus\PaymentRails\Enums\FileStatus;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\VirtualCardStatus;
use Nexus\PaymentRails\Enums\VirtualCardType;
use Nexus\PaymentRails\Enums\WireType;
use Nexus\PaymentRails\ValueObjects\CheckNumber;
use PHPUnit\Framework\TestCase;

class RailTransactionResultTest extends TestCase
{
    public function test_success_factory_creates_completed_result(): void
    {
        $amount = Money::of(1000, 'USD');
        $fees = Money::of(50, 'USD');
        $expectedSettlementDate = new \DateTimeImmutable('+1 day');
        
        $result = RailTransactionResult::success(
            transactionId: 'txn_123',
            railType: RailType::ACH,
            amount: $amount,
            referenceNumber: 'ref_123',
            fees: $fees,
            expectedSettlementDate: $expectedSettlementDate,
            metadata: ['key' => 'value']
        );

        $this->assertTrue($result->success);
        $this->assertEquals('completed', $result->status);
        $this->assertEquals('txn_123', $result->transactionId);
        $this->assertEquals(RailType::ACH, $result->railType);
        $this->assertTrue($amount->equals($result->amount));
        $this->assertEquals('ref_123', $result->referenceNumber);
        $this->assertTrue($fees->equals($result->fees));
        $this->assertEquals($expectedSettlementDate, $result->expectedSettlementDate);
        $this->assertEquals(['key' => 'value'], $result->metadata);
        $this->assertEmpty($result->errors);
        $this->assertTrue($result->isCompleted());
        $this->assertFalse($result->isPending());
        $this->assertFalse($result->isFailed());
    }

    public function test_pending_factory_creates_pending_result(): void
    {
        $amount = Money::of(500, 'EUR');
        
        $result = RailTransactionResult::pending(
            transactionId: 'txn_456',
            railType: RailType::WIRE,
            amount: $amount,
            referenceNumber: 'ref_456'
        );

        $this->assertTrue($result->success); // Pending is considered successful initiation
        $this->assertEquals('pending', $result->status);
        $this->assertEquals('txn_456', $result->transactionId);
        $this->assertEquals(RailType::WIRE, $result->railType);
        $this->assertTrue($amount->equals($result->amount));
        $this->assertEquals('ref_456', $result->referenceNumber);
        $this->assertNull($result->fees);
        $this->assertNull($result->expectedSettlementDate);
        $this->assertEmpty($result->metadata);
        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isCompleted());
        $this->assertFalse($result->isFailed());
    }

    public function test_failure_factory_creates_failed_result(): void
    {
        $amount = Money::of(100, 'GBP');
        $errors = ['INSUFFICIENT_FUNDS', 'Not enough balance'];
        
        $result = RailTransactionResult::failure(
            transactionId: 'txn_789',
            railType: RailType::ACH,
            amount: $amount,
            errors: $errors
        );

        $this->assertFalse($result->success);
        $this->assertEquals('failed', $result->status);
        $this->assertEquals('txn_789', $result->transactionId);
        $this->assertEquals(RailType::ACH, $result->railType);
        $this->assertTrue($amount->equals($result->amount));
        $this->assertEquals($errors, $result->errors);
        $this->assertNull($result->referenceNumber);
        $this->assertTrue($result->isFailed());
        $this->assertFalse($result->isCompleted());
        $this->assertFalse($result->isPending());
        $this->assertTrue($result->hasErrors());
    }

    public function test_get_total_amount_adds_fees(): void
    {
        $amount = Money::of(1000, 'USD');
        $fees = Money::of(50, 'USD');
        
        $result = RailTransactionResult::success(
            transactionId: 'txn_1',
            railType: RailType::ACH,
            amount: $amount,
            referenceNumber: 'ref_1',
            fees: $fees
        );

        $total = $result->getTotalAmount();
        $this->assertTrue($total->equals(Money::of(1050, 'USD')));
    }

    public function test_get_total_amount_returns_amount_when_no_fees(): void
    {
        $amount = Money::of(1000, 'USD');
        
        $result = RailTransactionResult::success(
            transactionId: 'txn_1',
            railType: RailType::ACH,
            amount: $amount,
            referenceNumber: 'ref_1'
        );

        $total = $result->getTotalAmount();
        $this->assertTrue($total->equals($amount));
    }

    public function test_get_metadata_returns_value_or_default(): void
    {
        $result = RailTransactionResult::success(
            transactionId: 'txn_1',
            railType: RailType::ACH,
            amount: Money::of(100, 'USD'),
            referenceNumber: 'ref_1',
            metadata: ['key' => 'value']
        );

        $this->assertEquals('value', $result->getMetadata('key'));
        $this->assertEquals('default', $result->getMetadata('missing', 'default'));
        $this->assertNull($result->getMetadata('missing'));
    }

    public function test_from_ach_result_creates_correct_result(): void
    {
        $achResult = new AchBatchResult(
            fileId: 'file_123',
            batchId: 'batch_123',
            success: true,
            status: FileStatus::ACCEPTED,
            entryCount: 10,
            totalDebits: Money::of(1000, 'USD'),
            totalCredits: Money::of(0, 'USD'),
            effectiveDate: new \DateTimeImmutable('+1 day')
        );

        $result = RailTransactionResult::fromAchResult($achResult);

        $this->assertEquals('file_123', $result->transactionId);
        $this->assertTrue($result->success);
        $this->assertEquals(FileStatus::ACCEPTED->value, $result->status);
        $this->assertEquals(RailType::ACH, $result->railType);
        $this->assertTrue($result->amount->equals(Money::of(1000, 'USD'))); // Assuming getTotalAmount() returns debits + credits or similar logic in AchBatchResult
        $this->assertEquals('batch_123', $result->referenceNumber);
        $this->assertEquals(['entry_count' => 10], $result->metadata);
        $this->assertEquals($achResult->effectiveDate, $result->expectedSettlementDate);
    }

    public function test_from_wire_result_creates_correct_result(): void
    {
        $wireResult = new WireTransferResult(
            transferId: 'wire_123',
            success: true,
            status: 'completed',
            amount: Money::of(5000, 'USD'),
            wireType: WireType::DOMESTIC,
            confirmationNumber: 'conf_123',
            fee: Money::of(25, 'USD'),
            expectedSettlementDate: new \DateTimeImmutable('+0 day')
        );

        $result = RailTransactionResult::fromWireResult($wireResult);

        $this->assertEquals('wire_123', $result->transactionId);
        $this->assertTrue($result->success);
        $this->assertEquals('completed', $result->status);
        $this->assertEquals(RailType::WIRE, $result->railType);
        $this->assertTrue($result->amount->equals(Money::of(5000, 'USD')));
        $this->assertEquals('conf_123', $result->referenceNumber);
        $this->assertTrue($result->fees->equals(Money::of(25, 'USD')));
        $this->assertEquals($wireResult->expectedSettlementDate, $result->expectedSettlementDate);
    }

    public function test_from_check_result_creates_correct_result(): void
    {
        $checkResult = new CheckResult(
            checkId: 'check_123',
            success: true,
            status: CheckStatus::MAILED,
            checkNumber: new CheckNumber('1001'),
            amount: Money::of(150, 'USD'),
            payeeName: 'John Doe'
        );

        $result = RailTransactionResult::fromCheckResult($checkResult);

        $this->assertEquals('check_123', $result->transactionId);
        $this->assertTrue($result->success);
        $this->assertEquals(CheckStatus::MAILED->value, $result->status);
        $this->assertEquals(RailType::CHECK, $result->railType);
        $this->assertTrue($result->amount->equals(Money::of(150, 'USD')));
        $this->assertEquals('1001', $result->referenceNumber);
        $this->assertEquals(['payee' => 'John Doe'], $result->metadata);
    }

    public function test_from_virtual_card_result_creates_correct_result(): void
    {
        $cardResult = new VirtualCardResult(
            cardId: 'card_123',
            success: true,
            status: VirtualCardStatus::ACTIVE,
            cardType: VirtualCardType::SINGLE_USE,
            creditLimit: Money::of(200, 'USD'),
            availableCredit: Money::of(200, 'USD'),
            maskedCardNumber: '************1234',
            expiresAt: new \DateTimeImmutable('+1 year')
        );

        $result = RailTransactionResult::fromVirtualCardResult($cardResult);

        $this->assertEquals('card_123', $result->transactionId);
        $this->assertTrue($result->success);
        $this->assertEquals(VirtualCardStatus::ACTIVE->value, $result->status);
        $this->assertEquals(RailType::VIRTUAL_CARD, $result->railType);
        $this->assertTrue($result->amount->equals(Money::of(200, 'USD')));
        $this->assertEquals('************1234', $result->referenceNumber);
        $this->assertEquals([
            'card_type' => VirtualCardType::SINGLE_USE->value,
            'available_credit' => 200.0 // Assuming Money::getAmount() returns cents
        ], $result->metadata);
        $this->assertEquals($cardResult->expiresAt, $result->expectedSettlementDate);
    }
}
