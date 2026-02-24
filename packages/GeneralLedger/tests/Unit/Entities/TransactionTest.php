<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\Entities\Transaction;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\Common\ValueObjects\Money;
use Brick\Math\BigDecimal;

final class TransactionTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $now = new \DateTimeImmutable();
        $amount = AccountBalance::debit(Money::of('100.00', 'USD'));
        $runningBalance = AccountBalance::debit(Money::of('500.00', 'USD'));

        $tx = Transaction::create(
            'id',
            'acc-id',
            'line-id',
            'je-id',
            TransactionType::DEBIT,
            $amount,
            $runningBalance,
            'period-id',
            $now,
            $now,
            'Description',
            'REF'
        );

        $this->assertEquals('id', $tx->id);
        $this->assertEquals(TransactionType::DEBIT, $tx->type);
        $this->assertTrue($tx->isDebit());
        $this->assertFalse($tx->isCredit());
        $this->assertTrue($tx->canReverse());
    }

    public function test_it_can_be_reversed(): void
    {
        $now = new \DateTimeImmutable();
        $amount = AccountBalance::debit(Money::of('100.00', 'USD'));
        $runningBalance = AccountBalance::debit(Money::of('500.00', 'USD'));

        $original = Transaction::create(
            'id',
            'acc-id',
            'line-id',
            'je-id',
            TransactionType::DEBIT,
            $amount,
            $runningBalance,
            'period-id',
            $now,
            $now
        );

        $newBalance = AccountBalance::debit(Money::of('400.00', 'USD'));
        [$reversal, $originalWithRef] = $original->reverse('rev-id', 'new-period', $newBalance);

        $this->assertEquals('rev-id', $reversal->id);
        $this->assertEquals(TransactionType::CREDIT, $reversal->type);
        $this->assertStringContainsString('Reversal of id', $reversal->reference);
        
        $this->assertTrue($originalWithRef->isReversed());
        $this->assertEquals('rev-id', $originalWithRef->reversedById);
    }

    public function test_it_can_get_summary(): void
    {
        $now = new \DateTimeImmutable();
        $tx = Transaction::create(
            'id', 'acc-id', 'line-id', 'je-id', 
            TransactionType::DEBIT, 
            AccountBalance::debit(Money::of('100.00', 'USD')),
            AccountBalance::debit(Money::of('500.00', 'USD')),
            'p', $now, $now
        );

        $summary = $tx->getSummary();
        $this->assertEquals('id', $summary['id']);
        $this->assertEquals(100.00, $summary['amount']);
        $this->assertFalse($summary['isReversed']);
    }

    public function test_it_can_get_effective_balance_impact(): void
    {
        $tx = Transaction::create(
            'id', 'acc-id', 'line-id', 'je-id', 
            TransactionType::DEBIT, 
            AccountBalance::debit(Money::of('100.00', 'USD')),
            AccountBalance::debit(Money::of('100.00', 'USD')),
            'p', new \DateTimeImmutable(), new \DateTimeImmutable()
        );

        // Debit impact on Debit account is positive
        $impact = $tx->getEffectiveBalanceImpact(BalanceType::DEBIT);
        $this->assertEquals('100.00', $impact->getAmount());

        // Debit impact on Credit account is negative
        $impact = $tx->getEffectiveBalanceImpact(BalanceType::CREDIT);
        $this->assertEquals('-100.00', $impact->getAmount());
        
        $creditTx = Transaction::create(
            'id', 'acc-id', 'line-id', 'je-id', 
            TransactionType::CREDIT, 
            AccountBalance::credit(Money::of('100.00', 'USD')),
            AccountBalance::credit(Money::of('100.00', 'USD')),
            'p', new \DateTimeImmutable(), new \DateTimeImmutable()
        );
        
        // Credit impact on Credit account is positive
        $impact = $creditTx->getEffectiveBalanceImpact(BalanceType::CREDIT);
        $this->assertEquals('100.00', $impact->getAmount());
        
        // Credit impact on Debit account is negative
        $impact = $creditTx->getEffectiveBalanceImpact(BalanceType::DEBIT);
        $this->assertEquals('-100.00', $impact->getAmount());
    }
}
