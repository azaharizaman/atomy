<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class RevenueRecognitionService
{
    public function __construct(
        private int $defaultRecognitionDays = 30,
        private bool $enablePerformanceObligations = true,
    ) {}

    public function analyze(RevenueRecognitionInput $input): RevenueRecognitionResult
    {
        $performanceObligations = $this->identifyPerformanceObligations($input);
        $totalTransactionPrice = $input->totalAmount;
        $allocation = $this->allocateTransactionPrice($performanceObligations, $totalTransactionPrice);
        $schedule = $this->createRecognitionSchedule($allocation, $input);

        return new RevenueRecognitionResult(
            contractId: $input->orderId,
            totalTransactionPrice: $totalTransactionPrice,
            currencyCode: $input->currencyCode,
            performanceObligations: $performanceObligations,
            allocation: $allocation,
            recognitionSchedule: $schedule,
            unrecognizedRevenue: $totalTransactionPrice,
            recognizedRevenue: 0.0,
        );
    }

    public function recognizeRevenue(
        string $contractId,
        array $schedule,
        \DateTimeImmutable $asOfDate
    ): RevenueRecognitionEntry {
        $recognizedAmount = 0.0;
        $remainingSchedule = [];

        foreach ($schedule as $entry) {
            $recognitionDate = $entry['date'] instanceof \DateTimeImmutable
                ? $entry['date']
                : new \DateTimeImmutable($entry['date']);

            if ($recognitionDate <= $asOfDate && !$entry['recognized']) {
                $recognizedAmount += $entry['amount'];
                $remainingSchedule[] = array_merge($entry, ['recognized' => true]);
            } else {
                $remainingSchedule[] = $entry;
            }
        }

        return new RevenueRecognitionEntry(
            contractId: $contractId,
            recognizedAmount: $recognizedAmount,
            recognizedAt: $asOfDate,
            remainingSchedule: $remainingSchedule,
        );
    }

    public function calculateDeferredRevenue(
        string $contractId,
        float $totalAmount,
        array $recognizedEntries
    ): DeferredRevenueResult {
        $totalRecognized = array_sum(array_column($recognizedEntries, 'amount'));
        $deferredAmount = $totalAmount - $totalRecognized;

        return new DeferredRevenueResult(
            contractId: $contractId,
            totalContractValue: $totalAmount,
            totalRecognized: $totalRecognized,
            deferredRevenue: $deferredAmount,
            recognitionPercent: $totalAmount > 0 ? ($totalRecognized / $totalAmount) * 100 : 0,
        );
    }

    public function createRecognitionScheduleForShipment(
        string $orderId,
        float $shipmentAmount,
        string $currencyCode,
        \DateTimeImmutable $shipmentDate
    ): array {
        $recognitionDate = $shipmentDate;

        return [
            [
                'date' => $recognitionDate->format('Y-m-d'),
                'amount' => $shipmentAmount,
                'type' => 'shipment',
                'recognized' => false,
                'description' => 'Revenue recognized upon shipment',
            ],
        ];
    }

    public function createRecognitionScheduleForService(
        string $orderId,
        float $totalAmount,
        string $currencyCode,
        \DateTimeImmutable $startDate,
        int $serviceDays
    ): array {
        $dailyRate = $totalAmount / $serviceDays;
        $schedule = [];

        for ($i = 0; $i < $serviceDays; $i++) {
            $date = $startDate->modify("+{$i} days");
            $schedule[] = [
                'date' => $date->format('Y-m-d'),
                'amount' => round($dailyRate, 2),
                'type' => 'service',
                'recognized' => false,
                'description' => "Day {$i + 1} of {$serviceDays}",
            ];
        }

        return $schedule;
    }

    private function identifyPerformanceObligations(RevenueRecognitionInput $input): array
    {
        if (!$this->enablePerformanceObligations) {
            return [
                [
                    'id' => 'po-001',
                    'description' => 'Single performance obligation',
                    'type' => 'single',
                    'standalone_price' => $input->totalAmount,
                ],
            ];
        }

        $obligations = [];
        $index = 1;

        foreach ($input->lines as $line) {
            $productId = $line['product_id'] ?? $line['product_variant_id'] ?? "product-{$index}";
            $productName = $line['product_name'] ?? "Product {$index}";
            $lineTotal = $line['line_total'] ?? ($line['quantity'] * $line['unit_price']);
            $type = $this->determineObligationType($line);

            $obligations[] = [
                'id' => "po-{$index}",
                'product_id' => $productId,
                'description' => $productName,
                'type' => $type,
                'standalone_price' => $lineTotal,
            ];

            $index++;
        }

        return $obligations;
    }

    private function allocateTransactionPrice(array $obligations, float $totalPrice): array
    {
        $totalStandalonePrice = array_sum(array_column($obligations, 'standalone_price'));

        if ($totalStandalonePrice <= 0) {
            return [];
        }

        $allocation = [];
        $allocatedTotal = 0.0;
        $lastIndex = count($obligations) - 1;

        foreach ($obligations as $index => $obligation) {
            if ($index === $lastIndex) {
                $allocatedPrice = $totalPrice - $allocatedTotal;
            } else {
                $allocatedPrice = ($obligation['standalone_price'] / $totalStandalonePrice) * $totalPrice;
                $allocatedTotal += $allocatedPrice;
            }

            $allocation[$obligation['id']] = [
                'obligation_id' => $obligation['id'],
                'standalone_price' => $obligation['standalone_price'],
                'allocated_price' => round($allocatedPrice, 2),
                'allocation_percent' => round(($obligation['standalone_price'] / $totalStandalonePrice) * 100, 4),
            ];
        }

        return $allocation;
    }

    private function createRecognitionSchedule(array $allocation, RevenueRecognitionInput $input): array
    {
        $schedule = [];
        $orderDate = $input->orderDate ?? new \DateTimeImmutable();

        foreach ($allocation as $obligationId => $alloc) {
            $recognitionDate = $orderDate->modify("+{$this->defaultRecognitionDays} days");

            $schedule[] = [
                'obligation_id' => $obligationId,
                'date' => $recognitionDate->format('Y-m-d'),
                'amount' => $alloc['allocated_price'],
                'type' => 'allocation',
                'recognized' => false,
                'description' => "Revenue recognition for obligation {$obligationId}",
            ];
        }

        return $schedule;
    }

    private function determineObligationType(array $line): string
    {
        $productType = $line['product_type'] ?? 'goods';

        return match ($productType) {
            'service', 'subscription' => 'service',
            'software', 'license' => 'license',
            'warranty' => 'warranty',
            default => 'goods',
        };
    }
}
