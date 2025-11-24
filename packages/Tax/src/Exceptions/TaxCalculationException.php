<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

use Nexus\Tax\ValueObjects\TaxContext;

/**
 * Tax Calculation Exception
 * 
 * Thrown when tax calculation fails for any reason.
 */
final class TaxCalculationException extends \RuntimeException
{
    public function __construct(
        private readonly TaxContext $context,
        string $reason,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "Tax calculation failed for transaction '%s': %s",
            $context->transactionId,
            $reason
        );

        parent::__construct($message, 0, $previous);
    }

    public function getContext(): TaxContext
    {
        return $this->context;
    }

    public function getContextArray(): array
    {
        return $this->context->toArray();
    }
}
