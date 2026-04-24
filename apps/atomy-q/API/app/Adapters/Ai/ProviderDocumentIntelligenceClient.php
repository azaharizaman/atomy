<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final readonly class ProviderDocumentIntelligenceClient
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function extract(array $payload): array
    {
        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_DOCUMENT, $payload);
    }
}
