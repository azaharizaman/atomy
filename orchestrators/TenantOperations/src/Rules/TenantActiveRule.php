<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

use Nexus\TenantOperations\Contracts\TenantValidationRuleInterface;
use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Rule to validate tenant is active.
 */
final readonly class TenantActiveRule implements TenantValidationRuleInterface
{
    public function __construct(
        private TenantStatusCheckerInterface $tenantStatusChecker,
    ) {}

    public function getName(): string
    {
        return 'tenant_active';
    }

    public function getDescription(): string
    {
        return 'Validates that the tenant is active and not suspended or archived';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!is_string($subject)) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule. Expected tenant ID string.',
                    'severity' => 'error',
                ],
            ]);
        }

        $isActive = $this->tenantStatusChecker->isActive($subject);

        if ($isActive) {
            return ValidationResult::passed();
        }

        return ValidationResult::failed([
            [
                'rule' => $this->getName(),
                'message' => "Tenant is not active",
                'severity' => 'error',
            ],
        ]);
    }
}

/**
 * Interface for checking tenant status.
 */
interface TenantStatusCheckerInterface
{
    public function isActive(string $tenantId): bool;

    public function getStatus(string $tenantId): ?string;
}
