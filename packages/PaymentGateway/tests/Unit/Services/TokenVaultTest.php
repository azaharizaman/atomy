<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Services;

use Nexus\PaymentGateway\Contracts\TokenizerInterface;
use Nexus\PaymentGateway\Contracts\TokenStorageInterface;
use Nexus\PaymentGateway\DTOs\TokenizationRequest;
use Nexus\PaymentGateway\Enums\CardBrand;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Events\TokenCreatedEvent;
use Nexus\PaymentGateway\Exceptions\TokenNotFoundException;
use Nexus\PaymentGateway\Services\TokenVault;
use Nexus\PaymentGateway\ValueObjects\CardMetadata;
use Nexus\PaymentGateway\ValueObjects\PaymentToken;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(TokenVault::class)]
final class TokenVaultTest extends TestCase
{
    private TokenStorageInterface&MockObject $storage;
    private TenantContextInterface&MockObject $tenantContext;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;
    private TokenVault $vault;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(TokenStorageInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->tenantContext->method('getCurrentTenantId')->willReturn('tenant_123');

        $this->vault = new TokenVault(
            storage: $this->storage,
            tenantContext: $this->tenantContext,
            eventDispatcher: $this->eventDispatcher,
            logger: $this->logger,
        );
    }

    #[Test]
    public function it_registers_tokenizer(): void
    {
        $tokenizer = $this->createMock(TokenizerInterface::class);
        $tokenizer->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        // Should not throw
        $this->vault->registerTokenizer($tokenizer);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_tokenizes_and_stores_card(): void
    {
        $tokenizer = $this->createMock(TokenizerInterface::class);
        $tokenizer->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $request = TokenizationRequest::fromCard(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $paymentToken = new PaymentToken(
            tokenId: 'tok_visa',
            provider: GatewayProvider::STRIPE,
            cardMetadata: new CardMetadata(
                lastFour: '4242',
                brand: CardBrand::VISA,
                expiryMonth: 12,
                expiryYear: 2030,
            ),
        );

        $tokenizer->expects($this->once())
            ->method('tokenize')
            ->with($request)
            ->willReturn($paymentToken);

        $this->storage->expects($this->once())
            ->method('store')
            ->with('tenant_123', 'cus_123', $paymentToken)
            ->willReturn('pm_stored_123');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(TokenCreatedEvent::class));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Payment method tokenized and stored', $this->anything());

        $this->vault->registerTokenizer($tokenizer);
        $result = $this->vault->tokenizeAndStore('cus_123', $request, GatewayProvider::STRIPE);

        $this->assertSame('pm_stored_123', $result);
    }

    #[Test]
    public function it_throws_when_tokenizer_not_found(): void
    {
        $request = TokenizationRequest::fromCard(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $this->expectException(TokenNotFoundException::class);
        $this->expectExceptionMessage('Tokenizer not found for provider: stripe');

        $this->vault->tokenizeAndStore('cus_123', $request, GatewayProvider::STRIPE);
    }

    #[Test]
    public function it_retrieves_token_by_storage_id(): void
    {
        $paymentToken = new PaymentToken(
            tokenId: 'tok_visa',
            provider: GatewayProvider::STRIPE,
            cardMetadata: new CardMetadata(
                lastFour: '4242',
                brand: CardBrand::VISA,
                expiryMonth: 12,
                expiryYear: 2030,
            ),
        );

        $this->storage->expects($this->once())
            ->method('retrieve')
            ->with('pm_stored_123')
            ->willReturn($paymentToken);

        $result = $this->vault->getToken('pm_stored_123');

        $this->assertSame($paymentToken, $result);
    }

    #[Test]
    public function it_retrieves_customer_tokens(): void
    {
        $paymentToken1 = new PaymentToken(
            tokenId: 'tok_visa_1',
            provider: GatewayProvider::STRIPE,
            cardMetadata: new CardMetadata(
                lastFour: '4242',
                brand: CardBrand::VISA,
                expiryMonth: 12,
                expiryYear: 2030,
            ),
        );

        $paymentToken2 = new PaymentToken(
            tokenId: 'tok_visa_2',
            provider: GatewayProvider::STRIPE,
            cardMetadata: new CardMetadata(
                lastFour: '1234',
                brand: CardBrand::MASTERCARD,
                expiryMonth: 6,
                expiryYear: 2025,
            ),
        );

        $this->storage->expects($this->once())
            ->method('getCustomerTokens')
            ->with('tenant_123', 'cus_123')
            ->willReturn([$paymentToken1, $paymentToken2]);

        $result = $this->vault->getCustomerTokens('cus_123');

        $this->assertCount(2, $result);
        $this->assertSame($paymentToken1, $result[0]);
        $this->assertSame($paymentToken2, $result[1]);
    }

    #[Test]
    public function it_deletes_token(): void
    {
        $this->storage->expects($this->once())
            ->method('delete')
            ->with('pm_stored_123');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Payment token deleted', $this->anything());

        $this->vault->deleteToken('pm_stored_123');
    }

    #[Test]
    public function it_deletes_customer_tokens(): void
    {
        $this->storage->expects($this->once())
            ->method('deleteCustomerTokens')
            ->with('tenant_123', 'cus_123');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('All customer tokens deleted', $this->anything());

        $this->vault->deleteCustomerTokens('cus_123');
    }

    #[Test]
    public function it_sets_default_token(): void
    {
        $this->storage->expects($this->once())
            ->method('setDefault')
            ->with('tenant_123', 'cus_123', 'pm_stored_123');

        $this->vault->setDefaultToken('cus_123', 'pm_stored_123');
    }

    #[Test]
    public function it_gets_default_token(): void
    {
        $paymentToken = new PaymentToken(
            tokenId: 'tok_visa',
            provider: GatewayProvider::STRIPE,
            cardMetadata: new CardMetadata(
                lastFour: '4242',
                brand: CardBrand::VISA,
                expiryMonth: 12,
                expiryYear: 2030,
            ),
        );

        $this->storage->expects($this->once())
            ->method('getDefault')
            ->with('tenant_123', 'cus_123')
            ->willReturn($paymentToken);

        $result = $this->vault->getDefaultToken('cus_123');

        $this->assertSame($paymentToken, $result);
    }

    #[Test]
    public function it_returns_null_when_no_default_token(): void
    {
        $this->storage->expects($this->once())
            ->method('getDefault')
            ->with('tenant_123', 'cus_123')
            ->willReturn(null);

        $result = $this->vault->getDefaultToken('cus_123');

        $this->assertNull($result);
    }

    #[Test]
    public function it_registers_multiple_tokenizers(): void
    {
        $stripeTokenizer = $this->createMock(TokenizerInterface::class);
        $stripeTokenizer->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $paypalTokenizer = $this->createMock(TokenizerInterface::class);
        $paypalTokenizer->method('getProvider')->willReturn(GatewayProvider::PAYPAL);

        $this->vault->registerTokenizer($stripeTokenizer);
        $this->vault->registerTokenizer($paypalTokenizer);

        // Both should be registered without errors
        $this->assertTrue(true);
    }

    #[Test]
    public function it_uses_correct_tokenizer_for_provider(): void
    {
        $stripeTokenizer = $this->createMock(TokenizerInterface::class);
        $stripeTokenizer->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $paypalTokenizer = $this->createMock(TokenizerInterface::class);
        $paypalTokenizer->method('getProvider')->willReturn(GatewayProvider::PAYPAL);

        $request = TokenizationRequest::fromCard(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $paymentToken = new PaymentToken(
            tokenId: 'tok_stripe',
            provider: GatewayProvider::STRIPE,
        );

        // Only Stripe tokenizer should be called
        $stripeTokenizer->expects($this->once())
            ->method('tokenize')
            ->willReturn($paymentToken);

        $paypalTokenizer->expects($this->never())
            ->method('tokenize');

        $this->storage->method('store')->willReturn('pm_stored_123');

        $this->vault->registerTokenizer($stripeTokenizer);
        $this->vault->registerTokenizer($paypalTokenizer);

        $this->vault->tokenizeAndStore('cus_123', $request, GatewayProvider::STRIPE);
    }

    #[Test]
    public function it_handles_empty_customer_tokens(): void
    {
        $this->storage->expects($this->once())
            ->method('getCustomerTokens')
            ->with('tenant_123', 'cus_123')
            ->willReturn([]);

        $result = $this->vault->getCustomerTokens('cus_123');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_handles_null_tenant_id(): void
    {
        // Create a new vault with null tenant context
        $nullTenantContext = $this->createMock(TenantContextInterface::class);
        $nullTenantContext->method('getCurrentTenantId')->willReturn(null);

        $vault = new TokenVault(
            storage: $this->storage,
            tenantContext: $nullTenantContext,
            eventDispatcher: $this->eventDispatcher,
            logger: $this->logger,
        );

        // Token should be stored with empty string as tenant ID
        $tokenizer = $this->createMock(TokenizerInterface::class);
        $tokenizer->method('getProvider')->willReturn(GatewayProvider::STRIPE);

        $request = TokenizationRequest::fromCard(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $paymentToken = new PaymentToken(
            tokenId: 'tok_visa',
            provider: GatewayProvider::STRIPE,
        );

        $tokenizer->method('tokenize')->willReturn($paymentToken);

        $this->storage->expects($this->once())
            ->method('store')
            ->with('', 'cus_123', $paymentToken)
            ->willReturn('pm_stored_123');

        $vault->registerTokenizer($tokenizer);
        $result = $vault->tokenizeAndStore('cus_123', $request, GatewayProvider::STRIPE);

        $this->assertSame('pm_stored_123', $result);
    }
}
