<?php

declare(strict_types=1);

namespace Nexus\Document\Tests\Unit\Contracts;

use Nexus\Document\Contracts\DisposalCertificationInterface;
use Nexus\Document\Contracts\DisposalCertificationRepositoryInterface;
use Nexus\Document\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for DisposalCertificationRepositoryInterface contract.
 *
 * These tests verify that the repository interface provides
 * all necessary methods for disposal certification management.
 */
final class DisposalCertificationRepositoryInterfaceTest extends TestCase
{
    private DisposalCertificationRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(DisposalCertificationRepositoryInterface::class);
    }

    public function testFindByIdReturnsCertification(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);
        $certification->method('getId')->willReturn('cert-001');

        $this->repository
            ->method('findById')
            ->with('cert-001')
            ->willReturn($certification);

        $result = $this->repository->findById('cert-001');
        $this->assertInstanceOf(DisposalCertificationInterface::class, $result);
    }

    public function testFindByDocumentIdReturnsCertification(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $this->repository
            ->method('findByDocumentId')
            ->with('doc-001')
            ->willReturn($certification);

        $result = $this->repository->findByDocumentId('doc-001');
        $this->assertInstanceOf(DisposalCertificationInterface::class, $result);
    }

    public function testFindByDateRange(): void
    {
        $start = $this->createPastDate(30);
        $end = $this->createDate();

        $cert1 = $this->createMock(DisposalCertificationInterface::class);
        $cert2 = $this->createMock(DisposalCertificationInterface::class);

        $this->repository
            ->method('findByDateRange')
            ->with($start, $end)
            ->willReturn([$cert1, $cert2]);

        $result = $this->repository->findByDateRange($start, $end);
        $this->assertCount(2, $result);
    }

    public function testFindByDisposalMethod(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);
        $certification->method('getDisposalMethod')->willReturn('SECURE_DELETE');

        $this->repository
            ->method('findByDisposalMethod')
            ->with('SECURE_DELETE')
            ->willReturn([$certification]);

        $result = $this->repository->findByDisposalMethod('SECURE_DELETE');
        $this->assertCount(1, $result);
        $this->assertEquals('SECURE_DELETE', $result[0]->getDisposalMethod());
    }

    public function testFindByDisposedBy(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $this->repository
            ->method('findByDisposedBy')
            ->with($this->createUserId('admin'))
            ->willReturn([$certification]);

        $result = $this->repository->findByDisposedBy($this->createUserId('admin'));
        $this->assertCount(1, $result);
    }

    public function testFindByDocumentType(): void
    {
        $cert1 = $this->createMock(DisposalCertificationInterface::class);
        $cert2 = $this->createMock(DisposalCertificationInterface::class);

        $this->repository
            ->method('findByDocumentType')
            ->with('invoice')
            ->willReturn([$cert1, $cert2]);

        $result = $this->repository->findByDocumentType('invoice');
        $this->assertCount(2, $result);
    }

    public function testFindByRegulatoryBasis(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);
        $certification->method('getRegulatoryBasis')->willReturn('SOX Section 802');

        $this->repository
            ->method('findByRegulatoryBasis')
            ->with('SOX Section 802')
            ->willReturn([$certification]);

        $result = $this->repository->findByRegulatoryBasis('SOX Section 802');
        $this->assertCount(1, $result);
    }

    public function testSave(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($certification)
            ->willReturn($certification);

        $result = $this->repository->save($certification);
        $this->assertInstanceOf(DisposalCertificationInterface::class, $result);
    }

    public function testCreate(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($certification);

        $result = $this->repository->create([
            'document_id' => 'doc-001',
            'disposal_method' => 'SECURE_DELETE',
            'disposed_by' => $this->createUserId('admin'),
        ]);

        $this->assertInstanceOf(DisposalCertificationInterface::class, $result);
    }

    public function testCountByDateRange(): void
    {
        $start = $this->createPastDate(30);
        $end = $this->createDate();

        $this->repository
            ->method('countByDateRange')
            ->with($start, $end)
            ->willReturn(42);

        $count = $this->repository->countByDateRange($start, $end);
        $this->assertEquals(42, $count);
    }

    public function testGetStatistics(): void
    {
        $start = $this->createPastDate(30);
        $end = $this->createDate();

        $this->repository
            ->method('getStatistics')
            ->with($start, $end)
            ->willReturn([
                'total_disposed' => 100,
                'by_method' => [
                    'SECURE_DELETE' => 80,
                    'ARCHIVE' => 15,
                    'ANONYMIZE' => 5,
                ],
                'by_type' => [
                    'invoice' => 60,
                    'contract' => 25,
                    'purchase_order' => 15,
                ],
                'compliant_count' => 98,
            ]);

        $stats = $this->repository->getStatistics($start, $end);

        $this->assertArrayHasKey('total_disposed', $stats);
        $this->assertEquals(100, $stats['total_disposed']);
        $this->assertArrayHasKey('by_method', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertEquals(98, $stats['compliant_count']);
    }

    public function testFindLateDisposals(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $this->repository
            ->method('findLateDisposals')
            ->willReturn([$certification]);

        $result = $this->repository->findLateDisposals();
        $this->assertCount(1, $result);
    }

    public function testHasBeenDisposed(): void
    {
        $this->repository
            ->method('hasBeenDisposed')
            ->willReturnCallback(fn($id) => $id === 'doc-disposed');

        $this->assertTrue($this->repository->hasBeenDisposed('doc-disposed'));
        $this->assertFalse($this->repository->hasBeenDisposed('doc-active'));
    }
}
