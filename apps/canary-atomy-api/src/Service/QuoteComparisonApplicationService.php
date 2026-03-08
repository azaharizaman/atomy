<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\QuoteComparisonRequestDto;
use App\Entity\QuoteComparisonRun;
use App\Entity\QuoteDecisionTrailEntry;
use App\Exception\ComparisonRunNotFoundException;
use App\Repository\QuoteComparisonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\Services\QuoteComparisonMatrixService;
use Nexus\QuotationIntelligence\Services\RuleBasedRiskAssessmentService;
use Nexus\QuotationIntelligence\Services\WeightedVendorScoringService;
use Nexus\QuotationIntelligence\Services\HighRiskApprovalGateService;
use Nexus\QuotationIntelligence\Services\HashChainedDecisionTrailWriter;
use Nexus\QuotationIntelligence\Contracts\ComparisonReadinessValidatorInterface;

final readonly class QuoteComparisonApplicationService
{
    public function __construct(
        private QuoteComparisonRunRepository $runRepository,
        private EntityManagerInterface $entityManager,
        private QuoteComparisonMatrixService $matrixService,
        private RuleBasedRiskAssessmentService $riskService,
        private WeightedVendorScoringService $scoringService,
        private HighRiskApprovalGateService $approvalGateService,
        private HashChainedDecisionTrailWriter $decisionTrailWriter,
        private ComparisonReadinessValidatorInterface $readinessValidator,
    ) {
    }

    /**
     * Execute a comparison (preview or final) and persist.
     *
     * @return array<string, mixed>
     */
    public function compare(
        string $tenantId,
        QuoteComparisonRequestDto $request,
        bool $isPreview = false,
        ?string $createdBy = null,
    ): array {
        if ($request->vendors === []) {
            throw new \InvalidArgumentException('vendors payload is required.');
        }
        $rfqId = $request->rfqId;
        $idempotencyKey = $request->idempotencyKey;

        if ($idempotencyKey !== null && !$isPreview) {
            $existing = $this->runRepository->findByTenantRfqAndIdempotency($tenantId, $rfqId, $idempotencyKey);
            if ($existing !== null) {
                $response = $existing->getResponsePayload();
                $response['idempotent_replay'] = true;
                return $response;
            }
        }

        $payload = $request->toPayload();
        $rawVendors = $request->vendors;

        $vendorLineSets = [];
        $vendorEvaluations = [];
        $vendorsOut = [];

        foreach ($rawVendors as $index => $rawVendor) {
            if (!is_array($rawVendor)) {
                throw new \InvalidArgumentException(sprintf('vendors[%d] must be an object.', $index));
            }
            $vendorId = (string)($rawVendor['vendor_id'] ?? '');
            if ($vendorId === '') {
                throw new \InvalidArgumentException(sprintf('vendors[%d].vendor_id is required.', $index));
            }

            $rawLines = $rawVendor['lines'] ?? null;
            if (!is_array($rawLines)) {
                throw new \InvalidArgumentException(sprintf('vendors[%d].lines must be an array for vendor %s.', $index, $vendorId));
            }

            $lines = $this->hydrateLines($rawLines, $vendorId);
            $baseRisks = $this->riskService->assess($tenantId, $rfqId, $lines);
            $inputRisks = is_array($rawVendor['risks'] ?? null) ? $rawVendor['risks'] : [];
            $mergedRisks = array_merge($baseRisks, $inputRisks);

            $vendorLineSets[] = [
                'vendor_id' => $vendorId,
                'lines' => $lines,
            ];
            $vendorEvaluations[] = [
                'vendor_id' => $vendorId,
                'lines' => $lines,
                'risks' => $mergedRisks,
            ];
            $vendorsOut[] = [
                'vendor_id' => $vendorId,
                'line_count' => count($lines),
                'risks' => $mergedRisks,
            ];
        }

        $readiness = $this->readinessValidator->validate($tenantId, $rfqId, $vendorLineSets, $isPreview);
        $readinessPayload = [
            'is_ready' => $readiness->isReady(),
            'is_preview_only' => $readiness->isPreviewOnly(),
            'blockers' => $readiness->getBlockers(),
            'warnings' => $readiness->getWarnings(),
        ];

        $matrix = $this->matrixService->buildMatrix($tenantId, $rfqId, $vendorLineSets);
        $scoring = $this->scoringService->score($tenantId, $rfqId, $vendorEvaluations);
        $approval = $this->approvalGateService->evaluate($vendorsOut, $scoring);

        $decisionTrail = [];
        if (!$isPreview) {
            $decisionTrail = $this->decisionTrailWriter->write($tenantId, $rfqId, [
                [
                    'event_type' => QuoteDecisionTrailEntry::EVENT_TYPE_MATRIX_BUILT,
                    'payload' => ['cluster_count' => count($matrix['clusters'] ?? [])],
                ],
                [
                    'event_type' => QuoteDecisionTrailEntry::EVENT_TYPE_SCORING_COMPUTED,
                    'payload' => [
                        'top_vendor_id' => $scoring['ranking'][0]['vendor_id'] ?? '',
                        'top_vendor_score' => $scoring['ranking'][0]['total_score'] ?? 0.0,
                    ],
                ],
                [
                    'event_type' => QuoteDecisionTrailEntry::EVENT_TYPE_APPROVAL_EVALUATED,
                    'payload' => $approval,
                ],
            ]);
        }

        if ($isPreview) {
            $status = QuoteComparisonRun::STATUS_DRAFT;
            $runName = sprintf('Preview — %s', (new \DateTimeImmutable())->format('Y-m-d H:i'));
        } else {
            $status = $approval['status'] === QuoteComparisonRun::STATUS_PENDING_APPROVAL
                ? QuoteComparisonRun::STATUS_PENDING_APPROVAL
                : QuoteComparisonRun::STATUS_APPROVED;
            $runName = sprintf('Run — %s', (new \DateTimeImmutable())->format('Y-m-d H:i'));
        }

        $run = new QuoteComparisonRun(
            tenantId: $tenantId,
            rfqId: $rfqId,
            name: $runName,
            description: null,
            idempotencyKey: $isPreview ? null : $idempotencyKey,
            isPreview: $isPreview,
            createdBy: $createdBy,
            requestPayload: $payload,
            matrixPayload: $matrix,
            scoringPayload: $scoring,
            approvalPayload: $approval,
            responsePayload: [],
            readinessPayload: $readinessPayload,
            status: $status,
        );

        $finalResponse = [
            'run_id' => $run->getId(),
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'name' => $run->getName(),
            'status' => $run->getStatus(),
            'is_preview' => $isPreview,
            'approval' => $approval,
            'matrix' => $matrix,
            'scoring' => $scoring,
            'decision_trail' => $decisionTrail,
            'vendors' => $vendorsOut,
            'readiness' => $readinessPayload,
            'idempotent_replay' => false,
        ];

        $run->markResponsePayload($finalResponse);

        $this->entityManager->persist($run);

        foreach ($decisionTrail as $entry) {
            $this->entityManager->persist(new QuoteDecisionTrailEntry(
                comparisonRun: $run,
                tenantId: $tenantId,
                rfqId: $rfqId,
                sequence: (int)$entry['sequence'],
                eventType: (string)$entry['event_type'],
                payloadHash: (string)$entry['payload_hash'],
                previousHash: (string)$entry['previous_hash'],
                entryHash: (string)$entry['entry_hash'],
                occurredAt: new \DateTimeImmutable((string)$entry['occurred_at'])
            ));
        }

        try {
            $this->entityManager->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            if ($idempotencyKey !== null && !$isPreview) {
                $this->entityManager->clear();
                $existing = $this->runRepository->findByTenantRfqAndIdempotency($tenantId, $rfqId, $idempotencyKey);
                if ($existing !== null) {
                    $response = $existing->getResponsePayload();
                    $response['idempotent_replay'] = true;
                    return $response;
                }
            }
            throw new \RuntimeException('Conflict creating comparison run.', 409);
        }

        return $finalResponse;
    }

    /**
     * Save (promote) a draft/preview run with a name, optional description, and expiry.
     *
     * @return array<string, mixed>
     */
    public function saveRun(
        string $tenantId,
        string $runId,
        string $name,
        ?string $description = null,
        ?\DateTimeImmutable $expiresAt = null,
    ): array {
        $run = $this->runRepository->findByIdAndTenant($runId, $tenantId);
        if ($run === null) {
            throw ComparisonRunNotFoundException::forId($runId);
        }

        if ($run->isTerminal()) {
            throw new \LogicException(sprintf(
                'Cannot save run "%s": it is in terminal status "%s".',
                $runId,
                $run->getStatus(),
            ));
        }

        $status = $run->getStatus() === QuoteComparisonRun::STATUS_DRAFT
            ? QuoteComparisonRun::STATUS_PENDING_APPROVAL
            : $run->getStatus();

        $run->save($name, $description, $status, $expiresAt);

        $this->entityManager->flush();

        return [
            'run_id' => $run->getId(),
            'name' => $run->getName(),
            'description' => $run->getDescription(),
            'status' => $run->getStatus(),
            'expires_at' => $run->getExpiresAt()?->format(\DATE_ATOM),
        ];
    }

    /**
     * Discard a run (soft-delete with audit trail).
     *
     * @return array<string, mixed>
     */
    public function discardRun(string $tenantId, string $runId, string $discardedBy): array
    {
        $run = $this->runRepository->findByIdAndTenant($runId, $tenantId);
        if ($run === null) {
            throw ComparisonRunNotFoundException::forId($runId);
        }

        if ($run->isTerminal()) {
            throw new \LogicException(sprintf(
                'Cannot discard run "%s": it is in terminal status "%s".',
                $runId,
                $run->getStatus(),
            ));
        }

        $run->discard($discardedBy);
        $this->entityManager->flush();

        return [
            'run_id' => $run->getId(),
            'status' => $run->getStatus(),
            'discarded_by' => $discardedBy,
            'discarded_at' => $run->getDiscardedAt()?->format(\DATE_ATOM),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rawLines
     * @return array<NormalizedQuoteLine>
     */
    private function hydrateLines(array $rawLines, string $vendorId): array
    {
        $lines = [];

        foreach ($rawLines as $index => $rawLine) {
            if (!is_array($rawLine)) {
                throw new \InvalidArgumentException(sprintf('line[%d] must be an object for vendor %s.', $index, $vendorId));
            }
            $metadata = is_array($rawLine['metadata'] ?? null) ? $rawLine['metadata'] : [];
            $metadata['vendor_id'] = $vendorId;

            $lines[] = new NormalizedQuoteLine(
                rfqLineId: (string)($rawLine['rfq_line_id'] ?? ''),
                vendorDescription: (string)($rawLine['vendor_description'] ?? ''),
                taxonomyCode: (string)($rawLine['taxonomy_code'] ?? ''),
                quotedQuantity: (float)($rawLine['quoted_quantity'] ?? 0.0),
                quotedUnit: (string)($rawLine['quoted_unit'] ?? ''),
                normalizedQuantity: (float)($rawLine['normalized_quantity'] ?? 0.0),
                quotedUnitPrice: (float)($rawLine['quoted_unit_price'] ?? 0.0),
                normalizedUnitPrice: (float)($rawLine['normalized_unit_price'] ?? 0.0),
                aiConfidence: (float)($rawLine['ai_confidence'] ?? 0.0),
                snippets: [],
                metadata: $metadata
            );
        }

        return $lines;
    }
}
