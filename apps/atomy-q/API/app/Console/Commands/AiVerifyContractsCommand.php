<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Adapters\Ai\ProviderDocumentIntelligenceClient;
use App\Adapters\Ai\ProviderNormalizationClient;
use App\Adapters\Ai\ProviderSourcingRecommendationClient;
use Illuminate\Console\Command;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;

final class AiVerifyContractsCommand extends Command
{
    protected $signature = 'atomy:ai-verify-contracts
        {--endpoint-group=* : Restrict verification to one or more endpoint groups}
        {--tenant-id=plan6-tenant : Tenant identifier used in sample payloads}
        {--rfq-id=plan6-rfq : RFQ identifier used in sample payloads}';

    protected $description = 'Run provider contract verification requests for every configured Atomy-Q AI endpoint group.';

    public function __construct(
        private readonly ProviderDocumentIntelligenceClient $documentClient,
        private readonly ProviderNormalizationClient $normalizationClient,
        private readonly ProviderSourcingRecommendationClient $recommendationClient,
        private readonly ComparisonAwardAiClientInterface $comparisonAwardClient,
        private readonly ProviderInsightClientInterface $insightClient,
        private readonly ProviderGovernanceClientInterface $governanceClient,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = trim((string) $this->option('tenant-id'));
        $rfqId = trim((string) $this->option('rfq-id'));
        if ($tenantId === '') {
            $this->error('The --tenant-id option must be non-empty.');

            return self::FAILURE;
        }

        if ($rfqId === '') {
            $this->error('The --rfq-id option must be non-empty.');

            return self::FAILURE;
        }

        $verifiers = [
            'document' => fn (): array => $this->documentClient->extract([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'document_id' => 'plan6-doc',
                'filename' => 'plan6.pdf',
                'mime_type' => 'application/pdf',
            ]),
            'normalization' => fn (): array => $this->normalizationClient->suggest([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'source_lines' => [['id' => 'src-1', 'text' => 'Plan 6 source line']],
            ]),
            'sourcing_recommendation' => fn (): array => $this->recommendationClient->enrich(
                new VendorRecommendationRequest(
                    tenantId: $tenantId,
                    rfqId: $rfqId,
                    categories: ['services'],
                    description: 'Operational hardening contract verification',
                    geography: 'MY',
                    spendBand: 'mid',
                    lineItemSummary: ['Line-item summary'],
                    candidates: [],
                ),
                [
                    new VendorRecommendationScoredCandidate(
                        vendorId: 'vendor-1',
                        vendorName: 'Vendor 1',
                        fitScore: 80,
                        confidenceBand: 'high',
                        recommendedReasonSummary: 'Meets deterministic fit checks.',
                        deterministicReasons: ['coverage'],
                    ),
                ],
            ),
            'comparison_award' => fn (): array => $this->comparisonAwardClient->comparisonOverlay(new ComparisonOverlayRequest(
                tenantId: $tenantId,
                rfqId: $rfqId,
                mode: 'preview',
                comparison: ['summary' => 'Plan 6 comparison contract verification'],
                snapshot: null,
            ))->payload,
            'insight' => fn (): array => $this->insightClient->summarize(new InsightSummaryRequest(
                featureKey: 'dashboard_ai_summary',
                tenantId: $tenantId,
                subjectType: 'dashboard_kpis',
                facts: ['active_rfqs' => 0],
            )),
            'governance' => fn (): array => $this->governanceClient->narrate(new GovernanceNarrativeRequest(
                featureKey: 'governance_ai_narrative',
                tenantId: $tenantId,
                vendorId: 'vendor-1',
                facts: ['summary_scores' => ['compliance' => 90], 'warning_flags' => []],
            )),
        ];

        foreach ($this->requestedEndpointGroups() as $endpointGroup) {
            $verifier = $verifiers[$endpointGroup] ?? null;
            if (! is_callable($verifier)) {
                $this->error('Unsupported endpoint group: ' . $endpointGroup);

                return self::FAILURE;
            }

            $result = $verifier();
            if (! is_array($result) || array_is_list($result)) {
                $this->error('Provider contract verification returned an invalid payload for ' . $endpointGroup);

                return self::FAILURE;
            }

            $this->info('Verified provider contract for ' . $endpointGroup);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function requestedEndpointGroups(): array
    {
        $requested = $this->option('endpoint-group');
        if (! is_array($requested) || $requested === []) {
            return ['document', 'normalization', 'sourcing_recommendation', 'comparison_award', 'insight', 'governance'];
        }

        $filtered = array_values(array_filter(array_map(
            static fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null,
            $requested,
        )));

        return $filtered === []
            ? ['document', 'normalization', 'sourcing_recommendation', 'comparison_award', 'insight', 'governance']
            : $filtered;
    }
}
