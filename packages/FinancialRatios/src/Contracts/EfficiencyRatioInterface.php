<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Contract for efficiency ratio calculations.
 */
interface EfficiencyRatioInterface
{
    /**
     * Calculate asset turnover (Revenue / Average Total Assets).
     */
    public function assetTurnover(float $revenue, float $averageTotalAssets): RatioResult;

    /**
     * Calculate inventory turnover (COGS / Average Inventory).
     */
    public function inventoryTurnover(float $cogs, float $averageInventory): RatioResult;

    /**
     * Calculate receivables turnover (Revenue / Average Receivables).
     */
    public function receivablesTurnover(float $revenue, float $averageReceivables): RatioResult;

    /**
     * Calculate payables turnover (COGS / Average Payables).
     */
    public function payablesTurnover(float $cogs, float $averagePayables): RatioResult;

    /**
     * Calculate days sales outstanding (365 / Receivables Turnover).
     */
    public function daysSalesOutstanding(float $receivablesTurnover): float;

    /**
     * Calculate days inventory outstanding (365 / Inventory Turnover).
     */
    public function daysInventoryOutstanding(float $inventoryTurnover): float;

    /**
     * Calculate days payables outstanding (365 / Payables Turnover).
     */
    public function daysPayablesOutstanding(float $payablesTurnover): float;

    /**
     * Calculate cash conversion cycle (DIO + DSO - DPO).
     */
    public function cashConversionCycle(float $dio, float $dso, float $dpo): float;
}
