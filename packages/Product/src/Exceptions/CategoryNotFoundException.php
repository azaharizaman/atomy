<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when a category is not found
 */
class CategoryNotFoundException extends ProductException
{
    public static function forId(string $id): self
    {
        return new self("Category with ID '{$id}' not found.");
    }

    public static function forCode(string $code): self
    {
        return new self("Category with code '{$code}' not found.");
    }
}
