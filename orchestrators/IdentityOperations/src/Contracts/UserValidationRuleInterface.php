<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\ValidationResult;

/**
 * Interface for user validation rules.
 */
interface UserValidationRuleInterface
{
    /**
     * Get the rule name.
     */
    public function getName(): string;

    /**
     * Get the rule description.
     */
    public function getDescription(): string;

    /**
     * Evaluate the rule.
     *
     * @param mixed $subject The subject to validate
     */
    public function evaluate(mixed $subject): ValidationResult;
}
