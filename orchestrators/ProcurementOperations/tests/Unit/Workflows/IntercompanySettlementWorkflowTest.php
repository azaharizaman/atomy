<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Workflows;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\IntercompanySettlementServiceInterface;
use Nexus\ProcurementOperations\DTOs\Financial\IntercompanySettlementData;
use Nexus\ProcurementOperations\DTOs\Financial\NetSettlementResult;
use Nexus\ProcurementOperations\Enums\SettlementStatus;
use Nexus\ProcurementOperations\Events\Financial\IntercompanyNettingCompletedEvent;
use Nexus\ProcurementOperations\Events\Financial\IntercompanySettlementCompletedEvent;
use Nexus\ProcurementOperations\Events\Financial\IntercompanySettlementInitiatedEvent;
use Nexus\ProcurementOperations\Workflows\IntercompanySettlementWorkflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(IntercompanySettlementWorkflow::class)]
final class IntercompanySettlementWorkflowTest extends TestCase
{
    private IntercompanySettlementServiceInterface&MockObject $settlementService;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private IntercompanySettlementWorkflow $workflow;

    protected function setUp(): void
    {
        $this->settlementService = $this->createMock(IntercompanySettlementServiceInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->workflow = new IntercompanySettlementWorkflow(
            settlementService: $this->settlementService,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function initiateSettlementCalculatesNettingAndReturnsSettlement(): void
    {
        // Arrange
        $tenantId = 'tenant-1';
        $fromEntityId = 'entity-us';
        $toEntityId = 'entity-uk';
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $initiatedBy = 'user-finance';

        $receivableTransaction = new IntercompanySettlementData(
            transactionId: 'txn-001',
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            transactionType: 'receivable',
            amount: Money::of(50000.00, 'USD'),
            originalCurrency: 'USD',
            exchangeRate: 1.0,
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            dueDate: new \DateTimeImmutable('2024-02-15'),
            documentReference: 'INV-2024-001',
            description: 'Intercompany services',
        );

        $payableTransaction = new IntercompanySettlementData(
            transactionId: 'txn-002',
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            transactionType: 'payable',
            amount: Money::of(30000.00, 'USD'),
            originalCurrency: 'USD',
            exchangeRate: 1.0,
            transactionDate: new \DateTimeImmutable('2024-01-20'),
            dueDate: new \DateTimeImmutable('2024-02-20'),
            documentReference: 'BILL-2024-001',
            description: 'Intercompany goods',
        );

        $nettingResult = new NetSettlementResult(
            settlementId: 'SETTLE-001',
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            grossReceivable: Money::of(50000.00, 'USD'),
            grossPayable: Money::of(30000.00, 'USD'),
            netAmount: Money::of(20000.00, 'USD'),
            settlementCurrency: 'USD',
            exchangeRatesUsed: ['USD' => 1.0],
            transactionCount: 2,
            metadata: [],
        );

        $this->settlementService
            ->expects($this->once())
            ->method('validateBalanceAgreement')
            ->with($fromEntityId, $toEntityId)
            ->willReturn(['balanced' => true, 'variance' => 0.0]);

        $this->settlementService
            ->expects($this->once())
            ->method('getPendingTransactions')
            ->willReturn([$receivableTransaction, $payableTransaction]);

        $this->settlementService
            ->expects($this->once())
            ->method('calculateNetSettlement')
            ->willReturn($nettingResult);

        // Expect two events: initiated and netting completed
        $this->eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (object $event) {
                $this->assertTrue(
                    $event instanceof IntercompanySettlementInitiatedEvent
                    || $event instanceof IntercompanyNettingCompletedEvent
                );
                return $event;
            });

        // Act
        $result = $this->workflow->initiateSettlement(
            tenantId: $tenantId,
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            initiatedBy: $initiatedBy,
        );

        // Assert
        $this->assertNotEmpty($result['settlement_id']);
        $this->assertSame(SettlementStatus::NETTING_CALCULATED, $result['status']);
        $this->assertSame($fromEntityId, $result['from_entity_id']);
        $this->assertSame($toEntityId, $result['to_entity_id']);
        $this->assertEquals(Money::of(20000.00, 'USD'), $result['net_amount']);
        $this->assertCount(2, $result['transactions']);
        $this->assertSame(2, $result['netting_result']->transactionCount);
    }

    #[Test]
    public function initiateSettlementThrowsOnUnbalancedAccounts(): void
    {
        // Arrange
        $this->settlementService
            ->expects($this->once())
            ->method('validateBalanceAgreement')
            ->willReturn([
                'balanced' => false,
                'variance' => 5000.00,
                'reason' => 'Outstanding differences',
            ]);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Balance validation failed');

        $this->workflow->initiateSettlement(
            tenantId: 'tenant-1',
            fromEntityId: 'entity-us',
            toEntityId: 'entity-uk',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            initiatedBy: 'user-finance',
        );
    }

    #[Test]
    public function approveSettlementTransitionsStatusCorrectly(): void
    {
        // Arrange
        $settlementId = 'SETTLE-001';
        $approvedBy = 'user-manager';

        $nettingResult = new NetSettlementResult(
            settlementId: $settlementId,
            fromEntityId: 'entity-us',
            toEntityId: 'entity-uk',
            grossReceivable: Money::of(50000.00, 'USD'),
            grossPayable: Money::of(30000.00, 'USD'),
            netAmount: Money::of(20000.00, 'USD'),
            settlementCurrency: 'USD',
            exchangeRatesUsed: [],
            transactionCount: 2,
            metadata: [],
        );

        $settlementState = [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::NETTING_CALCULATED,
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => $nettingResult,
            'transactions' => [],
        ];

        // Act
        $result = $this->workflow->approveSettlement(
            settlementId: $settlementId,
            approvedBy: $approvedBy,
            settlementState: $settlementState,
        );

        // Assert
        $this->assertSame($settlementId, $result['settlement_id']);
        $this->assertSame(SettlementStatus::APPROVED, $result['status']);
        $this->assertSame($approvedBy, $result['approved_by']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['approved_at']);
    }

    #[Test]
    public function approveSettlementRejectsInvalidTransition(): void
    {
        // Arrange
        $settlementState = [
            'settlement_id' => 'SETTLE-001',
            'status' => SettlementStatus::PENDING_NETTING, // Cannot approve from this state
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => null,
            'transactions' => [],
        ];

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot approve');

        $this->workflow->approveSettlement(
            settlementId: 'SETTLE-001',
            approvedBy: 'user-manager',
            settlementState: $settlementState,
        );
    }

    #[Test]
    public function executeSettlementRecordsTransactionsAndDispatchesEvents(): void
    {
        // Arrange
        $settlementId = 'SETTLE-001';
        $executedBy = 'user-finance';

        $nettingResult = new NetSettlementResult(
            settlementId: $settlementId,
            fromEntityId: 'entity-us',
            toEntityId: 'entity-uk',
            grossReceivable: Money::of(50000.00, 'USD'),
            grossPayable: Money::of(30000.00, 'USD'),
            netAmount: Money::of(20000.00, 'USD'),
            settlementCurrency: 'USD',
            exchangeRatesUsed: [],
            transactionCount: 2,
            metadata: [],
        );

        $settlementState = [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::APPROVED,
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => $nettingResult,
            'transactions' => [],
        ];

        $this->settlementService
            ->expects($this->once())
            ->method('recordSettlement')
            ->willReturn([
                'from_entry_id' => 'JE-001',
                'to_entry_id' => 'JE-002',
                'success' => true,
            ]);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(IntercompanySettlementCompletedEvent::class))
            ->willReturnArgument(0);

        // Act
        $result = $this->workflow->executeSettlement(
            settlementId: $settlementId,
            executedBy: $executedBy,
            settlementState: $settlementState,
        );

        // Assert
        $this->assertSame($settlementId, $result['settlement_id']);
        $this->assertSame(SettlementStatus::SETTLED, $result['status']);
        $this->assertArrayHasKey('journal_entries', $result);
        $this->assertSame('JE-001', $result['journal_entries']['from_entry_id']);
        $this->assertSame('JE-002', $result['journal_entries']['to_entry_id']);
    }

    #[Test]
    public function executeSettlementRetriesOnTemporaryFailure(): void
    {
        // Arrange
        $settlementId = 'SETTLE-001';

        $nettingResult = new NetSettlementResult(
            settlementId: $settlementId,
            fromEntityId: 'entity-us',
            toEntityId: 'entity-uk',
            grossReceivable: Money::of(50000.00, 'USD'),
            grossPayable: Money::of(30000.00, 'USD'),
            netAmount: Money::of(20000.00, 'USD'),
            settlementCurrency: 'USD',
            exchangeRatesUsed: [],
            transactionCount: 2,
            metadata: [],
        );

        $settlementState = [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::APPROVED,
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => $nettingResult,
            'transactions' => [],
        ];

        // Fail twice, succeed on third try
        $callCount = 0;
        $this->settlementService
            ->expects($this->exactly(3))
            ->method('recordSettlement')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                if ($callCount < 3) {
                    throw new \RuntimeException('Temporary failure');
                }
                return [
                    'from_entry_id' => 'JE-001',
                    'to_entry_id' => 'JE-002',
                    'success' => true,
                ];
            });

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        // Act
        $result = $this->workflow->executeSettlement(
            settlementId: $settlementId,
            executedBy: 'user-finance',
            settlementState: $settlementState,
        );

        // Assert
        $this->assertSame(SettlementStatus::SETTLED, $result['status']);
    }

    #[Test]
    public function generateEliminationEntriesReturnsConsolidationEntries(): void
    {
        // Arrange
        $settlementId = 'SETTLE-001';

        $nettingResult = new NetSettlementResult(
            settlementId: $settlementId,
            fromEntityId: 'entity-us',
            toEntityId: 'entity-uk',
            grossReceivable: Money::of(50000.00, 'USD'),
            grossPayable: Money::of(30000.00, 'USD'),
            netAmount: Money::of(20000.00, 'USD'),
            settlementCurrency: 'USD',
            exchangeRatesUsed: [],
            transactionCount: 2,
            metadata: [],
        );

        $settlementState = [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::SETTLED,
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => $nettingResult,
            'transactions' => [],
        ];

        $eliminationEntries = [
            [
                'entry_id' => 'ELIM-001',
                'account' => 'IC-Receivable',
                'debit' => Money::of(50000.00, 'USD'),
                'credit' => Money::of(0, 'USD'),
            ],
            [
                'entry_id' => 'ELIM-002',
                'account' => 'IC-Payable',
                'debit' => Money::of(0, 'USD'),
                'credit' => Money::of(30000.00, 'USD'),
            ],
        ];

        $this->settlementService
            ->expects($this->once())
            ->method('generateEliminationEntries')
            ->willReturn($eliminationEntries);

        // Act
        $result = $this->workflow->generateEliminationEntries(
            settlementId: $settlementId,
            consolidationPeriod: '2024-Q1',
            settlementState: $settlementState,
        );

        // Assert
        $this->assertSame($settlementId, $result['settlement_id']);
        $this->assertSame('2024-Q1', $result['consolidation_period']);
        $this->assertCount(2, $result['elimination_entries']);
    }

    #[Test]
    public function cancelSettlementMarksAsCancelledWhenModifiable(): void
    {
        // Arrange
        $settlementId = 'SETTLE-001';

        $nettingResult = new NetSettlementResult(
            settlementId: $settlementId,
            fromEntityId: 'entity-us',
            toEntityId: 'entity-uk',
            grossReceivable: Money::of(50000.00, 'USD'),
            grossPayable: Money::of(30000.00, 'USD'),
            netAmount: Money::of(20000.00, 'USD'),
            settlementCurrency: 'USD',
            exchangeRatesUsed: [],
            transactionCount: 2,
            metadata: [],
        );

        $settlementState = [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::NETTING_CALCULATED, // Modifiable state
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => $nettingResult,
            'transactions' => [],
        ];

        // Act
        $result = $this->workflow->cancelSettlement(
            settlementId: $settlementId,
            cancelledBy: 'user-manager',
            reason: 'Business requirements changed',
            settlementState: $settlementState,
        );

        // Assert
        $this->assertSame($settlementId, $result['settlement_id']);
        $this->assertSame(SettlementStatus::CANCELLED, $result['status']);
        $this->assertSame('user-manager', $result['cancelled_by']);
        $this->assertSame('Business requirements changed', $result['cancellation_reason']);
    }

    #[Test]
    public function cancelSettlementRejectsSettledStatus(): void
    {
        // Arrange
        $settlementState = [
            'settlement_id' => 'SETTLE-001',
            'status' => SettlementStatus::SETTLED, // Final state - not modifiable
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => null,
            'transactions' => [],
        ];

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot cancel');

        $this->workflow->cancelSettlement(
            settlementId: 'SETTLE-001',
            cancelledBy: 'user-manager',
            reason: 'Test',
            settlementState: $settlementState,
        );
    }

    #[Test]
    public function compensateFailedSettlementReversesJournalEntries(): void
    {
        // Arrange
        $settlementId = 'SETTLE-001';

        $nettingResult = new NetSettlementResult(
            settlementId: $settlementId,
            fromEntityId: 'entity-us',
            toEntityId: 'entity-uk',
            grossReceivable: Money::of(50000.00, 'USD'),
            grossPayable: Money::of(30000.00, 'USD'),
            netAmount: Money::of(20000.00, 'USD'),
            settlementCurrency: 'USD',
            exchangeRatesUsed: [],
            transactionCount: 2,
            metadata: [],
        );

        $settlementState = [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::PENDING_PAYMENT,
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => $nettingResult,
            'transactions' => [],
            'journal_entries' => [
                'from_entry_id' => 'JE-001',
                'to_entry_id' => 'JE-002',
            ],
        ];

        // Act
        $result = $this->workflow->compensateFailedSettlement(
            settlementId: $settlementId,
            failureReason: 'Payment gateway timeout',
            settlementState: $settlementState,
        );

        // Assert
        $this->assertSame($settlementId, $result['settlement_id']);
        $this->assertContains('journal_reversal', $result['compensations_applied']);
        $this->assertSame('Payment gateway timeout', $result['failure_reason']);
        $this->assertSame(SettlementStatus::CANCELLED, $result['final_status']);
    }

    #[Test]
    public function compensateFailedSettlementHandlesMissingJournalEntries(): void
    {
        // Arrange
        $settlementState = [
            'settlement_id' => 'SETTLE-001',
            'status' => SettlementStatus::APPROVED,
            'from_entity_id' => 'entity-us',
            'to_entity_id' => 'entity-uk',
            'netting_result' => null,
            'transactions' => [],
            // No journal_entries key - compensation should handle gracefully
        ];

        // Act
        $result = $this->workflow->compensateFailedSettlement(
            settlementId: 'SETTLE-001',
            failureReason: 'System error',
            settlementState: $settlementState,
        );

        // Assert
        $this->assertNotContains('journal_reversal', $result['compensations_applied']);
        $this->assertSame(SettlementStatus::CANCELLED, $result['final_status']);
    }
}
