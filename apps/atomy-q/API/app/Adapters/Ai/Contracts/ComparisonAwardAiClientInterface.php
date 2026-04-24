<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use App\Adapters\Ai\DTOs\ApprovalSummaryRequest;
use App\Adapters\Ai\DTOs\ApprovalSummaryResponse;
use App\Adapters\Ai\DTOs\AwardDebriefDraftRequest;
use App\Adapters\Ai\DTOs\AwardDebriefDraftResponse;
use App\Adapters\Ai\DTOs\AwardGuidanceRequest;
use App\Adapters\Ai\DTOs\AwardGuidanceResponse;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\DTOs\ComparisonOverlayResponse;
use App\Adapters\Ai\Exceptions\ComparisonAwardAiException;

interface ComparisonAwardAiClientInterface
{
    /**
     * @throws ComparisonAwardAiException
     */
    public function comparisonOverlay(ComparisonOverlayRequest $request): ComparisonOverlayResponse;

    /**
     * @throws ComparisonAwardAiException
     */
    public function awardGuidance(AwardGuidanceRequest $request): AwardGuidanceResponse;

    /**
     * @throws ComparisonAwardAiException
     */
    public function awardDebriefDraft(AwardDebriefDraftRequest $request): AwardDebriefDraftResponse;

    /**
     * @throws ComparisonAwardAiException
     */
    public function approvalSummary(ApprovalSummaryRequest $request): ApprovalSummaryResponse;
}
