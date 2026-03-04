<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Coordinators;

use Nexus\QuotationIntelligence\Contracts\BatchQuoteComparisonCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteComparisonMatrixServiceInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\Contracts\VendorScoringServiceInterface;
use Nexus\QuotationIntelligence\Contracts\ApprovalGateServiceInterface;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\Exceptions\MissingVendorContextException;
use Psr\Log\LoggerInterface;

/**
 * Peer-aware batch quote comparison coordinator for a single RFQ.
 */
final readonly class BatchQuoteComparisonCoordinator implements BatchQuoteComparisonCoordinatorInterface
{
    public function __construct(
        private QuotationIntelligenceCoordinatorInterface $quoteCoordinator,
        private QuoteComparisonMatrixServiceInterface $matrixService,
        private RiskAssessmentServiceInterface $riskAssessmentService,
        private VendorScoringServiceInterface $vendorScoringService,
        private ApprovalGateServiceInterface $approvalGateService,
        private DecisionTrailWriterInterface $decisionTrailWriter,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function compareQuotes(string $tenantId, string $rfqId, array $documentIds): array
    {
        $vendorLineSets = [];

        foreach ($documentIds as $documentId) {
            $result = $this->quoteCoordinator->processQuote($tenantId, $documentId);
            $lines = $this->hydrateLines($result['lines'] ?? []);
            if ($lines === []) {
                continue;
            }

            $vendorId = $this->resolveVendorId($lines, (string)$documentId);
            $vendorLineSets[$vendorId] = [
                'vendor_id' => $vendorId,
                'lines' => array_values(array_merge($vendorLineSets[$vendorId]['lines'] ?? [], $lines)),
            ];
        }

        $vendorLineSets = array_values($vendorLineSets);
        $matrix = $this->matrixService->buildMatrix($tenantId, $rfqId, $vendorLineSets);

        $peerRisksByVendor = $this->buildPeerRisks($matrix, $vendorLineSets);
        $vendors = [];

        $vendorEvaluations = [];
        foreach ($vendorLineSets as $vendorLineSet) {
            $vendorId = (string)$vendorLineSet['vendor_id'];
            /** @var array<NormalizedQuoteLine> $lines */
            $lines = $vendorLineSet['lines'];

            $baseRisks = $this->riskAssessmentService->assess($tenantId, $rfqId, $lines);
            $mergedRisks = array_merge($baseRisks, $peerRisksByVendor[$vendorId] ?? []);
            $vendorEvaluations[] = [
                'vendor_id' => $vendorId,
                'lines' => $lines,
                'risks' => $mergedRisks,
            ];

            $vendors[] = [
                'vendor_id' => $vendorId,
                'line_count' => count($lines),
                'risks' => $mergedRisks,
            ];
        }

        $scoring = $this->vendorScoringService->score($tenantId, $rfqId, $vendorEvaluations);
        $approval = $this->approvalGateService->evaluate($vendors, $scoring);
        $decisionTrail = $this->decisionTrailWriter->write($tenantId, $rfqId, [
            [
                'event_type' => 'matrix_built',
                'payload' => [
                    'cluster_count' => count($matrix['clusters'] ?? []),
                ],
            ],
            [
                'event_type' => 'scoring_computed',
                'payload' => [
                    'top_vendor_id' => $scoring['ranking'][0]['vendor_id'] ?? '',
                    'top_vendor_score' => $scoring['ranking'][0]['total_score'] ?? 0.0,
                ],
            ],
            [
                'event_type' => 'approval_evaluated',
                'payload' => $approval,
            ],
        ]);

        $this->logger->info('Batch quote comparison completed', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'document_count' => count($documentIds),
            'vendor_count' => count($vendors),
        ]);

        return [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'documents_processed' => count($documentIds),
            'matrix' => $matrix,
            'scoring' => $scoring,
            'approval' => $approval,
            'decision_trail' => $decisionTrail,
            'vendors' => $vendors,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rawLines
     * @return array<NormalizedQuoteLine>
     */
    private function hydrateLines(array $rawLines): array
    {
        $lines = [];

        foreach ($rawLines as $rawLine) {
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
                metadata: is_array($rawLine['metadata'] ?? null) ? $rawLine['metadata'] : []
            );
        }

        return $lines;
    }

    /**
     * @param array<NormalizedQuoteLine> $lines
     */
    private function resolveVendorId(array $lines, string $documentId): string
    {
        $vendorId = (string)($lines[0]->metadata['vendor_id'] ?? '');
        if ($vendorId === '') {
            throw new MissingVendorContextException(
                sprintf('Missing vendor_id metadata for document "%s"', $documentId)
            );
        }

        return $vendorId;
    }

    /**
     * @param array<string, mixed> $matrix
     * @param array<int, array{vendor_id: string, lines: array<NormalizedQuoteLine>}> $vendorLineSets
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildPeerRisks(array $matrix, array $vendorLineSets): array
    {
        $peerRisksByVendor = [];
        $lineMap = [];

        foreach ($vendorLineSets as $vendorLineSet) {
            $vendorId = $vendorLineSet['vendor_id'];
            foreach ($vendorLineSet['lines'] as $line) {
                $lineKey = $line->rfqLineId !== '' ? $line->rfqLineId : $line->taxonomyCode;
                $lineMap[$vendorId][$lineKey] = $line;
            }
        }

        foreach (($matrix['clusters'] ?? []) as $cluster) {
            $clusterKey = (string)($cluster['cluster_key'] ?? '');
            $offers = is_array($cluster['offers'] ?? null) ? $cluster['offers'] : [];

            foreach ($offers as $offer) {
                $vendorId = (string)($offer['vendor_id'] ?? '');
                $rfqLineId = (string)($offer['rfq_line_id'] ?? '');
                $taxonomyCode = (string)($offer['taxonomy_code'] ?? '');
                $lineKey = $rfqLineId !== '' ? $rfqLineId : $taxonomyCode;

                $currentLine = $lineMap[$vendorId][$lineKey] ?? null;
                if (!$currentLine instanceof NormalizedQuoteLine) {
                    continue;
                }

                $peerLines = [];
                foreach ($offers as $peerOffer) {
                    $peerVendorId = (string)($peerOffer['vendor_id'] ?? '');
                    if ($peerVendorId === '' || $peerVendorId === $vendorId) {
                        continue;
                    }

                    $peerRfqLineId = (string)($peerOffer['rfq_line_id'] ?? '');
                    $peerTaxonomyCode = (string)($peerOffer['taxonomy_code'] ?? '');
                    $peerLineKey = $peerRfqLineId !== '' ? $peerRfqLineId : $peerTaxonomyCode;
                    $peerLine = $lineMap[$peerVendorId][$peerLineKey] ?? null;

                    if ($peerLine instanceof NormalizedQuoteLine) {
                        $peerLines[] = $peerLine;
                    }
                }

                if ($peerLines === []) {
                    continue;
                }

                if ($this->riskAssessmentService->isPricingAnomaly($currentLine, $peerLines)) {
                    $peerRisksByVendor[$vendorId][] = [
                        'level' => 'high',
                        'message' => 'Pricing anomaly detected against peer vendor lines',
                        'cluster_key' => $clusterKey,
                        'rfq_line_id' => $currentLine->rfqLineId,
                    ];
                }

                $termDeviationMessage = $this->detectCommercialTermDeviation($currentLine, $peerLines);
                if ($termDeviationMessage !== null) {
                    $peerRisksByVendor[$vendorId][] = [
                        'level' => 'medium',
                        'message' => $termDeviationMessage,
                        'cluster_key' => $clusterKey,
                        'rfq_line_id' => $currentLine->rfqLineId,
                    ];
                }
            }
        }

        return $peerRisksByVendor;
    }

    /**
     * @param array<NormalizedQuoteLine> $peerLines
     */
    private function detectCommercialTermDeviation(NormalizedQuoteLine $line, array $peerLines): ?string
    {
        $currentTerms = is_array($line->metadata['commercial_terms'] ?? null) ? $line->metadata['commercial_terms'] : [];
        if ($currentTerms === []) {
            return null;
        }

        $currentIncoterm = strtoupper((string)($currentTerms['incoterm'] ?? ''));
        $peerIncoterms = [];
        foreach ($peerLines as $peerLine) {
            $terms = is_array($peerLine->metadata['commercial_terms'] ?? null) ? $peerLine->metadata['commercial_terms'] : [];
            $peerIncoterm = strtoupper((string)($terms['incoterm'] ?? ''));
            if ($peerIncoterm !== '') {
                $peerIncoterms[] = $peerIncoterm;
            }
        }

        if ($currentIncoterm !== '' && $peerIncoterms !== [] && !in_array($currentIncoterm, $peerIncoterms, true)) {
            return sprintf('Commercial term deviation detected: incoterm "%s" differs from peer lines.', $currentIncoterm);
        }

        $currentPaymentDays = $currentTerms['payment_days'] ?? null;
        $peerPaymentDays = [];
        foreach ($peerLines as $peerLine) {
            $terms = is_array($peerLine->metadata['commercial_terms'] ?? null) ? $peerLine->metadata['commercial_terms'] : [];
            if (isset($terms['payment_days']) && is_int($terms['payment_days'])) {
                $peerPaymentDays[] = $terms['payment_days'];
            }
        }

        if (is_int($currentPaymentDays) && $peerPaymentDays !== []) {
            $avgPeerPaymentDays = (int)round(array_sum($peerPaymentDays) / count($peerPaymentDays));
            if (abs($currentPaymentDays - $avgPeerPaymentDays) > 15) {
                return sprintf(
                    'Commercial term deviation detected: payment days (%d) differ materially from peer average (%d).',
                    $currentPaymentDays,
                    $avgPeerPaymentDays
                );
            }
        }

        return null;
    }
}
