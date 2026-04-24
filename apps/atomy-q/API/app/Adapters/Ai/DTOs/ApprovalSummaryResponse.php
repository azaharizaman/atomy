<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

final readonly class ApprovalSummaryResponse
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public array $payload,
    ) {}
}
