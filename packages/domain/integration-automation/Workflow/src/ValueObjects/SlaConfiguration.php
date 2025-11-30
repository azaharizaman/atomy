<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Immutable SLA configuration.
 */
final readonly class SlaConfiguration
{
    public function __construct(
        private string $duration,
        private bool $useBusinessHours = true,
        private ?string $onBreachAction = null
    ) {}

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function useBusinessHours(): bool
    {
        return $this->useBusinessHours;
    }

    public function getOnBreachAction(): ?string
    {
        return $this->onBreachAction;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'duration' => $this->duration,
            'use_business_hours' => $this->useBusinessHours,
            'on_breach_action' => $this->onBreachAction,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            duration: $data['duration'] ?? '1 day',
            useBusinessHours: $data['use_business_hours'] ?? true,
            onBreachAction: $data['on_breach_action'] ?? null
        );
    }
}
