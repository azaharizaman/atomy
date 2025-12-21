<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\PaymentGateway\Enums\CardBrand;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\CardMetadata;
use Nexus\PaymentGateway\ValueObjects\PaymentToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentToken::class)]
final class PaymentTokenTest extends TestCase
{
    #[Test]
    public function it_creates_token_with_minimal_parameters(): void
    {
        $token = new PaymentToken(
            tokenId: 'tok_123',
            provider: GatewayProvider::STRIPE,
        );

        $this->assertSame('tok_123', $token->tokenId);
        $this->assertSame(GatewayProvider::STRIPE, $token->provider);
        $this->assertNull($token->cardMetadata);
        $this->assertNull($token->customerId);
        $this->assertNull($token->expiresAt);
        $this->assertEmpty($token->metadata);
    }

    #[Test]
    public function it_creates_token_with_all_parameters(): void
    {
        $cardMetadata = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: 2030,
        );
        $expiresAt = new \DateTimeImmutable('+1 year');
        $createdAt = new \DateTimeImmutable();

        $token = new PaymentToken(
            tokenId: 'tok_456',
            provider: GatewayProvider::PAYPAL,
            cardMetadata: $cardMetadata,
            customerId: 'cus_789',
            expiresAt: $expiresAt,
            createdAt: $createdAt,
            metadata: ['source' => 'web'],
        );

        $this->assertSame('tok_456', $token->tokenId);
        $this->assertSame(GatewayProvider::PAYPAL, $token->provider);
        $this->assertSame($cardMetadata, $token->cardMetadata);
        $this->assertSame('cus_789', $token->customerId);
        $this->assertSame($expiresAt, $token->expiresAt);
        $this->assertSame($createdAt, $token->createdAt);
        $this->assertSame(['source' => 'web'], $token->metadata);
    }

    #[Test]
    public function it_detects_expired_token(): void
    {
        $expired = new PaymentToken(
            tokenId: 'tok_expired',
            provider: GatewayProvider::STRIPE,
            expiresAt: new \DateTimeImmutable('-1 day'),
        );
        $this->assertTrue($expired->isExpired());

        $notExpired = new PaymentToken(
            tokenId: 'tok_valid',
            provider: GatewayProvider::STRIPE,
            expiresAt: new \DateTimeImmutable('+1 day'),
        );
        $this->assertFalse($notExpired->isExpired());

        $noExpiry = new PaymentToken(
            tokenId: 'tok_no_expiry',
            provider: GatewayProvider::STRIPE,
        );
        $this->assertFalse($noExpiry->isExpired());
    }

    #[Test]
    public function it_detects_customer_linkage(): void
    {
        $linked = new PaymentToken(
            tokenId: 'tok_linked',
            provider: GatewayProvider::STRIPE,
            customerId: 'cus_123',
        );
        $this->assertTrue($linked->isLinkedToCustomer());

        $notLinked = new PaymentToken(
            tokenId: 'tok_not_linked',
            provider: GatewayProvider::STRIPE,
        );
        $this->assertFalse($notLinked->isLinkedToCustomer());
    }

    #[Test]
    public function it_generates_display_label_with_card_metadata(): void
    {
        $cardMetadata = new CardMetadata(
            brand: CardBrand::VISA,
            lastFour: '4242',
            expiryMonth: 12,
            expiryYear: 2030,
        );

        $token = new PaymentToken(
            tokenId: 'tok_123',
            provider: GatewayProvider::STRIPE,
            cardMetadata: $cardMetadata,
        );

        $this->assertSame('Visa •••• 4242', $token->getDisplayLabel());
    }

    #[Test]
    public function it_generates_display_label_without_card_metadata(): void
    {
        $token = new PaymentToken(
            tokenId: 'tok_12345678',
            provider: GatewayProvider::STRIPE,
        );

        $this->assertSame('Token tok_1234', $token->getDisplayLabel());
    }

    #[Test]
    public function it_returns_card_brand(): void
    {
        $cardMetadata = new CardMetadata(
            brand: CardBrand::MASTERCARD,
            lastFour: '5555',
            expiryMonth: 6,
            expiryYear: 2025,
        );

        $withCard = new PaymentToken(
            tokenId: 'tok_with_card',
            provider: GatewayProvider::STRIPE,
            cardMetadata: $cardMetadata,
        );
        $this->assertSame(CardBrand::MASTERCARD, $withCard->getCardBrand());

        $withoutCard = new PaymentToken(
            tokenId: 'tok_without_card',
            provider: GatewayProvider::STRIPE,
        );
        $this->assertNull($withoutCard->getCardBrand());
    }

    #[Test]
    public function it_returns_last_four(): void
    {
        $cardMetadata = new CardMetadata(
            brand: CardBrand::AMEX,
            lastFour: '0005',
            expiryMonth: 3,
            expiryYear: 2026,
        );

        $withCard = new PaymentToken(
            tokenId: 'tok_with_card',
            provider: GatewayProvider::STRIPE,
            cardMetadata: $cardMetadata,
        );
        $this->assertSame('0005', $withCard->getLastFour());

        $withoutCard = new PaymentToken(
            tokenId: 'tok_without_card',
            provider: GatewayProvider::STRIPE,
        );
        $this->assertNull($withoutCard->getLastFour());
    }
}
