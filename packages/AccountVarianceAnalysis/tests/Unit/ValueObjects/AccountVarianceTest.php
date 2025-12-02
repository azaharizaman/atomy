<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Tests\Unit\ValueObjects;

use Nexus\AccountVarianceAnalysis\Enums\VarianceType;
use Nexus\AccountVarianceAnalysis\ValueObjects\AccountVariance;
use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceResult;
use PHPUnit\Framework\TestCase;

final class AccountVarianceTest extends TestCase
{
    public function test_constructs_with_valid_data(): void
    {
        $varianceResult = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '4000',
            'Sales Revenue',
            $varianceResult,
            'Revenue'
        );

        $this->assertInstanceOf(AccountVariance::class, $accountVariance);
    }

    public function test_gets_account_code(): void
    {
        $varianceResult = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '4000',
            'Sales Revenue',
            $varianceResult,
            'Revenue'
        );

        $this->assertSame('4000', $accountVariance->getAccountCode());
    }

    public function test_gets_account_name(): void
    {
        $varianceResult = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '4000',
            'Sales Revenue',
            $varianceResult,
            'Revenue'
        );

        $this->assertSame('Sales Revenue', $accountVariance->getAccountName());
    }

    public function test_gets_variance_result(): void
    {
        $varianceResult = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '4000',
            'Sales Revenue',
            $varianceResult,
            'Revenue'
        );

        $this->assertSame($varianceResult, $accountVariance->getResult());
    }

    public function test_gets_category(): void
    {
        $varianceResult = new VarianceResult(
            150000.00,
            100000.00,
            50000.00,
            50.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '4000',
            'Sales Revenue',
            $varianceResult,
            'Revenue'
        );

        $this->assertSame('Revenue', $accountVariance->getCategory());
    }

    public function test_gets_null_category_when_not_provided(): void
    {
        $varianceResult = new VarianceResult(
            75000.00,
            100000.00,
            -25000.00,
            -25.0,
            VarianceType::UNFAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '5000',
            'Cost of Goods Sold',
            $varianceResult
        );

        $this->assertNull($accountVariance->getCategory());
    }

    public function test_handles_revenue_account(): void
    {
        $varianceResult = new VarianceResult(
            250000.00,
            200000.00,
            50000.00,
            25.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '4100',
            'Service Revenue',
            $varianceResult,
            'Revenue'
        );

        $this->assertSame('4100', $accountVariance->getAccountCode());
        $this->assertSame('Service Revenue', $accountVariance->getAccountName());
        $this->assertSame('Revenue', $accountVariance->getCategory());
    }

    public function test_handles_expense_account(): void
    {
        $varianceResult = new VarianceResult(
            120000.00,
            100000.00,
            20000.00,
            20.0,
            VarianceType::UNFAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '6000',
            'Salaries Expense',
            $varianceResult,
            'Expense'
        );

        $this->assertSame('6000', $accountVariance->getAccountCode());
        $this->assertSame('Salaries Expense', $accountVariance->getAccountName());
        $this->assertSame('Expense', $accountVariance->getCategory());
    }

    public function test_handles_asset_account(): void
    {
        $varianceResult = new VarianceResult(
            550000.00,
            500000.00,
            50000.00,
            10.0,
            VarianceType::NEUTRAL
        );
        
        $accountVariance = new AccountVariance(
            '1200',
            'Accounts Receivable',
            $varianceResult,
            'Asset'
        );

        $this->assertSame('1200', $accountVariance->getAccountCode());
        $this->assertSame('Accounts Receivable', $accountVariance->getAccountName());
        $this->assertSame('Asset', $accountVariance->getCategory());
    }

    public function test_handles_liability_account(): void
    {
        $varianceResult = new VarianceResult(
            180000.00,
            200000.00,
            -20000.00,
            -10.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '2000',
            'Accounts Payable',
            $varianceResult,
            'Liability'
        );

        $this->assertSame('2000', $accountVariance->getAccountCode());
        $this->assertSame('Accounts Payable', $accountVariance->getAccountName());
        $this->assertSame('Liability', $accountVariance->getCategory());
    }

    public function test_handles_account_code_with_dashes(): void
    {
        $varianceResult = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );
        
        $accountVariance = new AccountVariance(
            '1000-001',
            'Cash - Operating',
            $varianceResult,
            'Asset'
        );

        $this->assertSame('1000-001', $accountVariance->getAccountCode());
    }

    public function test_handles_account_code_with_dots(): void
    {
        $varianceResult = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );
        
        $accountVariance = new AccountVariance(
            '1000.100.001',
            'Cash - Main Account',
            $varianceResult,
            'Asset'
        );

        $this->assertSame('1000.100.001', $accountVariance->getAccountCode());
    }

    public function test_handles_long_account_name(): void
    {
        $longAccountName = 'Very Long Account Name That Describes The Account Purpose In Detail Including All Relevant Information';
        $varianceResult = new VarianceResult(
            50000.00,
            50000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );
        
        $accountVariance = new AccountVariance(
            '9999',
            $longAccountName,
            $varianceResult,
            'Other'
        );

        $this->assertSame($longAccountName, $accountVariance->getAccountName());
    }

    public function test_handles_zero_variance(): void
    {
        $varianceResult = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );
        
        $accountVariance = new AccountVariance(
            '5500',
            'On Target Account',
            $varianceResult,
            'Expense'
        );

        $result = $accountVariance->getResult();
        $this->assertSame(0.00, $result->getVarianceAmount());
        $this->assertSame(VarianceType::NEUTRAL, $result->getType());
    }

    public function test_handles_large_favorable_variance(): void
    {
        $varianceResult = new VarianceResult(
            5000000.00,
            1000000.00,
            4000000.00,
            400.0,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '4200',
            'Extraordinary Revenue',
            $varianceResult,
            'Revenue'
        );

        $result = $accountVariance->getResult();
        $this->assertSame(4000000.00, $result->getVarianceAmount());
    }

    public function test_handles_large_unfavorable_variance(): void
    {
        $varianceResult = new VarianceResult(
            5000000.00,
            1000000.00,
            4000000.00,
            400.0,
            VarianceType::UNFAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '6500',
            'Extraordinary Expense',
            $varianceResult,
            'Expense'
        );

        $result = $accountVariance->getResult();
        $this->assertTrue($result->isUnfavorable());
    }

    public function test_category_equity(): void
    {
        $varianceResult = new VarianceResult(
            1000000.00,
            950000.00,
            50000.00,
            5.26,
            VarianceType::FAVORABLE
        );
        
        $accountVariance = new AccountVariance(
            '3000',
            'Retained Earnings',
            $varianceResult,
            'Equity'
        );

        $this->assertSame('Equity', $accountVariance->getCategory());
    }

    public function test_numeric_account_code(): void
    {
        $varianceResult = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );
        
        $accountVariance = new AccountVariance(
            '100',
            'Cash',
            $varianceResult,
            'Asset'
        );

        $this->assertSame('100', $accountVariance->getAccountCode());
    }

    public function test_alphanumeric_account_code(): void
    {
        $varianceResult = new VarianceResult(
            100000.00,
            100000.00,
            0.00,
            0.0,
            VarianceType::NEUTRAL
        );
        
        $accountVariance = new AccountVariance(
            'A1000',
            'Special Account',
            $varianceResult,
            'Asset'
        );

        $this->assertSame('A1000', $accountVariance->getAccountCode());
    }

    public function test_is_readonly(): void
    {
        $reflection = new \ReflectionClass(AccountVariance::class);
        
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_is_final(): void
    {
        $reflection = new \ReflectionClass(AccountVariance::class);
        
        $this->assertTrue($reflection->isFinal());
    }
}
