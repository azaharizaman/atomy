<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

use Nexus\Tax\Enums\TaxType;

/**
 * Reverse Charge Not Allowed Exception
 * 
 * Thrown when reverse charge is requested for a tax type that doesn't support it.
 */
final class ReverseChargeNotAllowedException extends \RuntimeException
{
    public function __construct(
        private readonly TaxType $taxType,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "Reverse charge is not allowed for tax type '%s'",
            $taxType->value
        );

        parent::__construct($message, 0, $previous);
    }

    public function getTaxType(): TaxType
    {
        return $this->taxType;
    }

    public function getContext(): array
    {
        return [
            'tax_type' => $this->taxType->value,
            'tax_type_label' => $this->taxType->label(),
        ];
    }
}
