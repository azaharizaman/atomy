<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderNormalizationClientInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

/**
 * Invokes the normalization AI endpoint for source-line suggestions.
 *
 * Suggestions are returned as provider payloads for buyer-review workflows; this
 * adapter does not treat AI output as accepted normalized data by itself.
 */
final readonly class ProviderNormalizationClient implements ProviderNormalizationClientInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function suggest(array $payload): array
    {
        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION, $payload);
    }
}
