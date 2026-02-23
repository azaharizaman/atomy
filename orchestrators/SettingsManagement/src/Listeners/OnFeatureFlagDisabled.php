<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Event listener for feature flag disabled.
 * Handles kill switch functionality.
 */
final class OnFeatureFlagDisabled
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle feature flag disabled event.
     */
    public function handle(array $event): void
    {
        $flagKey = $event['key'] ?? 'unknown';
        $tenantId = $event['tenant_id'] ?? null;
        $graceful = $event['graceful'] ?? true;
        
        $this->logger->info('Feature flag disabled event received', [
            'key' => $flagKey,
            'tenant_id' => $tenantId,
            'graceful' => $graceful,
        ]);

        // In production, this would handle graceful degradation or immediate kill switch
    }
}
