<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\QuoteApprovalDecision;
use App\Entity\QuoteDecisionTrailEntry;
use App\Repository\QuoteComparisonRunRepository;
use App\Repository\QuoteDecisionTrailEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QuoteApprovalApplicationService
{
    public function __construct(
        private QuoteComparisonRunRepository $runRepository,
        private QuoteDecisionTrailEntryRepository $trailRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function decide(string $tenantId, string $runId, string $decision, string $reason, ?string $decidedBy): array
    {
        $run = $this->runRepository->findByIdAndTenant($runId, $tenantId);
        if ($run === null) {
            throw new \RuntimeException('Comparison run not found.');
        }

        $normalizedDecision = strtolower(trim($decision));
        if (!in_array($normalizedDecision, ['approve', 'reject'], true)) {
            throw new \InvalidArgumentException('decision must be approve or reject.');
        }
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('reason is required.');
        }
        if ($run->getStatus() !== 'pending_approval') {
            throw new \RuntimeException('Comparison run is not pending approval.');
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
        $sequence = $lastEntry ? $lastEntry->getSequence() + 1 : 1;
        $previousHash = $lastEntry ? $lastEntry->getEntryHash() : str_repeat('0', 64);
        $occurredAt = new \DateTimeImmutable();
        $payload = [
            'decision' => $normalizedDecision,
            'reason' => $reason,
            'decided_by' => $decidedBy,
        ];
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadHash = hash('sha256', $payloadJson);
        $entryHash = hash('sha256', json_encode([
            'tenant_id' => $tenantId,
            'rfq_id' => $run->getRfqId(),
            'sequence' => $sequence,
            'event_type' => 'approval_override',
            'payload_hash' => $payloadHash,
            'previous_hash' => $previousHash,
            'occurred_at' => $occurredAt->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));

        $trailEntry = new QuoteDecisionTrailEntry(
            comparisonRun: $run,
            tenantId: $tenantId,
            rfqId: $run->getRfqId(),
            sequence: $sequence,
            eventType: 'approval_override',
            payloadHash: $payloadHash,
            previousHash: $previousHash,
            entryHash: $entryHash,
            occurredAt: $occurredAt
        );
        $this->entityManager->persist($trailEntry);
        $this->entityManager->flush();

        return [
            'run_id' => $run->getId(),
            'status' => $run->getStatus(),
            'approval' => $run->getApprovalPayload(),
            'decision_trail_entry' => [
                'sequence' => $sequence,
                'event_type' => 'approval_override',
                'payload_hash' => $payloadHash,
                'previous_hash' => $previousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => $occurredAt->format(DATE_ATOM),
            ],
        ];
    }
}

