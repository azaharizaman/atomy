<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxComponent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WithholdingTaxComponent::class)]
final class WithholdingTaxComponentTest extends TestCase
{
    #[Test]
    public function royalty_creates_royalty_component(): void
    {
        $component = WithholdingTaxComponent::royalty(
            rate: 10.0,
            amount: Money::of(1000, 'MYR'),
            authority: 'LHDN',
        );

        $this->assertEquals('royalty', $component->type);
        $this->assertEquals(10.0, $component->rate);
        $this->assertEquals(1000_00, $component->amount->getAmountInCents());
        $this->assertEquals('LHDN', $component->authority);
    }

    #[Test]
    public function serviceFee_creates_service_fee_component(): void
    {
        $component = WithholdingTaxComponent::serviceFee(
            rate: 10.0,
            amount: Money::of(500, 'MYR'),
            authority: 'LHDN',
        );

        $this->assertEquals('service_fee', $component->type);
        $this->assertEquals(10.0, $component->rate);
        $this->assertEquals(500_00, $component->amount->getAmountInCents());
    }

    #[Test]
    public function interest_creates_interest_component(): void
    {
        $component = WithholdingTaxComponent::interest(
            rate: 15.0,
            amount: Money::of(750, 'MYR'),
            authority: 'LHDN',
        );

        $this->assertEquals('interest', $component->type);
        $this->assertEquals(15.0, $component->rate);
    }

    #[Test]
    public function dividend_creates_dividend_component(): void
    {
        $component = WithholdingTaxComponent::dividend(
            rate: 0.0,
            amount: Money::of(0, 'MYR'),
            authority: 'LHDN',
        );

        $this->assertEquals('dividend', $component->type);
        // Malaysia has single-tier dividend system (no withholding)
        $this->assertEquals(0.0, $component->rate);
    }

    #[Test]
    public function contractor_creates_contractor_component(): void
    {
        $component = WithholdingTaxComponent::contractor(
            rate: 10.0,
            amount: Money::of(2000, 'MYR'),
            authority: 'LHDN',
        );

        $this->assertEquals('contractor', $component->type);
        $this->assertEquals(10.0, $component->rate);
        $this->assertEquals(2000_00, $component->amount->getAmountInCents());
    }

    #[Test]
    public function getDescription_returns_readable_string(): void
    {
        $component = WithholdingTaxComponent::royalty(
            rate: 10.0,
            amount: Money::of(1000, 'MYR'),
            authority: 'LHDN',
        );

        $description = $component->getDescription();

        $this->assertStringContainsString('royalty', strtolower($description));
        $this->assertStringContainsString('10', $description);
    }

    #[Test]
    public function isZeroRated_returns_true_when_rate_is_zero(): void
    {
        $component = WithholdingTaxComponent::dividend(
            rate: 0.0,
            amount: Money::of(0, 'MYR'),
            authority: 'LHDN',
        );

        $this->assertTrue($component->isZeroRated());
    }

    #[Test]
    public function isZeroRated_returns_false_when_rate_is_positive(): void
    {
        $component = WithholdingTaxComponent::royalty(
            rate: 10.0,
            amount: Money::of(1000, 'MYR'),
            authority: 'LHDN',
        );

        $this->assertFalse($component->isZeroRated());
    }

    #[Test]
    public function getTaxCode_returns_appropriate_code(): void
    {
        $royalty = WithholdingTaxComponent::royalty(10.0, Money::of(100, 'MYR'), 'LHDN');
        $service = WithholdingTaxComponent::serviceFee(10.0, Money::of(100, 'MYR'), 'LHDN');
        $interest = WithholdingTaxComponent::interest(15.0, Money::of(100, 'MYR'), 'LHDN');
        $contractor = WithholdingTaxComponent::contractor(10.0, Money::of(100, 'MYR'), 'LHDN');

        $this->assertStringContainsString('WHT', strtoupper($royalty->getTaxCode()));
        $this->assertStringContainsString('WHT', strtoupper($service->getTaxCode()));
        $this->assertStringContainsString('WHT', strtoupper($interest->getTaxCode()));
        $this->assertStringContainsString('WHT', strtoupper($contractor->getTaxCode()));
    }

    #[Test]
    public function toArray_returns_serializable_data(): void
    {
        $component = WithholdingTaxComponent::royalty(
            rate: 10.0,
            amount: Money::of(1000, 'MYR'),
            authority: 'LHDN',
        );

        $array = $component->toArray();

        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('rate', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('authority', $array);
        $this->assertEquals('royalty', $array['type']);
        $this->assertEquals(10.0, $array['rate']);
    }

    #[Test]
    public function getRemittanceDueDate_returns_expected_date(): void
    {
        $component = WithholdingTaxComponent::serviceFee(
            rate: 10.0,
            amount: Money::of(500, 'MYR'),
            authority: 'LHDN',
        );

        $paymentDate = new \DateTimeImmutable('2024-01-15');
        $dueDate = $component->getRemittanceDueDate($paymentDate);

        // Malaysian WHT must be remitted within 1 month
        $this->assertGreaterThan($paymentDate, $dueDate);
        // Should be at most ~30-31 days later
        $diff = $paymentDate->diff($dueDate);
        $this->assertLessThanOrEqual(31, $diff->days);
    }
}
