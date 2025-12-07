<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception when tolerance limits are exceeded.
 */
class ToleranceExceededException extends MatchingException
{
    /**
     * Create exception for price tolerance exceeded.
     */
    public static function priceToleranceExceeded(
        string $lineId,
        int $expectedPriceCents,
        int $actualPriceCents,
        float $variancePercent,
        float $tolerancePercent
    ): self {
        return new self(
            sprintf(
                'Line %s: Price variance %.2f%% exceeds tolerance %.2f%%. ' .
                'Expected: %d cents, Actual: %d cents',
                $lineId,
                $variancePercent,
                $tolerancePercent,
                $expectedPriceCents,
                $actualPriceCents
            )
        );
    }

    /**
     * Create exception for quantity tolerance exceeded.
     */
    public static function quantityToleranceExceeded(
        string $lineId,
        float $expectedQuantity,
        float $actualQuantity,
        float $variancePercent,
        float $tolerancePercent
    ): self {
        return new self(
            sprintf(
                'Line %s: Quantity variance %.2f%% exceeds tolerance %.2f%%. ' .
                'Expected: %.2f, Actual: %.2f',
                $lineId,
                $variancePercent,
                $tolerancePercent,
                $expectedQuantity,
                $actualQuantity
            )
        );
    }

    /**
     * Create exception for amount tolerance exceeded.
     */
    public static function amountToleranceExceeded(
        int $expectedAmountCents,
        int $actualAmountCents,
        float $variancePercent,
        float $tolerancePercent
    ): self {
        return new self(
            sprintf(
                'Total amount variance %.2f%% exceeds tolerance %.2f%%. ' .
                'Expected: %d cents, Actual: %d cents',
                $variancePercent,
                $tolerancePercent,
                $expectedAmountCents,
                $actualAmountCents
            )
        );
    }
}
