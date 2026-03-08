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
        private TenantRepository $tenantRepository,
        private EntityManagerInterface $entityManager,
        private ComparisonRunAuditService $auditService,
        private LoggerInterface $logger,
        private int $draftStaleHours = self::DEFAULT_DRAFT_STALE_HOURS,
    ) {
    }

    /**
     * Scan and mark stale runs. Returns the total number of runs marked stale.
     */
    public function cleanup(?int $draftStaleHoursOverride = null): int
    {
        $staleHours = $draftStaleHoursOverride ?? $this->draftStaleHours;
        $tenants = $this->tenantRepository->findAll();
        $totalMarked = 0;
        $auditPayloads = [];

        foreach ($tenants as $tenant) {
            $tenantId = (string)$tenant->getId();
            
            [$markedDrafts, $draftAudits] = $this->markStaleDrafts($tenantId, $staleHours);
            $totalMarked += $markedDrafts;
            $auditPayloads = array_merge($auditPayloads, $draftAudits);

            [$markedExpired, $expiredAudits] = $this->markExpiredRuns($tenantId);
            $totalMarked += $markedExpired;
            $auditPayloads = array_merge($auditPayloads, $expiredAudits);
        }

        if ($totalMarked > 0) {
            try {
                $this->entityManager->flush();
                $this->entityManager->clear();
            } catch (\Doctrine\ORM\OptimisticLockException $e) {
                $this->logger->error('Optimistic locking failure during comparison run cleanup', [
                    'exception' => $e,
                ]);
                // Some were updated, some failed. In a real app we might retry.
                throw $e;
            }

            // Log audits only after successful flush
            foreach ($auditPayloads as $payload) {
                $this->auditService->logStale($payload['run'], $payload['reason']);
            }
        }

        return $totalMarked;
    }

    /**
     * @return array{0: int, 1: array<int, array{run: QuoteComparisonRun, reason: string}>}
     */
    private function markStaleDrafts(string $tenantId, int $staleHours): array
    {
        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d hours', $staleHours));
        $staleDrafts = $this->runRepository->findStaleDraftsBefore($tenantId, $cutoff);
        $count = 0;
        $audits = [];

        foreach ($staleDrafts as $run) {
            try {
                $run->markStale();
                $reason = sprintf('Draft run inactive for over %d hours.', $staleHours);
                $audits[] = ['run' => $run, 'reason' => $reason];

                $this->logger->info('Marked draft comparison run as stale', [
                    'run_id' => (string) $run->getId(),
                    'tenant_id' => $run->getTenantId(),
                    'rfq_id' => $run->getRfqId(),
                    'created_at' => $run->getCreatedAt()->format(\DATE_ATOM),
                ]);

                $count++;
            } catch (\DomainException $e) {
                $this->logger->warning('Skipped marking draft run as stale: ' . $e->getMessage(), [
                    'run_id' => (string)$run->getId(),
                ]);
            }
        }

        return [$count, $audits];
    }

    /**
     * @return array{0: int, 1: array<int, array{run: QuoteComparisonRun, reason: string}>}
     */
    private function markExpiredRuns(string $tenantId): array
    {
        $expiredRuns = $this->runRepository->findExpiredRuns($tenantId);
        $count = 0;
        $audits = [];

        foreach ($expiredRuns as $run) {
            try {
                $run->markStale();
                $reason = sprintf('Run expired at %s.', $run->getExpiresAt()?->format(\DATE_ATOM) ?? 'unknown');
                $audits[] = ['run' => $run, 'reason' => $reason];

                $this->logger->info('Marked expired comparison run as stale', [
                    'run_id' => (string) $run->getId(),
                    'tenant_id' => $run->getTenantId(),
                    'rfq_id' => $run->getRfqId(),
                    'expires_at' => $run->getExpiresAt()?->format(\DATE_ATOM),
                ]);

                $count++;
            } catch (\DomainException $e) {
                $this->logger->warning('Skipped marking expired run as stale: ' . $e->getMessage(), [
                    'run_id' => (string)$run->getId(),
                ]);
            }
        }

        return [$count, $audits];
    }
}
