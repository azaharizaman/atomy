<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\Contracts\ProviderNormalizationClientInterface;
use App\Adapters\Ai\Contracts\ProviderSourcingRecommendationClientInterface;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Adapters\Ai\Exceptions\AiTransportFailedException;
use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;
use App\Adapters\Ai\Exceptions\AiTransportUnavailableException;
use App\Adapters\Ai\Exceptions\ComparisonAwardAiException;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use InvalidArgumentException;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;
use RuntimeException;

final readonly class ProviderContractVerificationService implements ProviderContractVerifierInterface
{
    public const ENDPOINT_GROUPS = [
        'document',
        'normalization',
        'sourcing_recommendation',
        'comparison_award',
        'insight',
        'governance',
    ];

    public function __construct(
        private ProviderDocumentIntelligenceClientInterface $documentClient,
        private ProviderNormalizationClientInterface $normalizationClient,
        private ProviderSourcingRecommendationClientInterface $recommendationClient,
        private ComparisonAwardAiClientInterface $comparisonAwardClient,
        private ProviderInsightClientInterface $insightClient,
        private ProviderGovernanceClientInterface $governanceClient,
    ) {
    }

    public function endpointGroups(): array
    {
        return self::ENDPOINT_GROUPS;
    }

    public function assertEndpointGroups(array $endpointGroups): void
    {
        $unsupported = array_values(array_diff($endpointGroups, $this->endpointGroups()));
        if ($unsupported !== []) {
            throw new InvalidArgumentException(
                'Unsupported endpoint group(s): ' . implode(', ', $unsupported),
            );
        }
    }

    public function verify(array $endpointGroups, string $tenantId, string $rfqId): array
    {
        $this->assertEndpointGroups($endpointGroups);

        $results = [];
        foreach ($endpointGroups as $endpointGroup) {
            try {
                $payload = $this->verifyEndpointGroup($endpointGroup, $tenantId, $rfqId);
            } catch (AiTransportInvalidResponseException $exception) {
                $results[] = $this->failedResultForException($endpointGroup, $exception);

                continue;
            } catch (AiTransportUnavailableException $exception) {
                $results[] = $this->failedResultForException($endpointGroup, $exception);

                continue;
            } catch (AiTransportFailedException $exception) {
                $results[] = $this->failedResultForException($endpointGroup, $exception);

                continue;
            } catch (ComparisonAwardAiException $exception) {
                $results[] = $this->failedResultForException($endpointGroup, $exception);

                continue;
            }

            if (! is_array($payload) || array_is_list($payload)) {
                $results[] = new ProviderContractVerificationResult(
                    endpointGroup: $endpointGroup,
                    severity: AiProviderCheckSeverity::FAILED,
                    verified: false,
                    reasonCodes: ['provider_invalid_payload'],
                    message: 'Provider contract verification returned an invalid payload for ' . $endpointGroup,
                );

                continue;
            }

            $results[] = new ProviderContractVerificationResult(
                endpointGroup: $endpointGroup,
                severity: AiProviderCheckSeverity::OK,
                verified: true,
                reasonCodes: ['provider_available'],
                message: 'Verified provider contract for ' . $endpointGroup,
            );
        }

        return $results;
    }

    /**
     * @return array<mixed>
     */
    private function verifyEndpointGroup(string $endpointGroup, string $tenantId, string $rfqId): array
    {
        return match ($endpointGroup) {
            'document' => $this->documentClient->extract([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'document_id' => 'plan6-doc',
                'filename' => 'plan6.pdf',
                'mime_type' => 'application/pdf',
            ]),
            'normalization' => $this->normalizationClient->suggest([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'source_lines' => [['id' => 'src-1', 'text' => 'Plan 6 source line']],
            ]),
            'sourcing_recommendation' => $this->recommendationClient->enrich(
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
            'comparison_award' => $this->comparisonAwardClient->comparisonOverlay(new ComparisonOverlayRequest(
                tenantId: $tenantId,
                rfqId: $rfqId,
                mode: 'preview',
                comparison: ['summary' => 'Plan 6 comparison contract verification'],
                snapshot: null,
            ))->payload,
            'insight' => $this->insightClient->summarize(new InsightSummaryRequest(
                featureKey: 'dashboard_ai_summary',
                tenantId: $tenantId,
                subjectType: 'dashboard_kpis',
                facts: ['active_rfqs' => 0],
            )),
            'governance' => $this->governanceClient->narrate(new GovernanceNarrativeRequest(
                featureKey: 'governance_ai_narrative',
                tenantId: $tenantId,
                vendorId: 'vendor-1',
                facts: ['summary_scores' => ['compliance' => 90], 'warning_flags' => []],
            )),
            default => throw new InvalidArgumentException('Unsupported endpoint group: ' . $endpointGroup),
        };
    }

    private function failedResultForException(string $endpointGroup, RuntimeException $exception): ProviderContractVerificationResult
    {
        return new ProviderContractVerificationResult(
            endpointGroup: $endpointGroup,
            severity: AiProviderCheckSeverity::FAILED,
            verified: false,
            reasonCodes: [$this->reasonCodeForException($exception)],
            message: 'Provider contract verification failed for ' . $endpointGroup . '.',
        );
    }

    private function reasonCodeForException(RuntimeException $exception): string
    {
        return match (true) {
            $exception instanceof AiTransportInvalidResponseException => 'provider_invalid_payload',
            $exception instanceof AiTransportUnavailableException => 'provider_unavailable',
            $exception instanceof AiTransportFailedException => 'provider_request_failed',
            $exception instanceof ComparisonAwardAiException => 'provider_contract_failed',
            default => 'provider_contract_failed',
        };
    }
}
