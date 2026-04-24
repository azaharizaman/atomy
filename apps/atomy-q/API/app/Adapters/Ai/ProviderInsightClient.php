<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;

final readonly class ProviderInsightClient implements ProviderInsightClientInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(InsightSummaryRequest $request): array
    {
        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_INSIGHT, [
            ...$request->toPayload(),
            'action' => 'summary',
        ]);
    }
}
