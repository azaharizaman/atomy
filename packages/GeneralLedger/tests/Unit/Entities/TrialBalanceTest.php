<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\Entities\TrialBalance;
use Nexus\GeneralLedger\Entities\TrialBalanceLine;
use Nexus\Common\ValueObjects\Money;

final class TrialBalanceTest extends TestCase
{
    public function test_it_can_be_created_from_lines(): void
    {
        $now = new \DateTimeImmutable();
        $lines = [
            new TrialBalanceLine('a1', '1000', 'Cash', 'USD', Money::of('100.00', 'USD'), Money::zero('USD')),
            new TrialBalanceLine('a2', '2000', 'Payable', 'USD', Money::zero('USD'), Money::of('100.00', 'USD')),
        ];

        $tb = TrialBalance::create('id', 'ledger-id', 'period-id', $now, $lines);

        $this->assertEquals('id', $tb->id);
        $this->assertTrue($tb->isBalanced);
        $this->assertEquals('100.00', $tb->totalDebits->getAmount());
        $this->assertEquals('100.00', $tb->totalCredits->getAmount());
        $this->assertEquals(0, $tb->getDifference()->getAmount());
        $this->assertEquals(1, $tb->getDebitCount());
        $this->assertEquals(1, $tb->getCreditCount());
    }

    public function test_it_is_unbalanced_when_debits_dont_equal_credits(): void
    {
        $now = new \DateTimeImmutable();
        $lines = [
            new TrialBalanceLine('a1', '1000', 'Cash', 'USD', Money::of('100.00', 'USD'), Money::zero('USD')),
            new TrialBalanceLine('a2', '2000', 'Payable', 'USD', Money::zero('USD'), Money::of('90.00', 'USD')),
        ];

        $tb = TrialBalance::create('id', 'ledger-id', 'period-id', $now, $lines);

        $this->assertFalse($tb->isBalanced);
        $this->assertEquals('10.00', $tb->getDifference()->getAmount());
        
        $summary = $tb->getSummary();
        $this->assertEquals('10.00', $summary['difference']);
        $this->assertFalse($summary['is_balanced']);
    }

    public function test_trial_balance_line_methods(): void
    {
        $debitLine = new TrialBalanceLine('a1', '1000', 'Cash', 'USD', Money::of('100.00', 'USD'), Money::zero('USD'));
        $this->assertTrue($debitLine->isDebit());
        $this->assertFalse($debitLine->isCredit());
        $this->assertFalse($debitLine->isZero());
        $this->assertEquals('100.00', $debitLine->getNetBalance()->getAmount());
        
        $zeroLine = new TrialBalanceLine('a3', '3000', 'Old', 'USD', Money::zero('USD'), Money::zero('USD'));
        $this->assertTrue($zeroLine->isZero());
        
        $array = $debitLine->toArray();
        $this->assertEquals('100.00', $array['debit_balance']);
        $this->assertEquals('debit', $array['balance_type']);
    }

    public function test_it_can_get_debit_and_credit_accounts(): void
    {
        $now = new \DateTimeImmutable();
        $lines = [
            new TrialBalanceLine('a1', '1000', 'Cash', 'USD', Money::of('100.00', 'USD'), Money::zero('USD')),
            new TrialBalanceLine('a2', '2000', 'Payable', 'USD', Money::zero('USD'), Money::of('100.00', 'USD')),
        ];

        $tb = TrialBalance::create('id', 'ledger-id', 'period-id', $now, $lines);
        
        $this->assertCount(1, $tb->getDebitAccounts());
        $this->assertCount(1, $tb->getCreditAccounts());
    }

    public function test_it_throws_exception_if_no_lines(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Trial balance must have at least one line');

        TrialBalance::create('id', 'ledger-id', 'period-id', new \DateTimeImmutable(), []);
    }

    public function test_it_validates_currency_consistency(): void
    {
        $lines = [
            new TrialBalanceLine('a1', '1000', 'Cash', 'USD', Money::of('100.00', 'USD'), Money::zero('USD')),
            new TrialBalanceLine('a2', '2000', 'Payable', 'EUR', Money::zero('EUR'), Money::of('100.00', 'EUR')),
        ];

        $this->expectException(\InvalidArgumentException::class);
        TrialBalance::create('id', 'ledger-id', 'period-id', new \DateTimeImmutable(), $lines);
    }

    public function test_line_validates_debit_and_credit_not_both_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrialBalanceLine('a1', '1000', 'Cash', 'USD', Money::of('100.00', 'USD'), Money::of('100.00', 'USD'));
    }

    public function test_line_validates_currency_match(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TrialBalanceLine('a1', '1000', 'Cash', 'USD', Money::of('100.00', 'EUR'), Money::zero('EUR'));
    }
}
