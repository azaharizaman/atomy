<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\DTOs\ApprovalSummaryRequest;
use App\Adapters\Ai\DTOs\ApprovalSummaryResponse;
use App\Adapters\Ai\DTOs\AwardDebriefDraftRequest;
use App\Adapters\Ai\DTOs\AwardDebriefDraftResponse;
use App\Adapters\Ai\DTOs\AwardGuidanceRequest;
use App\Adapters\Ai\DTOs\AwardGuidanceResponse;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\DTOs\ComparisonOverlayResponse;
use App\Adapters\Ai\Exceptions\ComparisonAwardAiException;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final readonly class ProviderComparisonAwardClient implements ComparisonAwardAiClientInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
    ) {
    }

    /**
     * @throws ComparisonAwardAiException
     */
    public function comparisonOverlay(ComparisonOverlayRequest $request): ComparisonOverlayResponse
    {
        return $this->invokeComparisonAward(
            'comparison_overlay',
            $request->toPayload(),
            ComparisonOverlayResponse::class,
        );
    }

    /**
     * @throws ComparisonAwardAiException
     */
    public function awardGuidance(AwardGuidanceRequest $request): AwardGuidanceResponse
    {
        return $this->invokeComparisonAward(
            'award_guidance',
            $request->toPayload(),
            AwardGuidanceResponse::class,
        );
    }

    /**
     * @throws ComparisonAwardAiException
     */
    public function awardDebriefDraft(AwardDebriefDraftRequest $request): AwardDebriefDraftResponse
    {
        return $this->invokeComparisonAward(
            'award_debrief_draft',
            $request->toPayload(),
            AwardDebriefDraftResponse::class,
        );
    }

    /**
     * @throws ComparisonAwardAiException
     */
    public function approvalSummary(ApprovalSummaryRequest $request): ApprovalSummaryResponse
    {
        return $this->invokeComparisonAward(
            'approval_summary',
            $request->toPayload(),
            ApprovalSummaryResponse::class,
        );
    }

    /**
     * @template T of object
     * @param array<string, mixed> $payload
     * @param class-string<T> $responseClass
     * @return T
     *
     * @throws ComparisonAwardAiException
     */
    private function invokeComparisonAward(string $action, array $payload, string $responseClass): object
    {
        try {
            return new $responseClass($this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD, [
                ...$payload,
                'action' => $action,
            ]));
        } catch (\Throwable $e) {
            throw ComparisonAwardAiException::fromThrowable($e, $action, $this->sanitizedContext($payload));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizedContext(array $payload): array
    {
        $context = [];

        foreach (['tenant_id', 'rfq_id', 'approval_id', 'comparison_run_id'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                $context[$key] = (string) $payload[$key];
            }
        }

        return $context;
    }
}
