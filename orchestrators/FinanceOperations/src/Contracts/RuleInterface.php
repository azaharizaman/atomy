<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for finance business rules validation.
 *
 * This interface defines the contract for business rules that validate
 * finance operations. Rules are used by coordinators to enforce
 * cross-package constraints before executing operations.
 *
 * Interface Segregation Compliance:
 * - Defines single-responsibility rule checking
 * - Each rule validates one specific business constraint
 * - Used by coordinators to enforce business policies
 *
 * @see \Nexus\FinanceOperations\DTOs\RuleResult
 */
interface RuleInterface
{
    /**
     * Get the rule name for identification and logging.
     */
    public function getName(): string;

    /**
     * Check if the rule passes for the given context.
     *
     * @param object $context The context to validate (typically a DTO)
     * @return \Nexus\FinanceOperations\DTOs\RuleResult The rule check result
     */
    public function check(object $context): \Nexus\FinanceOperations\DTOs\RuleResult;
}
