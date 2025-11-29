<?php

declare(strict_types=1);

namespace App\Service;

use Nexus\Identity\Contracts\PasswordValidatorInterface;
use Nexus\Identity\Contracts\UserInterface;

final readonly class PasswordValidator implements PasswordValidatorInterface
{
    private const MIN_LENGTH = 8;
    private const COMMON_PASSWORDS = [
        'password', 'password1', 'password123', '12345678', '123456789',
        'qwerty123', 'admin123', 'letmein', 'welcome', 'monkey',
        'dragon', 'master', 'login', 'abc123', 'iloveyou',
    ];

    /**
     * @return array<string>
     */
    public function validate(string $password, ?UserInterface $user = null): array
    {
        $errors = [];

        if (!$this->meetsMinimumLength($password)) {
            $errors[] = 'Password must be at least ' . self::MIN_LENGTH . ' characters long';
        }

        if (!$this->hasRequiredComplexity($password)) {
            $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
        }

        if ($this->isCompromised($password)) {
            $errors[] = 'Password is too common and may be compromised';
        }

        if ($user !== null && $this->containsUserInfo($password, $user)) {
            $errors[] = 'Password should not contain your username or email';
        }

        return $errors;
    }

    public function meetsMinimumLength(string $password): bool
    {
        return mb_strlen($password) >= self::MIN_LENGTH;
    }

    public function hasRequiredComplexity(string $password): bool
    {
        // Check for uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for lowercase
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Check for special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            return false;
        }

        return true;
    }

    public function isCompromised(string $password): bool
    {
        $lowerPassword = strtolower($password);

        // Check against common passwords list
        if (in_array($lowerPassword, self::COMMON_PASSWORDS, true)) {
            return true;
        }

        // Check for sequential patterns
        if (preg_match('/^(123|abc|qwe|asd|zxc)/i', $password)) {
            return true;
        }

        // Check for repeated characters (e.g., "aaaa")
        if (preg_match('/(.)\1{3,}/', $password)) {
            return true;
        }

        return false;
    }

    public function isInPasswordHistory(string $password, UserInterface $user): bool
    {
        // This would require storing password history
        // For now, we just return false as history tracking is not implemented
        return false;
    }

    private function containsUserInfo(string $password, UserInterface $user): bool
    {
        $lowerPassword = strtolower($password);

        // Check if password contains username
        $username = strtolower($user->getUsername());
        if (strlen($username) >= 3 && str_contains($lowerPassword, $username)) {
            return true;
        }

        // Check if password contains email local part
        $email = strtolower($user->getEmail());
        $emailParts = explode('@', $email);
        $localPart = $emailParts[0];
        if (strlen($localPart) >= 3 && str_contains($lowerPassword, $localPart)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate password strength score (0-100)
     */
    public function getStrengthScore(string $password): int
    {
        $score = 0;

        // Length contribution (up to 30 points)
        $length = mb_strlen($password);
        $score += min(30, $length * 2);

        // Character variety (up to 40 points)
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 10;

        // Bonus for longer passwords (up to 20 points)
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 10;

        // Penalty for common patterns (up to -30 points)
        if ($this->isCompromised($password)) $score -= 30;

        return max(0, min(100, $score));
    }

    /**
     * Get strength label
     */
    public function getStrengthLabel(string $password): string
    {
        $score = $this->getStrengthScore($password);

        return match (true) {
            $score >= 80 => 'strong',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            $score >= 20 => 'weak',
            default => 'very_weak',
        };
    }
}
