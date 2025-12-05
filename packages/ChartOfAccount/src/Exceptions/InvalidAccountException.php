<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Exceptions;

/**
 * Exception thrown when account validation fails.
 */
class InvalidAccountException extends ChartOfAccountException
{
    /**
     * Create exception for invalid account code format.
     *
     * @param string $code The invalid code
     * @param string $reason Reason for invalidity
     */
    public static function invalidCode(string $code, string $reason): self
    {
        return new self(
            sprintf('Invalid account code "%s": %s', $code, $reason)
        );
    }

    /**
     * Create exception for invalid parent-child relationship.
     *
     * @param string $parentId Parent account ID
     * @param string $childType Child account type
     */
    public static function invalidParentChild(string $parentId, string $childType): self
    {
        return new self(
            sprintf(
                'Cannot create %s account as child of account %s: incompatible types',
                $childType,
                $parentId
            )
        );
    }

    /**
     * Create exception for invalid type change.
     *
     * @param string $id Account ID
     * @param string $currentType Current type
     * @param string $newType Attempted new type
     */
    public static function typeChangeNotAllowed(string $id, string $currentType, string $newType): self
    {
        return new self(
            sprintf(
                'Cannot change account %s type from %s to %s',
                $id,
                $currentType,
                $newType
            )
        );
    }

    /**
     * Create exception for header account change with children.
     *
     * @param string $id Account ID
     */
    public static function cannotChangeHeaderWithChildren(string $id): self
    {
        return new self(
            sprintf('Cannot change header status of account %s: account has children', $id)
        );
    }

    /**
     * Create exception for missing required field.
     *
     * @param string $field Field name
     */
    public static function missingField(string $field): self
    {
        return new self(
            sprintf('Missing required field: %s', $field)
        );
    }

    /**
     * Create exception for parent account not being a header.
     *
     * @param string $parentId Parent account ID
     */
    public static function parentMustBeHeader(string $parentId): self
    {
        return new self(
            sprintf('Parent account %s must be a header account', $parentId)
        );
    }
}
