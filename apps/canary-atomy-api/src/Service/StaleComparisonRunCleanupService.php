<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\QuoteComparisonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Identifies and marks stale comparison runs.
 *
 * Intended to be invoked by a Symfony console command or scheduler.
 * Two sources of staleness:
 *   1. Draft runs older than a configurable inactivity period.
 *   2. Active runs whose expires_at date has passed.
 */
final readonly class StaleComparisonRunCleanupService
{
    private const DEFAULT_DRAFT_STALE_HOURS = 24;

    public function __construct(
        private QuoteComparisonRunRepository $runRepository,
        private EntityManagerInterface $entityManager,
        private ComparisonRunAuditService $auditService,
        private LoggerInterface $logger,
        private int $draftStaleHours = self::DEFAULT_DRAFT_STALE_HOURS,
    ) {
    }

    /**
     * Scan and mark stale runs. Returns the total number of runs marked stale.
     */
    public function cleanup(): int
    {
        $totalMarked = 0;
        $totalMarked += $this->markStaleDrafts();
        $totalMarked += $this->markExpiredRuns();

        if ($totalMarked > 0) {
            $this->entityManager->flush();
        }

        return $totalMarked;
    }

    private function markStaleDrafts(): int
    {
        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d hours', $this->draftStaleHours));
        $staleDrafts = $this->runRepository->findStaleDraftsBefore($cutoff);
        $count = 0;

        foreach ($staleDrafts as $run) {
            $run->markStale();
            $this->auditService->logStale($run, sprintf(
                'Draft run inactive for over %d hours.',
                $this->draftStaleHours,
            ));

            $this->logger->info('Marked draft comparison run as stale', [
                'run_id' => (string) $run->getId(),
                'tenant_id' => $run->getTenantId(),
                'rfq_id' => $run->getRfqId(),
                'created_at' => $run->getCreatedAt()->format(\DATE_ATOM),
            ]);

            $count++;
        }

        return $count;
    }

    private function markExpiredRuns(): int
    {
        $expiredRuns = $this->runRepository->findExpiredRuns();
        $count = 0;

        foreach ($expiredRuns as $run) {
            $run->markStale();
            $this->auditService->logStale($run, sprintf(
                'Run expired at %s.',
                $run->getExpiresAt()?->format(\DATE_ATOM) ?? 'unknown',
            ));

            $this->logger->info('Marked expired comparison run as stale', [
                'run_id' => (string) $run->getId(),
                'tenant_id' => $run->getTenantId(),
                'rfq_id' => $run->getRfqId(),
                'expires_at' => $run->getExpiresAt()?->format(\DATE_ATOM),
            ]);

            $count++;
        }

        return $count;
    }
}
