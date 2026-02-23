<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Event listener for fiscal period closed.
 * Notifies modules when a period is closed.
 */
final class OnFiscalPeriodClosed
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle fiscal period closed event.
     */
    public function handle(array $event): void
    {
        $periodId = $event['period_id'] ?? 'unknown';
        $tenantId = $event['tenant_id'] ?? null;
        
        $this->logger->info('Fiscal period closed event received', [
            'period_id' => $periodId,
            'tenant_id' => $tenantId,
        ]);

        // In production, this would notify other modules about period closure
    }
}
