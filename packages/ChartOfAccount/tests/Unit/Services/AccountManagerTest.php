<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Tests\Unit\Services;

use Nexus\ChartOfAccount\Contracts\AccountInterface;
use Nexus\ChartOfAccount\Contracts\AccountPersistInterface;
use Nexus\ChartOfAccount\Contracts\AccountQueryInterface;
use Nexus\ChartOfAccount\Enums\AccountType;
use Nexus\ChartOfAccount\Exceptions\AccountNotFoundException;
use Nexus\ChartOfAccount\Exceptions\DuplicateAccountCodeException;
use Nexus\ChartOfAccount\Exceptions\InvalidAccountException;
use Nexus\ChartOfAccount\Services\AccountManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AccountManagerTest extends TestCase
{
    private AccountManager $manager;
    private MockObject&AccountQueryInterface $query;
    private MockObject&AccountPersistInterface $persist;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->query = $this->createMock(AccountQueryInterface::class);
        $this->persist = $this->createMock(AccountPersistInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new AccountManager(
            $this->query,
            $this->persist,
            $this->logger
        );
    }

    public function test_createAccount_validates_required_fields(): void
    {
        $this->expectException(InvalidAccountException::class);

        $this->manager->createAccount([
            'name' => 'Cash',
            'type' => 'asset',
        ]);
    }

    public function test_createAccount_validates_code_format(): void
    {
        $this->expectException(InvalidAccountException::class);

        $this->manager->createAccount([
            'code' => '', // Empty code
            'name' => 'Cash',
            'type' => 'ASSET',
        ]);
    }

    public function test_createAccount_checks_for_duplicate_code(): void
    {
        $this->query->method('codeExists')->willReturn(true);

        $this->expectException(DuplicateAccountCodeException::class);

        $this->manager->createAccount([
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'ASSET',
        ]);
    }

    public function test_createAccount_creates_valid_account(): void
    {
        $accountData = [
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',  // lowercase to match enum value
        ];

        $mockAccount = $this->createMock(AccountInterface::class);
        $mockAccount->method('getId')->willReturn('acc-1');
        $mockAccount->method('getCode')->willReturn('1000');

        $this->query->method('codeExists')->willReturn(false);
        
        $this->persist->expects($this->once())
            ->method('create')
            ->with($accountData)
            ->willReturn($mockAccount);

        $result = $this->manager->createAccount($accountData);

        $this->assertSame($mockAccount, $result);
    }

    public function test_updateAccount_throws_exception_for_nonexistent_account(): void
    {
        $this->query->method('find')->willReturn(null);

        $this->expectException(AccountNotFoundException::class);

        $this->manager->updateAccount('nonexistent-id', ['name' => 'New Name']);
    }

    public function test_updateAccount_validates_code_uniqueness(): void
    {
        $mockAccount = $this->createMock(AccountInterface::class);
        $mockAccount->method('getCode')->willReturn('1000');

        $this->query->method('find')->willReturn($mockAccount);
        $this->query->method('codeExists')->willReturn(true);

        $this->expectException(DuplicateAccountCodeException::class);

        $this->manager->updateAccount('acc-1', ['code' => '2000']);
    }

    public function test_updateAccount_prevents_type_change(): void
    {
        $mockAccount = $this->createMock(AccountInterface::class);
        $mockAccount->method('getType')->willReturn(AccountType::Asset);

        $this->query->method('find')->willReturn($mockAccount);

        $this->expectException(InvalidAccountException::class);

        $this->manager->updateAccount('acc-1', ['type' => AccountType::Liability]);
    }

    public function test_findById_throws_exception_for_nonexistent_account(): void
    {
        $this->query->method('find')->willReturn(null);

        $this->expectException(AccountNotFoundException::class);

        $this->manager->findById('nonexistent-id');
    }

    public function test_findById_returns_existing_account(): void
    {
        $mockAccount = $this->createMock(AccountInterface::class);

        $this->query->expects($this->once())
            ->method('find')
            ->with('acc-1')
            ->willReturn($mockAccount);

        $result = $this->manager->findById('acc-1');

        $this->assertSame($mockAccount, $result);
    }

    public function test_findByCode_throws_exception_for_nonexistent_account(): void
    {
        $this->query->method('findByCode')->willReturn(null);

        $this->expectException(AccountNotFoundException::class);

        $this->manager->findByCode('9999');
    }

    public function test_findByCode_returns_existing_account(): void
    {
        $mockAccount = $this->createMock(AccountInterface::class);

        $this->query->expects($this->once())
            ->method('findByCode')
            ->with('1000')
            ->willReturn($mockAccount);

        $result = $this->manager->findByCode('1000');

        $this->assertSame($mockAccount, $result);
    }

    public function test_getAccounts_returns_all_accounts(): void
    {
        $mockAccounts = [
            $this->createMock(AccountInterface::class),
            $this->createMock(AccountInterface::class),
        ];

        $this->query->expects($this->once())
            ->method('findAll')
            ->with([])
            ->willReturn($mockAccounts);

        $result = $this->manager->getAccounts();

        $this->assertSame($mockAccounts, $result);
    }

    public function test_getChildren_throws_exception_for_nonexistent_parent(): void
    {
        $this->query->method('find')->willReturn(null);

        $this->expectException(AccountNotFoundException::class);

        $this->manager->getChildren('nonexistent-id');
    }

    public function test_getChildren_returns_child_accounts(): void
    {
        $mockParent = $this->createMock(AccountInterface::class);
        $mockChildren = [
            $this->createMock(AccountInterface::class),
        ];

        $this->query->method('find')->willReturn($mockParent);
        $this->query->expects($this->once())
            ->method('findChildren')
            ->with('parent-1')
            ->willReturn($mockChildren);

        $result = $this->manager->getChildren('parent-1');

        $this->assertSame($mockChildren, $result);
    }

    public function test_getAccountsByType_returns_accounts_of_type(): void
    {
        $mockAccounts = [
            $this->createMock(AccountInterface::class),
        ];

        $this->query->expects($this->once())
            ->method('findByType')
            ->with(AccountType::Asset)
            ->willReturn($mockAccounts);

        $result = $this->manager->getAccountsByType(AccountType::Asset);

        $this->assertSame($mockAccounts, $result);
    }

    public function test_isCodeAvailable_returns_true_when_code_available(): void
    {
        $this->query->method('codeExists')->willReturn(false);

        $result = $this->manager->isCodeAvailable('1000');

        $this->assertTrue($result);
    }

    public function test_isCodeAvailable_returns_false_when_code_exists(): void
    {
        $this->query->method('codeExists')->willReturn(true);

        $result = $this->manager->isCodeAvailable('1000');

        $this->assertFalse($result);
    }
}
