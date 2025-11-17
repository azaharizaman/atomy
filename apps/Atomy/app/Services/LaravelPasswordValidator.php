<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PasswordHistory;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\PasswordValidatorInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;

/**
 * Laravel password validator implementation
 */
final readonly class LaravelPasswordValidator implements PasswordValidatorInterface
{
    public function __construct(
        private PasswordHasherInterface $hasher
    ) {
    }

    public function validate(string $password, ?UserInterface $user = null): array
    {
        $errors = [];

        if (!$this->meetsMinimumLength($password)) {
            $minLength = config('identity.password.min_length', 8);
            $errors[] = "Password must be at least {$minLength} characters long";
        }

        if (!$this->hasRequiredComplexity($password)) {
            $errors[] = 'Password must contain uppercase, lowercase, numbers, and special characters';
        }

        if ($this->isCompromised($password)) {
            $errors[] = 'This password has been found in data breaches and cannot be used';
        }

        if ($user && $this->isInPasswordHistory($password, $user)) {
            $errors[] = 'Password has been used recently and cannot be reused';
        }

        return $errors;
    }

    public function meetsMinimumLength(string $password): bool
    {
        return mb_strlen($password) >= config('identity.password.min_length', 8);
    }

    public function hasRequiredComplexity(string $password): bool
    {
        $requireUppercase = config('identity.password.require_uppercase', false);
        $requireLowercase = config('identity.password.require_lowercase', false);
        $requireNumbers = config('identity.password.require_numbers', false);
        $requireSpecialChars = config('identity.password.require_special_chars', false);

        // If no requirements set, pass by default
        if (!$requireUppercase && !$requireLowercase && !$requireNumbers && !$requireSpecialChars) {
            return true;
        }

        $hasUppercase = !$requireUppercase || preg_match('/[A-Z]/', $password);
        $hasLowercase = !$requireLowercase || preg_match('/[a-z]/', $password);
        $hasNumbers = !$requireNumbers || preg_match('/[0-9]/', $password);
        $hasSpecial = !$requireSpecialChars || preg_match('/[^A-Za-z0-9]/', $password);

        return $hasUppercase && $hasLowercase && $hasNumbers && $hasSpecial;
    }

    public function isCompromised(string $password): bool
    {
        $checkBreaches = config('identity.password.breach_check_enabled', false);

        if (!$checkBreaches) {
            return false;
        }

        // TODO: Implement Pwned Passwords API check
        // For now, return false
        return false;
    }

    public function isInPasswordHistory(string $password, UserInterface $user): bool
    {
        $historyLimit = config('identity.password.history_limit', 5);

        if ($historyLimit === 0) {
            return false;
        }

        $histories = PasswordHistory::where('user_id', $user->getId())
            ->orderBy('created_at', 'desc')
            ->limit($historyLimit)
            ->get();

        foreach ($histories as $history) {
            if ($this->hasher->verify($password, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }
}
