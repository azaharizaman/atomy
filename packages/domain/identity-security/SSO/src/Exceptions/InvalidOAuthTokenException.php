<?php

declare(strict_types=1);

namespace Nexus\SSO\Exceptions;

/**
 * Invalid OAuth token exception
 * 
 * Thrown when OAuth2/OIDC token validation fails
 */
final class InvalidOAuthTokenException extends SsoException
{
    public static function tokenExchangeFailed(string $error): self
    {
        return new self("OAuth token exchange failed: {$error}");
    }

    public static function invalidIdToken(string $reason): self
    {
        return new self("Invalid OIDC ID token: {$reason}");
    }

    public static function expiredToken(): self
    {
        return new self('OAuth access token has expired');
    }

    public static function invalidTokenResponse(): self
    {
        return new self('Invalid token response from OAuth provider');
    }
}
