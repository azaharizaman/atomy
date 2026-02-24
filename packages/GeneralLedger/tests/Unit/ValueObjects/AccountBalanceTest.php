<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\Common\ValueObjects\Money;

final class AccountBalanceTest extends TestCase
{
    public function test_it_can_create_zero_balance(): void
    {
        $balance = AccountBalance::zero('USD');
        $this->assertTrue($balance->isZero());
        $this->assertEquals(BalanceType::NONE, $balance->balanceType);
    }

    public function test_it_can_create_debit_balance(): void
    {
        $money = Money::of('100.00', 'USD');
        $balance = AccountBalance::debit($money);
        $this->assertTrue($balance->isDebit());
        $this->assertEquals('100.00', $balance->amount->getAmount());
    }

    public function test_it_can_create_credit_balance(): void
    {
        $money = Money::of('100.00', 'USD');
        $balance = AccountBalance::credit($money);
        $this->assertTrue($balance->isCredit());
        $this->assertEquals('100.00', $balance->amount->getAmount());
    }

    public function test_it_can_add_balances(): void
    {
        $b1 = AccountBalance::debit(Money::of('100.00', 'USD'));
        $b2 = AccountBalance::debit(Money::of('50.00', 'USD'));
        $result = $b1->add($b2);
        
        $this->assertTrue($result->isDebit());
        $this->assertEquals('150.00', $result->amount->getAmount());
    }

    public function test_it_can_net_balances(): void
    {
        $b1 = AccountBalance::debit(Money::of('100.00', 'USD'));
        $b2 = AccountBalance::credit(Money::of('40.00', 'USD'));
        $result = $b1->add($b2);
        
        $this->assertTrue($result->isDebit());
        $this->assertEquals('60.00', $result->amount->getAmount());
    }

    public function test_it_can_subtract_balances(): void
    {
        $b1 = AccountBalance::debit(Money::of('100.00', 'USD'));
        $b2 = AccountBalance::debit(Money::of('40.00', 'USD'));
        $result = $b1->subtract($b2);
        
        $this->assertTrue($result->isDebit());
        $this->assertEquals('60.00', $result->amount->getAmount());
    }

    public function test_it_gets_signed_amount(): void
    {
        $balance = AccountBalance::debit(Money::of('100.00', 'USD'));
        
        // Debit balance on debit account is positive
        $this->assertEquals('100.00', $balance->getSignedAmount(BalanceType::DEBIT)->getAmount());
        
        // Debit balance on credit account is negative
        $this->assertEquals('-100.00', $balance->getSignedAmount(BalanceType::CREDIT)->getAmount());
    }

    public function test_it_throws_when_none_account_type_passed_to_get_signed_amount(): void
    {
        $balance = AccountBalance::debit(Money::of('100.00', 'USD'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BalanceType::NONE is not a valid account type');
        $balance->getSignedAmount(BalanceType::NONE);
    }

    public function test_it_can_convert_to_array(): void
    {
        $balance = AccountBalance::debit(Money::of('100.00', 'USD'));
        $array = $balance->toArray();
        
        $this->assertEquals(100.00, $array['amount']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals('debit', $array['balance_type']);
    }

    public function test_it_validates_positive_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccountBalance(Money::of('-1.00', 'USD'), BalanceType::DEBIT);
    }

    public function test_it_validates_zero_amount_none_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccountBalance(Money::zero('USD'), BalanceType::DEBIT);
    }

    public function test_it_validates_positive_amount_not_none_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccountBalance(Money::of('1.00', 'USD'), BalanceType::NONE);
    }

    public function test_it_throws_on_different_currencies_add(): void
    {
        $b1 = AccountBalance::debit(Money::of('100.00', 'USD'));
        $b2 = AccountBalance::debit(Money::of('100.00', 'EUR'));
        
        $this->expectException(\InvalidArgumentException::class);
        $b1->add($b2);
    }
}
