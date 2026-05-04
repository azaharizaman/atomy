<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;
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
        private AiEndpointRegistryInterface $endpointRegistry,
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
        $endpointConfig = $this->endpointRegistry->endpointConfig(AiStatusSchema::ENDPOINT_GROUP_INSIGHT);

        if ($endpointConfig !== null && $this->usesChatCompletions($endpointConfig->providerName, $endpointConfig->endpointUri)) {
            return $this->summarizeWithChatCompletions($request, $endpointConfig->providerName, $endpointConfig->metadata);
        }

        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_INSIGHT, [
            ...$request->toPayload(),
            'action' => 'summary',
        ]);
    }

    /**
     * @param array<string, scalar|null> $metadata
     * @return array<string, mixed>
     */
    private function summarizeWithChatCompletions(
        InsightSummaryRequest $request,
        string $providerName,
        array $metadata,
    ): array {
        $modelId = $this->modelId($metadata);
        $response = $this->transport->invoke(
            AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
            [
                'model' => $modelId,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => implode(' ', [
                            'You generate concise procurement insight narratives for Atomy-Q.',
                            'Return only a valid JSON object with keys headline, summary, bullets, recommendations, confidence, and metadata.',
                            'Use arrays for bullets and recommendations.',
                        ]),
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            ...$request->toPayload(),
                            'action' => 'summary',
                        ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
                    ],
                ],
                'temperature' => 0.2,
            ],
        );
        $payload = $this->decodeChatCompletionPayload($response);
        $provenance = is_array($response['provenance'] ?? null) ? $response['provenance'] : [];

        return [
            'payload' => $payload,
            'provenance' => [
                ...$provenance,
                'provider_name' => $this->stringOrDefault($provenance['provider_name'] ?? null, $providerName),
                'endpoint_group' => AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                'model' => $this->stringOrDefault($provenance['model'] ?? null, $modelId),
                'provider_request_id' => $this->stringOrNull($response['id'] ?? null)
                    ?? $this->stringOrNull($provenance['provider_request_id'] ?? null),
            ],
        ];
    }

    private function usesChatCompletions(string $providerName, string $endpointUri): bool
    {
        return strtolower(trim($providerName)) === 'openrouter'
            || str_contains(strtolower($endpointUri), '/chat/completions');
    }

    /**
     * @param array<string, scalar|null> $metadata
     */
    private function modelId(array $metadata): string
    {
        $modelId = $metadata['model_id'] ?? null;

        if (! is_string($modelId) || trim($modelId) === '') {
            throw new AiTransportInvalidResponseException('Insight chat-completions endpoint requires AI_INSIGHT_MODEL_ID.');
        }

        return trim($modelId);
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function decodeChatCompletionPayload(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new AiTransportInvalidResponseException('Insight chat-completions response did not include message content.');
        }

        $decoded = json_decode($this->stripJsonFence($content), true);

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new AiTransportInvalidResponseException('Insight chat-completions response content must be a JSON object.');
        }

        return $decoded;
    }

    private function stripJsonFence(string $content): string
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }

        return trim($content);
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        return $this->stringOrNull($value) ?? $default;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
