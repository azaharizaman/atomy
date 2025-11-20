<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when a circular reference is detected in category hierarchy
 */
class CircularCategoryReferenceException extends ProductException
{
    public static function detected(string $categoryId, string $proposedParentId): self
    {
        return new self(
            "Cannot set category '{$proposedParentId}' as parent of category '{$categoryId}'. " .
            "This would create a circular reference in the category hierarchy."
        );
    }

    public static function selfReference(string $categoryId): self
    {
        return new self("Category '{$categoryId}' cannot be its own parent.");
    }
}
