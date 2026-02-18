<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Services;

use Nexus\SalesOperations\Services\RevenueRecognitionService;
use Nexus\SalesOperations\Services\RevenueRecognitionInput;
use Nexus\SalesOperations\Services\RevenueRecognitionResult;
use Nexus\SalesOperations\Services\RevenueRecognitionEntry;
use Nexus\SalesOperations\Services\DeferredRevenueResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RevenueRecognitionService::class)]
#[CoversClass(RevenueRecognitionInput::class)]
#[CoversClass(RevenueRecognitionResult::class)]
#[CoversClass(RevenueRecognitionEntry::class)]
#[CoversClass(DeferredRevenueResult::class)]
final class RevenueRecognitionServiceTest extends TestCase
{
    private RevenueRecognitionService $service;

    protected function setUp(): void
    {
        $this->service = new RevenueRecognitionService(
            defaultRecognitionDays: 30,
            enablePerformanceObligations: true
        );
    }

    #[Test]
    public function analyze_creates_performance_obligations(): void
    {
        $input = new RevenueRecognitionInput(
            orderId: 'order-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [
                ['product_id' => 'prod-1', 'product_name' => 'Product A', 'quantity' => 2, 'unit_price' => 500],
            ]
        );

        $result = $this->service->analyze($input);

        $this->assertSame('order-1', $result->contractId);
        $this->assertSame(1000.0, $result->totalTransactionPrice);
        $this->assertCount(1, $result->performanceObligations);
        $this->assertSame(1000.0, $result->unrecognizedRevenue);
    }

    #[Test]
    public function analyze_allocates_transaction_price(): void
    {
        $input = new RevenueRecognitionInput(
            orderId: 'order-1',
            totalAmount: 900.0,
            currencyCode: 'MYR',
            lines: [
                ['product_id' => 'prod-1', 'product_name' => 'Product A', 'quantity' => 1, 'unit_price' => 600],
                ['product_id' => 'prod-2', 'product_name' => 'Product B', 'quantity' => 1, 'unit_price' => 400],
            ]
        );

        $result = $this->service->analyze($input);

        $this->assertCount(2, $result->allocation);
        $this->assertSame(900.0, array_sum(array_column($result->allocation, 'allocated_price')));
    }

    #[Test]
    public function analyze_creates_recognition_schedule(): void
    {
        $input = new RevenueRecognitionInput(
            orderId: 'order-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR',
            lines: [
                ['product_id' => 'prod-1', 'product_name' => 'Product A', 'quantity' => 1, 'unit_price' => 1000],
            ],
            orderDate: new \DateTimeImmutable('2024-01-01')
        );

        $result = $this->service->analyze($input);

        $this->assertCount(1, $result->recognitionSchedule);
        $this->assertSame(1000.0, $result->recognitionSchedule[0]['amount']);
        $this->assertFalse($result->recognitionSchedule[0]['recognized']);
    }

    #[Test]
    public function recognize_revenue_updates_schedule(): void
    {
        $schedule = [
            ['date' => '2024-01-15', 'amount' => 500.0, 'recognized' => false],
            ['date' => '2024-02-15', 'amount' => 500.0, 'recognized' => false],
        ];

        $entry = $this->service->recognizeRevenue(
            'order-1',
            $schedule,
            new \DateTimeImmutable('2024-01-20')
        );

        $this->assertSame(500.0, $entry->recognizedAmount);
        $this->assertTrue($entry->remainingSchedule[0]['recognized']);
        $this->assertFalse($entry->remainingSchedule[1]['recognized']);
    }

    #[Test]
    public function calculate_deferred_revenue(): void
    {
        $result = $this->service->calculateDeferredRevenue(
            'order-1',
            1000.0,
            [
                ['amount' => 300.0],
                ['amount' => 200.0],
            ]
        );

        $this->assertSame(1000.0, $result->totalContractValue);
        $this->assertSame(500.0, $result->totalRecognized);
        $this->assertSame(500.0, $result->deferredRevenue);
        $this->assertSame(50.0, $result->recognitionPercent);
    }

    #[Test]
    public function create_schedule_for_shipment(): void
    {
        $schedule = $this->service->createRecognitionScheduleForShipment(
            'order-1',
            1000.0,
            'MYR',
            new \DateTimeImmutable('2024-01-15')
        );

        $this->assertCount(1, $schedule);
        $this->assertSame(1000.0, $schedule[0]['amount']);
        $this->assertSame('shipment', $schedule[0]['type']);
    }

    #[Test]
    public function create_schedule_for_service(): void
    {
        $schedule = $this->service->createRecognitionScheduleForService(
            'order-1',
            300.0,
            'MYR',
            new \DateTimeImmutable('2024-01-01'),
            3
        );

        $this->assertCount(3, $schedule);
        $this->assertSame(100.0, $schedule[0]['amount']);
        $this->assertSame('service', $schedule[0]['type']);
    }

    #[Test]
    public function result_is_fully_recognized(): void
    {
        $result = new RevenueRecognitionResult(
            contractId: 'order-1',
            totalTransactionPrice: 1000.0,
            currencyCode: 'MYR',
            performanceObligations: [],
            allocation: [],
            recognitionSchedule: [],
            unrecognizedRevenue: 0.0,
            recognizedRevenue: 1000.0
        );

        $this->assertTrue($result->isFullyRecognized());
        $this->assertSame(100.0, $result->recognitionProgress());
    }
}
