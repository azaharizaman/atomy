<?php

declare(strict_types=1);

namespace Nexus\SSO\Exceptions;

/**
 * Invalid SAML assertion exception
 * 
 * Thrown when SAML assertion validation fails
 */
final class InvalidSamlAssertionException extends SsoException
{
    public static function invalidSignature(): self
    {
        return new self('SAML assertion has invalid signature');
    }

    public static function expired(): self
    {
        return new self('SAML assertion has expired');
    }

    public static function notYetValid(): self
    {
        return new self('SAML assertion is not yet valid');
    }

    public static function invalidAudience(string $expected, string $actual): self
    {
        return new self("SAML assertion audience mismatch: expected '{$expected}', got '{$actual}'");
    }

    public static function missingAttribute(string $attribute): self
    {
        return new self("Required SAML attribute '{$attribute}' is missing");
    }
}
