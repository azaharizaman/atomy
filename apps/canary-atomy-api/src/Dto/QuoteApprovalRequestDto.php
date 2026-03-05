<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class QuoteApprovalRequestDto
{
    private function __construct(
        public string $decision,
        public string $reason
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (!isset($payload['decision']) || !is_string($payload['decision'])) {
            throw new \InvalidArgumentException('decision must be a string.');
        }
        if (!isset($payload['reason']) || !is_string($payload['reason'])) {
            throw new \InvalidArgumentException('reason must be a string.');
        }

        $decision = strtolower(trim($payload['decision']));
        $reason = trim($payload['reason']);

        if (!in_array($decision, ['approve', 'reject'], true)) {
            throw new \InvalidArgumentException('decision must be approve or reject.');
        }

        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required.');
        }

        return new self($decision, $reason);
    }
}

