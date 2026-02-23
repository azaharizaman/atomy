<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Interface for tenant validation rules.
 */
interface TenantValidationRuleInterface
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
     * Evaluate the rule against a subject.
     */
    public function evaluate(mixed $subject): ValidationResult;
}
