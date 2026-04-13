<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\DataProviders;

use Nexus\FinanceOperations\Contracts\AssetQueryInterface;
use Nexus\FinanceOperations\Contracts\LedgerQueryInterface;
use Nexus\FinanceOperations\Contracts\PayableQueryInterface;
use Nexus\FinanceOperations\Contracts\ReceivableQueryInterface;
use Nexus\FinanceOperations\DataProviders\GLReconciliationProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class GLReconciliationProviderTest extends TestCase
{
    private ReceivableQueryInterface $receivableQuery;
    private PayableQueryInterface $payableQuery;
    private AssetQueryInterface $assetQuery;
    private LedgerQueryInterface $ledgerQuery;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->receivableQuery = $this->createMock(ReceivableQueryInterface::class);
        $this->payableQuery = $this->createMock(PayableQueryInterface::class);
        $this->assetQuery = $this->createMock(AssetQueryInterface::class);
        $this->ledgerQuery = $this->createMock(LedgerQueryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetSubledgerBalanceForReceivableReturnsMappedData(): void
    {
        $balance = new class {
            public function getBalance(): string { return '350.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getInvoiceCount(): int { return 3; }
            public function getOpenItemCount(): int { return 2; }
        };

        $this->receivableQuery
            ->expects($this->once())
            ->method('getTotalBalance')
            ->with('tenant-1', '2026-04')
            ->willReturn($balance);

        $provider = new GLReconciliationProvider(
            $this->receivableQuery,
            $this->payableQuery,
            $this->assetQuery,
            $this->ledgerQuery,
            $this->logger
        );

        $result = $provider->getSubledgerBalance('tenant-1', '2026-04', 'receivable');

        $this->assertSame('receivable', $result['subledger_type']);
        $this->assertSame('2026-04', $result['period_id']);
        $this->assertSame('350.00', $result['balance']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame(3, $result['invoice_count']);
    }

    public function testGetSubledgerBalanceForUnknownTypeReturnsStructuredError(): void
    {
        $provider = new GLReconciliationProvider(
            $this->receivableQuery,
            $this->payableQuery,
            $this->assetQuery,
            $this->ledgerQuery,
            $this->logger
        );

        $result = $provider->getSubledgerBalance('tenant-1', '2026-04', 'unsupported');

        $this->assertSame('unsupported', $result['subledger_type']);
        $this->assertSame('0', $result['balance']);
        $this->assertStringContainsString('Unknown subledger type', (string) $result['error']);
    }

    public function testGetDiscrepanciesSupportsTraversableSources(): void
    {
        $receivableBalance = new class {
            public function getBalance(): string { return '350.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getInvoiceCount(): int { return 3; }
            public function getOpenItemCount(): int { return 2; }
        };
        $payableBalance = new class {
            public function getBalance(): string { return '0.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getInvoiceCount(): int { return 0; }
            public function getOpenItemCount(): int { return 0; }
        };
        $assetBalance = new class {
            public function getNetBookValue(): string { return '0.00'; }
            public function getCurrency(): string { return 'USD'; }
            public function getAssetCount(): int { return 0; }
            public function getAccumulatedDepreciation(): string { return '0.00'; }
        };

        $this->receivableQuery->method('getTotalBalance')->willReturn($receivableBalance);
        $this->payableQuery->method('getTotalBalance')->willReturn($payableBalance);
        $this->assetQuery->method('getNetBookValueTotal')->willReturn($assetBalance);

        $this->receivableQuery->method('getControlAccountCode')->willReturn('1100');
        $this->payableQuery->method('getControlAccountCode')->willReturn('2100');
        $this->assetQuery->method('getControlAccountCode')->willReturn('1500');

        $this->ledgerQuery
            ->method('getAccountBalance')
            ->willReturnCallback(static function (string $tenantId, string $accountId, string $periodId): object {
                return match ($accountId) {
                    '1100' => new class {
                        public function getBalance(): string { return '300.00'; }
                        public function getCurrency(): string { return 'USD'; }
                        public function getDebitTotal(): string { return '300.00'; }
                        public function getCreditTotal(): string { return '0.00'; }
                        public function getAsOfDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-04-30'); }
                    },
                    default => new class {
                        public function getBalance(): string { return '0.00'; }
                        public function getCurrency(): string { return 'USD'; }
                        public function getDebitTotal(): string { return '0.00'; }
                        public function getCreditTotal(): string { return '0.00'; }
                        public function getAsOfDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-04-30'); }
                    },
                };
            });

        $this->receivableQuery
            ->method('getUnpostedTransactions')
            ->willReturn(new \ArrayIterator([
                new class {
                    public function getReference(): string { return 'AR-UNPOSTED-1'; }
                    public function getAmount(): string { return '50.00'; }
                    public function getDate(): \DateTimeImmutable { return new \DateTimeImmutable('2026-04-29'); }
                    public function getDescription(): string { return 'Open AR item'; }
                },
            ]));

        $this->payableQuery
            ->method('getUnpostedTransactions')
            ->willReturn(new \ArrayIterator([]));

        $this->assetQuery
            ->method('getUnpostedDepreciation')
            ->willReturn(new \ArrayIterator([]));

        $provider = new GLReconciliationProvider(
            $this->receivableQuery,
            $this->payableQuery,
            $this->assetQuery,
            $this->ledgerQuery,
            $this->logger
        );

        $result = $provider->getDiscrepancies('tenant-1', '2026-04');

        $this->assertTrue($result['has_discrepancies']);
        $this->assertSame(1, $result['discrepancy_count']);
        $this->assertSame('receivable', $result['discrepancies'][0]['subledger_type']);
        $this->assertCount(1, $result['discrepancies'][0]['details']);
        $this->assertSame('AR-UNPOSTED-1', $result['discrepancies'][0]['details'][0]['reference']);
    }
}
