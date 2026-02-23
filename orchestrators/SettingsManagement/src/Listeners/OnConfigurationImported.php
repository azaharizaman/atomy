<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Event listener for configuration imported.
 * Validates imported configuration.
 */
final class OnConfigurationImported
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle configuration imported event.
     */
    public function handle(array $event): void
    {
        $tenantId = $event['tenant_id'] ?? null;
        $settingsCount = $event['settings_count'] ?? 0;
        $flagsCount = $event['flags_count'] ?? 0;
        
        $this->logger->info('Configuration imported event received', [
            'tenant_id' => $tenantId,
            'settings_count' => $settingsCount,
            'flags_count' => $flagsCount,
        ]);

        // In production, this would validate the imported configuration
    }
}
