<?php

declare(strict_types=1);

namespace Nexus\PayrollMysStatutory\Data;

/**
 * PCB (Potongan Cukai Bulanan) monthly tax deduction table.
 * 
 * Based on Malaysian Income Tax Act and LHDN MTD tables.
 * Uses simplified calculation for demonstration purposes.
 * 
 * For production use, implement full MTD calculation with:
 * - Tax relief (self, spouse, children, insurance, etc.)
 * - Zakat/Fitrah deductions
 * - EPF contributions deduction
 * - Full progressive tax bands
 */
final readonly class PcbTaxTable
{
    /**
     * Calculate monthly PCB tax deduction.
     * 
     * Simplified calculation using annual tax bands divided by 12.
     * 
     * NOTE: This implementation uses a simplified Monthly Tax Deduction (MTD) method 
     * that calculates tax based on monthly income annualized. It does NOT currently 
     * use the provided YTD (Year-To-Date) values for progressive tax adjustment.
     * 
     * For production use with mid-year salary changes or bonuses, this should be 
     * enhanced to:
     * 1. Calculate expected annual tax based on YTD income + projected remaining months
     * 2. Subtract YTD tax already paid
     * 3. Divide remaining tax by remaining months
     * 
     * This ensures accurate tax distribution throughout the year.
     * 
     * @param float $monthlyTaxableIncome Monthly taxable income (after EPF deduction)
     * @param float $ytdTaxableIncome Year-to-date taxable income (currently unused)
     * @param float $ytdTaxPaid Year-to-date tax paid (currently unused)
     * @param int $dependents Number of dependents (for tax relief)
     * @param string $maritalStatus 'single' or 'married'
     * @return float Monthly PCB amount
     */
    public static function calculateMonthlyPcb(
        float $monthlyTaxableIncome,
        float $ytdTaxableIncome,
        float $ytdTaxPaid,
        int $dependents = 0,
        string $maritalStatus = 'single'
    ): float {
        // Calculate annualized taxable income (simplified method)
        $annualTaxableIncome = $monthlyTaxableIncome * 12;
        
        // Apply basic personal relief
        $personalRelief = match($maritalStatus) {
            'married' => 13000.00, // Self + spouse relief
            default => 9000.00,    // Self relief only
        };
        
        // Additional relief for dependents (up to RM2,000 per child, max 6 children)
        $dependentRelief = min($dependents, 6) * 2000.00;
        
        // Deduct reliefs from taxable income
        $chargeableIncome = max(0, $annualTaxableIncome - $personalRelief - $dependentRelief);
        
        // Calculate annual tax using progressive rates (2024/2025)
        $annualTax = self::calculateProgressiveTax($chargeableIncome);
        
        // Monthly PCB is annual tax divided by 12
        $monthlyPcb = $annualTax / 12;
        
        // Return rounded to 2 decimal places
        return round($monthlyPcb, 2);
    }
    
    /**
     * Calculate progressive tax based on Malaysian tax bands.
     * 
     * Tax rates (2024/2025):
     * - Up to RM5,000: 0%
     * - RM5,001 - RM20,000: 1%
     * - RM20,001 - RM35,000: 3%
     * - RM35,001 - RM50,000: 6%
     * - RM50,001 - RM70,000: 11%
     * - RM70,001 - RM100,000: 19%
     * - RM100,001 - RM250,000: 25%
     * - RM250,001 - RM400,000: 26%
     * - RM400,001 - RM600,000: 28%
     * - RM600,001 - RM1,000,000: 30%
     * - Above RM1,000,000: 32%
     * 
     * @param float $chargeableIncome Annual chargeable income after reliefs
     * @return float Annual tax amount
     */
    private static function calculateProgressiveTax(float $chargeableIncome): float
    {
        if ($chargeableIncome <= 5000) {
            return 0.00;
        }
        
        $tax = 0.00;
        
        // RM5,001 - RM20,000 @ 1%
        if ($chargeableIncome > 5000) {
            $taxableInBand = min($chargeableIncome - 5000, 15000);
            $tax += $taxableInBand * 0.01;
        }
        
        // RM20,001 - RM35,000 @ 3%
        if ($chargeableIncome > 20000) {
            $taxableInBand = min($chargeableIncome - 20000, 15000);
            $tax += $taxableInBand * 0.03;
        }
        
        // RM35,001 - RM50,000 @ 6%
        if ($chargeableIncome > 35000) {
            $taxableInBand = min($chargeableIncome - 35000, 15000);
            $tax += $taxableInBand * 0.06;
        }
        
        // RM50,001 - RM70,000 @ 11%
        if ($chargeableIncome > 50000) {
            $taxableInBand = min($chargeableIncome - 50000, 20000);
            $tax += $taxableInBand * 0.11;
        }
        
        // RM70,001 - RM100,000 @ 19%
        if ($chargeableIncome > 70000) {
            $taxableInBand = min($chargeableIncome - 70000, 30000);
            $tax += $taxableInBand * 0.19;
        }
        
        // RM100,001 - RM250,000 @ 25%
        if ($chargeableIncome > 100000) {
            $taxableInBand = min($chargeableIncome - 100000, 150000);
            $tax += $taxableInBand * 0.25;
        }
        
        // RM250,001 - RM400,000 @ 26%
        if ($chargeableIncome > 250000) {
            $taxableInBand = min($chargeableIncome - 250000, 150000);
            $tax += $taxableInBand * 0.26;
        }
        
        // RM400,001 - RM600,000 @ 28%
        if ($chargeableIncome > 400000) {
            $taxableInBand = min($chargeableIncome - 400000, 200000);
            $tax += $taxableInBand * 0.28;
        }
        
        // RM600,001 - RM1,000,000 @ 30%
        if ($chargeableIncome > 600000) {
            $taxableInBand = min($chargeableIncome - 600000, 400000);
            $tax += $taxableInBand * 0.30;
        }
        
        // Above RM1,000,000 @ 32%
        if ($chargeableIncome > 1000000) {
            $taxableInBand = $chargeableIncome - 1000000;
            $tax += $taxableInBand * 0.32;
        }
        
        return round($tax, 2);
    }
}
