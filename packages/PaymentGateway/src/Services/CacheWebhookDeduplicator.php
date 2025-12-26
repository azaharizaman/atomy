<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Services;

use Nexus\PaymentGateway\Contracts\WebhookDeduplicatorInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Psr\SimpleCache\CacheInterface;

/**
 * Deduplicates webhooks using a PSR-16 cache.
 */
final readonly class CacheWebhookDeduplicator implements WebhookDeduplicatorInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $ttl = 86400 // Default 24 hours
    ) {}

    public function isDuplicate(GatewayProvider $provider, string $eventId): bool
    {
        $key = $this->getCacheKey($provider, $eventId);
        return $this->cache->has($key);
    }

    public function recordProcessed(GatewayProvider $provider, string $eventId, int $ttlSeconds = 86400): void
    {
        $key = $this->getCacheKey($provider, $eventId);
        $this->cache->set($key, true, $ttlSeconds);
    }

    private function getCacheKey(GatewayProvider $provider, string $eventId): string
    {
        return sprintf('webhook_processed:%s:%s', $provider->value, $eventId);
    }
}
