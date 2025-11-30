<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Providers;

use Nexus\SSO\Providers\OidcProvider;
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\Exceptions\SsoConfigurationException;
use Nexus\SSO\Exceptions\InvalidOAuthTokenException;
use PHPUnit\Framework\TestCase;

final class OidcProviderTest extends TestCase
{
    private OidcProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new OidcProvider();
    }

    public function test_it_returns_correct_name(): void
    {
        $this->assertSame('oidc', $this->provider->getName());
    }

    public function test_it_returns_correct_protocol(): void
    {
        $this->assertSame(SsoProtocol::OIDC, $this->provider->getProtocol());
    }

    public function test_it_generates_authorization_url_with_openid_scope(): void
    {
        $config = $this->createMockConfig();

        $authUrl = $this->provider->getAuthorizationUrl($config, 'test-state', ['scopes' => ['email', 'profile']]);

        $this->assertStringContainsString('client_id=test-client', $authUrl);
        $this->assertStringContainsString('state=test-state', $authUrl);
        $this->assertStringContainsString('scope=openid', $authUrl); // OIDC requires openid scope
    }

    public function test_it_validates_config_requires_issuer_url(): void
    {
        $this->expectException(SsoConfigurationException::class);
        $this->expectExceptionMessage('issuer_url');

        $invalidConfig = new SsoProviderConfig(
            providerName: 'oidc-test',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: '', // Empty issuer URL - THIS IS WHAT WE'RE TESTING
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([]),
            metadata: [
                // Provide OAuth2 endpoints so parent validation passes
                'authorization_endpoint' => 'https://example.com/authorize',
                'token_endpoint' => 'https://example.com/token',
                'userinfo_endpoint' => 'https://example.com/userinfo',
            ],
        );

        $this->provider->validateConfig($invalidConfig);
    }

    public function test_it_fetches_discovery_document(): void
    {
        $config = new SsoProviderConfig(
            providerName: 'oidc-test',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([]),
            metadata: [
                'mock_discovery_document' => [
                    'issuer' => 'https://example.com',
                    'authorization_endpoint' => 'https://example.com/oauth2/authorize',
                    'token_endpoint' => 'https://example.com/oauth2/token',
                    'userinfo_endpoint' => 'https://example.com/oauth2/userinfo',
                    'jwks_uri' => 'https://example.com/.well-known/jwks.json',
                ],
            ],
        );

        // Set test mode
        $GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT'] = [
            'issuer' => 'https://example.com',
            'authorization_endpoint' => 'https://example.com/oauth2/authorize',
            'token_endpoint' => 'https://example.com/oauth2/token',
            'userinfo_endpoint' => 'https://example.com/oauth2/userinfo',
            'jwks_uri' => 'https://example.com/.well-known/jwks.json',
        ];

        $discovery = $this->provider->getDiscoveryDocument('https://example.com');

        $this->assertArrayHasKey('issuer', $discovery);
        $this->assertArrayHasKey('authorization_endpoint', $discovery);
        $this->assertArrayHasKey('token_endpoint', $discovery);
        $this->assertArrayHasKey('jwks_uri', $discovery);

        unset($GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT']);
    }

    public function test_it_validates_id_token_successfully(): void
    {
        $mockIdToken = 'mock.id.token';

        $config = new SsoProviderConfig(
            providerName: 'oidc-test',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([]),
            metadata: [
                'mock_id_token_claims' => [
                    'iss' => 'https://example.com',
                    'sub' => 'user-123',
                    'aud' => 'test-client',
                    'exp' => 9999999999,
                    'iat' => 1700000000,
                    'email' => 'test@example.com',
                ],
            ],
        );

        $claims = $this->provider->parseIdToken($mockIdToken, $config);

        $this->assertArrayHasKey('sub', $claims);
        $this->assertArrayHasKey('email', $claims);
        $this->assertSame('user-123', $claims['sub']);
        $this->assertSame('test@example.com', $claims['email']);
    }

    public function test_it_throws_exception_for_expired_id_token(): void
    {
        $mockIdToken = 'expired.token';

        $config = new SsoProviderConfig(
            providerName: 'oidc-test',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([]),
            metadata: [
                'mock_id_token_claims' => [
                    'iss' => 'https://example.com',
                    'sub' => 'user-123',
                    'aud' => 'test-client',
                    'exp' => 1000000000, // Expired
                    'iat' => 999999999,
                ],
            ],
        );

        $this->expectException(InvalidOAuthTokenException::class);
        $this->expectExceptionMessage('expired');

        $this->provider->parseIdToken($mockIdToken, $config);
    }

    public function test_it_throws_exception_for_invalid_issuer(): void
    {
        $mockIdToken = 'invalid.issuer';

        $config = new SsoProviderConfig(
            providerName: 'oidc-test',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([]),
            metadata: [
                'mock_id_token_claims' => [
                    'iss' => 'https://evil.com', // Wrong issuer
                    'sub' => 'user-123',
                    'aud' => 'test-client',
                    'exp' => 9999999999,
                    'iat' => 1700000000,
                ],
            ],
        );

        $this->expectException(InvalidOAuthTokenException::class);
        $this->expectExceptionMessage('issuer');

        $this->provider->parseIdToken($mockIdToken, $config);
    }

    public function test_it_throws_exception_for_invalid_audience(): void
    {
        $mockIdToken = 'invalid.audience';

        $config = new SsoProviderConfig(
            providerName: 'oidc-test',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([]),
            metadata: [
                'mock_id_token_claims' => [
                    'iss' => 'https://example.com',
                    'sub' => 'user-123',
                    'aud' => 'wrong-client', // Wrong audience
                    'exp' => 9999999999,
                    'iat' => 1700000000,
                ],
            ],
        );

        $this->expectException(InvalidOAuthTokenException::class);
        $this->expectExceptionMessage('audience');

        $this->provider->parseIdToken($mockIdToken, $config);
    }

    public function test_it_handles_callback_with_id_token(): void
    {
        $config = new SsoProviderConfig(
            providerName: 'oidc-test',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([
                'sso_user_id' => 'sub',
                'email' => 'email',
                'display_name' => 'name',
            ]),
            metadata: [
                'authorization_endpoint' => 'https://example.com/authorize',
                'token_endpoint' => 'https://example.com/token',
                'userinfo_endpoint' => 'https://example.com/userinfo',
                'mock_token_response' => [
                    'access_token' => 'mock-access-token',
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'id_token' => 'mock.id.token',
                ],
                'mock_id_token_claims' => [
                    'iss' => 'https://example.com',
                    'sub' => 'user-123',
                    'aud' => 'test-client',
                    'exp' => 9999999999,
                    'iat' => 1700000000,
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ],
        );

        $userProfile = $this->provider->handleCallback($config, ['code' => 'mock_authorization_code']);

        $this->assertSame('user-123', $userProfile->ssoUserId);
        $this->assertSame('test@example.com', $userProfile->email);
        $this->assertSame('Test User', $userProfile->displayName);
    }

    private function createMockConfig(): SsoProviderConfig
    {
        return new SsoProviderConfig(
            providerName: 'oidc',
            protocol: SsoProtocol::OIDC,
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.example.com/callback',
            attributeMap: new AttributeMap([
                'sso_user_id' => 'sub',
                'email' => 'email',
            ]),
            scopes: ['openid', 'email', 'profile'],
            metadata: [
                'authorization_endpoint' => 'https://example.com/oauth2/authorize',
                'token_endpoint' => 'https://example.com/oauth2/token',
                'userinfo_endpoint' => 'https://example.com/oauth2/userinfo',
            ],
        );
    }
}
