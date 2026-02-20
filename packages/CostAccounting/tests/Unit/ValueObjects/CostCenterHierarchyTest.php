<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\ValueObjects;

use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\Enums\CostCenterStatus;
use Nexus\CostAccounting\ValueObjects\CostCenterHierarchy;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CostCenterHierarchy Value Object
 * 
 * @covers \Nexus\CostAccounting\ValueObjects\CostCenterHierarchy
 */
final class CostCenterHierarchyTest extends TestCase
{
    private CostCenter $rootCostCenter;
    private CostCenter $childCostCenter;
    private CostCenter $grandchildCostCenter;

    protected function setUp(): void
    {
        $this->rootCostCenter = new CostCenter(
            id: 'cc_root',
            code: 'CC001',
            name: 'Root Cost Center',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->childCostCenter = new CostCenter(
            id: 'cc_child',
            code: 'CC002',
            name: 'Child Cost Center',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active,
            parentCostCenterId: 'cc_root'
        );

        $this->grandchildCostCenter = new CostCenter(
            id: 'cc_grandchild',
            code: 'CC003',
            name: 'Grandchild Cost Center',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active,
            parentCostCenterId: 'cc_child'
        );
    }

    public function testCreateWithEmptyArrays(): void
    {
        $hierarchy = new CostCenterHierarchy([]);
        
        self::assertEmpty($hierarchy->getCostCenters());
        self::assertEmpty($hierarchy->getRootCostCenters());
    }

    public function testGetRootCostCenters(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter, $this->childCostCenter],
            ['cc_child' => 'cc_root']
        );
        
        $roots = $hierarchy->getRootCostCenters();
        
        self::assertCount(1, $roots);
        self::assertContains($this->rootCostCenter, $roots);
    }

    public function testGetChildren(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter, $this->childCostCenter],
            ['cc_child' => 'cc_root']
        );
        
        $children = $hierarchy->getChildren('cc_root');
        
        self::assertCount(1, $children);
        self::assertContains($this->childCostCenter, $children);
    }

    public function testGetChildrenWhenNoChildren(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter],
            []
        );
        
        $children = $hierarchy->getChildren('cc_root');
        
        self::assertEmpty($children);
    }

    public function testGetParent(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter, $this->childCostCenter],
            ['cc_child' => 'cc_root']
        );
        
        $parent = $hierarchy->getParent('cc_child');
        
        self::assertSame('cc_root', $parent);
    }

    public function testGetParentWhenNoParent(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter],
            []
        );
        
        $parent = $hierarchy->getParent('cc_root');
        
        self::assertNull($parent);
    }

    public function testHasChildren(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter, $this->childCostCenter],
            ['cc_child' => 'cc_root']
        );
        
        self::assertTrue($hierarchy->hasChildren('cc_root'));
        self::assertFalse($hierarchy->hasChildren('cc_child'));
    }

    public function testGetDepth(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [
                $this->rootCostCenter,
                $this->childCostCenter,
                $this->grandchildCostCenter
            ],
            [
                'cc_child' => 'cc_root',
                'cc_grandchild' => 'cc_child'
            ]
        );
        
        self::assertSame(0, $hierarchy->getDepth('cc_root'));
        self::assertSame(1, $hierarchy->getDepth('cc_child'));
        self::assertSame(2, $hierarchy->getDepth('cc_grandchild'));
    }

    public function testGetPath(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [
                $this->rootCostCenter,
                $this->childCostCenter,
                $this->grandchildCostCenter
            ],
            [
                'cc_child' => 'cc_root',
                'cc_grandchild' => 'cc_child'
            ]
        );
        
        $path = $hierarchy->getPath('cc_grandchild');
        
        self::assertSame(['cc_root', 'cc_child', 'cc_grandchild'], $path);
    }

    public function testGetPathForRoot(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter],
            []
        );
        
        $path = $hierarchy->getPath('cc_root');
        
        self::assertSame(['cc_root'], $path);
    }

    public function testGetCostCenters(): void
    {
        $hierarchy = new CostCenterHierarchy(
            [$this->rootCostCenter, $this->childCostCenter],
            ['cc_child' => 'cc_root']
        );
        
        $centers = $hierarchy->getCostCenters();
        
        self::assertCount(2, $centers);
    }
}
