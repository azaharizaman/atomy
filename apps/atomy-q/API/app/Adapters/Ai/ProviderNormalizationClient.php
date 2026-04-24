<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final readonly class ProviderNormalizationClient
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
