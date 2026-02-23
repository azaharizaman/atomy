<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Event listener for feature flag creation.
 * Initializes cache for new feature flags.
 */
final class OnFeatureFlagCreated
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle feature flag created event.
     */
    public function handle(array $event): void
    {
        $flagKey = $event['key'] ?? 'unknown';
        $tenantId = $event['tenant_id'] ?? null;
        
        $this->logger->info('Feature flag created event received', [
            'key' => $flagKey,
            'tenant_id' => $tenantId,
        ]);

        // In production, this would initialize cache for the new flag
    }
}
