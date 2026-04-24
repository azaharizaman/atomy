<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use App\Adapters\Ai\DTOs\InsightSummaryRequest;

interface ProviderInsightClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(InsightSummaryRequest $request): array;
}
