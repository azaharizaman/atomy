<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

/**
 * Invokes the insight AI endpoint for dashboard and reporting summaries.
 *
 * The transport enforces associative provider responses and raises invalid-payload
 * exceptions before narrative adapters can persist or display malformed output.
 */
final readonly class ProviderInsightClient implements ProviderInsightClientInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
    ) {
    }

    /**
     * Returns the associative summary artifact produced by ProviderAiTransport::invoke()
     * against AiStatusSchema::ENDPOINT_GROUP_INSIGHT.
     *
     * The transport enforces an associative-array response and throws
     * AiTransportInvalidResponseException when the provider payload is invalid.
     *
     * Expected keys include:
     * - `summary`: string
     * - `confidence`: float|null
     * - `metadata`: array<string, mixed>|null
     * - `headline`, `highlights`, `recommendations`, `payload`, `provenance`: optional provider-specific fields
     *
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
