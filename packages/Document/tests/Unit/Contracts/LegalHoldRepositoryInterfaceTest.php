<?php

declare(strict_types=1);

namespace Nexus\Document\Tests\Unit\Contracts;

use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Document\Contracts\LegalHoldRepositoryInterface;
use Nexus\Document\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for LegalHoldRepositoryInterface contract.
 *
 * These tests verify that the repository interface provides
 * all necessary methods for legal hold management.
 */
final class LegalHoldRepositoryInterfaceTest extends TestCase
{
    private LegalHoldRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(LegalHoldRepositoryInterface::class);
    }

    public function testFindByIdReturnsHold(): void
    {
        $legalHold = $this->createMock(LegalHoldInterface::class);
        $legalHold->method('getId')->willReturn('hold-001');

        $this->repository
            ->method('findById')
            ->with('hold-001')
            ->willReturn($legalHold);

        $result = $this->repository->findById('hold-001');
        $this->assertInstanceOf(LegalHoldInterface::class, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->repository
            ->method('findById')
            ->with('hold-nonexistent')
            ->willReturn(null);

        $result = $this->repository->findById('hold-nonexistent');
        $this->assertNull($result);
    }

    public function testFindByDocumentIdReturnsAllHolds(): void
    {
        $hold1 = $this->createMock(LegalHoldInterface::class);
        $hold2 = $this->createMock(LegalHoldInterface::class);

        $this->repository
            ->method('findByDocumentId')
            ->with('doc-001')
            ->willReturn([$hold1, $hold2]);

        $result = $this->repository->findByDocumentId('doc-001');
        $this->assertCount(2, $result);
    }

    public function testFindActiveByDocumentIdReturnsOnlyActive(): void
    {
        $activeHold = $this->createMock(LegalHoldInterface::class);
        $activeHold->method('isActive')->willReturn(true);

        $this->repository
            ->method('findActiveByDocumentId')
            ->with('doc-001')
            ->willReturn([$activeHold]);

        $result = $this->repository->findActiveByDocumentId('doc-001');
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->isActive());
    }

    public function testHasActiveHoldReturnsBoolean(): void
    {
        $this->repository
            ->method('hasActiveHold')
            ->willReturnCallback(fn($id) => $id === 'doc-001');

        $this->assertTrue($this->repository->hasActiveHold('doc-001'));
        $this->assertFalse($this->repository->hasActiveHold('doc-002'));
    }

    public function testFindAllActiveReturnsArray(): void
    {
        $hold1 = $this->createMock(LegalHoldInterface::class);
        $hold2 = $this->createMock(LegalHoldInterface::class);

        $this->repository
            ->method('findAllActive')
            ->willReturn([$hold1, $hold2]);

        $result = $this->repository->findAllActive();
        $this->assertCount(2, $result);
    }

    public function testFindByMatterReference(): void
    {
        $hold = $this->createMock(LegalHoldInterface::class);
        $hold->method('getMatterReference')->willReturn('CASE-001');

        $this->repository
            ->method('findByMatterReference')
            ->with('CASE-001')
            ->willReturn([$hold]);

        $result = $this->repository->findByMatterReference('CASE-001');
        $this->assertCount(1, $result);
    }

    public function testFindByAppliedBy(): void
    {
        $hold = $this->createMock(LegalHoldInterface::class);

        $this->repository
            ->method('findByAppliedBy')
            ->with($this->createUserId('legal'))
            ->willReturn([$hold]);

        $result = $this->repository->findByAppliedBy($this->createUserId('legal'));
        $this->assertCount(1, $result);
    }

    public function testSave(): void
    {
        $hold = $this->createMock(LegalHoldInterface::class);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($hold)
            ->willReturn($hold);

        $result = $this->repository->save($hold);
        $this->assertInstanceOf(LegalHoldInterface::class, $result);
    }

    public function testCreate(): void
    {
        $hold = $this->createMock(LegalHoldInterface::class);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($hold);

        $result = $this->repository->create([
            'document_id' => 'doc-001',
            'reason' => 'Pending litigation',
        ]);

        $this->assertInstanceOf(LegalHoldInterface::class, $result);
    }

    public function testFindExpiringBetween(): void
    {
        $start = $this->createDate();
        $end = $this->createFutureDate(30);

        $hold = $this->createMock(LegalHoldInterface::class);
        $hold->method('getExpiresAt')->willReturn($this->createFutureDate(15));

        $this->repository
            ->method('findExpiringBetween')
            ->with($start, $end)
            ->willReturn([$hold]);

        $result = $this->repository->findExpiringBetween($start, $end);
        $this->assertCount(1, $result);
    }

    public function testCountActive(): void
    {
        $this->repository
            ->method('countActive')
            ->willReturn(5);

        $count = $this->repository->countActive();
        $this->assertEquals(5, $count);
    }

    public function testGetDocumentIdsWithActiveHolds(): void
    {
        $this->repository
            ->method('getDocumentIdsWithActiveHolds')
            ->willReturn(['doc-001', 'doc-003', 'doc-007']);

        $result = $this->repository->getDocumentIdsWithActiveHolds();
        $this->assertCount(3, $result);
        $this->assertContains('doc-001', $result);
    }
}
