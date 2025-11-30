<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Services;

use Nexus\Manufacturing\Contracts\BomInterface;
use Nexus\Manufacturing\Contracts\BomRepositoryInterface;
use Nexus\Manufacturing\Enums\BomType;
use Nexus\Manufacturing\Exceptions\BomNotFoundException;
use Nexus\Manufacturing\Exceptions\CircularBomException;
use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\Tests\TestCase;
use Nexus\Manufacturing\ValueObjects\BomLine;
use PHPUnit\Framework\MockObject\MockObject;

final class BomManagerTest extends TestCase
{
    private BomRepositoryInterface&MockObject $repository;
    private BomManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(BomRepositoryInterface::class);

        $this->manager = new BomManager(
            $this->repository,
        );
    }

    public function testCreateBom(): void
    {
        $productId = 'prod-001';
        $version = '1.0';
        $type = BomType::MANUFACTURING->value;
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');

        $bom = $this->createMock(BomInterface::class);
        $bom->method('getId')->willReturn('bom-001');
        $bom->method('getProductId')->willReturn($productId);
        $bom->method('getVersion')->willReturn(1);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($bom);

        $result = $this->manager->create(
            $productId,
            $version,
            $type,
            [],
            $effectiveFrom
        );

        $this->assertSame($bom, $result);
    }

    public function testGetByIdReturnsExistingBom(): void
    {
        $bom = $this->createMock(BomInterface::class);
        $bom->method('getId')->willReturn('bom-001');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('bom-001')
            ->willReturn($bom);

        $result = $this->manager->getById('bom-001');

        $this->assertSame($bom, $result);
    }

    public function testGetByIdThrowsExceptionWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('non-existent')
            ->willThrowException(BomNotFoundException::withId('non-existent'));

        $this->expectException(BomNotFoundException::class);

        $this->manager->getById('non-existent');
    }

    public function testGetEffectiveForDate(): void
    {
        $date = new \DateTimeImmutable('2024-06-15');
        $bom = $this->createMock(BomInterface::class);
        $bom->method('getProductId')->willReturn('prod-001');

        $this->repository
            ->expects($this->once())
            ->method('findByProductId')
            ->with('prod-001', $date)
            ->willReturn($bom);

        $result = $this->manager->getEffective('prod-001', $date);

        $this->assertSame($bom, $result);
    }

    public function testGetEffectiveThrowsWhenNotFound(): void
    {
        $date = new \DateTimeImmutable('2024-06-15');

        $this->repository
            ->expects($this->once())
            ->method('findByProductId')
            ->with('prod-001', $date)
            ->willReturn(null);

        $this->expectException(BomNotFoundException::class);

        $this->manager->getEffective('prod-001', $date);
    }

    public function testCreateNewVersion(): void
    {
        $originalBom = $this->createMock(BomInterface::class);
        $originalBom->method('getId')->willReturn('bom-001');
        $originalBom->method('getProductId')->willReturn('prod-001');
        $originalBom->method('getVersion')->willReturn(1);
        $originalBom->method('getLines')->willReturn([]);
        $originalBom->method('getType')->willReturn('manufacturing');

        $newBom = $this->createMock(BomInterface::class);
        $newBom->method('getId')->willReturn('bom-002');
        $newBom->method('getVersion')->willReturn(2);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('bom-001')
            ->willReturn($originalBom);

        $this->repository
            ->expects($this->once())
            ->method('findAllVersions')
            ->with('prod-001')
            ->willReturn([$originalBom]);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($newBom);

        $result = $this->manager->createVersion(
            'bom-001',
            '2.0',
            new \DateTimeImmutable('2024-07-01')
        );

        $this->assertSame($newBom, $result);
    }

    public function testAddLine(): void
    {
        $bom = $this->createMock(BomInterface::class);
        $bom->method('getId')->willReturn('bom-001');
        $bom->method('getProductId')->willReturn('prod-001');
        $bom->method('getLines')->willReturn([]);
        $bom->method('getStatus')->willReturn('draft');

        $line = new BomLine(
            productId: 'comp-001',
            quantity: 2.0,
            uomCode: 'EA',
            lineNumber: 10,
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('bom-001')
            ->willReturn($bom);

        $this->repository
            ->expects($this->once())
            ->method('findByProductId')
            ->with('comp-001')
            ->willReturn(null); // No circular dependency

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('bom-001', $this->anything());

        $this->manager->addLine('bom-001', $line);
    }

    public function testExplodeBom(): void
    {
        $line1 = new BomLine(
            productId: 'comp-001',
            quantity: 2.0,
            uomCode: 'EA',
            lineNumber: 10,
        );
        $line2 = new BomLine(
            productId: 'comp-002',
            quantity: 1.0,
            uomCode: 'EA',
            lineNumber: 20,
        );

        $bom = $this->createMockBom('bom-001', 'prod-001', [$line1, $line2]);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('bom-001')
            ->willReturn($bom);

        // comp-001 and comp-002 have no BOMs (raw materials)
        $this->repository
            ->method('findByProductId')
            ->willReturn(null);

        $result = $this->manager->explode('bom-001', 10.0);

        $this->assertCount(2, $result);
        $this->assertSame('comp-001', $result[0]['productId']);
        $this->assertSame(20.0, $result[0]['quantity']); // 2 * 10
        $this->assertSame('comp-002', $result[1]['productId']);
        $this->assertSame(10.0, $result[1]['quantity']); // 1 * 10
    }

    public function testValidateBom(): void
    {
        $bom = $this->createMockBom('bom-001', 'prod-001', []);

        $this->repository
            ->method('findById')
            ->with('bom-001')
            ->willReturn($bom);

        $errors = $this->manager->validate('bom-001');

        $this->assertNotEmpty($errors);
        $this->assertContains('BOM has no components', $errors);
    }

    public function testReleaseBom(): void
    {
        $line = new BomLine(
            productId: 'comp-001',
            quantity: 1.0,
            uomCode: 'EA',
            lineNumber: 10,
        );

        $bom = $this->createMockBom('bom-001', 'prod-001', [$line]);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('bom-001')
            ->willReturn($bom);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('bom-001', ['status' => 'released']);

        $this->manager->release('bom-001');
    }

    public function testObsoleteBom(): void
    {
        $bom = $this->createMockBom('bom-001', 'prod-001', []);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('bom-001')
            ->willReturn($bom);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('bom-001', ['status' => 'obsolete']);

        $this->manager->obsolete('bom-001');
    }

    /**
     * Create a mock BOM with specified lines.
     *
     * @param array<BomLine> $lines
     */
    private function createMockBom(string $id, string $productId, array $lines): BomInterface&MockObject
    {
        $bom = $this->createMock(BomInterface::class);
        $bom->method('getId')->willReturn($id);
        $bom->method('getProductId')->willReturn($productId);
        $bom->method('getLines')->willReturn($lines);
        $bom->method('getVersion')->willReturn(1);
        $bom->method('getType')->willReturn('manufacturing');
        $bom->method('getStatus')->willReturn('draft');

        return $bom;
    }
}
