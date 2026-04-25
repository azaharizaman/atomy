<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

interface DocumentExtractionMapperInterface
{
    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public function map(array $response): array;
}
