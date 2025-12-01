<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Listeners;

use Nexus\AccountPeriodClose\Contracts\ClosingEntryGeneratorInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener that creates closing entries when a period close is initiated
 */
final readonly class CreateClosingEntriesOnPeriodClose
{
    public function __construct(
        private ClosingEntryGeneratorInterface $closingEntryGenerator,
        private ?AuditLogManagerInterface $auditLogger = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Handle the period close event
     *
     * @param object $event The period close event (framework-specific)
     * @return void
     */
    public function handle(object $event): void
    {
        $tenantId = $event->tenantId ?? '';
        $periodId = $event->periodId ?? '';

        $this->logger->info('Creating closing entries for period close', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        try {
            // Generate closing entries
            $closingEntries = $this->closingEntryGenerator->generate(
                tenantId: $tenantId,
                periodId: $periodId
            );

            $this->logger->info('Closing entries created successfully', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'entries_count' => count($closingEntries),
            ]);

            // Log to audit trail
            $this->auditLogger?->log(
                entityId: $periodId,
                action: 'closing_entries_created',
                description: sprintf(
                    'Created %d closing entries for period %s',
                    count($closingEntries),
                    $periodId
                ),
                metadata: [
                    'tenant_id' => $tenantId,
                    'entries_count' => count($closingEntries),
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create closing entries', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
