<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Tests\Unit\Services;

use Nexus\PaymentBank\Contracts\BankStatementQueryInterface;
use Nexus\PaymentBank\Contracts\BankTransactionQueryInterface;
use Nexus\PaymentBank\DTOs\InternalTransaction;
use Nexus\PaymentBank\Entities\BankStatementInterface;
use Nexus\PaymentBank\Entities\BankTransactionInterface;
use Nexus\PaymentBank\Services\BankReconciliationManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BankReconciliationManagerTest extends TestCase
{
    private BankTransactionQueryInterface $transactionQuery;
    private BankStatementQueryInterface $statementQuery;
    private LoggerInterface $logger;
    private BankReconciliationManager $manager;

    protected function setUp(): void
    {
        $this->transactionQuery = $this->createMock(BankTransactionQueryInterface::class);
        $this->statementQuery = $this->createMock(BankStatementQueryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new BankReconciliationManager(
            $this->transactionQuery,
            $this->statementQuery,
            $this->logger
        );
    }

    public function test_reconcile_returns_matched_and_unmatched_transactions(): void
    {
        $statement = $this->createMock(BankStatementInterface::class);
        $statement->method('getId')->willReturn('stmt-1');
        $statement->method('getConnectionId')->willReturn('conn-1');
        $statement->method('getStartDate')->willReturn(new \DateTimeImmutable('2023-01-01'));
        $statement->method('getEndDate')->willReturn(new \DateTimeImmutable('2023-01-31'));

        $this->statementQuery->expects($this->once())
            ->method('findById')
            ->with('stmt-1')
            ->willReturn($statement);

        $txn1 = $this->createMock(BankTransactionInterface::class);
        $txn1->method('getId')->willReturn('txn-1');
        $txn1->method('getAmount')->willReturn(100.0);
        $txn1->method('getDate')->willReturn(new \DateTimeImmutable('2023-01-05'));

        $txn2 = $this->createMock(BankTransactionInterface::class);
        $txn2->method('getId')->willReturn('txn-2');
        $txn2->method('getAmount')->willReturn(200.0);
        $txn2->method('getDate')->willReturn(new \DateTimeImmutable('2023-01-10'));

        $this->transactionQuery->expects($this->once())
            ->method('findByConnectionAndDateRange')
            ->with('conn-1', $statement->getStartDate(), $statement->getEndDate())
            ->willReturn([$txn1, $txn2]);

        // Mock internal transactions using InternalTransaction DTO
        $internalTxn1 = new InternalTransaction('int-1', 100.0, new \DateTimeImmutable('2023-01-05'), 'ref-1');
        $internalTxn3 = new InternalTransaction('int-3', 300.0, new \DateTimeImmutable('2023-01-15'), 'ref-3');

        $result = $this->manager->reconcile('stmt-1', [$internalTxn1, $internalTxn3]);

        $this->assertInstanceOf(\Nexus\PaymentBank\DTOs\ReconciliationResult::class, $result);

        // txn1 matches int-1 (amount and date match)
        $matched = $result->getMatchedTransactions();
        $this->assertCount(1, $matched);
        $this->assertEquals('txn-1', $matched[0]['bank_transaction']->getId());
        $this->assertEquals('int-1', $matched[0]['internal_transaction']->getId());

        // txn2 and int-3 are unmatched
        $unmatched = $result->getUnmatchedTransactions();
        $this->assertCount(2, $unmatched);
        
        // Verify we have both unmatched bank and unmatched internal transactions
        $unmatchedIds = array_map(fn($txn) => $txn->getId(), $unmatched);
        $this->assertContains('txn-2', $unmatchedIds);
        $this->assertContains('int-3', $unmatchedIds);
    }
}
