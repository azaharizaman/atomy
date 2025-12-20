<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\TokenizerInterface;
use Nexus\PaymentGateway\Contracts\TokenStorageInterface;
use Nexus\PaymentGateway\DTOs\TokenizationRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Events\TokenCreatedEvent;
use Nexus\PaymentGateway\Exceptions\TokenNotFoundException;
use Nexus\PaymentGateway\ValueObjects\PaymentToken;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Secure token vault for managing customer payment tokens.
 *
 * Combines tokenization (via gateway) with secure storage
 * for reusable payment methods.
 */
final readonly class TokenVault
{
    /** @var array<string, TokenizerInterface> */
    private array $tokenizers;

    public function __construct(
        private TokenStorageInterface $storage,
        private TenantContextInterface $tenantContext,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->tokenizers = [];
    }

    /**
     * Register a tokenizer for a provider.
     */
    public function registerTokenizer(TokenizerInterface $tokenizer): void
    {
        $this->tokenizers[$tokenizer->getProvider()->value] = $tokenizer;
    }

    /**
     * Tokenize and store a payment method.
     *
     * @param string $customerId Customer identifier
     * @param TokenizationRequest $request Tokenization request
     * @param GatewayProvider $provider Gateway provider
     * @return string Storage ID for the token
     */
    public function tokenizeAndStore(
        string $customerId,
        TokenizationRequest $request,
        GatewayProvider $provider,
    ): string {
        $tokenizer = $this->getTokenizer($provider);
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $token = $tokenizer->tokenize($request);

        $storageId = $this->storage->store($tenantId, $customerId, $token);

        $this->logger->info('Payment method tokenized and stored', [
            'customer_id' => $customerId,
            'provider' => $provider->value,
            'storage_id' => $storageId,
        ]);

        $this->eventDispatcher?->dispatch(
            TokenCreatedEvent::fromToken(
                tenantId: $tenantId,
                customerId: $customerId,
                provider: $provider,
                token: $token,
            )
        );

        return $storageId;
    }

    /**
     * Retrieve a token by storage ID.
     */
    public function getToken(string $storageId): PaymentToken
    {
        return $this->storage->retrieve($storageId);
    }

    /**
     * Get all tokens for a customer.
     *
     * @return array<PaymentToken>
     */
    public function getCustomerTokens(string $customerId): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        return $this->storage->getCustomerTokens($tenantId, $customerId);
    }

    /**
     * Delete a token.
     */
    public function deleteToken(string $storageId): void
    {
        $this->storage->delete($storageId);

        $this->logger->info('Payment token deleted', [
            'storage_id' => $storageId,
        ]);
    }

    /**
     * Delete all tokens for a customer.
     */
    public function deleteCustomerTokens(string $customerId): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $this->storage->deleteCustomerTokens($tenantId, $customerId);

        $this->logger->info('All customer tokens deleted', [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Set a token as default for a customer.
     */
    public function setDefaultToken(string $customerId, string $storageId): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $this->storage->setDefault($tenantId, $customerId, $storageId);
    }

    /**
     * Get the default token for a customer.
     */
    public function getDefaultToken(string $customerId): ?PaymentToken
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        return $this->storage->getDefault($tenantId, $customerId);
    }

    private function getTokenizer(GatewayProvider $provider): TokenizerInterface
    {
        if (!isset($this->tokenizers[$provider->value])) {
            throw new TokenNotFoundException("Tokenizer not found for provider: {$provider->value}");
        }

        return $this->tokenizers[$provider->value];
    }
}
