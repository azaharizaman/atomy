<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Enums\BalanceType;

final class LedgerAccountTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $account = LedgerAccount::create(
            'id',
            'ledger-id',
            'coa-id',
            '1000',
            'Cash',
            BalanceType::DEBIT
        );

        $this->assertEquals('id', $account->id);
        $this->assertEquals('1000', $account->accountCode);
        $this->assertEquals(BalanceType::DEBIT, $account->balanceType);
        $this->assertTrue($account->allowPosting);
        $this->assertTrue($account->isActive);
        $this->assertTrue($account->canPostTransactions());
    }

    public function test_it_can_be_closed(): void
    {
        $account = LedgerAccount::create('id', 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT);
        $closed = $account->close();

        $this->assertFalse($closed->isActive);
        $this->assertFalse($closed->canPostTransactions());
        $this->assertNotNull($closed->closedAt);
    }

    public function test_it_can_be_reopened(): void
    {
        $account = LedgerAccount::create('id', 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT);
        $closed = $account->close();
        $reopened = $closed->reopen();

        $this->assertTrue($reopened->isActive);
        $this->assertNull($reopened->closedAt);
    }

    public function test_it_can_assign_cost_center(): void
    {
        $account = LedgerAccount::create('id', 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT);
        $updated = $account->assignCostCenter('cc-id');

        $this->assertEquals('cc-id', $updated->costCenterId);
        $this->assertTrue($updated->hasCostCenter());
    }

    public function test_it_can_remove_cost_center(): void
    {
        $account = LedgerAccount::create('id', 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT, costCenterId: 'cc-id');
        $updated = $account->removeCostCenter();

        $this->assertNull($updated->costCenterId);
        $this->assertFalse($updated->hasCostCenter());
    }

    public function test_it_validates_bank_account_must_allow_posting(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LedgerAccount(
            'id',
            'ledger-id',
            'coa-id',
            '1000',
            'Cash',
            BalanceType::DEBIT,
            false, // allowPosting = false
            true,  // isBankAccount = true
            true,  // isActive = true
            null,
            null,
            new \DateTimeImmutable()
        );
    }
}
