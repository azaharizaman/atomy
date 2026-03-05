<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\QuoteComparisonRequestDto;
use App\Entity\QuoteComparisonRun;
use App\Entity\QuoteDecisionTrailEntry;
use App\Repository\QuoteComparisonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\Services\QuoteComparisonMatrixService;
use Nexus\QuotationIntelligence\Services\RuleBasedRiskAssessmentService;
use Nexus\QuotationIntelligence\Services\WeightedVendorScoringService;
use Nexus\QuotationIntelligence\Services\HighRiskApprovalGateService;
use Nexus\QuotationIntelligence\Services\HashChainedDecisionTrailWriter;

final readonly class QuoteComparisonApplicationService
{
    public function __construct(
        private QuoteComparisonRunRepository $runRepository,
        private EntityManagerInterface $entityManager,
        private QuoteComparisonMatrixService $matrixService,
        private RuleBasedRiskAssessmentService $riskService,
        private WeightedVendorScoringService $scoringService,
        private HighRiskApprovalGateService $approvalGateService,
        private HashChainedDecisionTrailWriter $decisionTrailWriter
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(string $tenantId, QuoteComparisonRequestDto $request): array
    {
        if ($request->vendors === []) {
            throw new \InvalidArgumentException('vendors payload is required.');
        }
        $rfqId = $request->rfqId;
        $idempotencyKey = $request->idempotencyKey;

        if ($idempotencyKey !== null) {
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

        $matrix = $this->matrixService->buildMatrix($tenantId, $rfqId, $vendorLineSets);
        $scoring = $this->scoringService->score($tenantId, $rfqId, $vendorEvaluations);
        $approval = $this->approvalGateService->evaluate($vendorsOut, $scoring);
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

        $status = $approval['status'] === QuoteComparisonRun::STATUS_PENDING_APPROVAL 
            ? QuoteComparisonRun::STATUS_PENDING_APPROVAL 
            : QuoteComparisonRun::STATUS_APPROVED;

        $run = new QuoteComparisonRun(
            tenantId: $tenantId,
            rfqId: $rfqId,
            idempotencyKey: $idempotencyKey,
            requestPayload: $payload,
            matrixPayload: $matrix,
            scoringPayload: $scoring,
            approvalPayload: $approval,
            responsePayload: [], // Placeholder
            status: $status
        );

        $finalResponse = [
            'run_id' => $run->getId(),
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'status' => $run->getStatus(),
            'approval' => $approval,
            'matrix' => $matrix,
            'scoring' => $scoring,
            'decision_trail' => $decisionTrail,
            'vendors' => $vendorsOut,
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
            if ($idempotencyKey !== null) {
                $this->entityManager->clear(); // Clear to avoid broken state
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
