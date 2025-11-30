<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when variant generation would exceed configured limits
 */
class VariantExplosionException extends ProductException
{
    public static function exceededLimit(int $proposedCount, int $maxAllowed): self
    {
        return new self(
            "Cannot generate {$proposedCount} variants. Maximum allowed is {$maxAllowed}. " .
            "Consider reducing the number of attributes or attribute values."
        );
    }

    public static function tooManyAttributes(int $attributeCount, int $maxAttributes): self
    {
        return new self(
            "Cannot use {$attributeCount} attributes for variant generation. Maximum allowed is {$maxAttributes}."
        );
    }
}
