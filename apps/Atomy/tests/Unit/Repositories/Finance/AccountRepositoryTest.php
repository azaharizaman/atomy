<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Finance;

use App\Models\Finance\Account;
use App\Repositories\Finance\EloquentAccountRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Finance\Enums\AccountType;
use Nexus\Finance\Exceptions\AccountHasTransactionsException;
use Tests\TestCase;

final class AccountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentAccountRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentAccountRepository();
    }

    /** @test */
    public function it_can_find_account_by_id(): void
    {
        $account = Account::factory()->create();

        $found = $this->repository->find($account->id);

        $this->assertNotNull($found);
        $this->assertEquals($account->id, $found->getId());
    }

    /** @test */
    public function it_returns_null_when_account_not_found(): void
    {
        $found = $this->repository->find('non-existent-id');

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_find_account_by_code(): void
    {
        $account = Account::factory()->withCode('1000')->create();

        $found = $this->repository->findByCode('1000');

        $this->assertNotNull($found);
        $this->assertEquals('1000', $found->getCode());
    }

    /** @test */
    public function it_can_find_all_accounts(): void
    {
        Account::factory()->count(3)->create();

        $accounts = $this->repository->findAll();

        $this->assertCount(3, $accounts);
    }

    /** @test */
    public function it_can_filter_accounts_by_type(): void
    {
        Account::factory()->asset()->count(2)->create();
        Account::factory()->liability()->create();

        $assets = $this->repository->findAll(['type' => AccountType::Asset->value]);

        $this->assertCount(2, $assets);
        foreach ($assets as $account) {
            $this->assertEquals(AccountType::Asset->value, $account->getType());
        }
    }

    /** @test */
    public function it_can_filter_accounts_by_active_status(): void
    {
        Account::factory()->active()->count(2)->create();
        Account::factory()->inactive()->create();

        $activeAccounts = $this->repository->findAll(['active' => true]);

        $this->assertCount(2, $activeAccounts);
        foreach ($activeAccounts as $account) {
            $this->assertTrue($account->isActive());
        }
    }

    /** @test */
    public function it_can_find_child_accounts(): void
    {
        $parent = Account::factory()->header()->create();
        $child1 = Account::factory()->withParent($parent->id)->create();
        $child2 = Account::factory()->withParent($parent->id)->create();
        Account::factory()->create(); // Unrelated account

        $children = $this->repository->findChildren($parent->id);

        $this->assertCount(2, $children);
    }

    /** @test */
    public function it_can_save_account(): void
    {
        $account = Account::factory()->make();
        
        $this->repository->save($account);

        $this->assertDatabaseHas('accounts', [
            'code' => $account->code,
            'name' => $account->name,
        ]);
    }

    /** @test */
    public function it_can_check_if_code_exists(): void
    {
        Account::factory()->withCode('1000')->create();

        $exists = $this->repository->codeExists('1000');

        $this->assertTrue($exists);
    }

    /** @test */
    public function it_can_check_code_existence_excluding_specific_account(): void
    {
        $account = Account::factory()->withCode('1000')->create();

        $exists = $this->repository->codeExists('1000', $account->id);

        $this->assertFalse($exists);
    }

    /** @test */
    public function it_throws_exception_when_deleting_account_with_transactions(): void
    {
        $account = Account::factory()->create();
        
        // Create a journal entry line to simulate a transaction
        \App\Models\Finance\JournalEntryLine::factory()
            ->forAccount($account->id)
            ->create();

        $this->expectException(AccountHasTransactionsException::class);

        $this->repository->delete($account->id);
    }

    /** @test */
    public function it_can_delete_account_without_transactions(): void
    {
        $account = Account::factory()->create();

        $this->repository->delete($account->id);

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    /** @test */
    public function it_can_get_transaction_count_for_account(): void
    {
        $account = Account::factory()->create();
        
        \App\Models\Finance\JournalEntryLine::factory()
            ->forAccount($account->id)
            ->count(3)
            ->create();

        $count = $this->repository->getTransactionCount($account->id);

        $this->assertEquals(3, $count);
    }
}
