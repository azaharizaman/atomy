<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when a product template is not found
 */
class ProductTemplateNotFoundException extends ProductException
{
    public static function forId(string $id): self
    {
        return new self("Product template with ID '{$id}' not found.");
    }

    public static function forCode(string $code): self
    {
        return new self("Product template with code '{$code}' not found.");
    }
}
