<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * Start a new operational approval instance.
 *
 * Tenant id is a non-empty string (ULID in Atomy-Q); passed through to persistence and PolicyEngine mapping.
 */
final readonly class StartOperationalApprovalCommand
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $tenantId,
        public ApprovalSubjectRef $subject,
        public string $initiatorPrincipalId,
        public array $context = [],
    ) {
        if (\trim($this->tenantId) === '') {
            throw new \InvalidArgumentException('tenantId cannot be empty.');
        }
        if (\trim($this->initiatorPrincipalId) === '') {
            throw new \InvalidArgumentException('initiatorPrincipalId cannot be empty.');
        }
    }
}
