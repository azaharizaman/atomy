<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\DTOs\RailSelectionCriteria;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Events\RailSelected;
use PHPUnit\Framework\TestCase;

final class RailSelectedEventTest extends TestCase
{
    public function test_to_array_contains_expected_fields(): void
    {
        $criteria = new RailSelectionCriteria(
            amountCents: 2500,
            currency: 'USD',
            destinationCountry: 'US',
            urgency: 'standard',
        );

        $event = new RailSelected(
            railType: RailType::ACH,
            criteria: $criteria,
            score: 88.5,
        );

        $data = $event->toArray();

        self::assertSame('rail.selected', $data['event_name']);
        self::assertSame('ach', $data['rail_type']);
        self::assertSame(88.5, $data['score']);
        self::assertIsString($data['occurred_at']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $data['occurred_at']);
        self::assertSame(2500, $data['criteria']['amount_cents']);
    }
}
