<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;

interface WebhookDeduplicatorInterface
{
    /**
     * Check if a webhook event has already been processed.
     *
     * @param GatewayProvider $provider The payment gateway provider
     * @param string $eventId The unique event ID from the provider
     * @return bool True if the event is a duplicate, false otherwise
     */
    public function isDuplicate(GatewayProvider $provider, string $eventId): bool;

    /**
     * Record a webhook event as processed.
     *
     * @param GatewayProvider $provider The payment gateway provider
     * @param string $eventId The unique event ID from the provider
     * @param int $ttlSeconds Time-to-live for the deduplication record in seconds
     */
    public function recordProcessed(GatewayProvider $provider, string $eventId, int $ttlSeconds): void;
}
