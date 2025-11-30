<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * OpenID Connect (OIDC) provider interface
 * 
 * Extends OAuth2 with JWT ID token validation and discovery document support
 */
interface OidcProviderInterface extends OAuthProviderInterface
{
    /**
     * Fetch and parse OIDC discovery document (.well-known/openid-configuration)
     * 
     * @param string $issuerUrl OIDC issuer URL (e.g., https://accounts.google.com)
     * @return array<string, mixed> Discovery document with endpoints
     * @throws \Nexus\SSO\Exceptions\InvalidSsoConfigException If discovery fails
     */
    public function getDiscoveryDocument(string $issuerUrl): array;

    /**
     * Parse and validate JWT ID token claims
     * 
     * Validates:
     * - Token signature (using JWKS from discovery document)
     * - Issuer (iss) claim matches expected issuer
     * - Audience (aud) claim matches client ID
     * - Expiration (exp) claim is in the future
     * - Issued at (iat) claim is in the past
     * - Not before (nbf) claim (if present)
     * 
     * @param string $idToken JWT ID token from OIDC provider
     * @param SsoProviderConfig $config Provider configuration
     * @return array<string, mixed> Validated ID token claims
     * @throws \Nexus\SSO\Exceptions\InvalidOAuthTokenException If validation fails
     */
    public function parseIdToken(string $idToken, SsoProviderConfig $config): array;

    /**
     * Get JSON Web Key Set (JWKS) from discovery document
     * 
     * @param string $jwksUri JWKS URI from discovery document
     * @return array<string, mixed> JWKS with public keys
     */
    public function getJwks(string $jwksUri): array;
}
