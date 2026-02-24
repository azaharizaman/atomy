<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\GeneralLedger\Services\LedgerService;
use Nexus\GeneralLedger\Contracts\LedgerQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerPersistInterface;
use Nexus\GeneralLedger\Contracts\IdGeneratorInterface;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Enums\LedgerType;
use Nexus\GeneralLedger\Enums\LedgerStatus;
use Nexus\GeneralLedger\Exceptions\LedgerNotFoundException;
use Nexus\GeneralLedger\Exceptions\InvalidCurrencyException;

final class LedgerServiceTest extends TestCase
{
    private readonly MockObject&LedgerQueryInterface $queryRepository;
    private readonly MockObject&LedgerPersistInterface $persistRepository;
    private readonly MockObject&IdGeneratorInterface $idGenerator;
    private readonly LedgerService $service;

    protected function setUp(): void
    {
        $this->queryRepository = $this->createMock(LedgerQueryInterface::class);
        $this->persistRepository = $this->createMock(LedgerPersistInterface::class);
        $this->idGenerator = $this->createMock(IdGeneratorInterface::class);
        $this->service = new LedgerService(
            $this->queryRepository,
            $this->persistRepository,
            $this->idGenerator
        );
    }

    public function test_it_can_create_a_ledger(): void
    {
        $tenantId = '01AN4Z07BY79KA1307SR9X4MV3';
        $name = 'Main Ledger';
        $type = LedgerType::STATUTORY;
        $currency = 'USD';
        $id = '01AN4Z07BY79KA1307SR9X4MV4';

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($id);

        $this->persistRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Ledger $ledger) use ($id, $tenantId, $name, $currency, $type) {
                return $ledger->id === $id &&
                    $ledger->tenantId === $tenantId &&
                    $ledger->name === $name &&
                    $ledger->currency === $currency &&
                    $ledger->type === $type &&
                    $ledger->status === LedgerStatus::ACTIVE;
            }));

        $ledger = $this->service->createLedger($tenantId, $name, $type, $currency);

        $this->assertInstanceOf(Ledger::class, $ledger);
        $this->assertEquals($id, $ledger->id);
    }

    public function test_it_throws_exception_on_invalid_currency_when_creating_ledger(): void
    {
        $this->expectException(InvalidCurrencyException::class);
        $this->expectExceptionMessage('Invalid currency code: US. Expected ISO 4217 format');

        $this->service->createLedger(
            'tenant-id',
            'Main Ledger',
            LedgerType::STATUTORY,
            'US'
        );
    }

    public function test_it_can_get_a_ledger(): void
    {
        $ledgerId = '01AN4Z07BY79KA1307SR9X4MV4';
        $ledger = Ledger::create(
            $ledgerId,
            'tenant-id',
            'Main Ledger',
            'USD',
            LedgerType::STATUTORY
        );

        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $result = $this->service->getLedger($ledgerId);

        $this->assertSame($ledger, $result);
    }

    public function test_it_throws_exception_when_ledger_not_found(): void
    {
        $ledgerId = 'non-existent';
        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn(null);

        $this->expectException(LedgerNotFoundException::class);
        $this->service->getLedger($ledgerId);
    }

    public function test_it_can_get_ledgers_by_tenant(): void
    {
        $tenantId = 'tenant-id';
        $ledgers = [
            Ledger::create('id1', $tenantId, 'Ledger 1', 'USD', LedgerType::STATUTORY),
            Ledger::create('id2', $tenantId, 'Ledger 2', 'USD', LedgerType::MANAGEMENT),
        ];

        $this->queryRepository->expects($this->once())
            ->method('findByTenant')
            ->with($tenantId)
            ->willReturn($ledgers);

        $result = $this->service->getLedgersByTenant($tenantId);

        $this->assertCount(2, $result);
        $this->assertSame($ledgers, $result);
    }

    public function test_it_can_close_a_ledger(): void
    {
        $ledgerId = '01AN4Z07BY79KA1307SR9X4MV4';
        $ledger = Ledger::create(
            $ledgerId,
            'tenant-id',
            'Main Ledger',
            'USD',
            LedgerType::STATUTORY
        );

        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->persistRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn(Ledger $l) => $l->status === LedgerStatus::CLOSED));

        $result = $this->service->closeLedger($ledgerId);

        $this->assertTrue($result->isClosed());
    }

    public function test_it_can_get_active_ledgers(): void
    {
        $tenantId = 'tenant-id';
        $ledgers = [Ledger::create('id1', $tenantId, 'L1', 'USD', LedgerType::STATUTORY)];
        
        $this->queryRepository->expects($this->once())
            ->method('findActiveByTenant')
            ->with($tenantId)
            ->willReturn($ledgers);

        $result = $this->service->getActiveLedgers($tenantId);
        $this->assertSame($ledgers, $result);
    }

    public function test_it_can_get_ledgers_by_type(): void
    {
        $tenantId = 'tenant-id';
        $type = LedgerType::MANAGEMENT;
        $ledgers = [Ledger::create('id1', $tenantId, 'L1', 'USD', $type)];
        
        $this->queryRepository->expects($this->once())
            ->method('findByType')
            ->with($tenantId, $type)
            ->willReturn($ledgers);

        $result = $this->service->getLedgersByType($tenantId, $type);
        $this->assertSame($ledgers, $result);
    }

    public function test_it_can_archive_a_ledger(): void
    {
        $ledgerId = 'ledger-id';
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'L1', 'USD', LedgerType::STATUTORY);
        
        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->persistRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn(Ledger $l) => $l->isArchived()));

        $result = $this->service->archiveLedger($ledgerId);
        $this->assertTrue($result->isArchived());
    }

    public function test_it_can_reactivate_a_ledger(): void
    {
        $ledgerId = 'ledger-id';
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'L1', 'USD', LedgerType::STATUTORY)->close();
        
        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->persistRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn(Ledger $l) => $l->isActive()));

        $result = $this->service->reactivateLedger($ledgerId);
        $this->assertTrue($result->isActive());
    }

    public function test_it_can_check_if_it_can_post_transactions(): void
    {
        $ledgerId = 'ledger-id';
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'L1', 'USD', LedgerType::STATUTORY);
        
        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->assertTrue($this->service->canPostTransactions($ledgerId));
    }
}
