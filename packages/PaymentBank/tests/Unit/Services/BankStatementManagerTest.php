<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Tests\Unit\Services;

use Nexus\PaymentBank\Contracts\BankConnectionQueryInterface;
use Nexus\PaymentBank\Contracts\BankProviderInterface;
use Nexus\PaymentBank\Contracts\BankStatementPersistInterface;
use Nexus\PaymentBank\Contracts\BankStatementQueryInterface;
use Nexus\PaymentBank\Contracts\ProviderRegistryInterface;
use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Entities\BankStatementInterface;
use Nexus\PaymentBank\Exceptions\BankConnectionNotFoundException;
use Nexus\PaymentBank\Services\BankStatementManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BankStatementManagerTest extends TestCase
{
    private BankStatementPersistInterface $persist;
    private BankStatementQueryInterface $query;
    private BankConnectionQueryInterface $connectionQuery;
    private ProviderRegistryInterface $providerRegistry;
    private LoggerInterface $logger;
    private BankStatementManager $manager;

    protected function setUp(): void
    {
        $this->persist = $this->createMock(BankStatementPersistInterface::class);
        $this->query = $this->createMock(BankStatementQueryInterface::class);
        $this->connectionQuery = $this->createMock(BankConnectionQueryInterface::class);
        $this->providerRegistry = $this->createMock(ProviderRegistryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new BankStatementManager(
            $this->persist,
            $this->query,
            $this->connectionQuery,
            $this->providerRegistry,
            $this->logger
        );
    }

    public function test_fetch_statements_saves_statements(): void
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
            ->method('fetchStatements')
            ->willReturn([
                ['id' => 'stmt-1', 'date' => '2023-01-01', 'amount' => 1000],
                ['id' => 'stmt-2', 'date' => '2023-01-02', 'amount' => 2000],
            ]);

        $this->providerRegistry->expects($this->once())
            ->method('get')
            ->with('plaid')
            ->willReturn($provider);

        $this->persist->expects($this->exactly(2))
            ->method('save');

        $result = $this->manager->fetchStatements('conn-1', new \DateTimeImmutable('2023-01-01'), new \DateTimeImmutable('2023-01-31'));

        $this->assertCount(2, $result);
    }

    public function test_fetch_statements_throws_exception_if_connection_not_found(): void
    {
        $this->connectionQuery->expects($this->once())
            ->method('findById')
            ->with('conn-1')
            ->willReturn(null);

        $this->expectException(BankConnectionNotFoundException::class);

        $this->manager->fetchStatements('conn-1', new \DateTimeImmutable('2023-01-01'), new \DateTimeImmutable('2023-01-31'));
    }

    public function test_get_statement_returns_statement(): void
    {
        $statement = $this->createMock(BankStatementInterface::class);
        $this->query->expects($this->once())
            ->method('findById')
            ->with('stmt-1')
            ->willReturn($statement);

        $result = $this->manager->getStatement('stmt-1');

        $this->assertSame($statement, $result);
    }
}
