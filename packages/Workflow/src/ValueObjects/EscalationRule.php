<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Immutable escalation rule.
 */
final readonly class EscalationRule
{
    public function __construct(
        private string $threshold,
        private string $action,
        private ?string $target = null,
        private ?string $message = null
    ) {}

    public function getThreshold(): string
    {
        return $this->threshold;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'threshold' => $this->threshold,
            'action' => $this->action,
            'target' => $this->target,
            'message' => $this->message,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            threshold: $data['threshold'] ?? '',
            action: $data['action'] ?? '',
            target: $data['target'] ?? null,
            message: $data['message'] ?? null
        );
    }
}
