<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\ValueObjects\WebhookPayload;

/**
 * Contract for webhook processing orchestration.
 *
 * Handles the full webhook lifecycle: verification, parsing,
 * routing to appropriate handlers, and response.
 */
interface WebhookProcessorInterface
{
    /**
     * Process an incoming webhook request.
     *
     * @param string $providerName Gateway provider name
     * @param string $payload Raw webhook payload
     * @param array<string, string> $headers Request headers
     * @return WebhookPayload Processed webhook payload
     * @throws \Nexus\PaymentGateway\Exceptions\WebhookVerificationFailedException
     * @throws \Nexus\PaymentGateway\Exceptions\WebhookProcessingException
     */
    public function process(
        string $providerName,
        string $payload,
        array $headers,
    ): WebhookPayload;

    /**
     * Register a webhook handler for a provider.
     */
    public function registerHandler(WebhookHandlerInterface $handler): void;

    /**
     * Check if a handler is registered for a provider.
     */
    public function hasHandler(string $providerName): bool;
}
