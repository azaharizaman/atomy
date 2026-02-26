<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

/**
 * DTO for MFA enrollment status.
 */
final readonly class MfaStatusResult
{
    /**
     * @param array<string, bool> $enrollments Enrollment status by method
     */
    public function __construct(
        public string $userId,
        public array $enrollments,
    ) {}

    public function isEnrolled(MfaMethod $method): bool
    {
        return $this->enrollments[$method->value] ?? false;
    }

    public function hasAnyEnrollment(): bool
    {
        return in_array(true, $this->enrollments, true);
    }
}
