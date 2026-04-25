<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

interface ProviderDocumentIntelligenceClientInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function extract(array $payload): array;
}
