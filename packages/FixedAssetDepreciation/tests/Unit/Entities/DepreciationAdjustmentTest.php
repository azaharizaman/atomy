<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Test\Unit\Entities;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Entities\DepreciationAdjustment;
use PHPUnit\Framework\TestCase;

class DepreciationAdjustmentTest extends TestCase
{
    public function testConstructorInitializesAllProperties(): void
    {
        $id = 'adj_001';
        $scheduleId = 'sch_001';
        $assetId = 'asset_001';
        $tenantId = 'tenant_001';
        $adjustmentType = 'useful_life_change';
        $previousValues = ['useful_life' => 5, 'salvage_value' => 1000];
        $newValues = ['useful_life' => 7, 'salvage_value' => 1500];
        $remainingDepreciationBefore = 5000.0;
        $remainingDepreciationAfter = 3500.0;
        $reason = 'Extended useful life due to maintenance';
        $adjustmentDate = new DateTimeImmutable('2024-01-15');
        $adjustedBy = 'user_001';
        $approvedBy = 'manager_001';
        $approvedAt = new DateTimeImmutable('2024-01-16');

        $adjustment = new DepreciationAdjustment(
            id: $id,
            scheduleId: $scheduleId,
            assetId: $assetId,
            tenantId: $tenantId,
            adjustmentType: $adjustmentType,
            previousValues: $previousValues,
            newValues: $newValues,
            remainingDepreciationBefore: $remainingDepreciationBefore,
            remainingDepreciationAfter: $remainingDepreciationAfter,
            reason: $reason,
            adjustmentDate: $adjustmentDate,
            adjustedBy: $adjustedBy,
            approvedBy: $approvedBy,
            approvedAt: $approvedAt,
        );

        self::assertSame($id, $adjustment->id);
        self::assertSame($scheduleId, $adjustment->scheduleId);
        self::assertSame($assetId, $adjustment->assetId);
        self::assertSame($tenantId, $adjustment->tenantId);
        self::assertSame($adjustmentType, $adjustment->adjustmentType);
        self::assertSame($previousValues, $adjustment->previousValues);
        self::assertSame($newValues, $adjustment->newValues);
        self::assertSame($remainingDepreciationBefore, $adjustment->remainingDepreciationBefore);
        self::assertSame($remainingDepreciationAfter, $adjustment->remainingDepreciationAfter);
        self::assertSame($reason, $adjustment->reason);
        self::assertSame($adjustmentDate, $adjustment->adjustmentDate);
        self::assertSame($adjustedBy, $adjustment->adjustedBy);
        self::assertSame($approvedBy, $adjustment->approvedBy);
        self::assertSame($approvedAt, $adjustment->approvedAt);
    }

    public function testConstructorWithOptionalParameters(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_002',
            scheduleId: 'sch_002',
            assetId: 'asset_002',
            tenantId: 'tenant_002',
            adjustmentType: 'method_change',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 3000.0,
            remainingDepreciationAfter: 2800.0,
            reason: 'Changed depreciation method',
            adjustmentDate: new DateTimeImmutable('2024-02-01'),
        );

        self::assertNull($adjustment->adjustedBy);
        self::assertNull($adjustment->approvedBy);
        self::assertNull($adjustment->approvedAt);
    }

    public function testGetScheduleId(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_003',
            scheduleId: 'sch_003',
            assetId: 'asset_003',
            tenantId: 'tenant_003',
            adjustmentType: 'salvage_change',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 1000.0,
            remainingDepreciationAfter: 900.0,
            reason: 'Updated salvage value',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertSame('sch_003', $adjustment->getScheduleId());
    }

    public function testGetAssetId(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_004',
            scheduleId: 'sch_004',
            assetId: 'asset_004',
            tenantId: 'tenant_004',
            adjustmentType: 'other',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 2000.0,
            remainingDepreciationAfter: 1800.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertSame('asset_004', $adjustment->getAssetId());
    }

    public function testGetAdjustmentType(): void
    {
        $adjustmentType = 'useful_life_change';
        $adjustment = new DepreciationAdjustment(
            id: 'adj_005',
            scheduleId: 'sch_005',
            assetId: 'asset_005',
            tenantId: 'tenant_005',
            adjustmentType: $adjustmentType,
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 1500.0,
            remainingDepreciationAfter: 1400.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertSame($adjustmentType, $adjustment->getAdjustmentType());
    }

    public function testGetDepreciationDelta(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_006',
            scheduleId: 'sch_006',
            assetId: 'asset_006',
            tenantId: 'tenant_006',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 5000.0,
            remainingDepreciationAfter: 3500.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertSame(-1500.0, $adjustment->getDepreciationDelta());
    }

    public function testGetDepreciationDeltaWithPositiveChange(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_007',
            scheduleId: 'sch_007',
            assetId: 'asset_007',
            tenantId: 'tenant_007',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 3000.0,
            remainingDepreciationAfter: 4500.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertSame(1500.0, $adjustment->getDepreciationDelta());
    }

    public function testHasSignificantImpactWithSignificantImpact(): void
    {
        // 5% of 5000 is 250, delta is 1500 which is > 250
        $adjustment = new DepreciationAdjustment(
            id: 'adj_008',
            scheduleId: 'sch_008',
            assetId: 'asset_008',
            tenantId: 'tenant_008',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 5000.0,
            remainingDepreciationAfter: 3500.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertTrue($adjustment->hasSignificantImpact());
    }

    public function testHasSignificantImpactWithoutSignificantImpact(): void
    {
        // 5% of 10000 is 500, delta is 100 which is < 500
        $adjustment = new DepreciationAdjustment(
            id: 'adj_009',
            scheduleId: 'sch_009',
            assetId: 'asset_009',
            tenantId: 'tenant_009',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 10000.0,
            remainingDepreciationAfter: 9900.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertFalse($adjustment->hasSignificantImpact());
    }

    public function testHasSignificantImpactWithZeroBeforeValue(): void
    {
        // When remainingDepreciationBefore is 0, abs(100) > 0 is true
        $adjustment = new DepreciationAdjustment(
            id: 'adj_010',
            scheduleId: 'sch_010',
            assetId: 'asset_010',
            tenantId: 'tenant_010',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 0.0,
            remainingDepreciationAfter: 100.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertTrue($adjustment->hasSignificantImpact());
    }

    public function testIsApprovedWhenApproved(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_011',
            scheduleId: 'sch_011',
            assetId: 'asset_011',
            tenantId: 'tenant_011',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 1000.0,
            remainingDepreciationAfter: 900.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
            approvedBy: 'manager_001',
        );

        self::assertTrue($adjustment->isApproved());
    }

    public function testIsApprovedWhenNotApproved(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_012',
            scheduleId: 'sch_012',
            assetId: 'asset_012',
            tenantId: 'tenant_012',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 1000.0,
            remainingDepreciationAfter: 900.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        self::assertFalse($adjustment->isApproved());
    }

    public function testToArray(): void
    {
        $adjustmentDate = new DateTimeImmutable('2024-03-15 10:30:00');
        $adjustment = new DepreciationAdjustment(
            id: 'adj_013',
            scheduleId: 'sch_013',
            assetId: 'asset_013',
            tenantId: 'tenant_013',
            adjustmentType: 'useful_life_change',
            previousValues: ['useful_life' => 5],
            newValues: ['useful_life' => 7],
            remainingDepreciationBefore: 5000.0,
            remainingDepreciationAfter: 3500.0,
            reason: 'Extended useful life',
            adjustmentDate: $adjustmentDate,
            adjustedBy: 'user_001',
            approvedBy: 'manager_001',
            approvedAt: new DateTimeImmutable('2024-03-16 09:00:00'),
        );

        $array = $adjustment->toArray();

        self::assertSame('adj_013', $array['id']);
        self::assertSame('sch_013', $array['schedule_id']);
        self::assertSame('asset_013', $array['asset_id']);
        self::assertSame('tenant_013', $array['tenant_id']);
        self::assertSame('useful_life_change', $array['adjustment_type']);
        self::assertSame(['useful_life' => 5], $array['previous_values']);
        self::assertSame(['useful_life' => 7], $array['new_values']);
        self::assertSame(5000.0, $array['remaining_depreciation_before']);
        self::assertSame(3500.0, $array['remaining_depreciation_after']);
        self::assertSame(-1500.0, $array['depreciation_delta']);
        self::assertSame('Extended useful life', $array['reason']);
        self::assertSame('2024-03-15 10:30:00', $array['adjustment_date']);
        self::assertSame('user_001', $array['adjusted_by']);
        self::assertSame('manager_001', $array['approved_by']);
        self::assertSame('2024-03-16 09:00:00', $array['approved_at']);
        self::assertTrue($array['has_significant_impact']);
    }

    public function testToArrayWithNullOptionalFields(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_014',
            scheduleId: 'sch_014',
            assetId: 'asset_014',
            tenantId: 'tenant_014',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 1000.0,
            remainingDepreciationAfter: 950.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable('2024-04-01'),
        );

        $array = $adjustment->toArray();

        self::assertNull($array['adjusted_by']);
        self::assertNull($array['approved_by']);
        self::assertNull($array['approved_at']);
    }

    public function testJsonSerialize(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_015',
            scheduleId: 'sch_015',
            assetId: 'asset_015',
            tenantId: 'tenant_015',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 1000.0,
            remainingDepreciationAfter: 900.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
        );

        $json = $adjustment->jsonSerialize();

        self::assertIsArray($json);
        self::assertSame('adj_015', $json['id']);

        // Test actual JSON encoding
        $encoded = json_encode($adjustment);
        self::assertIsString($encoded);
        $decoded = json_decode($encoded, true);
        self::assertSame('adj_015', $decoded['id']);
    }

    public function testIsApprovedWithOnlyApprovedAtSet(): void
    {
        $adjustment = new DepreciationAdjustment(
            id: 'adj_013',
            scheduleId: 'sch_013',
            assetId: 'asset_013',
            tenantId: 'tenant_013',
            adjustmentType: 'test',
            previousValues: [],
            newValues: [],
            remainingDepreciationBefore: 1000.0,
            remainingDepreciationAfter: 900.0,
            reason: 'Test',
            adjustmentDate: new DateTimeImmutable(),
            approvedAt: new DateTimeImmutable(),
        );

        self::assertFalse($adjustment->isApproved());
    }
}
