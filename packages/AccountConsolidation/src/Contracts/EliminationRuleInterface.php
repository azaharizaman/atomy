<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Contracts;

use Nexus\AccountConsolidation\ValueObjects\EliminationEntry;

/**
 * Contract for elimination rules.
 */
interface EliminationRuleInterface
{
    /**
     * Get the rule identifier.
     */
    public function getId(): string;

    /**
     * Get the rule name.
     */
    public function getName(): string;

    /**
     * Apply the elimination rule.
     *
     * @param array<string, mixed> $consolidationData
     * @return array<EliminationEntry>
     */
    public function apply(array $consolidationData): array;

    /**
     * Check if this rule applies to the given data.
     *
     * @param array<string, mixed> $consolidationData
     * @return bool
     */
    public function appliesTo(array $consolidationData): bool;
}
