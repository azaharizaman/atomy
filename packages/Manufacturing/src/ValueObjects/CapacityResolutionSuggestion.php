<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

use Nexus\Manufacturing\Enums\ResolutionAction;

/**
 * Capacity Resolution Suggestion value object.
 *
 * Represents a suggested action to resolve a capacity constraint.
 */
final readonly class CapacityResolutionSuggestion
{
    /**
     * @param ResolutionAction $action Suggested action type
     * @param string $description Human-readable description
     * @param float $resolvesHours Hours of overload this resolves
     * @param int $priority Priority ranking (1 = highest)
     * @param float $estimatedCost Estimated additional cost
     * @param int $leadTimeImpact Impact on lead time in days (negative = faster)
     * @param bool $requiresApproval Whether approval is needed
     * @param bool $canAutoApply Whether can be applied automatically
     * @param array<string, mixed> $parameters Action-specific parameters
     * @param string|null $targetResourceId Target resource (alternative WC, etc.)
     * @param \DateTimeImmutable|null $suggestedDate Suggested new date (for reschedule)
     * @param string|null $reason Detailed reason for suggestion
     */
    public function __construct(
        public ResolutionAction $action,
        public string $description,
        public float $resolvesHours,
        public int $priority = 5,
        public float $estimatedCost = 0.0,
        public int $leadTimeImpact = 0,
        public bool $requiresApproval = false,
        public bool $canAutoApply = false,
        public array $parameters = [],
        public ?string $targetResourceId = null,
        public ?\DateTimeImmutable $suggestedDate = null,
        public ?string $reason = null,
    ) {
        if ($this->resolvesHours < 0) {
            throw new \InvalidArgumentException('Resolved hours cannot be negative');
        }
        if ($this->priority < 1) {
            throw new \InvalidArgumentException('Priority must be at least 1');
        }
    }

    /**
     * Check if this suggestion fully resolves the constraint.
     */
    public function fullyResolves(float $constraintHours): bool
    {
        return $this->resolvesHours >= $constraintHours;
    }

    /**
     * Get resolution effectiveness as percentage.
     */
    public function getEffectiveness(float $constraintHours): float
    {
        if ($constraintHours <= 0) {
            return 100.0;
        }
        return min(100, ($this->resolvesHours / $constraintHours) * 100);
    }

    /**
     * Get cost per resolved hour.
     */
    public function getCostPerHour(): float
    {
        if ($this->resolvesHours <= 0) {
            return $this->estimatedCost;
        }
        return $this->estimatedCost / $this->resolvesHours;
    }

    /**
     * Check if this is a low-cost solution.
     */
    public function isLowCost(float $threshold = 100.0): bool
    {
        return $this->estimatedCost <= $threshold;
    }

    /**
     * Check if this improves delivery time.
     */
    public function improvesDelivery(): bool
    {
        return $this->leadTimeImpact < 0;
    }

    /**
     * Compare priority with another suggestion.
     */
    public function hasHigherPriorityThan(self $other): bool
    {
        return $this->priority < $other->priority;
    }

    /**
     * Create a copy with updated priority.
     */
    public function withPriority(int $priority): self
    {
        return new self(
            action: $this->action,
            description: $this->description,
            resolvesHours: $this->resolvesHours,
            priority: $priority,
            estimatedCost: $this->estimatedCost,
            leadTimeImpact: $this->leadTimeImpact,
            requiresApproval: $this->requiresApproval,
            canAutoApply: $this->canAutoApply,
            parameters: $this->parameters,
            targetResourceId: $this->targetResourceId,
            suggestedDate: $this->suggestedDate,
            reason: $this->reason,
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action->value,
            'actionLabel' => $this->action->label(),
            'description' => $this->description,
            'resolvesHours' => $this->resolvesHours,
            'priority' => $this->priority,
            'estimatedCost' => $this->estimatedCost,
            'leadTimeImpact' => $this->leadTimeImpact,
            'requiresApproval' => $this->requiresApproval,
            'canAutoApply' => $this->canAutoApply,
            'parameters' => $this->parameters,
            'targetResourceId' => $this->targetResourceId,
            'suggestedDate' => $this->suggestedDate?->format('Y-m-d'),
            'reason' => $this->reason,
            'costPerHour' => $this->getCostPerHour(),
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            action: ResolutionAction::from($data['action']),
            description: $data['description'],
            resolvesHours: (float) $data['resolvesHours'],
            priority: (int) ($data['priority'] ?? 5),
            estimatedCost: (float) ($data['estimatedCost'] ?? 0.0),
            leadTimeImpact: (int) ($data['leadTimeImpact'] ?? 0),
            requiresApproval: (bool) ($data['requiresApproval'] ?? false),
            canAutoApply: (bool) ($data['canAutoApply'] ?? false),
            parameters: $data['parameters'] ?? [],
            targetResourceId: $data['targetResourceId'] ?? null,
            suggestedDate: isset($data['suggestedDate'])
                ? new \DateTimeImmutable($data['suggestedDate'])
                : null,
            reason: $data['reason'] ?? null,
        );
    }

    /**
     * Create alternative work center suggestion.
     */
    public static function alternativeWorkCenter(
        string $alternativeWorkCenterId,
        float $resolvesHours,
        float $additionalCost = 0.0
    ): self {
        return new self(
            action: ResolutionAction::ALTERNATIVE_WORK_CENTER,
            description: "Route to alternative work center {$alternativeWorkCenterId}",
            resolvesHours: $resolvesHours,
            priority: ResolutionAction::ALTERNATIVE_WORK_CENTER->getDefaultPriority(),
            estimatedCost: $additionalCost,
            leadTimeImpact: 0,
            requiresApproval: false,
            canAutoApply: true,
            targetResourceId: $alternativeWorkCenterId,
            reason: 'Alternative work center has available capacity',
        );
    }

    /**
     * Create overtime suggestion.
     */
    public static function overtime(
        float $overtimeHours,
        float $overtimeCostPerHour
    ): self {
        return new self(
            action: ResolutionAction::OVERTIME,
            description: "Add {$overtimeHours} hours of overtime",
            resolvesHours: $overtimeHours,
            priority: ResolutionAction::OVERTIME->getDefaultPriority(),
            estimatedCost: $overtimeHours * $overtimeCostPerHour,
            leadTimeImpact: 0,
            requiresApproval: false,
            canAutoApply: false,
            parameters: [
                'overtimeHours' => $overtimeHours,
                'costPerHour' => $overtimeCostPerHour,
            ],
            reason: 'Overtime can accommodate additional load',
        );
    }

    /**
     * Create reschedule suggestion.
     */
    public static function reschedule(
        \DateTimeImmutable $newDate,
        float $resolvesHours,
        int $daysDelayed
    ): self {
        return new self(
            action: ResolutionAction::RESCHEDULE,
            description: "Reschedule to {$newDate->format('Y-m-d')} ({$daysDelayed} days later)",
            resolvesHours: $resolvesHours,
            priority: ResolutionAction::RESCHEDULE->getDefaultPriority(),
            estimatedCost: 0.0,
            leadTimeImpact: $daysDelayed,
            requiresApproval: false,
            canAutoApply: true,
            suggestedDate: $newDate,
            parameters: ['daysDelayed' => $daysDelayed],
            reason: "Capacity available on {$newDate->format('Y-m-d')}",
        );
    }
}
