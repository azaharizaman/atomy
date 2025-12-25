<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

use Nexus\PaymentRails\DTOs\RailSelectionCriteria;

/**
 * Exception thrown when no payment rail is eligible for selection.
 */
final class NoEligibleRailException extends PaymentRailException
{
    public static function forCriteria(RailSelectionCriteria $criteria): self
    {
        return new self(
            message: 'No eligible payment rail found for the given selection criteria',
            railType: null,
            context: [
                'criteria' => $criteria->toArray(),
            ],
        );
    }
}
