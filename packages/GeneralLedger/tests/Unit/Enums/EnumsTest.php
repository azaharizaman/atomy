<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\Enums\SubledgerType;
use Nexus\GeneralLedger\Enums\LedgerStatus;
use Nexus\GeneralLedger\Enums\LedgerType;

final class EnumsTest extends TestCase
{
    public function test_balance_type(): void
    {
        $this->assertTrue(BalanceType::DEBIT->isDebit());
        $this->assertTrue(BalanceType::CREDIT->isCredit());
        $this->assertTrue(BalanceType::NONE->isNone());
        $this->assertEquals(BalanceType::CREDIT, BalanceType::DEBIT->opposite());
        $this->assertEquals(BalanceType::DEBIT, BalanceType::CREDIT->opposite());
        $this->assertEquals(BalanceType::NONE, BalanceType::NONE->opposite());
        $this->assertEquals('Debit Balance', BalanceType::DEBIT->label());
    }

    public function test_transaction_type(): void
    {
        $this->assertTrue(TransactionType::DEBIT->isDebit());
        $this->assertTrue(TransactionType::CREDIT->isCredit());
        $this->assertEquals(TransactionType::CREDIT, TransactionType::DEBIT->opposite());
        $this->assertEquals(TransactionType::DEBIT, TransactionType::CREDIT->opposite());
        $this->assertEquals('Debit', TransactionType::DEBIT->label());
    }

    public function test_subledger_type(): void
    {
        $this->assertTrue(SubledgerType::RECEIVABLE->isReceivable());
        $this->assertTrue(SubledgerType::PAYABLE->isPayable());
        $this->assertTrue(SubledgerType::ASSET->isAsset());
        $this->assertEquals('AR', SubledgerType::RECEIVABLE->getControlAccountPrefix());
        $this->assertEquals('AP', SubledgerType::PAYABLE->getControlAccountPrefix());
        $this->assertEquals('FA', SubledgerType::ASSET->getControlAccountPrefix());
    }
}
