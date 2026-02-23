<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Event listener for setting changes.
 * Notifies dependent packages when settings change.
 */
final class OnSettingChanged
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle setting changed event.
     */
    public function handle(array $event): void
    {
        $settingKey = $event['key'] ?? 'unknown';
        $tenantId = $event['tenant_id'] ?? null;
        
        $this->logger->info('Setting changed event received', [
            'key' => $settingKey,
            'tenant_id' => $tenantId,
        ]);

        // In production, this would dispatch notifications to dependent packages
    }
}
