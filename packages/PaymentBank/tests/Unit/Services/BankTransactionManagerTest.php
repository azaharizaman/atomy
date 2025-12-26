<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Tests\Unit\Services;

use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\BankProviderInterface;
use Nexus\PaymentBank\Contracts\BankTransactionPersistInterface;
use Nexus\PaymentBank\Contracts\BankTransactionQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Entities\BankTransactionInterface;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Nexus\PaymentBank\Services\BankTransactionManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BankTransactionManagerTest extends TestCase
{
    private BankTransactionPersistInterface $persist;
    private BankTransactionQueryInterface $query;
    private BankConnectionQueryInterface $connectionQuery;
    private ProviderRegistryInterface $providerRegistry;
    private LoggerInterface $logger;
    private BankTransactionManager $manager;

    protected function setUp(): void
    {
        $this->persist = $this->createMock(BankTransactionPersistInterface::class);
        $this->query = $this->createMock(BankTransactionQueryInterface::class);
        $this->connectionQuery = $this->createMock(BankConnectionQueryInterface::class);
        $this->providerRegistry = $this->createMock(ProviderRegistryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new BankTransactionManager(
            $this->persist,
            $this->query,
            $this->connectionQuery,
            $this->providerRegistry,
            $this->logger
        );
    }

    public function test_fetch_transactions_saves_transactions(): void
    {
        $connection = $this->createMock(BankConnectionInterface::class);
        $connection->method('getId')->willReturn('conn-1');
        $connection->method('getProviderName')->willReturn('plaid');
        $connection->method('getCredentials')->willReturn(['access_token' => 'token']);

        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with('conn-1')
            ->willReturn($connection);

        $provider = $this->createMock(BankProviderInterface::class);
        $provider->expects($this->once())
            ->method('fetchTransactions')
            ->willReturn([
                ['id' => 'txn-1', 'date' => '2023-01-01', 'amount' => 100, 'description' => 'Txn 1'],
                ['id' => 'txn-2', 'date' => '2023-01-02', 'amount' => 200, 'description' => 'Txn 2'],
            ]);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $this->persist->expects($this->exactly(2))
            ->method('save');

        $result = $this->manager->fetchTransactions('conn-1', new \DateTimeImmutable('2023-01-01'), new \DateTimeImmutable('2023-01-31'));

        $this->assertCount(2, $result);
    }

    public function test_fetch_transactions_throws_exception_if_connection_not_found(): void
    {
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with('conn-1')
            ->willReturn(null);

        $this->expectException(BankConnectionNotFoundException::class);

        $this->manager->fetchTransactions('conn-1', new \DateTimeImmutable('2023-01-01'), new \DateTimeImmutable('2023-01-31'));
    }

    public function test_get_transaction_returns_transaction(): void
    {
        $transaction = $this->createMock(BankTransactionInterface::class);
        $this->query->expects($this->once())
            ->method('findById')
            ->with('txn-1')
            ->willReturn($transaction);

        $result = $this->manager->getTransaction('txn-1');

        $this->assertSame($transaction, $result);
    }
}
