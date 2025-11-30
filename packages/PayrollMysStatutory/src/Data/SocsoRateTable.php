<?php

declare(strict_types=1);

namespace Nexus\PayrollMysStatutory\Data;

/**
 * SOCSO (Social Security Organization) contribution rates table.
 * 
 * Based on official SOCSO contribution schedule.
 * Rates are fixed by wage brackets up to RM5,000 monthly salary.
 */
final readonly class SocsoRateTable
{
    /**
     * Get SOCSO contributions for given monthly salary.
     * 
     * @param float $monthlySalary Monthly salary in RM
     * @return array{employee: float, employer: float} Employee and employer contributions
     */
    public static function getContributions(float $monthlySalary): array
    {
        // SOCSO wage brackets with fixed contributions
        return match(true) {
            $monthlySalary <= 30.00 => ['employee' => 0.10, 'employer' => 0.40],
            $monthlySalary <= 50.00 => ['employee' => 0.20, 'employer' => 0.70],
            $monthlySalary <= 70.00 => ['employee' => 0.30, 'employer' => 1.10],
            $monthlySalary <= 100.00 => ['employee' => 0.40, 'employer' => 1.50],
            $monthlySalary <= 140.00 => ['employee' => 0.60, 'employer' => 2.10],
            $monthlySalary <= 200.00 => ['employee' => 0.85, 'employer' => 2.95],
            $monthlySalary <= 300.00 => ['employee' => 1.25, 'employer' => 4.35],
            $monthlySalary <= 400.00 => ['employee' => 1.75, 'employer' => 6.15],
            $monthlySalary <= 500.00 => ['employee' => 2.25, 'employer' => 7.85],
            $monthlySalary <= 600.00 => ['employee' => 2.75, 'employer' => 9.65],
            $monthlySalary <= 700.00 => ['employee' => 3.25, 'employer' => 11.35],
            $monthlySalary <= 800.00 => ['employee' => 3.75, 'employer' => 13.15],
            $monthlySalary <= 900.00 => ['employee' => 4.25, 'employer' => 14.85],
            $monthlySalary <= 1000.00 => ['employee' => 4.75, 'employer' => 16.65],
            $monthlySalary <= 1100.00 => ['employee' => 5.25, 'employer' => 18.35],
            $monthlySalary <= 1200.00 => ['employee' => 5.75, 'employer' => 20.15],
            $monthlySalary <= 1300.00 => ['employee' => 6.25, 'employer' => 21.85],
            $monthlySalary <= 1400.00 => ['employee' => 6.75, 'employer' => 23.65],
            $monthlySalary <= 1500.00 => ['employee' => 7.25, 'employer' => 25.35],
            $monthlySalary <= 1600.00 => ['employee' => 7.75, 'employer' => 27.15],
            $monthlySalary <= 1700.00 => ['employee' => 8.25, 'employer' => 28.85],
            $monthlySalary <= 1800.00 => ['employee' => 8.75, 'employer' => 30.65],
            $monthlySalary <= 1900.00 => ['employee' => 9.25, 'employer' => 32.35],
            $monthlySalary <= 2000.00 => ['employee' => 9.75, 'employer' => 34.15],
            $monthlySalary <= 2100.00 => ['employee' => 10.25, 'employer' => 35.85],
            $monthlySalary <= 2200.00 => ['employee' => 10.75, 'employer' => 37.65],
            $monthlySalary <= 2300.00 => ['employee' => 11.25, 'employer' => 39.35],
            $monthlySalary <= 2400.00 => ['employee' => 11.75, 'employer' => 41.15],
            $monthlySalary <= 2500.00 => ['employee' => 12.25, 'employer' => 42.85],
            $monthlySalary <= 2600.00 => ['employee' => 12.75, 'employer' => 44.65],
            $monthlySalary <= 2700.00 => ['employee' => 13.25, 'employer' => 46.35],
            $monthlySalary <= 2800.00 => ['employee' => 13.75, 'employer' => 48.15],
            $monthlySalary <= 2900.00 => ['employee' => 14.25, 'employer' => 49.85],
            $monthlySalary <= 3000.00 => ['employee' => 14.75, 'employer' => 51.65],
            $monthlySalary <= 3100.00 => ['employee' => 15.25, 'employer' => 53.35],
            $monthlySalary <= 3200.00 => ['employee' => 15.75, 'employer' => 55.15],
            $monthlySalary <= 3300.00 => ['employee' => 16.25, 'employer' => 56.85],
            $monthlySalary <= 3400.00 => ['employee' => 16.75, 'employer' => 58.65],
            $monthlySalary <= 3500.00 => ['employee' => 17.25, 'employer' => 60.35],
            $monthlySalary <= 3600.00 => ['employee' => 17.75, 'employer' => 62.15],
            $monthlySalary <= 3700.00 => ['employee' => 18.25, 'employer' => 63.85],
            $monthlySalary <= 3800.00 => ['employee' => 18.75, 'employer' => 65.65],
            $monthlySalary <= 3900.00 => ['employee' => 19.25, 'employer' => 67.35],
            $monthlySalary <= 4000.00 => ['employee' => 19.75, 'employer' => 69.05],
            $monthlySalary <= 4100.00 => ['employee' => 20.25, 'employer' => 70.85],
            $monthlySalary <= 4200.00 => ['employee' => 20.75, 'employer' => 72.55],
            $monthlySalary <= 4300.00 => ['employee' => 21.25, 'employer' => 74.35],
            $monthlySalary <= 4400.00 => ['employee' => 21.75, 'employer' => 76.05],
            $monthlySalary <= 4500.00 => ['employee' => 22.25, 'employer' => 77.85],
            $monthlySalary <= 4600.00 => ['employee' => 22.75, 'employer' => 79.55],
            $monthlySalary <= 4700.00 => ['employee' => 23.25, 'employer' => 81.35],
            $monthlySalary <= 4800.00 => ['employee' => 23.75, 'employer' => 83.05],
            $monthlySalary <= 4900.00 => ['employee' => 24.25, 'employer' => 84.85],
            // Maximum contribution for salaries >= RM5,000
            default => ['employee' => 24.75, 'employer' => 86.75],
        };
    }
}
