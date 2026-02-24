<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

use Nexus\IdentityOperations\Contracts\UserValidationRuleInterface;
use Nexus\IdentityOperations\DTOs\ValidationResult;

/**
 * Rule to validate MFA enrollment status.
 */
final readonly class MfaEnrollmentRule implements UserValidationRuleInterface
{
    public function __construct(
        private MfaEnrollmentCheckerInterface $mfaEnrollmentChecker,
    ) {}

    public function getName(): string
    {
        return 'mfa_enrollment';
    }

    public function getDescription(): string
    {
        return 'Validates that user has MFA enrolled when required';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!is_array($subject) || !isset($subject['user_id'])) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule. Expected array with user_id.',
                    'severity' => 'error',
                ],
            ]);
        }

        $userId = $subject['user_id'];
        $requireMfa = $subject['require_mfa'] ?? false;
        $tenantId = $subject['tenant_id'] ?? null;

        $isEnrolled = $this->mfaEnrollmentChecker->isEnrolled($userId, $tenantId);

        if ($requireMfa && !$isEnrolled) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => "MFA is required but user '{$userId}' is not enrolled",
                    'severity' => 'error',
                ],
            ]);
        }

        return ValidationResult::passed();
    }
}

/**
 * Interface for checking MFA enrollment.
 */
interface MfaEnrollmentCheckerInterface
{
    public function isEnrolled(string $userId, ?string $tenantId = null): bool;

    public function getEnrollmentStatus(string $userId, ?string $tenantId = null): array;
}
