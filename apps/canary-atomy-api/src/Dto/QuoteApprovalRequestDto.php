<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class QuoteApprovalRequestDto
{
    public function __construct(
        public string $decision,
        public string $reason
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $decision = strtolower(trim((string)($payload['decision'] ?? '')));
        $reason = trim((string)($payload['reason'] ?? ''));

        if (!in_array($decision, ['approve', 'reject'], true)) {
            throw new \InvalidArgumentException('decision must be approve or reject.');
        }

        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required.');
        }

        return new self($decision, $reason);
    }
}

