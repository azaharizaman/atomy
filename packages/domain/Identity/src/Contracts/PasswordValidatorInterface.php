<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * Password validator interface
 * 
 * Validates password strength and enforces password policies
 */
interface PasswordValidatorInterface
{
    /**
     * Validate a password against security requirements
     * 
     * @param string $password Password to validate
     * @param UserInterface|null $user User context (for checking password history)
     * @return array<string> Array of validation error messages (empty if valid)
     */
    public function validate(string $password, ?UserInterface $user = null): array;

    /**
     * Check if password meets minimum length requirement
     */
    public function meetsMinimumLength(string $password): bool;

    /**
     * Check if password contains required character types
     */
    public function hasRequiredComplexity(string $password): bool;

    /**
     * Check if password is in common password breach database
     */
    public function isCompromised(string $password): bool;

    /**
     * Check if password was recently used by user
     */
    public function isInPasswordHistory(string $password, UserInterface $user): bool;
}
