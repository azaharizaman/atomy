<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use App\Adapters\Ai\DTOs\DocumentExtractionRequest;

interface ProviderDocumentIntelligenceClientInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function extract(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function extractDocument(DocumentExtractionRequest $request): array;
}
