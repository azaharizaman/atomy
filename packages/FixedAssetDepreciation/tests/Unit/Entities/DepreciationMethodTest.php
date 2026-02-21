<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Entities;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Entities\DepreciationMethod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use PHPUnit\Framework\TestCase;

final class DepreciationMethodTest extends TestCase
{
    public function testConstructorInitializesAllProperties(): void
    {
        $id = 'method_001';
        $tenantId = 'tenant_001';
        $methodType = DepreciationMethodType::STRAIGHT_LINE;
        $name = 'Straight Line Method';
        $description = 'Standard straight line depreciation';
        $parameters = ['useful_life' => 5, 'salvage_value' => 1000];
        $tierLevel = 1;
        $isActive = true;
        $isDefault = true;
        $createdAt = new DateTimeImmutable('2024-01-01');
        $updatedAt = new DateTimeImmutable('2024-01-15');

        $method = new DepreciationMethod(
            id: $id,
            tenantId: $tenantId,
            methodType: $methodType,
            name: $name,
            description: $description,
            parameters: $parameters,
            tierLevel: $tierLevel,
            isActive: $isActive,
            isDefault: $isDefault,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        self::assertSame($id, $method->id);
        self::assertSame($tenantId, $method->tenantId);
        self::assertSame($methodType, $method->methodType);
        self::assertSame($name, $method->name);
        self::assertSame($description, $method->description);
        self::assertSame($parameters, $method->parameters);
        self::assertSame($tierLevel, $method->tierLevel);
        self::assertSame($isActive, $method->isActive);
        self::assertSame($isDefault, $method->isDefault);
        self::assertSame($createdAt, $method->createdAt);
        self::assertSame($updatedAt, $method->updatedAt);
    }

    public function testConstructorWithOptionalParameters(): void
    {
        $method = new DepreciationMethod(
            id: 'method_002',
            tenantId: 'tenant_002',
            methodType: DepreciationMethodType::DOUBLE_DECLINING,
            name: 'Double Declining',
            description: 'Double declining balance',
            parameters: ['factor' => 2.0],
            tierLevel: 2,
        );

        self::assertTrue($method->isActive);
        self::assertFalse($method->isDefault);
        self::assertNull($method->createdAt);
        self::assertNull($method->updatedAt);
    }

    public function testGetMethodType(): void
    {
        $method = new DepreciationMethod(
            id: 'method_003',
            tenantId: 'tenant_003',
            methodType: DepreciationMethodType::SUM_OF_YEARS,
            name: 'Sum of Years',
            description: 'Sum of years digits method',
            parameters: [],
            tierLevel: 1,
        );

        self::assertSame(DepreciationMethodType::SUM_OF_YEARS, $method->getMethodType());
    }

    public function testGetName(): void
    {
        $method = new DepreciationMethod(
            id: 'method_004',
            tenantId: 'tenant_004',
            methodType: DepreciationMethodType::UNITS_OF_PRODUCTION,
            name: 'Units of Production',
            description: 'Units produced method',
            parameters: [],
            tierLevel: 1,
        );

        self::assertSame('Units of Production', $method->getName());
    }

    public function testGetParameterWithExistingKey(): void
    {
        $method = new DepreciationMethod(
            id: 'method_005',
            tenantId: 'tenant_005',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            name: 'Test',
            description: 'Test',
            parameters: ['factor' => 1.5, 'useful_life' => 10],
            tierLevel: 1,
        );

        self::assertSame(1.5, $method->getParameter('factor'));
        self::assertSame(10, $method->getParameter('useful_life'));
    }

    public function testGetParameterWithNonExistingKeyReturnsDefault(): void
    {
        $method = new DepreciationMethod(
            id: 'method_006',
            tenantId: 'tenant_006',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            name: 'Test',
            description: 'Test',
            parameters: [],
            tierLevel: 1,
        );

        self::assertNull($method->getParameter('non_existent'));
        self::assertSame('default_value', $method->getParameter('non_existent', 'default_value'));
    }

    public function testIsAcceleratedWithAcceleratedMethod(): void
    {
        $method = new DepreciationMethod(
            id: 'method_007',
            tenantId: 'tenant_007',
            methodType: DepreciationMethodType::DOUBLE_DECLINING,
            name: 'Double Declining',
            description: 'Accelerated method',
            parameters: [],
            tierLevel: 1,
        );

        self::assertTrue($method->isAccelerated());
    }

    public function testIsAcceleratedWithStraightLineMethod(): void
    {
        $method = new DepreciationMethod(
            id: 'method_008',
            tenantId: 'tenant_008',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            name: 'Straight Line',
            description: 'Non-accelerated method',
            parameters: [],
            tierLevel: 1,
        );

        self::assertFalse($method->isAccelerated());
    }

    public function testRequiresUnitTrackingWithUnitsOfProduction(): void
    {
        $method = new DepreciationMethod(
            id: 'method_009',
            tenantId: 'tenant_009',
            methodType: DepreciationMethodType::UNITS_OF_PRODUCTION,
            name: 'Units of Production',
            description: 'Unit tracking required',
            parameters: [],
            tierLevel: 1,
        );

        self::assertTrue($method->requiresUnitTracking());
    }

    public function testRequiresUnitTrackingWithOtherMethods(): void
    {
        $method = new DepreciationMethod(
            id: 'method_010',
            tenantId: 'tenant_010',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            name: 'Straight Line',
            description: 'No unit tracking',
            parameters: [],
            tierLevel: 1,
        );

        self::assertFalse($method->requiresUnitTracking());
    }

    public function testGetDecliningFactorWithDoubleDeclining(): void
    {
        $method = new DepreciationMethod(
            id: 'method_011',
            tenantId: 'tenant_011',
            methodType: DepreciationMethodType::DOUBLE_DECLINING,
            name: 'Double Declining',
            description: 'Test',
            parameters: [],
            tierLevel: 1,
        );

        // Default factor for double declining is 2.0
        self::assertSame(2.0, $method->getDecliningFactor());
    }

    public function testGetDecliningFactorWithDoubleDecliningCustomFactor(): void
    {
        $method = new DepreciationMethod(
            id: 'method_012',
            tenantId: 'tenant_012',
            methodType: DepreciationMethodType::DOUBLE_DECLINING,
            name: 'Double Declining',
            description: 'Test',
            parameters: ['factor' => 3.0],
            tierLevel: 1,
        );

        self::assertSame(3.0, $method->getDecliningFactor());
    }

    public function testGetDecliningFactorWithDeclining150(): void
    {
        $method = new DepreciationMethod(
            id: 'method_013',
            tenantId: 'tenant_013',
            methodType: DepreciationMethodType::DECLINING_150,
            name: 'Declining 150%',
            description: 'Test',
            parameters: [],
            tierLevel: 1,
        );

        // Default factor for declining 150 is 1.5
        self::assertSame(1.5, $method->getDecliningFactor());
    }

    public function testGetDecliningFactorWithDeclining150CustomFactor(): void
    {
        $method = new DepreciationMethod(
            id: 'method_014',
            tenantId: 'tenant_014',
            methodType: DepreciationMethodType::DECLINING_150,
            name: 'Declining 150%',
            description: 'Test',
            parameters: ['factor' => 1.75],
            tierLevel: 1,
        );

        self::assertSame(1.75, $method->getDecliningFactor());
    }

    public function testGetDecliningFactorWithNonDecliningMethod(): void
    {
        $method = new DepreciationMethod(
            id: 'method_015',
            tenantId: 'tenant_015',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            name: 'Straight Line',
            description: 'Test',
            parameters: [],
            tierLevel: 1,
        );

        // Non-declining methods return 1.0
        self::assertSame(1.0, $method->getDecliningFactor());
    }

    public function testToArray(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2024-01-15 14:30:00');

        $method = new DepreciationMethod(
            id: 'method_016',
            tenantId: 'tenant_016',
            methodType: DepreciationMethodType::DOUBLE_DECLINING,
            name: 'Double Declining Balance',
            description: 'Accelerated depreciation method',
            parameters: ['factor' => 2.0],
            tierLevel: 2,
            isActive: true,
            isDefault: false,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $array = $method->toArray();

        self::assertSame('method_016', $array['id']);
        self::assertSame('tenant_016', $array['tenant_id']);
        self::assertSame('double_declining', $array['method_type']);
        self::assertSame('Double Declining Balance', $array['name']);
        self::assertSame('Accelerated depreciation method', $array['description']);
        self::assertSame(['factor' => 2.0], $array['parameters']);
        self::assertSame(2, $array['tier_level']);
        self::assertTrue($array['is_active']);
        self::assertFalse($array['is_default']);
        self::assertTrue($array['is_accelerated']);
        self::assertFalse($array['requires_unit_tracking']);
        self::assertSame('2024-01-01 10:00:00', $array['created_at']);
        self::assertSame('2024-01-15 14:30:00', $array['updated_at']);
    }

    public function testToArrayWithNullTimestamps(): void
    {
        $method = new DepreciationMethod(
            id: 'method_017',
            tenantId: 'tenant_017',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            name: 'Straight Line',
            description: 'Test',
            parameters: [],
            tierLevel: 1,
        );

        $array = $method->toArray();

        self::assertNull($array['created_at']);
        self::assertNull($array['updated_at']);
    }

    public function testJsonSerialize(): void
    {
        $method = new DepreciationMethod(
            id: 'method_018',
            tenantId: 'tenant_018',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            name: 'Straight Line',
            description: 'Test',
            parameters: [],
            tierLevel: 1,
        );

        $json = $method->jsonSerialize();

        self::assertIsArray($json);
        self::assertSame('method_018', $json['id']);
    }
}
