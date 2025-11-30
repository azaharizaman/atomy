<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Providers;

use Nexus\SSO\Providers\Saml2Provider;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\SamlAssertion;
use Nexus\SSO\Exceptions\InvalidSamlAssertionException;
use Nexus\SSO\Exceptions\SsoConfigurationException;
use PHPUnit\Framework\TestCase;

final class Saml2ProviderTest extends TestCase
{
    private Saml2Provider $provider;

    protected function setUp(): void
    {
        $this->provider = new Saml2Provider();
    }

    public function test_it_returns_correct_name(): void
    {
        $this->assertSame('saml2', $this->provider->getName());
    }

    public function test_it_returns_correct_protocol(): void
    {
        $this->assertSame(SsoProtocol::SAML2, $this->provider->getProtocol());
    }

    public function test_it_generates_authorization_url(): void
    {
        $config = $this->createMockConfig();
        $state = 'random_state_token';

        $url = $this->provider->getAuthorizationUrl($config, $state, []);

        $this->assertStringContainsString('SAMLRequest=', $url);
        $this->assertStringContainsString('idp.example.com', $url);
    }

    public function test_it_generates_sp_metadata(): void
    {
        $config = $this->createMockConfig();

        $metadata = $this->provider->getSpMetadata($config);

        $this->assertStringContainsString('<?xml', $metadata);
        $this->assertStringContainsString('EntityDescriptor', $metadata);
        $this->assertStringContainsString('https://sp.example.com/metadata', $metadata);
    }

    public function test_it_validates_configuration(): void
    {
        $config = $this->createMockConfig();

        $this->provider->validateConfig($config);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_it_throws_exception_for_invalid_configuration(): void
    {
        $this->expectException(SsoConfigurationException::class);

        $invalidConfig = new SsoProviderConfig(
            providerName: 'saml2',
            protocol: SsoProtocol::SAML2,
            clientId: '',
            clientSecret: '',
            discoveryUrl: '',
            redirectUri: '',
            attributeMap: new AttributeMap([]),
            enabled: true,
            scopes: [],
            metadata: [],
        );

        $this->provider->validateConfig($invalidConfig);
    }

    public function test_it_parses_saml_assertion_from_callback(): void
    {
        $config = $this->createMockConfig();
        
        // Mock SAML response (base64 encoded)
        $callbackData = [
            'SAMLResponse' => $this->getMockSamlResponse(),
        ];

        $assertion = $this->provider->parseSamlAssertion($callbackData);

        $this->assertInstanceOf(SamlAssertion::class, $assertion);
        $this->assertNotEmpty($assertion->nameId);
        $this->assertNotEmpty($assertion->sessionIndex);
    }

    public function test_it_handles_callback_and_extracts_user_profile(): void
    {
        $config = $this->createMockConfig();
        
        $callbackData = [
            'SAMLResponse' => $this->getMockSamlResponse(),
        ];

        $profile = $this->provider->handleCallback($config, $callbackData);

        $this->assertNotEmpty($profile->ssoUserId);
        $this->assertNotEmpty($profile->email);
    }

    public function test_it_throws_exception_for_expired_saml_assertion(): void
    {
        $this->expectException(InvalidSamlAssertionException::class);
        $this->expectExceptionMessage('expired');

        $config = $this->createMockConfig();
        
        $callbackData = [
            'SAMLResponse' => $this->getExpiredSamlResponse(),
        ];

        $this->provider->handleCallback($config, $callbackData);
    }

    public function test_it_generates_logout_url(): void
    {
        $config = $this->createMockConfig();
        $sessionId = 'test_session_123';

        $logoutUrl = $this->provider->getLogoutUrl($config, $sessionId);

        $this->assertNotNull($logoutUrl);
        $this->assertStringContainsString('idp.example.com', $logoutUrl);
        $this->assertStringContainsString('SAMLRequest=', $logoutUrl);
    }

    private function createMockConfig(): SsoProviderConfig
    {
        return new SsoProviderConfig(
            providerName: 'saml2',
            protocol: SsoProtocol::SAML2,
            clientId: 'https://sp.example.com/metadata', // SP Entity ID
            clientSecret: '', // Not used for SAML
            discoveryUrl: 'https://idp.example.com/metadata', // IdP Metadata URL
            redirectUri: 'https://sp.example.com/sso/callback',
            attributeMap: new AttributeMap([
                'sso_user_id' => 'urn:oid:0.9.2342.19200300.100.1.1',
                'email' => 'urn:oid:0.9.2342.19200300.100.1.3',
                'first_name' => 'urn:oid:2.5.4.42',
                'last_name' => 'urn:oid:2.5.4.4',
            ]),
            enabled: true,
            scopes: [],
            metadata: [
                'sp_entity_id' => 'https://sp.example.com/metadata',
                'idp_entity_id' => 'https://idp.example.com',
                'idp_sso_url' => 'https://idp.example.com/sso',
                'idp_slo_url' => 'https://idp.example.com/slo',
                'idp_certificate' => 'MIIDXTCCAkWgAwIBAgIJAKMa...',
                // Omit sp_private_key and sp_certificate to disable signing in tests
            ],
        );
    }

    private function getMockSamlResponse(): string
    {
        // This would be a real base64-encoded SAML response in production
        // For testing, we'll create a minimal valid response
        return base64_encode('mock_saml_response');
    }

    private function getExpiredSamlResponse(): string
    {
        return base64_encode('expired_saml_response');
    }
}
