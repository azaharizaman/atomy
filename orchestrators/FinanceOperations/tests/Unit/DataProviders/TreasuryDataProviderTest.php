<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\DataProviders;

use Nexus\FinanceOperations\Contracts\LedgerQueryInterface;
use Nexus\FinanceOperations\Contracts\PayableQueryInterface;
use Nexus\FinanceOperations\Contracts\ReceivableQueryInterface;
use Nexus\FinanceOperations\Contracts\TreasuryManagerQueryInterface;
use Nexus\FinanceOperations\DataProviders\TreasuryDataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TreasuryDataProviderTest extends TestCase
{
    private TreasuryManagerQueryInterface $treasuryManager;
    private LedgerQueryInterface $ledgerQuery;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->treasuryManager = $this->createMock(TreasuryManagerQueryInterface::class);
        $this->ledgerQuery = $this->createMock(LedgerQueryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetCashPositionReturnsMappedData(): void
    {
        $position = new class {
            public function getBalance(): string { return '1200.25'; }
            public function getCurrency(): string { return 'USD'; }
            public function getAsOfDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-04-07'); }
            public function getAvailableBalance(): string { return '1100.25'; }
            public function getPendingCredits(): string { return '150.00'; }
            public function getPendingDebits(): string { return '50.00'; }
        };

        $this->treasuryManager
            ->expects($this->once())
            ->method('getPosition')
            ->with('tenant-1', 'bank-1')
            ->willReturn($position);

        $provider = new TreasuryDataProvider(
            $this->treasuryManager,
            $this->ledgerQuery,
            null,
            null,
            $this->logger
        );

        $result = $provider->getCashPosition('tenant-1', 'bank-1');

        $this->assertSame('bank-1', $result['bank_account_id']);
        $this->assertSame('1200.25', $result['balance']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame('2026-04-07', $result['as_of_date']);
    }

    public function testGetCashFlowForecastAggregatesInflowsAndOutflows(): void
    {
        $receivableQuery = $this->createMock(ReceivableQueryInterface::class);
        $payableQuery = $this->createMock(PayableQueryInterface::class);

        $receivableQuery
            ->expects($this->once())
            ->method('getExpectedReceipts')
            ->willReturn([
                new class {
                    public function getExpectedDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-05-01'); }
                    public function getAmount(): string { return '200.00'; }
                    public function getCurrency(): string { return 'USD'; }
                    public function getReference(): string { return 'AR-001'; }
                    public function getPartyId(): string { return 'customer-1'; }
                },
            ]);

        $payableQuery
            ->expects($this->once())
            ->method('getExpectedPayments')
            ->willReturn([
                new class {
                    public function getDueDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-05-02'); }
                    public function getAmount(): string { return '75.00'; }
                    public function getCurrency(): string { return 'USD'; }
                    public function getReference(): string { return 'AP-001'; }
                    public function getPartyId(): string { return 'vendor-1'; }
                },
            ]);

        $provider = new TreasuryDataProvider(
            $this->treasuryManager,
            $this->ledgerQuery,
            $receivableQuery,
            $payableQuery,
            $this->logger
        );

        $result = $provider->getCashFlowForecast('tenant-1', '2026-05');

        $this->assertSame('125', $result['net_cash_flow']);
        $this->assertSame('200', $result['total_inflows']);
        $this->assertSame('75', $result['total_outflows']);
        $this->assertCount(1, $result['inflows']);
        $this->assertCount(1, $result['outflows']);
    }

    public function testGetBankReconciliationDataSupportsTraversableSources(): void
    {
        $statementLine = new class {
            public function getDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-05-01'); }
            public function getDescription(): string { return 'bank line'; }
            public function getAmount(): string { return '100.00'; }
            public function getReference(): string { return 'STMT-1'; }
            public function isReconciled(): bool { return false; }
        };

        $glTransaction = new class {
            public function getDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-05-01'); }
            public function getDescription(): string { return 'gl line'; }
            public function getAmount(): string { return '100.00'; }
            public function getReference(): string { return 'GL-1'; }
            public function isReconciled(): bool { return true; }
        };

        $statementLines = new \ArrayIterator([$statementLine]);
        $glTransactions = new \ArrayIterator([$glTransaction]);

        $this->treasuryManager
            ->expects($this->once())
            ->method('getStatementLines')
            ->willReturn($statementLines);

        $this->ledgerQuery
            ->expects($this->once())
            ->method('getAccountTransactions')
            ->willReturn($glTransactions);

        $provider = new TreasuryDataProvider(
            $this->treasuryManager,
            $this->ledgerQuery,
            null,
            null,
            $this->logger
        );

        $result = $provider->getBankReconciliationData('tenant-1', 'bank-1');

        $this->assertSame('100', $result['statement_balance']);
        $this->assertSame('100', $result['gl_balance']);
        $this->assertCount(1, $result['statement_lines']);
        $this->assertCount(1, $result['gl_transactions']);
    }
}
