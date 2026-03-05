<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\QuoteApprovalDecision;
use App\Entity\QuoteDecisionTrailEntry;
use App\Exception\ComparisonRunNotFoundException;
use App\Exception\ComparisonRunNotPendingApprovalException;
use App\Repository\QuoteComparisonRunRepository;
use App\Repository\QuoteDecisionTrailEntryRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\QuotationIntelligence\Services\HashChainedDecisionTrailWriter;

final readonly class QuoteApprovalApplicationService
{
    public function __construct(
        private QuoteComparisonRunRepository $runRepository,
        private QuoteDecisionTrailEntryRepository $trailRepository,
        private EntityManagerInterface $entityManager,
        private HashChainedDecisionTrailWriter $decisionTrailWriter
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function decide(string $tenantId, string $runId, string $decision, string $reason, ?string $decidedBy): array
    {
        return $this->entityManager->wrapInTransaction(function () use ($tenantId, $runId, $decision, $reason, $decidedBy) {
            $run = $this->runRepository->findByIdAndTenant($runId, $tenantId);
            if ($run === null) {
                throw ComparisonRunNotFoundException::forId($runId);
            }

            // Acquire pessimistic write lock
            $this->entityManager->lock($run, LockMode::PESSIMISTIC_WRITE);

            $normalizedDecision = strtolower(trim($decision));
            if (!in_array($normalizedDecision, ['approve', 'reject'], true)) {
                throw new \InvalidArgumentException('decision must be approve or reject.');
            }
            if (trim($reason) === '') {
                throw new \InvalidArgumentException('reason is required.');
            }
            if ($run->getStatus() !== 'pending_approval') {
                throw ComparisonRunNotPendingApprovalException::forId($runId);
            }

            $status = $normalizedDecision === 'approve' ? 'approved' : 'rejected';
            $approvalPayload = $run->getApprovalPayload();
            $approvalPayload['status'] = $status;
            $approvalPayload['override'] = [
                'decision' => $normalizedDecision,
                'reason' => $reason,
                'decided_by' => $decidedBy,
                'decided_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ];

            $run->markDecision($status, $approvalPayload);

            $decisionRecord = new QuoteApprovalDecision(
                comparisonRun: $run,
                tenantId: $tenantId,
                rfqId: $run->getRfqId(),
                decision: $normalizedDecision,
                reason: $reason,
                decidedBy: $decidedBy
            );
            $this->entityManager->persist($decisionRecord);

            $lastEntry = $this->trailRepository->findLastForRun($run);
            $previousHash = $lastEntry ? $lastEntry->getEntryHash() : str_repeat('0', 64);
            $nextSequence = $lastEntry ? $lastEntry->getSequence() + 1 : 1;

            $payload = [
                'decision' => $normalizedDecision,
                'reason' => $reason,
                'decided_by' => $decidedBy,
            ];

            $trailData = $this->decisionTrailWriter->write(
                tenantId: $tenantId,
                rfqId: $run->getRfqId(),
                entries: [
                    [
                        'event_type' => 'approval_override',
                        'payload' => $payload,
                    ]
                ],
                startingSequence: $nextSequence,
                previousHash: $previousHash
            );

            $trailEntryData = $trailData[0];

            $trailEntry = new QuoteDecisionTrailEntry(
                comparisonRun: $run,
                tenantId: $tenantId,
                rfqId: $run->getRfqId(),
                sequence: (int)$trailEntryData['sequence'],
                eventType: 'approval_override',
                payloadHash: (string)$trailEntryData['payload_hash'],
                previousHash: (string)$trailEntryData['previous_hash'],
                entryHash: (string)$trailEntryData['entry_hash'],
                occurredAt: new \DateTimeImmutable((string)$trailEntryData['occurred_at'])
            );
            $this->entityManager->persist($trailEntry);

            try {
                $this->entityManager->flush();
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                throw new \RuntimeException('Approval conflict: this run was already decided by another process.', 409);
            }

            return [
                'run_id' => $run->getId(),
                'status' => $run->getStatus(),
                'approval' => $run->getApprovalPayload(),
                'decision_trail_entry' => $trailEntryData,
            ];
        });
    }
}
