<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Entities\AssetDepreciation;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;

/**
 * Test cases for AssetDepreciation entity.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Entities
 */
final class AssetDepreciationTest extends TestCase
{
    private AssetDepreciation $assetDepreciation;

    protected function setUp(): void
    {
        $this->assetDepreciation = new AssetDepreciation(
            id: 'depr_123',
            assetId: 'asset_456',
            scheduleId: 'sch_789',
            periodId: 'period_001',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationAmount: new DepreciationAmount(
                amount: 1000.00,
                currency: 'USD',
                accumulatedDepreciation: 3000.00
            ),
            bookValueBefore: new BookValue(
                cost: 10000.00,
                salvageValue: 1000.00,
                accumulatedDepreciation: 2000.00
            ),
            bookValueAfter: new BookValue(
                cost: 10000.00,
                salvageValue: 1000.00,
                accumulatedDepreciation: 3000.00
            ),
            calculationDate: new \DateTimeImmutable('2024-01-31'),
            postingDate: null,
            status: DepreciationStatus::CALCULATED
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        $this->assertEquals('depr_123', $this->assetDepreciation->id);
        $this->assertEquals('asset_456', $this->assetDepreciation->assetId);
        $this->assertEquals('sch_789', $this->assetDepreciation->scheduleId);
        $this->assertEquals('period_001', $this->assetDepreciation->periodId);
        $this->assertEquals(DepreciationMethodType::STRAIGHT_LINE, $this->assetDepreciation->methodType);
        $this->assertEquals(DepreciationType::BOOK, $this->assetDepreciation->depreciationType);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isPosted_withCalculatedStatus_returnsFalse(): void
    {
        $this->assertFalse($this->assetDepreciation->isPosted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isPosted_withPostedStatus_returnsTrue(): void
    {
        $postedDepreciation = $this->createPostedDepreciation();
        $this->assertTrue($postedDepreciation->isPosted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canBeReversed_withPostedStatus_returnsTrue(): void
    {
        $postedDepreciation = $this->createPostedDepreciation();
        $this->assertTrue($postedDepreciation->canBeReversed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canBeReversed_withCalculatedStatus_returnsFalse(): void
    {
        $this->assertFalse($this->assetDepreciation->canBeReversed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canBeReversed_withReversedStatus_returnsFalse(): void
    {
        $reversedDepreciation = new AssetDepreciation(
            id: 'depr_123',
            assetId: 'asset_456',
            scheduleId: 'sch_789',
            periodId: 'period_001',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationAmount: new DepreciationAmount(
                amount: 1000.00,
                currency: 'USD',
                accumulatedDepreciation: 3000.00
            ),
            bookValueBefore: new BookValue(
                cost: 10000.00,
                salvageValue: 1000.00,
                accumulatedDepreciation: 2000.00
            ),
            bookValueAfter: new BookValue(
                cost: 10000.00,
                salvageValue: 1000.00,
                accumulatedDepreciation: 3000.00
            ),
            calculationDate: new \DateTimeImmutable('2024-01-31'),
            postingDate: new \DateTimeImmutable('2024-01-31'),
            status: DepreciationStatus::REVERSED
        );
        
        $this->assertFalse($reversedDepreciation->canBeReversed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getNetBookValue_returnsCorrectValue(): void
    {
        // bookValueAfter has accumulatedDepreciation of 3000, cost 10000, salvage 1000
        // Net book value = 10000 - 3000 = 7000
        $this->assertEquals(7000.00, $this->assetDepreciation->getNetBookValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationAmount_returnsCorrectAmount(): void
    {
        $this->assertEquals(1000.00, $this->assetDepreciation->getDepreciationAmount());
    }

    private function createPostedDepreciation(): AssetDepreciation
    {
        return new AssetDepreciation(
            id: 'depr_posted',
            assetId: 'asset_456',
            scheduleId: 'sch_789',
            periodId: 'period_001',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationAmount: new DepreciationAmount(
                amount: 1000.00,
                currency: 'USD',
                accumulatedDepreciation: 3000.00
            ),
            bookValueBefore: new BookValue(
                cost: 10000.00,
                salvageValue: 1000.00,
                accumulatedDepreciation: 2000.00
            ),
            bookValueAfter: new BookValue(
                cost: 10000.00,
                salvageValue: 1000.00,
                accumulatedDepreciation: 3000.00
            ),
            calculationDate: new \DateTimeImmutable('2024-01-31'),
            postingDate: new \DateTimeImmutable('2024-02-01'),
            status: DepreciationStatus::POSTED
        );
    }
}
