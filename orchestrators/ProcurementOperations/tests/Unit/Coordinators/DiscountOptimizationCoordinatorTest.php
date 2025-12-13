<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\DiscountCalculationServiceInterface;
use Nexus\ProcurementOperations\Coordinators\DiscountOptimizationCoordinator;
use Nexus\ProcurementOperations\DTOs\Financial\DiscountOpportunityData;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use Nexus\ProcurementOperations\DTOs\Financial\RoiAnalysisResult;
use Nexus\ProcurementOperations\Enums\DiscountStatus;
use Nexus\ProcurementOperations\Events\Financial\DiscountOpportunitiesIdentifiedEvent;
use Nexus\ProcurementOperations\Events\Financial\DiscountOptimizationCompletedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(DiscountOptimizationCoordinator::class)]
final class DiscountOptimizationCoordinatorTest extends TestCase
{
    private DiscountCalculationServiceInterface&MockObject $discountService;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private DiscountOptimizationCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->discountService = $this->createMock(DiscountCalculationServiceInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->coordinator = new DiscountOptimizationCoordinator(
            discountService: $this->discountService,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_analyzes_discount_opportunities(): void
    {
        $this->discountService
            ->method('calculateAnnualizedReturn')
            ->willReturn(new RoiAnalysisResult(
                discountId: 'DISC-001',
                discountAmount: Money::of(200, 'USD'),
                annualizedRoi: 36.5,
                effectiveRate: 2.0,
                daysToDiscount: 10,
                netDaysDifference: 20,
                breakEvenRate: 2.5,
                isWorthCapturing: true,
            ));

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(DiscountOpportunitiesIdentifiedEvent::class));

        $eligibleDiscounts = [
            $this->createTestDiscount('DISC-001', 10000.0, 2.0),
        ];

        $result = $this->coordinator->analyzeDiscountOpportunities(
            tenantId: 'TENANT-001',
            options: [
                'eligible_discounts' => $eligibleDiscounts,
                'currency' => 'USD',
            ],
        );

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('opportunities', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('analysis_date', $result);

        $this->assertEquals(1, $result['summary']['total_opportunities']);
        $this->assertGreaterThan(0, $result['summary']['average_roi']);
    }

    #[Test]
    public function it_filters_opportunities_below_roi_threshold(): void
    {
        $this->discountService
            ->method('calculateAnnualizedReturn')
            ->willReturn(new RoiAnalysisResult(
                discountId: 'DISC-001',
                discountAmount: Money::of(50, 'USD'),
                annualizedRoi: 5.0, // Below default 10% threshold
                effectiveRate: 0.5,
                daysToDiscount: 10,
                netDaysDifference: 20,
                breakEvenRate: 1.0,
                isWorthCapturing: false,
            ));

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $result = $this->coordinator->analyzeDiscountOpportunities(
            tenantId: 'TENANT-001',
            options: [
                'eligible_discounts' => [
                    $this->createTestDiscount('DISC-001', 10000.0, 0.5),
                ],
                'min_roi_threshold' => 10.0,
            ],
        );

        $this->assertEquals(0, $result['summary']['total_opportunities']);
        $this->assertCount(0, $result['opportunities']);
    }

    #[Test]
    public function it_optimizes_discount_capture_within_cash_constraints(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(DiscountOptimizationCompletedEvent::class));

        $availableCash = Money::of(50000, 'USD');
        $opportunities = [
            $this->createTestOpportunity('OPP-001', 30000.0, 500.0, 36.5), // ROI 36.5%
            $this->createTestOpportunity('OPP-002', 25000.0, 400.0, 32.0), // ROI 32%
            $this->createTestOpportunity('OPP-003', 15000.0, 200.0, 24.0), // ROI 24%
        ];

        $result = $this->coordinator->optimizeDiscountCapture(
            tenantId: 'TENANT-001',
            availableCash: $availableCash,
            opportunities: $opportunities,
        );

        $this->assertArrayHasKey('selected_opportunities', $result);
        $this->assertArrayHasKey('excluded_opportunities', $result);
        $this->assertArrayHasKey('total_investment', $result);
        $this->assertArrayHasKey('total_savings', $result);
        $this->assertArrayHasKey('portfolio_roi', $result);

        // Should select opportunities until cash is exhausted
        $this->assertGreaterThan(0, count($result['selected_opportunities']));
        $this->assertTrue($result['total_investment']->lessThanOrEqual($availableCash));
    }

    #[Test]
    public function it_excludes_opportunities_below_min_roi(): void
    {
        $availableCash = Money::of(100000, 'USD');
        $opportunities = [
            $this->createTestOpportunity('OPP-001', 10000.0, 100.0, 5.0), // Below 10% threshold
        ];

        $result = $this->coordinator->optimizeDiscountCapture(
            tenantId: 'TENANT-001',
            availableCash: $availableCash,
            opportunities: $opportunities,
            constraints: ['min_roi' => 10.0],
        );

        $this->assertCount(0, $result['selected_opportunities']);
        $this->assertCount(1, $result['excluded_opportunities']);
        $this->assertArrayHasKey('OPP-001', $result['exclusion_reasons']);
        $this->assertStringContainsString('ROI below threshold', $result['exclusion_reasons']['OPP-001']);
    }

    #[Test]
    public function it_respects_vendor_exclusion_list(): void
    {
        $availableCash = Money::of(100000, 'USD');
        $opportunities = [
            $this->createTestOpportunity('OPP-001', 10000.0, 300.0, 36.0, 'VENDOR-EXCLUDED'),
        ];

        $result = $this->coordinator->optimizeDiscountCapture(
            tenantId: 'TENANT-001',
            availableCash: $availableCash,
            opportunities: $opportunities,
            constraints: ['excluded_vendors' => ['VENDOR-EXCLUDED']],
        );

        $this->assertCount(0, $result['selected_opportunities']);
        $this->assertStringContainsString('excluded', $result['exclusion_reasons']['OPP-001']);
    }

    #[Test]
    public function it_limits_maximum_opportunities_selected(): void
    {
        $availableCash = Money::of(1000000, 'USD');
        $opportunities = [];
        for ($i = 1; $i <= 10; $i++) {
            $opportunities[] = $this->createTestOpportunity("OPP-{$i}", 10000.0, 300.0, 30.0 + $i);
        }

        $result = $this->coordinator->optimizeDiscountCapture(
            tenantId: 'TENANT-001',
            availableCash: $availableCash,
            opportunities: $opportunities,
            constraints: ['max_opportunities' => 5],
        );

        $this->assertCount(5, $result['selected_opportunities']);
        $this->assertCount(5, $result['excluded_opportunities']);
    }

    #[Test]
    public function it_executes_discount_capture(): void
    {
        $this->discountService
            ->method('captureDiscount')
            ->willReturn(true);

        $opportunities = [
            $this->createTestOpportunity('EPD-DISC-001', 10000.0, 200.0, 36.5),
        ];

        $result = $this->coordinator->executeDiscountCapture(
            tenantId: 'TENANT-001',
            opportunities: $opportunities,
            executedBy: 'USER-001',
        );

        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('total_captured', $result);
        $this->assertArrayHasKey('execution_date', $result);
    }

    #[Test]
    public function it_skips_expired_opportunities_during_execution(): void
    {
        $expiredOpportunity = new DiscountOpportunityData(
            opportunityId: 'EPD-DISC-001',
            type: 'EARLY_PAYMENT',
            vendorId: 'VENDOR-001',
            vendorName: 'Test Vendor',
            invoiceId: 'INV-001',
            invoiceAmount: Money::of(10000, 'USD'),
            discountPercent: 2.0,
            potentialSavings: Money::of(200, 'USD'),
            investmentRequired: Money::of(9800, 'USD'),
            annualizedRoi: 36.5,
            daysRemaining: 0,
            expirationDate: new \DateTimeImmutable('-1 day'),
            priority: 5,
        );

        $result = $this->coordinator->executeDiscountCapture(
            tenantId: 'TENANT-001',
            opportunities: [$expiredOpportunity],
            executedBy: 'USER-001',
        );

        $this->assertCount(0, $result['successful']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('expired', $result['failed']['EPD-DISC-001']);
    }

    #[Test]
    public function it_generates_discount_forecast(): void
    {
        $result = $this->coordinator->generateDiscountForecast(
            tenantId: 'TENANT-001',
            forecastStartDate: new \DateTimeImmutable('2024-01-01'),
            forecastMonths: 6,
            assumptions: [
                'monthly_invoice_volume' => Money::of(100000, 'USD'),
                'historical_capture_rate' => 0.65,
                'average_discount_percent' => 2.0,
            ],
        );

        $this->assertArrayHasKey('monthly_forecast', $result);
        $this->assertArrayHasKey('annual_projection', $result);
        $this->assertArrayHasKey('confidence_level', $result);
        $this->assertArrayHasKey('assumptions', $result);

        $this->assertCount(6, $result['monthly_forecast']);
        $this->assertGreaterThan(0, $result['annual_projection']->getAmount());
        $this->assertGreaterThan(0.3, $result['confidence_level']);
        $this->assertLessThanOrEqual(0.95, $result['confidence_level']);
    }

    #[Test]
    public function it_returns_discount_performance_metrics(): void
    {
        $capturedDiscount = new EarlyPaymentDiscountData(
            discountId: 'DISC-001',
            vendorId: 'VENDOR-001',
            invoiceAmount: Money::of(10000, 'USD'),
            discountPercent: 2.0,
            discountAmount: Money::of(200, 'USD'),
            discountDueDate: new \DateTimeImmutable('+10 days'),
            netDueDate: new \DateTimeImmutable('+30 days'),
            status: DiscountStatus::CAPTURED,
        );

        $this->discountService
            ->method('getCapturedDiscounts')
            ->willReturn([$capturedDiscount]);

        $this->discountService
            ->method('getMissedDiscounts')
            ->willReturn([]);

        $result = $this->coordinator->getDiscountPerformanceMetrics(
            tenantId: 'TENANT-001',
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
        );

        $this->assertArrayHasKey('discounts_captured', $result);
        $this->assertArrayHasKey('total_savings', $result);
        $this->assertArrayHasKey('average_roi', $result);
        $this->assertArrayHasKey('capture_rate', $result);
        $this->assertArrayHasKey('missed_opportunities', $result);
        $this->assertArrayHasKey('missed_savings', $result);
        $this->assertArrayHasKey('top_vendors', $result);

        $this->assertEquals(1, $result['discounts_captured']);
        $this->assertEquals(100.0, $result['capture_rate']); // 1 captured, 0 missed
    }

    #[Test]
    #[DataProvider('priorityCalculationProvider')]
    public function it_calculates_opportunity_priority_correctly(
        float $roi,
        int $daysRemaining,
        int $expectedPriority,
    ): void {
        // Use reflection to test private method
        $reflector = new \ReflectionClass($this->coordinator);
        $method = $reflector->getMethod('calculatePriority');
        $method->setAccessible(true);

        $priority = $method->invoke($this->coordinator, $roi, $daysRemaining);

        $this->assertEquals($expectedPriority, $priority);
    }

    public static function priorityCalculationProvider(): array
    {
        return [
            'high_roi_urgent' => [50.0, 2, 5],
            'high_roi_not_urgent' => [50.0, 30, 4],
            'medium_roi_urgent' => [25.0, 3, 4],
            'medium_roi_not_urgent' => [25.0, 30, 3],
            'low_roi_urgent' => [12.0, 2, 4],
            'low_roi_not_urgent' => [12.0, 30, 2],
        ];
    }

    #[Test]
    public function it_generates_recommendations_for_high_roi_opportunities(): void
    {
        $this->discountService
            ->method('calculateAnnualizedReturn')
            ->willReturn(new RoiAnalysisResult(
                discountId: 'DISC-001',
                discountAmount: Money::of(200, 'USD'),
                annualizedRoi: 45.0, // High ROI
                effectiveRate: 2.0,
                daysToDiscount: 10,
                netDaysDifference: 20,
                breakEvenRate: 2.5,
                isWorthCapturing: true,
            ));

        $this->eventDispatcher->method('dispatch');

        $result = $this->coordinator->analyzeDiscountOpportunities(
            tenantId: 'TENANT-001',
            options: [
                'eligible_discounts' => [
                    $this->createTestDiscount('DISC-001', 10000.0, 2.0),
                ],
            ],
        );

        $this->assertNotEmpty($result['recommendations']);
        $hasHighRoiRecommendation = false;
        foreach ($result['recommendations'] as $recommendation) {
            if (str_contains($recommendation, '30%+')) {
                $hasHighRoiRecommendation = true;
                break;
            }
        }
        $this->assertTrue($hasHighRoiRecommendation);
    }

    /**
     * Create a test discount for analysis.
     */
    private function createTestDiscount(
        string $discountId,
        float $invoiceAmount,
        float $discountPercent,
    ): EarlyPaymentDiscountData {
        return new EarlyPaymentDiscountData(
            discountId: $discountId,
            vendorId: 'VENDOR-001',
            invoiceAmount: Money::of($invoiceAmount, 'USD'),
            discountPercent: $discountPercent,
            discountAmount: Money::of($invoiceAmount * $discountPercent / 100, 'USD'),
            discountDueDate: new \DateTimeImmutable('+10 days'),
            netDueDate: new \DateTimeImmutable('+30 days'),
            status: DiscountStatus::ELIGIBLE,
        );
    }

    /**
     * Create a test opportunity for optimization.
     */
    private function createTestOpportunity(
        string $opportunityId,
        float $investmentRequired,
        float $potentialSavings,
        float $annualizedRoi,
        string $vendorId = 'VENDOR-001',
    ): DiscountOpportunityData {
        return new DiscountOpportunityData(
            opportunityId: $opportunityId,
            type: 'EARLY_PAYMENT',
            vendorId: $vendorId,
            vendorName: 'Test Vendor',
            invoiceId: 'INV-001',
            invoiceAmount: Money::of($investmentRequired + $potentialSavings, 'USD'),
            discountPercent: 2.0,
            potentialSavings: Money::of($potentialSavings, 'USD'),
            investmentRequired: Money::of($investmentRequired, 'USD'),
            annualizedRoi: $annualizedRoi,
            daysRemaining: 10,
            expirationDate: new \DateTimeImmutable('+10 days'),
            priority: 3,
        );
    }
}
