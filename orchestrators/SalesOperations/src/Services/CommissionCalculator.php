<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class CommissionCalculator
{
    public function __construct(
        private string $defaultBasis = 'gross_profit',
        private float $defaultRate = 10.0,
        private float $tier2Threshold = 50000.0,
        private float $tier2Rate = 12.0,
        private float $tier3Threshold = 100000.0,
        private float $tier3Rate = 15.0,
    ) {}

    public function calculate(CommissionInput $input): CommissionResult
    {
        $basis = $input->basis ?? $this->defaultBasis;
        $rate = $this->determineRate($input);

        $commissionableAmount = $this->calculateCommissionableAmount($input, $basis);
        $commissionAmount = $commissionableAmount * ($rate / 100);

        return new CommissionResult(
            salespersonId: $input->salespersonId,
            orderId: $input->orderId,
            paymentId: $input->paymentId,
            basis: $basis,
            rate: $rate,
            revenueAmount: $input->revenueAmount,
            costAmount: $input->costAmount,
            grossProfit: $input->revenueAmount - $input->costAmount,
            commissionableAmount: $commissionableAmount,
            commissionAmount: $commissionAmount,
            currencyCode: $input->currencyCode,
        );
    }

    public function calculateTeamSplit(
        CommissionInput $input,
        array $splits
    ): array {
        $mainResult = $this->calculate($input);
        $results = [];

        foreach ($splits as $split) {
            $splitPercent = $split['percent'] ?? $split->percent ?? 0;
            $salespersonId = $split['salesperson_id'] ?? $split->salespersonId ?? $split['salespersonId'];

            $splitAmount = $mainResult->commissionAmount * ($splitPercent / 100);

            $results[] = new CommissionSplitResult(
                salespersonId: $salespersonId,
                splitPercent: $splitPercent,
                commissionAmount: $splitAmount,
                currencyCode: $input->currencyCode,
            );
        }

        return $results;
    }

    public function calculateWithOverride(
        CommissionInput $input,
        float $overrideRate,
        ?string $overrideReason = null
    ): CommissionResult {
        $basis = $input->basis ?? $this->defaultBasis;
        $commissionableAmount = $this->calculateCommissionableAmount($input, $basis);
        $commissionAmount = $commissionableAmount * ($overrideRate / 100);

        return new CommissionResult(
            salespersonId: $input->salespersonId,
            orderId: $input->orderId,
            paymentId: $input->paymentId,
            basis: $basis,
            rate: $overrideRate,
            revenueAmount: $input->revenueAmount,
            costAmount: $input->costAmount,
            grossProfit: $input->revenueAmount - $input->costAmount,
            commissionableAmount: $commissionableAmount,
            commissionAmount: $commissionAmount,
            currencyCode: $input->currencyCode,
            isOverride: true,
            overrideReason: $overrideReason,
        );
    }

    public function calculateForPeriod(
        string $salespersonId,
        array $payments,
        string $currencyCode = 'MYR'
    ): CommissionSummary {
        $totalRevenue = 0.0;
        $totalCost = 0.0;
        $totalCommission = 0.0;
        $transactionCount = 0;

        foreach ($payments as $payment) {
            $input = new CommissionInput(
                salespersonId: $salespersonId,
                orderId: $payment['order_id'],
                paymentId: $payment['payment_id'],
                revenueAmount: $payment['revenue_amount'],
                costAmount: $payment['cost_amount'] ?? $payment['revenue_amount'] * 0.6,
                currencyCode: $currencyCode,
            );

            $result = $this->calculate($input);

            $totalRevenue += $result->revenueAmount;
            $totalCost += $result->costAmount;
            $totalCommission += $result->commissionAmount;
            $transactionCount++;
        }

        return new CommissionSummary(
            salespersonId: $salespersonId,
            totalRevenue: $totalRevenue,
            totalCost: $totalCost,
            totalGrossProfit: $totalRevenue - $totalCost,
            totalCommission: $totalCommission,
            transactionCount: $transactionCount,
            currencyCode: $currencyCode,
        );
    }

    private function determineRate(CommissionInput $input): float
    {
        if ($input->overrideRate !== null) {
            return $input->overrideRate;
        }

        if ($input->customRate !== null) {
            return $input->customRate;
        }

        $ytdRevenue = $input->ytdRevenue ?? 0;
        $orderRevenue = $input->revenueAmount;

        if ($ytdRevenue >= $this->tier3Threshold) {
            return $this->tier3Rate;
        }

        if ($ytdRevenue >= $this->tier2Threshold) {
            return $this->tier2Rate;
        }

        return $this->defaultRate;
    }

    private function calculateCommissionableAmount(CommissionInput $input, string $basis): float
    {
        return match ($basis) {
            'revenue' => $input->revenueAmount,
            'gross_profit' => max(0, $input->revenueAmount - $input->costAmount),
            'net_profit' => max(0, $input->revenueAmount - $input->costAmount - ($input->overheadAmount ?? 0)),
            default => max(0, $input->revenueAmount - $input->costAmount),
        };
    }
}
