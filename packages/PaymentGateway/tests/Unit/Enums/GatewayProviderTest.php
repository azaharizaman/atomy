<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Enums;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GatewayProvider::class)]
final class GatewayProviderTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_providers(): void
    {
        $expectedProviders = [
            'STRIPE',
            'PAYPAL',
            'SQUARE',
            'ADYEN',
            'BRAINTREE',
            'AUTHORIZE_NET',
        ];

        $actualProviders = array_map(
            fn (GatewayProvider $provider) => $provider->name,
            GatewayProvider::cases()
        );

        $this->assertSame($expectedProviders, $actualProviders);
    }

    #[Test]
    #[DataProvider('providerLabelProvider')]
    public function it_returns_correct_labels(GatewayProvider $provider, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $provider->label());
    }

    public static function providerLabelProvider(): array
    {
        return [
            'stripe' => [GatewayProvider::STRIPE, 'Stripe'],
            'paypal' => [GatewayProvider::PAYPAL, 'PayPal'],
            'square' => [GatewayProvider::SQUARE, 'Square'],
            'adyen' => [GatewayProvider::ADYEN, 'Adyen'],
            'braintree' => [GatewayProvider::BRAINTREE, 'Braintree'],
            'authorize_net' => [GatewayProvider::AUTHORIZE_NET, 'Authorize.net'],
        ];
    }

    #[Test]
    #[DataProvider('providerValueProvider')]
    public function it_has_correct_backing_values(GatewayProvider $provider, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $provider->value);
    }

    public static function providerValueProvider(): array
    {
        return [
            'stripe' => [GatewayProvider::STRIPE, 'stripe'],
            'paypal' => [GatewayProvider::PAYPAL, 'paypal'],
            'square' => [GatewayProvider::SQUARE, 'square'],
            'adyen' => [GatewayProvider::ADYEN, 'adyen'],
            'braintree' => [GatewayProvider::BRAINTREE, 'braintree'],
            'authorize_net' => [GatewayProvider::AUTHORIZE_NET, 'authorize_net'],
        ];
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $provider = GatewayProvider::from('stripe');
        $this->assertSame(GatewayProvider::STRIPE, $provider);
    }

    #[Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        $this->assertNull(GatewayProvider::tryFrom('invalid'));
    }

    #[Test]
    #[DataProvider('supports3DSProvider')]
    public function it_returns_correct_3ds_support(GatewayProvider $provider, bool $expected): void
    {
        $this->assertSame($expected, $provider->supports3DS());
    }

    public static function supports3DSProvider(): array
    {
        return [
            'stripe' => [GatewayProvider::STRIPE, true],
            'paypal' => [GatewayProvider::PAYPAL, false],
            'square' => [GatewayProvider::SQUARE, false],
            'adyen' => [GatewayProvider::ADYEN, true],
            'braintree' => [GatewayProvider::BRAINTREE, true],
            'authorize_net' => [GatewayProvider::AUTHORIZE_NET, false],
        ];
    }

    #[Test]
    #[DataProvider('supportsTokenizationProvider')]
    public function it_returns_correct_tokenization_support(GatewayProvider $provider, bool $expected): void
    {
        $this->assertSame($expected, $provider->supportsTokenization());
    }

    public static function supportsTokenizationProvider(): array
    {
        return [
            'stripe' => [GatewayProvider::STRIPE, true],
            'paypal' => [GatewayProvider::PAYPAL, true],
            'square' => [GatewayProvider::SQUARE, true],
            'adyen' => [GatewayProvider::ADYEN, true],
            'braintree' => [GatewayProvider::BRAINTREE, true],
            'authorize_net' => [GatewayProvider::AUTHORIZE_NET, false],
        ];
    }
}
