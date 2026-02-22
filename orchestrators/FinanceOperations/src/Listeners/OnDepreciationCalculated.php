<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Listeners;

use Nexus\FinanceOperations\Contracts\GLPostingCoordinatorInterface;
use Nexus\FinanceOperations\DTOs\GLPostingRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener for depreciation calculation events.
 * 
 * Automatically triggers GL posting when depreciation is calculated.
 * This listener bridges the FixedAssetDepreciation package with the
 * JournalEntry package through the FinanceOperations orchestrator.
 * 
 * @since 1.0.0
 */
final readonly class OnDepreciationCalculated
{
    public function __construct(
        private GLPostingCoordinatorInterface $glPostingCoordinator,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the depreciation calculated event.
     * 
     * @param object $event The depreciation event containing:
     *                      - tenantId: string
     *                      - periodId: string
     *                      - runId: string
     *                      - totalDepreciation: string
     *                      - assetCount: int
     */
    public function handle(object $event): void
    {
        $this->logger->info('Handling depreciation calculated event', [
            'tenant_id' => $event->tenantId ?? 'unknown',
            'period_id' => $event->periodId ?? 'unknown',
            'run_id' => $event->runId ?? 'unknown',
        ]);

        try {
            // Create GL posting request
            $request = new GLPostingRequest(
                tenantId: $event->tenantId,
                periodId: $event->periodId,
                subledgerType: 'asset',
                options: [
                    'run_id' => $event->runId ?? null,
                    'auto_post' => true,
                ],
            );

            // Post to GL
            $result = $this->glPostingCoordinator->postToGL($request);

            if ($result->success) {
                $this->logger->info('Depreciation posted to GL successfully', [
                    'posting_id' => $result->postingId,
                    'entries_posted' => $result->entriesPosted,
                ]);
            } else {
                $this->logger->error('Failed to post depreciation to GL', [
                    'error' => $result->error ?? 'Unknown error',
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Exception in depreciation event handler', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
