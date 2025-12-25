<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\DTOs\RailSelectionCriteria;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\Exceptions\AchValidationException;
use Nexus\PaymentRails\Exceptions\NoEligibleRailException;
use Nexus\PaymentRails\Exceptions\PaymentRailException;
use PHPUnit\Framework\TestCase;

final class PaymentRailExceptionTest extends TestCase
{
    public function test_payment_rail_exception_rail_unavailable_sets_rail_type_and_context(): void
    {
        $e = PaymentRailException::railUnavailable('ACH', 'maintenance');

        self::assertSame("Payment rail 'ACH' is currently unavailable: maintenance", $e->getMessage());
        self::assertSame('ACH', $e->getRailType());
        self::assertSame(['reason' => 'maintenance'], $e->getContext());
    }

    public function test_ach_validation_exception_multiple_errors_includes_errors_in_context(): void
    {
        $e = AchValidationException::multipleErrors(['a', 'b']);

        self::assertSame('ACH', $e->getRailType());
        self::assertSame(['a', 'b'], $e->getErrors());
        self::assertSame(['errors' => ['a', 'b']], $e->getContext());
    }

    public function test_no_eligible_rail_exception_includes_criteria_context(): void
    {
        $criteria = new RailSelectionCriteria(
            amountCents: 100,
            currency: 'USD',
            destinationCountry: 'US',
            preferredRail: RailType::ACH,
        );

        $e = NoEligibleRailException::forCriteria($criteria);

        self::assertSame('No eligible payment rail found for the given selection criteria', $e->getMessage());
        self::assertNull($e->getRailType());

        $context = $e->getContext();
        self::assertArrayHasKey('criteria', $context);
        self::assertSame('USD', $context['criteria']['currency']);
        self::assertSame('ach', $context['criteria']['preferred_rail']);
    }

    public function test_ach_validation_exception_invalid_sec_code_mentions_reason(): void
    {
        $e = AchValidationException::invalidSecCode(SecCode::CCD, 'Reason.');

        self::assertStringContainsString('SEC code CCD cannot be used: Reason.', $e->getMessage());
        self::assertSame(['Reason.'], $e->getErrors());
    }
}
