<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * Invalid Tax Code Exception
 * 
 * Thrown when tax code format is invalid.
 */
final class InvalidTaxCodeException extends \InvalidArgumentException
{
    public function __construct(
        private readonly string $taxCode,
        string $reason = 'Tax code format is invalid',
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "Invalid tax code '%s': %s",
            $taxCode,
            $reason
        );

        parent::__construct($message, 0, $previous);
    }

    public function getTaxCode(): string
    {
        return $this->taxCode;
    }

    public function getContext(): array
    {
        return [
            'tax_code' => $this->taxCode,
        ];
    }
}
