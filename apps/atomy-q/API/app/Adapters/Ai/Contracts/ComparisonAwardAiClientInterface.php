<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

interface ComparisonAwardAiClientInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function comparisonOverlay(array $payload): array;

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function awardGuidance(array $payload): array;

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function awardDebriefDraft(array $payload): array;

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function approvalSummary(array $payload): array;
}
