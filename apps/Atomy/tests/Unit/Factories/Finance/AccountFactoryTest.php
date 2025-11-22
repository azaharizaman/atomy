<?php

declare(strict_types=1);

namespace Tests\Unit\Factories\Finance;

use App\Models\Finance\Account;
use Database\Factories\Finance\AccountFactory;
use Nexus\Finance\Enums\AccountType;
use Tests\TestCase;

final class AccountFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_asset_account_with_correct_type(): void
    {
        $account = Account::factory()->asset()->make();

        $this->assertEquals(AccountType::Asset, $account->account_type);
    }

    /** @test */
    public function it_creates_liability_account_with_correct_type(): void
    {
        $account = Account::factory()->liability()->make();

        $this->assertEquals(AccountType::Liability, $account->account_type);
    }

    /** @test */
    public function it_creates_equity_account_with_correct_type(): void
    {
        $account = Account::factory()->equity()->make();

        $this->assertEquals(AccountType::Equity, $account->account_type);
    }

    /** @test */
    public function it_creates_revenue_account_with_correct_type(): void
    {
        $account = Account::factory()->revenue()->make();

        $this->assertEquals(AccountType::Revenue, $account->account_type);
    }

    /** @test */
    public function it_creates_expense_account_with_correct_type(): void
    {
        $account = Account::factory()->expense()->make();

        $this->assertEquals(AccountType::Expense, $account->account_type);
    }

    /** @test */
    public function it_chains_state_methods_correctly(): void
    {
        $account = Account::factory()
            ->asset()
            ->active()
            ->withCode('1000')
            ->make();

        $this->assertEquals(AccountType::Asset, $account->account_type);
        $this->assertTrue($account->is_active);
        $this->assertEquals('1000', $account->code);
    }

    /** @test */
    public function it_returns_new_instance_for_chaining(): void
    {
        $factory1 = Account::factory();
        $factory2 = $factory1->asset();

        $this->assertNotSame($factory1, $factory2);
        $this->assertInstanceOf(AccountFactory::class, $factory2);
    }

    /** @test */
    public function it_creates_header_account(): void
    {
        $account = Account::factory()->header()->make();

        $this->assertTrue($account->is_header);
    }

    /** @test */
    public function it_creates_inactive_account(): void
    {
        $account = Account::factory()->inactive()->make();

        $this->assertFalse($account->is_active);
    }

    /** @test */
    public function it_sets_parent_account(): void
    {
        $parentId = '01JCQR5XYZ1234567890ABCDEF';
        $account = Account::factory()->withParent($parentId)->make();

        $this->assertEquals($parentId, $account->parent_id);
    }
}
