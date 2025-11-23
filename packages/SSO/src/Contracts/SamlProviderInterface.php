<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SamlAssertion;
use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * SAML 2.0 provider interface
 * 
 * Extends base provider with SAML-specific operations
 */
interface SamlProviderInterface extends SsoProviderInterface
{
    /**
     * Get Service Provider (SP) metadata XML
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @return string SAML metadata XML
     */
    public function getSpMetadata(SsoProviderConfig $config): string;

    /**
     * Parse SAML assertion from callback
     * 
     * @param array<string, mixed> $callbackData SAML response data
     * @return SamlAssertion Validated SAML assertion
     * @throws \Nexus\SSO\Exceptions\InvalidSamlAssertionException
     */
    public function parseSamlAssertion(array $callbackData): SamlAssertion;

    /**
     * Validate SAML signature
     * 
     * @param string $samlResponse Base64-encoded SAML response
     * @param string $certificate IdP X.509 certificate
     * @throws \Nexus\SSO\Exceptions\InvalidSamlAssertionException
     */
    public function validateSignature(string $samlResponse, string $certificate): void;
}
