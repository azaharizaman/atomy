<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TreasuryPolicyDataTest extends TestCase
{
    public function test_creates_with_required_fields(): void
    {
        $data = new TreasuryPolicyData(
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD')
        );

        $this->assertEquals('Test Policy', $data->name);
        $this->assertEquals(10000, $data->minimumCashBalance->getAmount());
        $this->assertEquals(50000, $data->maximumSingleTransaction->getAmount());
        $this->assertEquals(5000, $data->approvalThreshold->getAmount());
        $this->assertTrue($data->approvalRequired);
        $this->assertNull($data->description);
        $this->assertNull($data->effectiveFrom);
        $this->assertNull($data->effectiveTo);
    }

    public function test_creates_with_all_fields(): void
    {
        $from = new DateTimeImmutable('2024-01-01');
        $to = new DateTimeImmutable('2024-12-31');

        $data = new TreasuryPolicyData(
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: false,
            description: 'Test description',
            effectiveFrom: $from,
            effectiveTo: $to
        );

        $this->assertEquals('Test description', $data->description);
        $this->assertFalse($data->approvalRequired);
        $this->assertEquals($from, $data->effectiveFrom);
        $this->assertEquals($to, $data->effectiveTo);
    }

    public function test_throws_exception_on_currency_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All monetary values must be in the same currency');

        new TreasuryPolicyData(
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'EUR'),
            approvalThreshold: Money::of(5000, 'USD')
        );
    }

    public function test_throws_exception_on_approval_threshold_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TreasuryPolicyData(
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'USD'),
            approvalThreshold: Money::of(5000, 'EUR')
        );
    }

    public function test_from_array_creates_data(): void
    {
        $data = TreasuryPolicyData::fromArray([
            'name' => 'Test Policy',
            'minimum_cash_balance' => 10000,
            'maximum_single_transaction' => 50000,
            'approval_threshold' => 5000,
            'currency' => 'USD',
            'approval_required' => true,
            'description' => 'Test',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
        ]);

        $this->assertEquals('Test Policy', $data->name);
        $this->assertEquals('Test', $data->description);
    }

    public function test_from_array_with_camel_case(): void
    {
        $data = TreasuryPolicyData::fromArray([
            'name' => 'Test Policy',
            'minimumCashBalance' => 10000,
            'maximumSingleTransaction' => 50000,
            'approvalThreshold' => 5000,
            'currency' => 'USD',
        ]);

        $this->assertEquals('Test Policy', $data->name);
        $this->assertEquals(10000, $data->minimumCashBalance->getAmount());
    }

    public function test_to_array_returns_array(): void
    {
        $data = new TreasuryPolicyData(
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD')
        );

        $array = $data->toArray();

        $this->assertEquals('Test Policy', $array['name']);
        $this->assertTrue($array['approvalRequired']);
        $this->assertIsArray($array['minimumCashBalance']);
    }

    public function test_get_currency_returns_currency(): void
    {
        $data = new TreasuryPolicyData(
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'EUR'),
            maximumSingleTransaction: Money::of(50000, 'EUR'),
            approvalThreshold: Money::of(5000, 'EUR')
        );

        $this->assertEquals('EUR', $data->getCurrency());
    }
}
