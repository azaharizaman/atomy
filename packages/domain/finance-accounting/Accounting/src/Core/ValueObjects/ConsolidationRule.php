<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

use Nexus\Accounting\Core\Enums\ConsolidationMethod;

/**
 * Consolidation elimination rule.
 *
 * Defines how intercompany transactions should be eliminated.
 */
final readonly class ConsolidationRule
{
    public function __construct(
        private string $id,
        private string $name,
        private string $ruleType,
        private string $sourceEntityId,
        private string $targetEntityId,
        private ?string $accountPattern = null,
        private ?float $percentage = null,
        private array $metadata = []
    ) {
        if ($this->percentage !== null && ($this->percentage < 0 || $this->percentage > 100)) {
            throw new \InvalidArgumentException('Percentage must be between 0 and 100');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRuleType(): string
    {
        return $this->ruleType;
    }

    public function getSourceEntityId(): string
    {
        return $this->sourceEntityId;
    }

    public function getTargetEntityId(): string
    {
        return $this->targetEntityId;
    }

    public function getAccountPattern(): ?string
    {
        return $this->accountPattern;
    }

    public function getPercentage(): ?float
    {
        return $this->percentage;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if this rule applies to an account.
     */
    public function appliesToAccount(string $accountCode): bool
    {
        if ($this->accountPattern === null) {
            return true;
        }

        return (bool) preg_match($this->accountPattern, $accountCode);
    }

    /**
     * Calculate elimination amount.
     */
    public function calculateEliminationAmount(float $amount): float
    {
        if ($this->percentage === null) {
            return $amount;
        }

        return $amount * ($this->percentage / 100);
    }

    /**
     * Check if entities are intercompany.
     */
    public function isIntercompany(string $entity1, string $entity2): bool
    {
        return ($this->sourceEntityId === $entity1 && $this->targetEntityId === $entity2)
            || ($this->sourceEntityId === $entity2 && $this->targetEntityId === $entity1);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rule_type' => $this->ruleType,
            'source_entity_id' => $this->sourceEntityId,
            'target_entity_id' => $this->targetEntityId,
            'account_pattern' => $this->accountPattern,
            'percentage' => $this->percentage,
            'metadata' => $this->metadata,
        ];
    }
}
