<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorRatingData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorRatingData::class)]
final class VendorRatingDataTest extends TestCase
{
    #[Test]
    public function it_creates_rating_with_all_scores(): void
    {
        $rating = new VendorRatingData(
            qualityScore: 85.0,
            deliveryScore: 90.0,
            priceScore: 75.0,
            serviceScore: 88.0,
            complianceScore: 95.0,
            totalDeliveries: 100,
            onTimeDeliveries: 90,
            totalOrders: 50,
            qualityRejections: 2,
            lastEvaluationDate: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertSame(85.0, $rating->qualityScore);
        $this->assertSame(90.0, $rating->deliveryScore);
        $this->assertSame(75.0, $rating->priceScore);
        $this->assertSame(88.0, $rating->serviceScore);
        $this->assertSame(95.0, $rating->complianceScore);
        $this->assertSame(100, $rating->totalDeliveries);
        $this->assertSame(90, $rating->onTimeDeliveries);
    }

    #[Test]
    public function it_creates_excellent_rating(): void
    {
        $rating = VendorRatingData::excellent();

        $this->assertSame(95.0, $rating->qualityScore);
        $this->assertSame(95.0, $rating->deliveryScore);
        $this->assertSame(90.0, $rating->priceScore);
        $this->assertSame(95.0, $rating->serviceScore);
        $this->assertSame(100.0, $rating->complianceScore);
    }

    #[Test]
    public function it_creates_good_rating(): void
    {
        $rating = VendorRatingData::good();

        $this->assertSame(80.0, $rating->qualityScore);
        $this->assertSame(85.0, $rating->deliveryScore);
        $this->assertSame(75.0, $rating->priceScore);
        $this->assertSame(80.0, $rating->serviceScore);
        $this->assertSame(90.0, $rating->complianceScore);
    }

    #[Test]
    public function it_creates_average_rating(): void
    {
        $rating = VendorRatingData::average();

        $this->assertSame(70.0, $rating->qualityScore);
        $this->assertSame(70.0, $rating->deliveryScore);
        $this->assertSame(65.0, $rating->priceScore);
        $this->assertSame(70.0, $rating->serviceScore);
        $this->assertSame(75.0, $rating->complianceScore);
    }

    #[Test]
    public function it_creates_new_vendor_rating(): void
    {
        $rating = VendorRatingData::newVendor();

        $this->assertSame(0.0, $rating->qualityScore);
        $this->assertSame(0.0, $rating->deliveryScore);
        $this->assertSame(0.0, $rating->priceScore);
        $this->assertSame(0.0, $rating->serviceScore);
        $this->assertSame(0.0, $rating->complianceScore);
        $this->assertSame(0, $rating->totalDeliveries);
        $this->assertSame(0, $rating->totalOrders);
    }

    #[Test]
    public function it_calculates_overall_score_with_weights(): void
    {
        $rating = new VendorRatingData(
            qualityScore: 80.0,     // 30% weight
            deliveryScore: 90.0,    // 25% weight
            priceScore: 70.0,       // 20% weight
            serviceScore: 85.0,     // 15% weight
            complianceScore: 100.0, // 10% weight
        );

        $overall = $rating->getOverallScore();

        // Expected: (80*0.3) + (90*0.25) + (70*0.2) + (85*0.15) + (100*0.1)
        // = 24 + 22.5 + 14 + 12.75 + 10 = 83.25
        $this->assertSame(83.25, $overall);
    }

    #[Test]
    public function it_determines_grade_from_score(): void
    {
        $excellent = VendorRatingData::excellent();
        $good = VendorRatingData::good();
        $average = VendorRatingData::average();

        $this->assertSame('A', $excellent->getGrade());
        $this->assertSame('B', $good->getGrade());
        $this->assertSame('C', $average->getGrade());
    }

    #[Test]
    public function it_calculates_on_time_delivery_rate(): void
    {
        $rating = new VendorRatingData(
            qualityScore: 80.0,
            deliveryScore: 90.0,
            priceScore: 75.0,
            serviceScore: 85.0,
            complianceScore: 95.0,
            totalDeliveries: 100,
            onTimeDeliveries: 85,
        );

        $this->assertSame(85.0, $rating->getOnTimeDeliveryRate());
    }

    #[Test]
    public function it_handles_zero_deliveries(): void
    {
        $rating = VendorRatingData::newVendor();

        $this->assertSame(0.0, $rating->getOnTimeDeliveryRate());
    }

    #[Test]
    public function it_calculates_quality_acceptance_rate(): void
    {
        $rating = new VendorRatingData(
            qualityScore: 80.0,
            deliveryScore: 90.0,
            priceScore: 75.0,
            serviceScore: 85.0,
            complianceScore: 95.0,
            totalOrders: 100,
            qualityRejections: 5,
        );

        // 95% acceptance rate (100 - 5 = 95)
        $this->assertSame(95.0, $rating->getQualityAcceptanceRate());
    }

    #[Test]
    public function it_checks_if_preferred_vendor(): void
    {
        $excellent = VendorRatingData::excellent();
        $average = VendorRatingData::average();

        $this->assertTrue($excellent->isPreferredVendor());
        $this->assertFalse($average->isPreferredVendor());
    }

    #[Test]
    public function it_checks_if_at_risk(): void
    {
        $poor = new VendorRatingData(
            qualityScore: 50.0,
            deliveryScore: 55.0,
            priceScore: 60.0,
            serviceScore: 45.0,
            complianceScore: 40.0,
        );

        $good = VendorRatingData::good();

        $this->assertTrue($poor->isAtRisk());
        $this->assertFalse($good->isAtRisk());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $rating = VendorRatingData::good();

        $array = $rating->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('quality_score', $array);
        $this->assertArrayHasKey('delivery_score', $array);
        $this->assertArrayHasKey('price_score', $array);
        $this->assertArrayHasKey('service_score', $array);
        $this->assertArrayHasKey('compliance_score', $array);
        $this->assertArrayHasKey('overall_score', $array);
        $this->assertArrayHasKey('grade', $array);
    }
}
