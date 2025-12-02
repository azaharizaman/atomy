<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Tests\Unit\ValueObjects;

use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceAttribution;
use PHPUnit\Framework\TestCase;

final class VarianceAttributionTest extends TestCase
{
    public function test_constructs_with_valid_data(): void
    {
        $attribution = new VarianceAttribution(
            'Price Variance',
            15000.00,
            60.0,
            'Increase in unit prices'
        );

        $this->assertInstanceOf(VarianceAttribution::class, $attribution);
    }

    public function test_gets_factor_name(): void
    {
        $attribution = new VarianceAttribution(
            'Price Variance',
            15000.00,
            60.0,
            'Increase in unit prices'
        );

        $this->assertSame('Price Variance', $attribution->getFactorName());
    }

    public function test_gets_contribution(): void
    {
        $attribution = new VarianceAttribution(
            'Volume Variance',
            10000.00,
            40.0,
            'Increased sales volume'
        );

        $this->assertSame(10000.00, $attribution->getContribution());
    }

    public function test_gets_percentage(): void
    {
        $attribution = new VarianceAttribution(
            'Mix Variance',
            5000.00,
            20.0,
            'Product mix shift'
        );

        $this->assertSame(20.0, $attribution->getPercentage());
    }

    public function test_gets_description(): void
    {
        $attribution = new VarianceAttribution(
            'Efficiency Variance',
            8000.00,
            32.0,
            'Labor efficiency improved'
        );

        $this->assertSame('Labor efficiency improved', $attribution->getDescription());
    }

    public function test_gets_null_description_when_not_provided(): void
    {
        $attribution = new VarianceAttribution(
            'Other Variance',
            2000.00,
            8.0
        );

        $this->assertNull($attribution->getDescription());
    }

    public function test_handles_positive_contribution(): void
    {
        $attribution = new VarianceAttribution(
            'Revenue Increase',
            50000.00,
            100.0,
            'Higher sales revenue'
        );

        $this->assertSame(50000.00, $attribution->getContribution());
        $this->assertSame(100.0, $attribution->getPercentage());
    }

    public function test_handles_negative_contribution(): void
    {
        $attribution = new VarianceAttribution(
            'Cost Overrun',
            -25000.00,
            -50.0,
            'Increased material costs'
        );

        $this->assertSame(-25000.00, $attribution->getContribution());
        $this->assertSame(-50.0, $attribution->getPercentage());
    }

    public function test_handles_zero_contribution(): void
    {
        $attribution = new VarianceAttribution(
            'No Impact',
            0.00,
            0.0,
            'No variance in this factor'
        );

        $this->assertSame(0.00, $attribution->getContribution());
        $this->assertSame(0.0, $attribution->getPercentage());
    }

    public function test_handles_percentage_over_100(): void
    {
        $attribution = new VarianceAttribution(
            'Major Factor',
            120000.00,
            150.0,
            'Dominant contributing factor'
        );

        $this->assertSame(150.0, $attribution->getPercentage());
    }

    public function test_handles_negative_percentage(): void
    {
        $attribution = new VarianceAttribution(
            'Offsetting Factor',
            -15000.00,
            -30.0,
            'Partially offsets positive variance'
        );

        $this->assertSame(-30.0, $attribution->getPercentage());
    }

    public function test_handles_small_contribution(): void
    {
        $attribution = new VarianceAttribution(
            'Minor Factor',
            0.01,
            0.001,
            'Negligible impact'
        );

        $this->assertSame(0.01, $attribution->getContribution());
        $this->assertSame(0.001, $attribution->getPercentage());
    }

    public function test_handles_large_contribution(): void
    {
        $attribution = new VarianceAttribution(
            'Major Variance Driver',
            10000000.00,
            95.0,
            'Primary cause of total variance'
        );

        $this->assertSame(10000000.00, $attribution->getContribution());
    }

    public function test_handles_decimal_precision_contribution(): void
    {
        $attribution = new VarianceAttribution(
            'Precise Factor',
            12345.6789,
            45.123,
            'Precisely calculated contribution'
        );

        $this->assertSame(12345.6789, $attribution->getContribution());
        $this->assertSame(45.123, $attribution->getPercentage());
    }

    public function test_price_variance_example(): void
    {
        $attribution = new VarianceAttribution(
            'Price Variance',
            25000.00,
            55.56,
            'Unit price increased from $10 to $12'
        );

        $this->assertSame('Price Variance', $attribution->getFactorName());
        $this->assertSame(25000.00, $attribution->getContribution());
        $this->assertSame(55.56, $attribution->getPercentage());
        $this->assertSame('Unit price increased from $10 to $12', $attribution->getDescription());
    }

    public function test_volume_variance_example(): void
    {
        $attribution = new VarianceAttribution(
            'Volume Variance',
            20000.00,
            44.44,
            'Sales volume increased by 10%'
        );

        $this->assertSame('Volume Variance', $attribution->getFactorName());
        $this->assertSame(20000.00, $attribution->getContribution());
        $this->assertSame(44.44, $attribution->getPercentage());
    }

    public function test_mix_variance_example(): void
    {
        $attribution = new VarianceAttribution(
            'Mix Variance',
            -5000.00,
            -11.11,
            'Shift to lower margin products'
        );

        $this->assertSame('Mix Variance', $attribution->getFactorName());
        $this->assertSame(-5000.00, $attribution->getContribution());
        $this->assertSame(-11.11, $attribution->getPercentage());
    }

    public function test_rate_variance_example(): void
    {
        $attribution = new VarianceAttribution(
            'Labor Rate Variance',
            12000.00,
            30.0,
            'Wage rates higher than budgeted'
        );

        $this->assertSame('Labor Rate Variance', $attribution->getFactorName());
    }

    public function test_efficiency_variance_example(): void
    {
        $attribution = new VarianceAttribution(
            'Labor Efficiency Variance',
            -8000.00,
            -20.0,
            'Actual hours exceeded standard hours'
        );

        $this->assertSame('Labor Efficiency Variance', $attribution->getFactorName());
        $this->assertSame(-8000.00, $attribution->getContribution());
    }

    public function test_empty_factor_name(): void
    {
        $attribution = new VarianceAttribution(
            '',
            5000.00,
            25.0,
            'No factor name provided'
        );

        $this->assertSame('', $attribution->getFactorName());
    }

    public function test_long_factor_name(): void
    {
        $longName = 'Very Long Factor Name That Describes A Complex Attribution Analysis Component';
        
        $attribution = new VarianceAttribution(
            $longName,
            3000.00,
            15.0
        );

        $this->assertSame($longName, $attribution->getFactorName());
    }

    public function test_long_description(): void
    {
        $longDescription = 'This is a very detailed description that explains the attribution '
            . 'factor in great detail including multiple reasons and complex calculations that '
            . 'contributed to the overall variance analysis results.';
        
        $attribution = new VarianceAttribution(
            'Complex Factor',
            7500.00,
            35.0,
            $longDescription
        );

        $this->assertSame($longDescription, $attribution->getDescription());
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(VarianceAttribution::class);
        
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_is_final(): void
    {
        $reflection = new \ReflectionClass(VarianceAttribution::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function test_percentage_at_exact_100(): void
    {
        $attribution = new VarianceAttribution(
            'Complete Attribution',
            100000.00,
            100.0,
            'This factor accounts for 100% of the variance'
        );

        $this->assertSame(100.0, $attribution->getPercentage());
    }
}
