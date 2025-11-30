<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Providers;

use Nexus\SSO\Providers\OAuth2Provider;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\OAuthToken;
use Nexus\SSO\Exceptions\InvalidOAuthTokenException;
use Nexus\SSO\Exceptions\SsoConfigurationException;
use PHPUnit\Framework\TestCase;

final class OAuth2ProviderTest extends TestCase
{
    private OAuth2Provider $provider;

    protected function setUp(): void
    {
        $this->provider = new OAuth2Provider();
    }

    public function test_it_returns_correct_name(): void
    {
        $this->assertSame('oauth2', $this->provider->getName());
    }

    public function test_it_returns_correct_protocol(): void
    {
        $this->assertSame(SsoProtocol::OAuth2, $this->provider->getProtocol());
    }

    public function test_it_generates_authorization_url(): void
    {
        $config = $this->createMockConfig();
        $state = 'random_state_token';

        $url = $this->provider->getAuthorizationUrl($config, $state, []);

        $this->assertStringContainsString('client_id=', $url);
        $this->assertStringContainsString('state=' . $state, $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=', $url);
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
            providerName: 'oauth2',
            protocol: SsoProtocol::OAuth2,
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

    public function test_it_exchanges_authorization_code_for_token(): void
    {
        $config = $this->createMockConfig();
        $code = 'mock_authorization_code';

        $token = $this->provider->getAccessToken($config, $code);

        $this->assertInstanceOf(OAuthToken::class, $token);
        $this->assertNotEmpty($token->accessToken);
        $this->assertSame('Bearer', $token->tokenType);
    }

    public function test_it_gets_user_info_with_access_token(): void
    {
        $config = $this->createMockConfig();
        $accessToken = 'mock_access_token';

        $userInfo = $this->provider->getUserInfo($config, $accessToken);

        $this->assertIsArray($userInfo);
        $this->assertArrayHasKey('sub', $userInfo);
        $this->assertArrayHasKey('email', $userInfo);
    }

    public function test_it_handles_callback_and_extracts_user_profile(): void
    {
        $config = $this->createMockConfig();
        
        $callbackData = [
            'code' => 'mock_authorization_code',
        ];

        $profile = $this->provider->handleCallback($config, $callbackData);

        $this->assertNotEmpty($profile->ssoUserId);
        $this->assertNotEmpty($profile->email);
    }

    public function test_it_refreshes_access_token(): void
    {
        $config = $this->createMockConfig();
        $refreshToken = 'mock_refresh_token';

        $newToken = $this->provider->refreshToken($config, $refreshToken);

        $this->assertInstanceOf(OAuthToken::class, $newToken);
        $this->assertNotEmpty($newToken->accessToken);
    }

    public function test_it_returns_null_for_logout_url(): void
    {
        $config = $this->createMockConfig();
        $sessionId = 'test_session_123';

        $logoutUrl = $this->provider->getLogoutUrl($config, $sessionId);

        // OAuth2 doesn't have standard logout mechanism
        $this->assertNull($logoutUrl);
    }

    private function createMockConfig(): SsoProviderConfig
    {
        return new SsoProviderConfig(
            providerName: 'oauth2',
            protocol: SsoProtocol::OAuth2,
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            discoveryUrl: 'https://oauth.example.com',
            redirectUri: 'https://sp.example.com/oauth/callback',
            attributeMap: new AttributeMap([
                'sso_user_id' => 'sub',
                'email' => 'email',
                'first_name' => 'given_name',
                'last_name' => 'family_name',
            ]),
            enabled: true,
            scopes: ['openid', 'profile', 'email'],
            metadata: [
                'authorization_endpoint' => 'https://oauth.example.com/authorize',
                'token_endpoint' => 'https://oauth.example.com/token',
                'userinfo_endpoint' => 'https://oauth.example.com/userinfo',
            ],
        );
    }
}
