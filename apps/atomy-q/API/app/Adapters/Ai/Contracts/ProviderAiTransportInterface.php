<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

interface ProviderAiTransportInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function invoke(string $endpointGroup, array $payload): array;
}
