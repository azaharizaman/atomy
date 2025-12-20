<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Contracts\WebhookProcessorInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Events\WebhookReceivedEvent;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\Exceptions\WebhookVerificationFailedException;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Processes incoming webhooks from payment gateways.
 *
 * Handles verification, parsing, and routing to appropriate handlers.
 */
final class WebhookProcessor implements WebhookProcessorInterface
{
    /** @var array<string, WebhookHandlerInterface> */
    private array $handlers = [];

    /** @var array<string, string> */
    private array $secrets = [];

    public function __construct(
        private readonly TenantContextInterface $tenantContext,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Configure webhook secret for a provider.
     */
    public function setSecret(GatewayProvider $provider, string $secret): void
    {
        $this->secrets[$provider->value] = $secret;
    }

    public function process(
        string $providerName,
        string $payload,
        array $headers,
    ): WebhookPayload {
        $provider = GatewayProvider::tryFrom($providerName);

        if ($provider === null) {
            throw new GatewayException("Unknown provider: {$providerName}");
        }

        $handler = $this->handlers[$provider->value] ?? null;

        if ($handler === null) {
            throw new GatewayException("No webhook handler registered for provider: {$providerName}");
        }

        $signature = $this->extractSignature($headers, $provider);
        $secret = $this->secrets[$provider->value] ?? '';

        if (!$handler->verifySignature($payload, $signature, $secret)) {
            $this->logger->warning('Webhook signature verification failed', [
                'provider' => $providerName,
            ]);
            throw WebhookVerificationFailedException::invalidSignature($provider);
        }

        $webhookPayload = $handler->parsePayload($payload, $headers);

        $tenantId = $this->tenantContext->getCurrentTenantId() ?? '';

        $this->logger->info('Webhook received', [
            'provider' => $providerName,
            'event_type' => $webhookPayload->eventType->value,
            'event_id' => $webhookPayload->eventId,
        ]);

        $handler->processWebhook($webhookPayload);

        $this->eventDispatcher?->dispatch(
            WebhookReceivedEvent::fromPayload(
                tenantId: $tenantId,
                provider: $provider,
                payload: $webhookPayload,
            )
        );

        return $webhookPayload;
    }

    public function registerHandler(WebhookHandlerInterface $handler): void
    {
        $this->handlers[$handler->getProvider()->value] = $handler;
    }

    public function hasHandler(string $providerName): bool
    {
        return isset($this->handlers[$providerName]);
    }

    private function extractSignature(array $headers, GatewayProvider $provider): string
    {
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);

        return match ($provider) {
            GatewayProvider::STRIPE => $normalizedHeaders['stripe-signature'] ?? '',
            GatewayProvider::PAYPAL => $normalizedHeaders['paypal-transmission-sig'] ?? '',
            GatewayProvider::SQUARE => $normalizedHeaders['x-square-signature'] ?? '',
            GatewayProvider::ADYEN => $normalizedHeaders['x-adyen-hmac-signature'] ?? '',
            default => '',
        };
    }
}
