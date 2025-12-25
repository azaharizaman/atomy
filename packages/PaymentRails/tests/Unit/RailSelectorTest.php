<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\PaymentRailInterface;
use Nexus\PaymentRails\DTOs\RailSelectionCriteria;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Events\RailSelected;
use Nexus\PaymentRails\Exceptions\NoEligibleRailException;
use Nexus\PaymentRails\Services\RailSelector;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class RailSelectorTest extends TestCase
{
    public function test_select_throws_when_no_eligible_rails(): void
    {
        $achCapabilities = new RailCapabilities(
            railType: RailType::ACH,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 2,
        );

        $ach = $this->createRailMock(
            type: RailType::ACH,
            capabilities: $achCapabilities,
            isAvailable: false,
        );

        $selector = new RailSelector(rails: [$ach]);

        $criteria = new RailSelectionCriteria(
            amountCents: 1000,
            currency: 'USD',
            destinationCountry: 'US',
        );

        $this->expectException(NoEligibleRailException::class);

        $selector->select($criteria);
    }

    public function test_select_returns_best_scored_rail_and_dispatches_event(): void
    {
        $achCapabilities = new RailCapabilities(
            railType: RailType::ACH,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 2,
        );

        $wireCapabilities = new RailCapabilities(
            railType: RailType::WIRE,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 0,
            supportsRecurring: false,
            additionalCapabilities: ['is_real_time' => true],
        );

        $ach = $this->createRailMock(RailType::ACH, $achCapabilities, true);
        $wire = $this->createRailMock(RailType::WIRE, $wireCapabilities, true);

        $criteria = new RailSelectionCriteria(
            amountCents: 10_000_000, // $100,000 (high value)
            currency: 'USD',
            destinationCountry: 'US',
            urgency: 'standard',
            preferLowCost: false,
        );

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (object $event) use ($criteria): bool {
                self::assertInstanceOf(RailSelected::class, $event);
                self::assertSame(RailType::WIRE, $event->railType);
                self::assertSame($criteria, $event->criteria);
                self::assertIsFloat($event->score);
                self::assertGreaterThan(0.0, $event->score);

                return true;
            }))
            ->willReturnArgument(0);

        $selector = new RailSelector(rails: [$ach, $wire], eventDispatcher: $dispatcher);

        $selected = $selector->select($criteria);

        self::assertSame(RailType::WIRE, $selected->getRailType());
    }

    public function test_get_eligible_rails_filters_by_urgency_and_beneficiary_rules(): void
    {
        $wireCapabilities = new RailCapabilities(
            railType: RailType::WIRE,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 0,
            supportsRecurring: false,
            additionalCapabilities: ['is_real_time' => true],
        );

        $rtgsCapabilities = new RailCapabilities(
            railType: RailType::RTGS,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 0,
            supportsRecurring: false,
            additionalCapabilities: ['is_real_time' => true],
        );

        $virtualCardCapabilities = new RailCapabilities(
            railType: RailType::VIRTUAL_CARD,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 2,
        );

        $wire = $this->createRailMock(RailType::WIRE, $wireCapabilities, true);
        $rtgs = $this->createRailMock(RailType::RTGS, $rtgsCapabilities, true);
        $virtualCard = $this->createRailMock(RailType::VIRTUAL_CARD, $virtualCardCapabilities, true);

        $selector = new RailSelector(rails: [$wire, $rtgs, $virtualCard]);

        $criteria = new RailSelectionCriteria(
            amountCents: 2_000_000,
            currency: 'USD',
            destinationCountry: 'US',
            urgency: 'real-time',
            beneficiaryType: 'individual',
        );

        $eligible = $selector->getEligibleRails($criteria);

        $eligibleTypes = array_map(static fn (PaymentRailInterface $rail): string => $rail->getRailType()->value, $eligible);
        sort($eligibleTypes);

        self::assertSame(['rtgs', 'wire'], $eligibleTypes);
    }

    public function test_get_fastest_rail_prefers_urgent_and_excludes_slow_rails(): void
    {
        $achCapabilities = new RailCapabilities(
            railType: RailType::ACH,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 2,
        );

        $wireCapabilities = new RailCapabilities(
            railType: RailType::WIRE,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 0,
            supportsRecurring: false,
            additionalCapabilities: ['is_real_time' => true],
        );

        $ach = $this->createRailMock(RailType::ACH, $achCapabilities, true);
        $wire = $this->createRailMock(RailType::WIRE, $wireCapabilities, true);

        $selector = new RailSelector(rails: [$ach, $wire]);

        $criteria = new RailSelectionCriteria(
            amountCents: 2_000_000,
            currency: 'USD',
            destinationCountry: 'US',
            urgency: 'standard',
            preferLowCost: true,
        );

        $fastest = $selector->getFastestRail($criteria);

        self::assertSame(RailType::WIRE, $fastest->getRailType());
    }

    public function test_get_cheapest_rail_prefers_low_cost_when_multiple_rails_are_eligible(): void
    {
        $achCapabilities = new RailCapabilities(
            railType: RailType::ACH,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 1,
        );

        $wireCapabilities = new RailCapabilities(
            railType: RailType::WIRE,
            supportedCurrencies: ['USD'],
            typicalSettlementDays: 0,
            supportsRecurring: false,
            additionalCapabilities: ['is_real_time' => true],
        );

        $ach = $this->createRailMock(RailType::ACH, $achCapabilities, true);
        $wire = $this->createRailMock(RailType::WIRE, $wireCapabilities, true);

        $selector = new RailSelector(rails: [$ach, $wire]);

        $criteria = new RailSelectionCriteria(
            amountCents: 2_000_000,
            currency: 'USD',
            destinationCountry: 'US',
        );

        $cheapest = $selector->getCheapestRail($criteria);

        self::assertSame(RailType::ACH, $cheapest->getRailType());
    }

    public function test_fit_score_penalizes_amounts_close_to_maximum_limit(): void
    {
        $capabilitiesWithMax = new RailCapabilities(
            railType: RailType::ACH,
            supportedCurrencies: ['USD'],
            minimumAmount: Money::of(0.01, 'USD'),
            maximumAmount: new Money(10_000, 'USD'),
            typicalSettlementDays: 2,
            additionalCapabilities: ['supports_refunds' => true],
        );

        $criteriaFar = new RailSelectionCriteria(
            amountCents: 5_000,
            currency: 'USD',
            destinationCountry: 'US',
        );

        $criteriaNear = new RailSelectionCriteria(
            amountCents: 9_500,
            currency: 'USD',
            destinationCountry: 'US',
        );

        $selector = new RailSelector(rails: []);

        $fitScoreMethod = new \ReflectionMethod(RailSelector::class, 'calculateFitScore');
        $fitScoreMethod->setAccessible(true);

        $scoreFar = $fitScoreMethod->invoke($selector, $capabilitiesWithMax, $criteriaFar);
        $scoreNear = $fitScoreMethod->invoke($selector, $capabilitiesWithMax, $criteriaNear);

        self::assertIsFloat($scoreFar);
        self::assertIsFloat($scoreNear);
        self::assertGreaterThan($scoreNear, $scoreFar);
    }

    private function createRailMock(
        RailType $type,
        RailCapabilities $capabilities,
        bool $isAvailable,
    ): PaymentRailInterface {
        $rail = $this->createMock(PaymentRailInterface::class);

        $rail->method('getRailType')->willReturn($type);
        $rail->method('isAvailable')->willReturn($isAvailable);
        $rail->method('getCapabilities')->willReturn($capabilities);

        return $rail;
    }
}
