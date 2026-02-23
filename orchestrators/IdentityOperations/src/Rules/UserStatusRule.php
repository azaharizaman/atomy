<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

use Nexus\IdentityOperations\Contracts\UserValidationRuleInterface;
use Nexus\IdentityOperations\DTOs\ValidationResult;

/**
 * Rule to validate user status allows the operation.
 */
final readonly class UserStatusRule implements UserValidationRuleInterface
{
    private const ALLOWED_STATUSES = ['active', 'pending'];

    public function __construct(
        private UserStatusCheckerInterface $userStatusChecker,
    ) {}

    public function getName(): string
    {
        return 'user_status';
    }

    public function getDescription(): string
    {
        return 'Validates that user status allows the operation (not suspended or deactivated)';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!is_string($subject)) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule. Expected user ID string.',
                    'severity' => 'error',
                ],
            ]);
        }

        $status = $this->userStatusChecker->getStatus($subject);

        if ($status === null) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => "User '{$subject}' not found",
                    'severity' => 'error',
                ],
            ]);
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => "User status '{$status}' does not allow this operation",
                    'severity' => 'error',
                ],
            ]);
        }

        return ValidationResult::passed();
    }
}

/**
 * Interface for checking user status.
 */
interface UserStatusCheckerInterface
{
    public function getStatus(string $userId): ?string;

    public function isActive(string $userId): bool;

    public function isSuspended(string $userId): bool;

    public function isDeactivated(string $userId): bool;
}
