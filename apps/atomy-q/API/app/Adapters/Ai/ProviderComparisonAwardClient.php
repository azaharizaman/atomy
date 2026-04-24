<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\DTOs\ApprovalSummaryRequest;
use App\Adapters\Ai\DTOs\ApprovalSummaryResponse;
use App\Adapters\Ai\DTOs\AwardDebriefDraftRequest;
use App\Adapters\Ai\DTOs\AwardDebriefDraftResponse;
use App\Adapters\Ai\DTOs\AwardGuidanceRequest;
use App\Adapters\Ai\DTOs\AwardGuidanceResponse;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\DTOs\ComparisonOverlayResponse;
use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
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
        $payload = $request->toPayload();

        try {
            return new ComparisonOverlayResponse($this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD, [
                ...$payload,
                'action' => 'comparison_overlay',
            ]));
        } catch (\Throwable $e) {
            throw ComparisonAwardAiException::fromThrowable($e, 'comparison_overlay', $payload);
        }
    }

    /**
     * @throws ComparisonAwardAiException
     */
    public function awardGuidance(AwardGuidanceRequest $request): AwardGuidanceResponse
    {
        $payload = $request->toPayload();

        try {
            return new AwardGuidanceResponse($this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD, [
                ...$payload,
                'action' => 'award_guidance',
            ]));
        } catch (\Throwable $e) {
            throw ComparisonAwardAiException::fromThrowable($e, 'award_guidance', $payload);
        }
    }

    /**
     * @throws ComparisonAwardAiException
     */
    public function awardDebriefDraft(AwardDebriefDraftRequest $request): AwardDebriefDraftResponse
    {
        $payload = $request->toPayload();

        try {
            return new AwardDebriefDraftResponse($this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD, [
                ...$payload,
                'action' => 'award_debrief_draft',
            ]));
        } catch (\Throwable $e) {
            throw ComparisonAwardAiException::fromThrowable($e, 'award_debrief_draft', $payload);
        }
    }

    /**
     * @throws ComparisonAwardAiException
     */
    public function approvalSummary(ApprovalSummaryRequest $request): ApprovalSummaryResponse
    {
        $payload = $request->toPayload();

        try {
            return new ApprovalSummaryResponse($this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD, [
                ...$payload,
                'action' => 'approval_summary',
            ]));
        } catch (\Throwable $e) {
            throw ComparisonAwardAiException::fromThrowable($e, 'approval_summary', $payload);
        }
    }
}
