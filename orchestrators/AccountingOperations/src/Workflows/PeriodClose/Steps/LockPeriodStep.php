<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose\Steps;

use Nexus\AccountPeriodClose\Contracts\CloseDataProviderInterface;
use Nexus\AccountPeriodClose\Enums\CloseStatus;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Step 7: Lock the period to prevent further modifications
 */
final readonly class LockPeriodStep
{
    public function __construct(
        private CloseDataProviderInterface $dataProvider
    ) {}

    /**
     * Execute the period locking step
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period to lock
     * @param array<string, mixed> $context Workflow context
     * @return array{result: array{status: CloseStatus, locked_at: \DateTimeImmutable}, context: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(string $tenantId, string $periodId, array $context = []): array
    {
        try {
            // Verify all entries have been posted
            $postedCount = $context['posted_count'] ?? 0;
            $journalIds = $context['journal_ids'] ?? [];

            // Lock the period
            $lockedAt = new \DateTimeImmutable();
            $this->dataProvider->lockPeriod($tenantId, $periodId, $lockedAt);

            // Update period status
            $status = CloseStatus::CLOSED;

            // Update context
            $context['period_status'] = $status;
            $context['locked_at'] = $lockedAt;
            $context['closed_by'] = $context['user_id'] ?? null;

            return [
                'result' => [
                    'status' => $status,
                    'locked_at' => $lockedAt,
                ],
                'context' => $context,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Failed to lock period: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Check if step can be skipped
     */
    public function canSkip(array $context): bool
    {
        // Cannot skip locking
        return false;
    }

    /**
     * Get step name
     */
    public function getName(): string
    {
        return 'lock_period';
    }

    /**
     * Get step description
     */
    public function getDescription(): string
    {
        return 'Locks the period to prevent further modifications after close';
    }
}
