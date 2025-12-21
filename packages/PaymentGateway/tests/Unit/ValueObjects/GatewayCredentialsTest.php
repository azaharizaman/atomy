<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Exceptions\InvalidCredentialsException;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GatewayCredentials::class)]
final class GatewayCredentialsTest extends TestCase
{
    #[Test]
    public function it_creates_credentials_with_minimal_parameters(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_test_123',
        );

        $this->assertSame(GatewayProvider::STRIPE, $credentials->provider);
        $this->assertSame('sk_test_123', $credentials->apiKey);
        $this->assertNull($credentials->apiSecret);
        $this->assertNull($credentials->merchantId);
        $this->assertNull($credentials->webhookSecret);
        $this->assertFalse($credentials->sandboxMode);
        $this->assertEmpty($credentials->additionalConfig);
    }

    #[Test]
    public function it_creates_credentials_with_all_parameters(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::PAYPAL,
            apiKey: 'client_id',
            apiSecret: 'client_secret',
            merchantId: 'merchant_123',
            webhookSecret: 'webhook_secret',
            sandboxMode: true,
            additionalConfig: ['partner_id' => 'partner_123'],
        );

        $this->assertSame(GatewayProvider::PAYPAL, $credentials->provider);
        $this->assertSame('client_id', $credentials->apiKey);
        $this->assertSame('client_secret', $credentials->apiSecret);
        $this->assertSame('merchant_123', $credentials->merchantId);
        $this->assertSame('webhook_secret', $credentials->webhookSecret);
        $this->assertTrue($credentials->sandboxMode);
        $this->assertSame(['partner_id' => 'partner_123'], $credentials->additionalConfig);
    }

    #[Test]
    public function it_throws_exception_for_empty_api_key(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: '',
        );
    }

    #[Test]
    public function it_throws_exception_for_whitespace_only_api_key(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: '   ',
        );
    }

    #[Test]
    public function it_creates_stripe_credentials(): void
    {
        $credentials = GatewayCredentials::forStripe(
            secretKey: 'sk_test_stripe',
            webhookSecret: 'whsec_test',
            sandboxMode: true,
        );

        $this->assertSame(GatewayProvider::STRIPE, $credentials->provider);
        $this->assertSame('sk_test_stripe', $credentials->apiKey);
        $this->assertSame('whsec_test', $credentials->webhookSecret);
        $this->assertTrue($credentials->sandboxMode);
    }

    #[Test]
    public function it_creates_paypal_credentials(): void
    {
        $credentials = GatewayCredentials::forPayPal(
            clientId: 'paypal_client',
            clientSecret: 'paypal_secret',
            webhookId: 'webhook_123',
            sandboxMode: true,
        );

        $this->assertSame(GatewayProvider::PAYPAL, $credentials->provider);
        $this->assertSame('paypal_client', $credentials->apiKey);
        $this->assertSame('paypal_secret', $credentials->apiSecret);
        $this->assertSame('webhook_123', $credentials->webhookSecret);
        $this->assertTrue($credentials->sandboxMode);
    }

    #[Test]
    public function it_creates_square_credentials(): void
    {
        $credentials = GatewayCredentials::forSquare(
            accessToken: 'sq_access_token',
            locationId: 'LOC123',
            webhookSignatureKey: 'sig_key',
            sandboxMode: false,
        );

        $this->assertSame(GatewayProvider::SQUARE, $credentials->provider);
        $this->assertSame('sq_access_token', $credentials->apiKey);
        $this->assertSame('LOC123', $credentials->merchantId);
        $this->assertSame('sig_key', $credentials->webhookSecret);
        $this->assertFalse($credentials->sandboxMode);
    }

    #[Test]
    public function it_creates_adyen_credentials(): void
    {
        $credentials = GatewayCredentials::forAdyen(
            apiKey: 'adyen_api_key',
            merchantAccount: 'MerchantAccount',
            hmacKey: 'hmac_key_123',
            sandboxMode: true,
        );

        $this->assertSame(GatewayProvider::ADYEN, $credentials->provider);
        $this->assertSame('adyen_api_key', $credentials->apiKey);
        $this->assertSame('MerchantAccount', $credentials->merchantId);
        $this->assertSame('hmac_key_123', $credentials->webhookSecret);
        $this->assertTrue($credentials->sandboxMode);
    }

    #[Test]
    public function it_can_verify_webhooks_when_secret_is_set(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_test_123',
            webhookSecret: 'whsec_123',
        );

        $this->assertTrue($credentials->canVerifyWebhooks());
    }

    #[Test]
    public function it_cannot_verify_webhooks_when_secret_is_null(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_test_123',
            webhookSecret: null,
        );

        $this->assertFalse($credentials->canVerifyWebhooks());
    }

    #[Test]
    public function it_cannot_verify_webhooks_when_secret_is_empty(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_test_123',
            webhookSecret: '',
        );

        $this->assertFalse($credentials->canVerifyWebhooks());
    }

    #[Test]
    public function it_returns_sandbox_environment(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_test_123',
            sandboxMode: true,
        );

        $this->assertSame('sandbox', $credentials->getEnvironment());
    }

    #[Test]
    public function it_returns_production_environment(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_live_123',
            sandboxMode: false,
        );

        $this->assertSame('production', $credentials->getEnvironment());
    }

    #[Test]
    public function it_returns_config_value(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_test_123',
            additionalConfig: ['timeout' => 30, 'region' => 'us-east-1'],
        );

        $this->assertSame(30, $credentials->getConfig('timeout'));
        $this->assertSame('us-east-1', $credentials->getConfig('region'));
    }

    #[Test]
    public function it_returns_default_for_missing_config(): void
    {
        $credentials = new GatewayCredentials(
            provider: GatewayProvider::STRIPE,
            apiKey: 'sk_test_123',
        );

        $this->assertNull($credentials->getConfig('missing'));
        $this->assertSame('default_value', $credentials->getConfig('missing', 'default_value'));
    }
}
