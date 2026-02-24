<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\ValueObjects\TransactionDetail;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\Common\ValueObjects\Money;

final class TransactionDetailTest extends TestCase
{
    public function test_it_can_create_debit_detail(): void
    {
        $amount = AccountBalance::debit(Money::of('100.00', 'USD'));
        $detail = TransactionDetail::debit(
            'acc-id',
            'je-id',
            $amount,
            'line-id',
            'Description',
            'REF'
        );

        $this->assertEquals(TransactionType::DEBIT, $detail->type);
        $this->assertTrue($detail->isDebit());
        $this->assertEquals('acc-id', $detail->ledgerAccountId);
    }

    public function test_it_can_create_credit_detail(): void
    {
        $amount = AccountBalance::credit(Money::of('100.00', 'USD'));
        $detail = TransactionDetail::credit(
            'acc-id',
            'je-id',
            $amount
        );

        $this->assertEquals(TransactionType::CREDIT, $detail->type);
        $this->assertTrue($detail->isCredit());
    }

    public function test_it_validates_amount_type_mismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debit transaction must have a debit-typed AccountBalance');
        
        new TransactionDetail(
            'acc-id',
            'je-id',
            TransactionType::DEBIT,
            AccountBalance::credit(Money::of('100.00', 'USD'))
        );
    }

    public function test_it_can_convert_to_array(): void
    {
        $amount = AccountBalance::debit(Money::of('100.00', 'USD'));
        $detail = TransactionDetail::debit('acc-id', 'je-id', $amount);
        $array = $detail->toArray();
        
        $this->assertEquals('acc-id', $array['ledger_account_id']);
        $this->assertEquals('debit', $array['type']);
    }

    public function test_it_can_get_amount(): void
    {
        $amount = AccountBalance::debit(Money::of('100.00', 'USD'));
        $detail = TransactionDetail::debit('acc-id', 'je-id', $amount);
        $this->assertSame($amount, $detail->getAmount());
    }

    public function test_it_validates_zero_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TransactionDetail('a', 'j', TransactionType::DEBIT, AccountBalance::zero('USD'));
    }

    public function test_it_validates_journal_entry_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TransactionDetail('a', '', TransactionType::DEBIT, AccountBalance::debit(Money::of('1.00', 'USD')));
    }
}
