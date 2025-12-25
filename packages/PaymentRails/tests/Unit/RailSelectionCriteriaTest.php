<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\DTOs\RailSelectionCriteria;
use Nexus\PaymentRails\Enums\RailType;
use PHPUnit\Framework\TestCase;

final class RailSelectionCriteriaTest extends TestCase
{
    public function test_to_array_includes_defaults_and_preferred_rail_value(): void
    {
        $criteria = new RailSelectionCriteria(
            amountCents: 123,
            currency: 'USD',
            destinationCountry: 'US',
            preferredRail: RailType::ACH,
        );

        $data = $criteria->toArray();

        self::assertSame(123, $data['amount_cents']);
        self::assertSame('USD', $data['currency']);
        self::assertSame('US', $data['destination_country']);
        self::assertSame('standard', $data['urgency']);
        self::assertTrue($data['prefer_low_cost']);
        self::assertFalse($data['is_international']);
        self::assertFalse($data['requires_recurring']);
        self::assertSame('individual', $data['beneficiary_type']);
        self::assertSame('ach', $data['preferred_rail']);
    }
}
