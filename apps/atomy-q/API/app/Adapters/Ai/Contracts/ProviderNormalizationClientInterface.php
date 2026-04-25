<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

interface ProviderNormalizationClientInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function suggest(array $payload): array;
}
