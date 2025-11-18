<?php

declare(strict_types=1);

namespace Nexus\Accounting\Exceptions;

/**
 * Exception thrown when compliance validation fails.
 */
final class ComplianceViolationException extends \RuntimeException
{
    public static function forStandard(string $standard, array $violations): self
    {
        $violationList = implode('; ', $violations);
        return new self(
            "Statement violates {$standard} compliance: {$violationList}"
        );
    }

    public static function missingDisclosure(string $standard, string $disclosure): self
    {
        return new self(
            "Missing required disclosure for {$standard}: {$disclosure}"
        );
    }

    public static function invalidFormat(string $standard, string $element): self
    {
        return new self(
            "Invalid format for {$standard}: {$element} does not meet standard requirements"
        );
    }

    public static function incompatibleStandard(string $requestedStandard, string $entityStandard): self
    {
        return new self(
            "Entity uses {$entityStandard} but statement requested with {$requestedStandard}"
        );
    }
}
